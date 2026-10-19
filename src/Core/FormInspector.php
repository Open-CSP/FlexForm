<?php

namespace FlexForm\Core;

use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Revision\SlotRecord;
use Skin;
use TemplateParser;
use Title;
use TextContent;

class FormInspector {

	/**
	 * @var bool
	 */
	private static bool $active = false;

	/**
	 * @var array
	 */
	private static array $fields = [];

	/**
	 * @var array
	 */
	private static array $creates = [];

	/**
	 * @var array
	 */
	private static array $edits = [];

	/**
	 * @var array
	 */
	private static array $emails = [];

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) : void {
		$context = $out->getContext();
		$request = $context->getRequest();
		$user = $context->getUser();
		$canInspect = false;
		if ( $user !== null && $user->isRegistered() ) {
			$allowedGroups = Config::getConfigVariable( 'allowedGroups' ) ?? [ 'sysop' ];
			$userGroups = MediaWikiServices::getInstance()
				->getUserGroupManager()
				->getUserEffectiveGroups( $user );
			$canInspect = !empty( array_intersect( $allowedGroups, $userGroups ) );
		}

		if ( $request->getVal( 'action' ) === 'inspect' && $canInspect ) {
			self::start( true );
		}
	}

	/**
	 * @param bool $enable
	 *
	 * @return void
	 */
	public static function start( bool $enable ): void {
		self::$active = $enable;
		self::$fields = [];
		self::$creates = [];
		self::$edits = [];
		self::$emails = [];
	}

	public static function isActive(): bool {
		return self::$active;
	}

	public static function addField( string $name, string $type = 'text', array $args = [] ): void {
		if ( !self::$active || $name === '' ) {
			return;
		}
		$cleanName = preg_replace( '/\[.*\]$/', '', $name );
		self::$fields[$cleanName] = [
			'rawName' => $name,
			'type'    => $type,
			'ignore'  => isset( $args['ignore'] ),
			'args'    => $args
		];
	}

	public static function addCreate( array $args ): void {
		if ( !self::$active ) {
			return;
		}
		self::$creates[] = [
			'template' => $args['template'] ?? null,
			'slot'     => $args['slot'] ?? 'main',
			'title'    => $args['title'] ?? null,
			'fields'   => $args['fields'] ?? null
		];
	}

	public static function addEdit( array $args ): void {
		if ( !self::$active ) {
			return;
		}
		self::$edits[] = [
			'target'    => $args['target'] ?? ( $args['mwtitle'] ?? null ),
			'template'  => $args['template'] ?? null,
			'slot'      => $args['slot'] ?? 'main',
			'formfield' => $args['formfield'] ?? ( $args['usefield'] ?? null ),
			'mwfield'   => $args['mwfield'] ?? null,
		];
	}

	public static function addEmail( array $args ): void {
		if ( !self::$active ) {
			return;
		}
		self::$emails[] = [
			'template' => $args['template'] ?? null,
			'to'       => $args['to'] ?? null,
			'subject'  => $args['subject'] ?? null,
		];
	}

	/**
	 * Extracts {{{parameters}}} from a MediaWiki Template page
	 */
	private static function getTemplateParameters( string $templateName ): ?array {
		$title = Title::newFromText( $templateName, NS_TEMPLATE );
		if ( !$title || !$title->exists() ) {
			return null;
		}

		$wikiPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$content = $wikiPage->getContent( SlotRecord::MAIN );
		if ( !( $content instanceof TextContent ) ) {
			return [];
		}

		$text = $content->getText();
		$text = preg_replace( '/<noinclude>.*?<\/noinclude>/is', '', $text );

		preg_match_all( '/\{\{\{\s*([^|\}\n<]+)(?:\|([^}]*))?\}\}\}/', $text, $matches );

		$params = [];
		if ( !empty( $matches[1] ) ) {
			foreach ( $matches[1] as $index => $paramName ) {
				$paramName = trim( $paramName );
				if ( $paramName !== '' ) {
					$params[$paramName] = $matches[2][$index] ?? null;
				}
			}
		}
		return $params;
	}

	/**
	 * Extracts %_headers% and $variables from a FlexForm email template page
	 */
	private static function getEmailTemplateData( string $pageName ): ?array {
		$title = Title::newFromText( $pageName );
		if ( !$title || !$title->exists() ) {
			return null;
		}

		$wikiPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$content = $wikiPage->getContent( SlotRecord::MAIN );
		if ( !( $content instanceof TextContent ) ) {
			return [ 'headers' => [], 'vars' => [] ];
		}

		$text = $content->getText();
		$text = preg_replace( '/<noinclude>.*?<\/noinclude>/is', '', $text );

		preg_match_all( '/%_([a-z-]+)=([^%]*)%/i', $text, $headerMatches );
		$headers = [];
		if ( !empty( $headerMatches[1] ) ) {
			foreach ( $headerMatches[1] as $idx => $key ) {
				$headers[strtolower( trim( $key ) )] = trim( $headerMatches[2][$idx] );
			}
		}

		preg_match_all( '/\$([a-zA-Z0-9_-]+)/', $text, $varMatches );
		$vars = !empty( $varMatches[1] ) ? array_values( array_unique( $varMatches[1] ) ) : [];

		return [
			'headers' => $headers,
			'vars'    => $vars
		];
	}

	/**
	 * Helper for i18n messages with direct English fallback before i18n JSON is populated
	 */
	private static function msg( string $key, string $fallback, ...$params ): string {
		$msg = wfMessage( $key, ...$params );
		return $msg->exists() ? $msg->text() : $fallback;
	}

	/**
	 * Renders the Admin Snippet
	 * @param string $formId
	 * @param bool|null $isOfficialForm
	 * @param string|null $action
	 * @param string|null $extension
	 *
	 * @return string
	 */
	public static function renderSnippet(
		string $formId,
		?bool $isOfficialForm,
		?string $action,
		?string $extension
	): string {
		if ( !self::$active ) {
			return '';
		}

		$templatesToAnalyze = [];
		foreach ( self::$creates as $create ) {
			if ( !empty( $create['template'] ) ) {
				$tpl = $create['template'];
				$templatesToAnalyze[$tpl]['creates'][] = $create;
			}
		}

		$explicitEditMappings = [];
		foreach ( self::$edits as $edit ) {
			if ( !empty( $edit['template'] ) ) {
				$tpl = $edit['template'];
				$templatesToAnalyze[$tpl]['edits'][] = $edit;
				if ( !empty( $edit['mwfield'] ) && !empty( $edit['formfield'] ) ) {
					$explicitEditMappings[$tpl][$edit['mwfield']] = $edit['formfield'];
				}
			}
		}

		$templateData = self::buildTemplateData(
			$formId,
			$isOfficialForm,
			$action,
			$extension,
			$templatesToAnalyze,
			$explicitEditMappings
		);

		self::$active = false;

		$templateParser = new TemplateParser( dirname( __DIR__, 2 ) . '/templates' );
		return $templateParser->processTemplate( 'FormInspector', $templateData );
	}

	/**
	 * @param string $formId
	 * @param bool|null $isOfficialForm
	 * @param string|null $action
	 * @param string|null $extension
	 * @param array $templatesToAnalyze
	 * @param array $explicitEditMappings
	 *
	 * @return array
	 */
	/**
	 * Builds the structured data array for FormInspector.mustache
	 */
	private static function buildTemplateData(
		string $formId,
		?bool $isOfficialForm,
		?string $action,
		?string $extension,
		array $templatesToAnalyze,
		array $explicitEditMappings
	): array {
		$usedFormFields = [];
		$allOmittedFields = [];
		$totalMissingParams = 0;
		$totalBrokenTemplates = 0;

		// 1. Extension check
		$hasExtension = ( $extension !== null && $extension !== '' );
		$extensionExists = false;
		if ( $hasExtension ) {
			$handlerClass = "FlexForm\\Modules\\Handlers\\{$extension}\\PostHandler";
			$extensionExists = class_exists( $handlerClass );
		}

		// 2. Creates & Edits summary list
		$slotRoleRegistry = MediaWikiServices::getInstance()->getSlotRoleRegistry();

		$explicitMappings = $explicitEditMappings;

		$createsList = [];
		foreach ( self::$creates as $c ) {
			$titleStr = (string)( $c['title'] ?? '' );
			$isIdRef = str_starts_with( $titleStr, '::id::' );
			$slotName = (string)( $c['slot'] ?? 'main' );
			$isSlotValid = $slotRoleRegistry->isDefinedRole( $slotName );

			$missingTitleFields = [];
			if ( preg_match_all( '/\[([^\]]+)\]/', $titleStr, $matches ) ) {
				foreach ( $matches[1] as $fieldName ) {
					$cleanField = trim( $fieldName );
					if ( !isset( self::$fields[$cleanField] ) ) {
						$missingTitleFields[] = $cleanField;
						$totalMissingParams++;
					} else {
						$usedFormFields[$cleanField] = true;
					}
				}
			}

			$missingMwFields = [];
			if ( !empty( $c['fields'] ) ) {
				$mwFieldsArray = array_map( 'trim', explode( ',', $c['fields'] ) );
				foreach ( $mwFieldsArray as $f ) {
					if ( $f === '' ) {
						continue;
					}

					$parts = array_map( 'trim', explode( '::', $f ) );
					$formFieldName = $parts[0];
					$templateParamName = $parts[1] ?? $parts[0];

					$usedFormFields[$formFieldName] = true;

					if ( !isset( self::$fields[$formFieldName] ) ) {
						$missingMwFields[] = $formFieldName;
						$totalMissingParams++;
					}

					$tpl = (string)( $c['template'] ?? '' );
					if ( $tpl !== '' && strtolower( $tpl ) !== 'wsnone' ) {
						$explicitMappings[$tpl][$templateParamName] = $formFieldName;
					}
				}
			}

			$createsList[] = [
				'template'            => (string)( $c['template'] ?? '' ),
				'isWsNone'            => strtolower( (string)( $c['template'] ?? '' ) ) === 'wsnone',
				'slot'                => $slotName,
				'isSlotValid'         => $isSlotValid,
				'hasTitle'            => !empty( $titleStr ) && !$isIdRef,
				'title'               => $titleStr,
				'isIdReference'       => $isIdRef,
				'referencedId'        => $isIdRef ? substr( $titleStr, 6 ) : '',
				'hasMissingTitleVars' => !empty( $missingTitleFields ),
				'missingTitleVars'    => implode( ', ', $missingTitleFields ),
				'hasMissingMwFields'  => !empty( $missingMwFields ),
				'missingMwFields'     => implode( ', ', $missingMwFields )
			];
		}

		$editsList = [];
		foreach ( self::$edits as $e ) {
			$targetStr = (string)( $e['target'] ?? '' );
			$slotName = (string)( $e['slot'] ?? 'main' );
			$isSlotValid = $slotRoleRegistry->isDefinedRole( $slotName );

			$missingTargetFields = [];
			if ( preg_match_all( '/\[([^\]]+)\]/', $targetStr, $matches ) ) {
				foreach ( $matches[1] as $fieldName ) {
					$cleanField = trim( $fieldName );
					if ( !isset( self::$fields[$cleanField] ) ) {
						$missingTargetFields[] = $cleanField;
						$totalMissingParams++;
					} else {
						$usedFormFields[$cleanField] = true;
					}
				}
			}

			$editsList[] = [
				'target'              => $targetStr,
				'template'            => (string)( $e['template'] ?? '' ),
				'slot'                => $slotName,
				'isSlotValid'         => $isSlotValid,
				'hasMissingTitleVars' => !empty( $missingTargetFields ),
				'missingTitleVars'    => implode( ', ', $missingTargetFields )
			];
		}

		// 3. Wiki Templates analysis
		$templatesData = [];
		foreach ( $templatesToAnalyze as $tplName => $usageData ) {
			if ( strtolower( $tplName ) === 'wsnone' ) {
				continue;
			}

			$tplParams = self::getTemplateParameters( $tplName );
			if ( $tplParams === null ) {
				$totalBrokenTemplates++;
				$templatesData[] = [
					'name'   => $tplName,
					'exists' => false
				];
				continue;
			}

			$tplOmittedFields = [];
			if ( !empty( $usageData['creates'] ) ) {
				foreach ( $usageData['creates'] as $c ) {
					$tplOmittedFields = array_merge( $tplOmittedFields, $c['omit'] ?? [] );
				}
			}
			$allOmittedFields = array_merge( $allOmittedFields, $tplOmittedFields );

			$paramRows = [];
			foreach ( $tplParams as $paramName => $defaultVal ) {
				$isOmitted = in_array( $paramName, $tplOmittedFields, true );
				$matchedField = null;
				$mappedFormField = '';

				if ( isset( self::$fields[$paramName] ) ) {
					$matchedField = self::$fields[$paramName];
					$usedFormFields[$paramName] = true;
				} elseif ( isset( $explicitMappings[$tplName][$paramName] ) ) {
					// Pakt nu ZOWEL de formfield="..." mapping uit _edit als de X::Y mapping uit mwfields!
					$candidateField = $explicitMappings[$tplName][$paramName];
					if ( isset( self::$fields[$candidateField] ) ) {
						$matchedField = self::$fields[$candidateField];
						$usedFormFields[$candidateField] = true;
						$mappedFormField = $candidateField;
					}
				}

				$isMatched = ( !$isOmitted && $matchedField !== null );
				$isMissing = ( !$isOmitted && $matchedField === null );

				if ( $isMissing ) {
					$totalMissingParams++;
				}

				$paramRows[] = [
					'name'            => $paramName,
					'hasDefault'      => ( $defaultVal !== null && $defaultVal !== '' ),
					'defaultVal'      => (string)$defaultVal,
					'isOmitted'       => $isOmitted,
					'isMatched'       => $isMatched,
					'isMissing'       => $isMissing,
					'matchedType'     => $matchedField['type'] ?? '',
					'hasEditMapping'  => ( $mappedFormField !== '' ),
					'mappedFormField' => $mappedFormField
				];
			}

			$templatesData[] = [
				'name'      => $tplName,
				'exists'    => true,
				'hasParams' => !empty( $paramRows ),
				'params'    => $paramRows
			];
		}

		// 4. Email Templates analysis (Geen wijzigingen)
		$emailsData = [];
		foreach ( self::$emails as $emailConfig ) {
			$mailTpl = $emailConfig['template'] ?? '';
			if ( $mailTpl === '' ) {
				continue;
			}
			$mailData = self::getEmailTemplateData( $mailTpl );

			if ( $mailData === null ) {
				$totalBrokenTemplates++;
				$emailsData[] = [
					'name'   => $mailTpl,
					'exists' => false
				];
				continue;
			}

			$missingHeaders = [];
			if ( empty( $emailConfig['to'] ) && empty( $mailData['headers']['to'] ) ) {
				$missingHeaders[] = '_to';
			}
			if ( empty( $mailData['headers']['from'] ) ) {
				$missingHeaders[] = '_from';
			}
			if ( empty( $emailConfig['subject'] ) && empty( $mailData['headers']['subject'] ) ) {
				$missingHeaders[] = '_subject';
			}

			$varRows = [];
			foreach ( $mailData['vars'] as $varName ) {
				$isMatched = isset( self::$fields[$varName] );
				if ( $isMatched ) {
					$usedFormFields[$varName] = true;
				} else {
					$totalMissingParams++;
				}

				$varRows[] = [
					'name'        => $varName,
					'isMatched'   => $isMatched,
					'isMissing'   => !$isMatched,
					'matchedType' => self::$fields[$varName]['type'] ?? ''
				];
			}

			$emailsData[] = [
				'name'              => $mailTpl,
				'exists'            => true,
				'hasHeaderWarnings' => !empty( $missingHeaders ),
				'missingHeaders'    => implode( ', ', $missingHeaders ),
				'hasVars'           => !empty( $varRows ),
				'vars'              => $varRows
			];
		}

		// 5. Orphan form fields check
		$orphansList = [];
		if ( !empty( $templatesToAnalyze ) || !empty( self::$emails ) ) {
			foreach ( self::$fields as $fName => $fData ) {
				if ( in_array( $fData['type'], [ 'submit', 'button', 'reset' ], true ) || $fData['ignore'] ) {
					continue;
				}
				if ( !isset( $usedFormFields[$fName] ) && !in_array( $fName, $allOmittedFields, true ) ) {
					$orphansList[] = [
						'name' => $fData['rawName'],
						'type' => $fData['type']
					];
				}
			}
		}

		$hasBrokenTemplates = ( $totalBrokenTemplates > 0 );
		$hasMissingParams   = ( !$hasBrokenTemplates && $totalMissingParams > 0 );
		$isHealthOk         = ( !$hasBrokenTemplates && !$hasMissingParams );

		return [
			'formId'               => $formId,
			'isValidated'          => (bool)$isOfficialForm,
			'fieldsCount'          => count( self::$fields ),
			'hasBrokenTemplates'   => $hasBrokenTemplates,
			'brokenTemplatesCount' => $totalBrokenTemplates,
			'hasMissingParams'     => $hasMissingParams,
			'missingParamsCount'   => $totalMissingParams,
			'isHealthOk'           => $isHealthOk,
			'action'               => $action ?? 'default',
			'hasExtension'         => $hasExtension,
			'extensionName'        => (string)$extension,
			'extensionExists'      => $extensionExists,
			'creates'              => $createsList,
			'edits'                => $editsList,
			'templates'            => $templatesData,
			'emails'               => $emailsData,
			'hasOrphans'           => !empty( $orphansList ),
			'orphans'              => $orphansList,
			'i18n'                 => [
				'title'              => self::msg( 'flexform-inspector-title', 'FlexForm Inspector' ),
				'validated'          => self::msg( 'flexform-inspector-validated', 'Validated' ),
				'unvalidated'        => self::msg( 'flexform-inspector-unvalidated', 'Unvalidated' ),
				'fields'             => self::msg( 'flexform-inspector-fields', 'Fields' ),
				'missingTemplates'   => self::msg( 'flexform-inspector-missing-templates', 'Missing Templates' ),
				'missingParams'      => self::msg( 'flexform-inspector-missing-params', 'Missing Params' ),
				'templatesOk'        => self::msg( 'flexform-inspector-templates-ok', 'Templates OK' ),
				'configHeading'      => self::msg( 'flexform-inspector-config-heading', 'Form Configuration' ),
				'formAction'         => self::msg( 'flexform-inspector-form-action', 'Form Action' ),
				'extension'          => self::msg( 'flexform-inspector-extension', 'Extension' ),
				'postHandlerFound'   => self::msg( 'flexform-inspector-handler-found', 'PostHandler class found' ),
				'postHandlerMissing' => self::msg( 'flexform-inspector-handler-missing', 'No PostHandler class (falls back to FFAfterFormHandling hook)' ),
				'createAction'       => self::msg( 'flexform-inspector-create-action', 'Create' ),
				'editAction'         => self::msg( 'flexform-inspector-edit-action', 'Edit' ),
				'templateLabel'      => self::msg( 'flexform-inspector-template-label', 'Template' ),
				'emailTemplateLabel' => self::msg( 'flexform-inspector-email-template-label', 'Email Template' ),
				'slotLabel'          => self::msg( 'flexform-inspector-slot-label', 'Slot' ),
				'titleLabel'         => self::msg( 'flexform-inspector-title-label', 'Title' ),
				'targetLabel'        => self::msg( 'flexform-inspector-target-label', 'Target' ),
				'templateNotFound'   => self::msg( 'flexform-inspector-template-not-found', 'does not exist in the wiki.' ),
				'noParamsFound'      => self::msg( 'flexform-inspector-no-params-found', 'No {{{parameters}}} found in this template.' ),
				'colParameter'       => self::msg( 'flexform-inspector-col-parameter', 'Parameter' ),
				'colVariable'        => self::msg( 'flexform-inspector-col-variable', 'Mail Variable' ),
				'colStatus'          => self::msg( 'flexform-inspector-col-status', 'Status' ),
				'colAction'          => self::msg( 'flexform-inspector-col-action', 'Action' ),
				'defaultLabel'       => self::msg( 'flexform-inspector-default-label', 'default' ),
				'statusOmitted'      => self::msg( 'flexform-inspector-status-omitted', 'Omitted' ),
				'statusMapped'       => self::msg( 'flexform-inspector-status-mapped', 'Mapped' ),
				'statusMissing'      => self::msg( 'flexform-inspector-status-missing', 'Missing in form' ),
				'viaEdit'            => self::msg( 'flexform-inspector-via-edit', 'via _edit' ),
				'btnAddCode'         => self::msg( 'flexform-inspector-btn-add-code', '+ Code' ),
				'missingMailHeaders' => self::msg( 'flexform-inspector-missing-mail-headers', 'Missing required mail headers in template' ),
				'orphanFieldsAlert'  => self::msg( 'flexform-inspector-orphan-fields', 'Unmapped form fields (not found in any linked template)' ),
				'generatedCodeTitle' => self::msg( 'flexform-inspector-generated-code', 'Generated FlexForm Code' ),
				'btnCopy'            => self::msg( 'flexform-inspector-btn-copy', 'Copy to clipboard' ),
				'btnClear'           => self::msg( 'flexform-inspector-btn-clear', 'Clear' ),
				'invalidSlot'        => self::msg( 'flexform-inspector-invalid-slot', 'Invalid slot!' ),
				'missingTitleVars'   => self::msg( 'flexform-inspector-missing-title-vars', 'Missing field(s) for title/target:' ),
				'missingMwFields'    => self::msg( 'flexform-inspector-missing-mwfields', 'Missing field(s) defined in mwfields:' ),
				'viaMapping'         => self::msg( 'flexform-inspector-via-mapping', 'mapped via formfield/mwfields' ),
			]
		];
	}
}
