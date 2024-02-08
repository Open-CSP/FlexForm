<?php

namespace FlexForm\Render;

use Composer\Command\ScriptAliasCommand;
use ExtensionRegistry;
use FlexForm\Core\Messaging;
use FlexForm\Core\Sql;
use FlexForm\Processors\Content\Render;
use FlexForm\Processors\Files\FilesCore;
use FlexForm\Processors\Utilities\General;
use FlexForm\Render\Helpers\Email;
use FlexForm\Render\Helpers\Json;
use FlexForm\Render\Helpers\MobileScreenShot;
use MediaWiki\MediaWikiServices;
use Parser;
use PPFrame;
use RequestContext;
use FlexForm\Core\Config;
use FlexForm\Core\Core;
use FlexForm\Core\Protect;
use FlexForm\Core\Validate;
use FlexForm\FlexFormException;
use User;

/**
 * Class TagHooks
 *
 * This class is responsible for rendering tags.
 */
class TagHooks {

	/**
	 * @var bool|null
	 */
	private $officialForm;

	/**
	 * @var ThemeStore
	 */
	private $themeStore;

	/**
	 * TagHooks constructor.
	 *
	 * @param ThemeStore $themeStore The theme store to use
	 */
	public function __construct( ThemeStore $themeStore ) {
		$this->themeStore = $themeStore;
	}

	/**
	 * @param int $pageId
	 * @param string $input
	 *
	 * @return void
	 */
	public function setOfficialForm( int $pageId, string $input ) {
		$hash = Sql::createHash( trim( $input ) );
		$this->officialForm = Sql::exists( $pageId, $hash );
	}

	/**
	 * @brief Function to render the Form itself.
	 *
	 * This function will call its subfunction render_form()
	 * It will also add the JavaScript on the loadscript variable
	 * \n Additional parameters
	 * \li loadscript
	 * \li showmessages
	 * \li restrictions
	 * \li no_submit_on_return
	 * \li action
	 * \li changetrigger
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 * @param string $tagName the tagname used
	 *
	 * @return array|string send to the MediaWiki Parser or send to the MediaWiki Parser with the message not a valid
	 *     function
	 * @throws FlexFormException
	 */
	public function renderForm( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $wgUser, $wgEmailConfirmToEdit, $IP, $wgScript;
		$ret = '';
		$addFFJS = '';
		//$parser->getOutput()->addModuleStyles( 'ext.wsForm.general.styles' );
		$renderonlyapprovedforms = Config::getConfigVariable( 'renderonlyapprovedforms' );
		$renderi18nErrorInsteadofImageForApprovedForms = Config::getConfigVariable(
			'renderi18nErrorInsteadofImageForApprovedForms'
		);
		// Do we have some messages to show?
		if ( isset( $args['showmessages'] ) ) {
			return '<!--' . wfMessage( 'flexform-showmessages-deprecated' )->text() . '-->';
		}
		$this->officialForm = null;
		if ( $renderonlyapprovedforms === false ) {
			$this->officialForm = true;
		}
		if ( $this->officialForm === null ) {
			$title = $frame->getTitle();
			$id = $title->getArticleID();
			if ( $input === null ) {
				$formContent = '';
			} else {
				$formContent = $input;
			}
			$this->setOfficialForm( (int)$id, $formContent );

		}

		if ( isset( $_COOKIE['ffSaveFields'] ) ) {
			Core::addPreSaved( json_decode( base64_decode( $_COOKIE['ffSaveFields'] ), true ) );
			setcookie(
				"ffSaveFields",
				"",
				time() - 3600,
				'/'
			);
		}

		Core::$securityId = uniqid();
		Core::$chkSums = [];
		Core::includeTagsCSS( Core::getRealUrl() . '/Modules/ext.WSForm.css' );

		// Are there explicit 'restrictions' lifts set?
		// TODO: Allow administrators of a wiki to configure whether lifting restrictions is allowed (useful for public wikis)
		if ( isset( $args['restrictions'] ) ) {
			// Parse the given restriction
			$restrictions = $parser->recursiveTagParse(
				$args['restrictions'],
				$frame
			);

			// Only allow anonymous users if the restrictions are lifted
			$allowAnonymous = strtolower( $restrictions ) === 'lifted';

			unset( $args['restrictions'] );
		} else {
			// By default, deny anonymous users
			$allowAnonymous = false;
		}

		if ( !$this->officialForm ) {
			return $this->returnNonValidatedResponse( $renderi18nErrorInsteadofImageForApprovedForms );
		}
		// && $allowAnonymous === false

		if ( Config::isSecure() === true ) {
			Core::includeInlineScript( "const wgFlexFormSecure = true;" );
		} else {
			Core::includeInlineScript( "const wgFlexFormSecure = false;" );
		}

		if ( isset( $args['messageonsuccess'] ) ) {
			$messageOnSuccess = $parser->recursiveTagParse(
				$args['messageonsuccess'],
				$frame
			);
			unset( $args['messageonsuccess'] );

			Core::includeInlineScript( 'var mwonsuccess = "' . htmlspecialchars( $messageOnSuccess ) . '";' );
		} else {
			$messageOnSuccess = null;
		}

		if ( isset( $args['attachmessageto'] ) && $args['attachmessageto'] !== '' ) {
			Core::includeInlineScript(
				'var mwMessageAttach = "' . htmlspecialchars( $args['attachmessageto'] ) . '";'
			);
		}

		if ( isset( $args['setwikicomment'] ) ) {
			$wikiComment = $parser->recursiveTagParse(
				$args['setwikicomment'],
				$frame
			);
			unset( $args['setwikicomment'] );
		} else {
			$wikiComment = null;
		}

		if ( isset( $args['mwreturn'] ) ) {
			$mwReturn = self::tagParseIfNeeded( $args['mwreturn'], $parser, $frame );
			$mwReturn = Core::getMWReturn( $mwReturn );
			unset( $args['mwreturn'] );
		} else {
			$mwReturn = $parser->getTitle()->getLinkURL();
		}

		if ( isset( $args['formtarget'] ) ) {
			$formTarget = $args['formtarget'];
			unset( $args['formtarget'] );
		} else {
			$formTarget = null;
		}

		if ( isset( $args['action'] ) ) {
			$action = $parser->recursiveTagParse(
				$args['action'],
				$frame
			);
			unset( $args['action'] );
		} else {
			$action = null;
		}

		if ( !empty( $args['extension'] ) ) {

			$extension = $parser->recursiveTagParse(
				$args['extension'],
				$frame
			);
			if ( empty( $extension ) ) {
				$extension = null;
			}
			unset( $args['extension'] );
		} else {
			if ( isset( $args['extension'] ) ) {
				unset( $args['extension'] );
			}
			$extension = null;
		}


		if ( isset( $args['autosave'] ) ) {
			$autosaveType = $parser->recursiveTagParse(
				$args['autosave'],
				$frame
			);
			unset( $args['autosave'] );
		} else {
			$autosaveType = null;
		}

		if ( isset( $args['class'] ) ) {
			$additionalClass = $parser->recursiveTagParse(
				$args['class'],
				$frame
			);
			unset( $args['class'] );
		} else {
			$additionalClass = null;
		}

		if ( isset( $args['show-on-select'] ) && strtolower(
													 $parser->recursiveTagParse(
														 $args['show-on-select'],
														 $frame
													 )
												 ) === 'show-on-select' ) {
			$showOnSelect = true;
			unset( $args['show-on-select'] );

			Core::setShowOnSelectActive();
			$input = Core::checkForShowOnSelectValueAndType( $input );
		} else {
			$showOnSelect = false;
		}

		if ( isset( $args['id'] ) ) {
			$formId = $parser->recursiveTagParse(
				$args['id'],
				$frame
			);

			if ( ! $this->checkValidInput( $formId ) ) {
				return [ 'Invalid form ID.' ];
			}

			unset( $args['id'] );
		} else {
			// Generate a form ID for the user to use in JavaScript snippets and such
			$formId = bin2hex( random_bytes( 16 ) );
		}

		if ( isset( $args['recaptcha-v3-action'] ) ) {
			Core::$reCaptcha = $args['recaptcha-v3-action'];
			unset( $args['recaptcha-v3-action'] );
		}

		if ( isset( $args['changetrigger'] ) ) {
			$changeCall = $parser->recursiveTagParse(
				$args['changetrigger'],
				$frame
			);
			unset( $args['changetrigger'] );

			if ( $this->checkValidInput( $changeCall ) ) {
				Core::includeInlineScript( "$('#" . $formId . "').change(" . $changeCall . "(this));" );
			}
		}

		// Do we have scripts to load?
		if ( isset( $args['loadscript'] ) && $args['loadscript'] !== '' ) {
			$scriptToLoad = $parser->recursiveTagParse(
				$args['loadscript'],
				$frame
			);
			unset( $args['loadscript'] );

			// Validate the file name
			if ( ! $this->checkValidInput( $scriptToLoad ) ) {
				return [
					'The script specified in "loadscript" could not be loaded because the file name is invalid.',
					'noparse' => true
				];
			}

			// Is this script already loaded?
			if ( !Core::isLoaded( $scriptToLoad ) ) {
				$loadScriptPath = Config::getConfigVariable( 'loadScriptPath' );
				if ( !file_exists(
					$loadScriptPath . $scriptToLoad . '.js'
				) ) {
					return [ 'The script specified in "loadscript" could not be loaded because it does not exist.' ];
				}

				$scriptContent = @file_get_contents(
					$loadScriptPath . $scriptToLoad . '.js'
				);

				if ( $scriptContent === false ) {
					return [ 'The script specified in "loadscript" could not be loaded because it is unreadable.' ];
				}

				if ( $formId !== null ) {
					Core::includeJavaScriptConfig(
						'wsForm_' . $scriptToLoad,
						$formId
					);
				}

				Core::includeInlineScript( $scriptContent );
				Core::addAsLoaded( $scriptToLoad );
			}
		}

		if ( isset( $args['no_disable_on_submit'] ) ) {
			Core::includeJavaScriptConfig( 'ffDoNotDisableSubmit', true );
		}

		$fPermissions = null;
		if ( isset( $args['permissions'] ) ) {
			$fPermissions = $args['permissions'];
		}

		if ( isset( $args['no_submit_on_return'] ) ) {
			unset( $args['no_submit_on_return'] );
			if ( isset( $args['class'] ) ) {
				$args['class'] .= ' ff-nosubmit-onreturn';
			} else {
				$args['class'] = 'ff-nosubmit-onreturn';
			}
			//Core::includeJavaScriptConfig( 'noSubmit', $formId );
			if ( !Core::isLoaded( 'keypress' ) ) {
				$noEnter = <<<SCRIPT
                wachtff( noReturnOnEnter ); 
                SCRIPT;

				Core::includeInlineScript( $noEnter );
				Core::addAsLoaded( 'keypress' );
			}
		}

		$additionalArgs = [];
		foreach ( $args as $name => $argument ) {
			if ( Validate::validFormParameters( $name ) ) {
				$additionalArgs[$name] = $parser->recursiveTagParse(
					$argument,
					$frame
				);
			}
		}

		// Block the request if the user is not logged in and anonymous users are not allowed
		if ( $allowAnonymous === false && !$wgUser->isRegistered() ) {
			return wfMessage( "flexform-anonymous-user" )->parse();
		}

		// If the action is add to wiki, make sure the user has confirmed their email address
		if ( $action === 'addToWiki' && $wgEmailConfirmToEdit === true && $wgUser->isRegistered(
			) && ! $wgUser->isEmailConfirmed() ) {
			return wfMessage( "flexform-unverified-email1" )->parse() . wfMessage(
					"flexform-unverified-email2"
				)->parse();
		}

		if ( Core::getRun() === false ) {
			// FIXME: Move to ResourceLoader
			//Core::includeTagsScript( Core::getRealUrl() . '/Modules/FlexForm.general.js' );
			$addFFJS  = '<script type="text/javascript" charset="UTF-8" src="' . Core::getRealUrl() . '/Modules/FlexForm.general.js"></script>' . "\n";

			Core::setRun( true );
		}

		$actionUrl = $formTarget ?? Core::getAPIurl();
		$output = '';
		if ( isset( $args['json'] ) ) {
			$handleJSON = new Json();
			$output = $handleJSON->handleJSON( $args['json'], $args, $parser, $frame, $this->themeStore );
		}
		$output .= $parser->recursiveTagParse(
			trim( $input ),
			$frame
		);

		$separator = $this->createSeparatorField( Core::$separator );

		try {
			$previousTheme = $this->themeStore->getFormThemeName();

			if ( isset( $args['theme'] ) ) {
				$theme = $parser->recursiveTagParse(
					$args['theme'],
					$frame
				);

				try {
					$this->themeStore->setFormThemeName( $theme );
				} catch ( FlexFormException $exception ) {
					// Silently ignore and use the default theme
				}
			}

			// Render the actual contents of the form
			$ret .= $this->themeStore->getFormTheme()->getFormRenderer()->render_form(
				$output,
				$actionUrl,
				$mwReturn,
				$formId,
				$messageOnSuccess,
				$wikiComment,
				$action,
				$extension,
				$autosaveType,
				$additionalClass,
				$showOnSelect,
				$additionalArgs,
				$separator,
				$fPermissions
			);
		} finally {
			$this->themeStore->setFormThemeName( $previousTheme );
		}

		$ret .= $addFFJS;

		if ( Core::isShowOnSelectActive() ) {
			$ret .= Core::addShowOnSelectJS();
		}

		if ( Core::$reCaptcha !== false && ! Core::isLoaded( 'google-captcha' ) ) {
			$captcha = Recaptcha::render();

			if ( $captcha !== false ) {
				Core::addAsLoaded( 'google-captcha' );
				$ret = $captcha . $ret;
			} else {
				return wfMessage( "flexform-captcha-missing-config" )->parse();
			}
		}

		if ( Core::$reCaptcha !== false ) {
			if ( $formId === null ) {
				return wfMessage( "flexform-recaptcha-no-form-id" )->parse();
			}

			if ( file_exists( $IP . '/extensions/FlexForm/Modules/recaptcha.js' ) ) {
				$rcaptcha = file_get_contents( $IP . '/extensions/FlexForm/Modules/recaptcha.js' );

				$replace = array(
					'%%id%%',
					'%%action%%',
					'%%sitekey%%',
				);

				$with = array(
					$formId,
					Core::$reCaptcha,
					Recaptcha::$rc_site_key
				);

				$rcaptcha = str_replace(
					$replace,
					$with,
					$rcaptcha
				);

				Core::includeInlineScript( $rcaptcha );
				Core::$reCaptcha = false;
			} else {

				return wfMessage( "flexform-recaptcha-no-js" )->parse();
			}
		}

		self::addInlineJavaScriptAndCSS();

		return [
			$ret,
			"markerType" => 'nowiki'
		];
	}

	/**
	 * @param bool $renderi18nErrorInsteadofImageForApprovedForms
	 *
	 * @return string[]
	 */
	private function returnNonValidatedResponse( bool $renderi18nErrorInsteadofImageForApprovedForms ){
		if ( $renderi18nErrorInsteadofImageForApprovedForms === true ) {
			return [
				'<span class="ff-invalid">' . wfMessage( 'flexform-unvalidated-form' )->text() . '</span>',
				"markerType" => 'nowiki'
			];
			// TODO: Add image here
		} else {
			global $wgScript;
			$realUrl               = str_replace(
				'/index.php',
				'',
				$wgScript
			);
			$img = $realUrl . '/extensions/FlexForm/Modules/unnaproved.png';
			$html = '<img src="' . $img . '" alt="'.wfMessage( 'flexform-unvalidated-form' )->text().'">';
			return [
				$html,
				"markerType" => 'nowiki'
			];
		}
	}

	/**
	 * @brief Function to render am Option input field.
	 *
	 * This function will look for the option input fields and will call its subfunction render_<inputfield>
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderOption( $input, array $args, Parser $parser, PPFrame $frame ) {
		$args['type'] = 'option';
		return $this->renderField( $input, $args, $parser, $frame );
	}

	/**
	 * @brief Function to render a button field.
	 *
	 * This function will look for a button input field and will call its subfunction render_<inputfield>
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderButton( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( isset( $args['type'] ) ) {
			$args['buttontype'] = $args['type'];
		}
		$args['type'] = 'button';
		return $this->renderField( $input, $args, $parser, $frame );
	}

	/**
	 * @brief Function to render a textarea input field.
	 *
	 * This function will look for the textarea input field and will call its subfunction render_<inputfield>
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderTextarea( $input, array $args, Parser $parser, PPFrame $frame ) {
		$args['type'] = 'textarea';
		return $this->renderField( $input, $args, $parser, $frame );
	}

	/**
	 * @brief Function to render an input field.
	 *
	 * This function will look for the type of input field and will call its subfunction render_<inputfield>
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderField( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $IP;

		$args = $this->filterInputTags( $args );

		if ( !isset( $args['type'] ) ) {
			return [
				wfMessage( "flexform-field-invalid" )->parse(),
				"markerType" => 'nowiki'
			];
		}


		$fieldType = $args['type'];

		$fieldType = trim( $this->tagParseIfNeeded(
			$fieldType,
			$parser,
			$frame
		) );

		if ( !Validate::validInputTypes( $fieldType ) ) {
			return [
				wfMessage( "flexform-field-invalid" )->parse() . ": " . $fieldType,
				"markerType" => 'nowiki'
			];
		}

		if ( isset( $args['parsepost'] ) && isset( $args['name'] ) ) {
			// FIXME: What is this, and can it be removed?
			// CC: No it cannot!
			$parsePost = true;
			$parseName = $args['name'];

			unset( $args['parsepost'] );
		} else {
			$parsePost = false;
		}
		$secure = Config::isSecure();
		Protect::setCrypt( Core::$checksumKey );
		if ( isset( $args['tempex'] ) && $args['tempex'] !== '' ) {
			if ( $secure ) {
				$args['data-tempex'] = Protect::encrypt( $args['tempex'] );
			} else {
				$args['data-tempex'] = $args['tempex'];
			}
			unset( $args['tempex'] );
		}

		// We always parse the input, unless noparse is set.
		if ( !isset( $args['noparse'] ) ) {
			$input = self::recursiveParseMe( $parser, $frame, $input );
			$noParse = false;
		} else {
			unset( $args['noparse'] );
			$noParse = true;
		}

		// Parse the arguments
		/* TODO: This is not a good solution. Sometimes arguments need to be parsed but not always.
		   TODO: Like you want to have {{Template:test}} inside the content of a page!
		*/
		foreach ( $args as $name => $value ) {
			if ( $name === 'value' ) {
				if ( $noParse ) {
					$tempValue = $value;
				} else {
					$tempValue = $this->tagParseIfNeeded(
						$value,
						$parser,
						$frame
					);
				}
			} else {
				$tempValue = $this->tagParseIfNeeded(
					$value,
					$parser,
					$frame
				);
			}
			if ( $tempValue !== $value ) {
				// If we have had to parse the content, then make sure HTMLPurifier leaves it alone
				$args['html'] = 'all';
			}
			$args[$name] = $tempValue;
		}


		$renderer = $this->themeStore->getFormTheme()->getFieldRenderer();
		switch ( $fieldType ) {
			case 'text':
				if ( isset( $args['mwidentifier'] ) && $args['mwidentifier'] === 'datepicker' ) {
					$parser->getOutput()->addModules( 'ext.wsForm.datePicker.scripts' );
					$parser->getOutput()->addModuleStyles( 'ext.wsForm.datePicker.styles' );
				}
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"text"
				);
				$ret               = $renderer->render_text( $preparedArguments );

				break;
			case 'hidden':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"hidden"
				);
				$ret               = $renderer->render_hidden( $preparedArguments );

				break;
			case 'message':
				$toUser = '';
				$userMessage = '';
				$messageTitle = '';
				$messagePersistent = "no";

				if ( isset( $args['user'] ) ) {
					$toUser = $args['user'];
				}

				$messageType = $args['message-type'] ?? 'danger';

				if ( isset( $args['message'] ) ) {
					$userMessage = $args['message'];
				}

				if ( isset( $args['message-title'] ) ) {
					$messageTitle = $args['message-title'];
				}

				if ( isset( $args['message-confirm'] ) && $args['message-confirm'] === "message-confirm" ) {
					$messagePersistent = "yes";
				}

				if ( $userMessage === '' || $toUser === '' ) {
					return [ '' ];
				}

				$args['name'] = 'ff-message[]';
				$args['value'] = $toUser . '^^-^^' . $messageType . '^^-^^' . $userMessage;
				$args['value'] .= '^^-^^' . $messageTitle . '^^-^^' . $messagePersistent;

				if ( !Config::isSecure() ) {
					$preparedArguments = Validate::doSimpleParameters(
						$args,
						"hidden"
					);
				} else {
					$preparedArguments = Validate::doSimpleParameters(
						$args,
						"secure"
					);
				}
				$ret = $renderer->render_message( $preparedArguments );

				break;
			case 'secure':
				if ( !Config::isSecure() ) {
					return [ wfMessage( 'flexform-field-secure-not-available' )->parse() ];
				}

				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"secure"
				);
				$ret               = $renderer->render_secure( $preparedArguments );

				break;
			case 'search':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"search"
				);
				$ret               = $renderer->render_search( $preparedArguments );

				break;
			case 'number':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"number"
				);
				if ( isset( $preparedArguments['calc'] ) && $preparedArguments['calc'] !== '' ) {
					if ( $secure ) {
						$preparedArguments['data-calc'] = Protect::encrypt( $preparedArguments['calc'] );
					} else {
						$preparedArguments['data-calc'] = $preparedArguments['calc'];
					}
					unset( $preparedArguments['calc'] );
					$preparedArguments['readonly'] = 'readonly';
				}
				$ret = $renderer->render_number( $preparedArguments );

				break;
			case 'radio':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"radio"
				);
				$ret               = $renderer->render_radio(
					$preparedArguments,
					$args['show-on-checked'] ?? ''
				);
				break;
			case 'checkbox':

				// Added in v0.8.0.9.6.2. Allowing for a default value for a checkbox
				// for when the checkbox is not checked.
				$default = false;
				$defaultName = false;
				if ( isset( $args['default'] ) && $args['default'] !== '' ) {
					$default = $args['default'];
					$defaultName = false;
					if ( isset( $args['name'] ) ) {
						$defaultName = "wsdefault_" . $args['name'];
						if ( strpos( $defaultName, "[]" ) ) {
							$defaultName = rtrim( $defaultName, '[]' );
						}
					}
				}
				// END default checkbox

				$preparedArguments = Validate::doCheckboxParameters( $args );

				$ret = $renderer->render_checkbox(
					$preparedArguments,
					$args['show-on-checked'] ?? '',
					$args['show-on-unchecked'] ?? '',
					$default,
					$defaultName
				);

				break;
			case 'file':
				// TODO: Move most of the render_file logic to here
				// TODO: Can you do this @Charlot?
				$ret = trim( $renderer->render_file( $this->renderFileUpload( $args ) ) );

				break;
			case 'date':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"date"
				);
				$ret               = $renderer->render_date( $preparedArguments );

				break;
			case 'month':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"month"
				);
				$ret               = $renderer->render_month( $preparedArguments );

				break;
			case 'week':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"week"
				);
				$ret               = $renderer->render_week( $preparedArguments );

				break;
			case 'time':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"time"
				);
				$ret               = $renderer->render_time( $preparedArguments );

				break;
			case 'datetime':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"datetime"
				);
				$ret               = $renderer->render_datetime( $preparedArguments );

				break;
			case 'datetime-local':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"datetime-local"
				);
				$ret               = $renderer->render_datetimelocal( $preparedArguments );

				break;
			case 'password':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"password"
				);
				$ret               = $renderer->render_password( $preparedArguments );

				break;
			case 'email':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"email"
				);
				$ret               = $renderer->render_email( $preparedArguments );

				break;
			case 'color':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"color"
				);
				$ret               = $renderer->render_color( $preparedArguments );

				break;
			case 'range':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"range"
				);
				$ret               = $renderer->render_range( $preparedArguments );

				break;
			case 'image':
				$imageArguments = [];

				foreach ( $args as $name => $value ) {
					if ( Validate::validParameters( $name ) ) {
						continue;
					}

					$imageArguments[$name] = $value;

					Core::addCheckSum(
						"image",
						$name,
						$value
					);
				}

				$ret = $renderer->render_image( $args );

				break;
			case 'url':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"url"
				);
				$ret               = $renderer->render_url( $preparedArguments );

				break;
			case 'tel':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"tel"
				);
				$ret               = $renderer->render_tel( $preparedArguments );

				break;
			case 'option':
				// Get the value for the option, or give an error if no value is set
				if ( isset( $args['value'] ) ) {
					$value = $args['value'];
					unset( $args['value'] );
				} else {
					return [ 'Missing value attribute.' ];
				}

				// Check whether show-on-select is enabled
				if ( isset( $args['show-on-select'] ) ) {
					$showOnSelect = $args['show-on-select'];
					unset( $args['show-on-select'] );
				} else {
					$showOnSelect = null;
				}
				Core::setSeparator( $this->getSeparator( $args ) );

				// Check if a 'for' option is set, to determine whether the field should be rendered as 'selected'
				if ( isset( $args['for'] ) ) {
					$selectedParameterName = $args['for'];

					$selectedValues = $_GET[$selectedParameterName] ?? '';
					$selectedValues = explode(
						Core::$separator,
						$selectedValues
					);
					$selectedValues = array_map(
						'trim',
						$selectedValues
					);

					$isSelected = in_array(
						$value,
						$selectedValues
					);

					unset( $args['for'] );
				} else {
					$isSelected = false;
				}

				$additionalArguments = [];
				foreach ( $args as $name => $v ) {
					if ( ! Validate::check_disable_readonly_required_selected(
							$name,
							$v
						) || ! Validate::validParameters( $name ) ) {
						$additionalArguments[$name] = $v;
					}
				}

				/* Input is already parse
				$ret = $renderer->render_option(
					$parser->recursiveTagParse(
						$input,
						$frame
					),
					$value,
					$showOnSelect,
					$isSelected,
					$additionalArguments
				);
				*/
				$ret = $renderer->render_option(
					$input,
					$value,
					$showOnSelect,
					$isSelected,
					$additionalArguments
				);

				break;
			case 'submit':
				$identifier     = false;
				$callBack       = 0;
				$beforeCallBack = 0;
				$validArgs      = [];
				$additionalHtml = '';
				foreach ( $args as $k => $v ) {
					switch ( strtolower( $k ) ) {
						case "mwidentifier" :
							if ( strtolower( $v ) === 'ajax' ) {
								$additionalHtml .= Core::createHiddenField(
									'mwidentifier',
									$v
								);
								if ( ! Core::isLoaded( 'wsform-ajax' ) ) {
									if ( file_exists( $IP . '/extensions/FlexForm/Modules/wsform-ajax.js' ) ) {
										Core::includeTagsScript( Core::getRealUrl() . '/Modules/wsform-ajax.js' );
										/*
										$additionalHtml .= '<script src="' . wfGetServerUrl(
												null
											) . '/extensions/FlexForm/Modules/wsform-ajax.js"></script>' . "\n";
										*/
										Core::addAsLoaded( 'wsform-ajax' );
									}
								}

								$identifier = true;
							}
							break;
						case "mwpausebeforerefresh":
							$additionalHtml .= '<input type="hidden" name="mwpause" value="' . $v . '">' . PHP_EOL;
							break;
						case "callback":
							$callBack = trim( $v );
							break;
						case "beforecallback":
							$beforeCallBack = trim( $v );
							break;
					}

					if ( Validate::validParameters( $k ) ) {
						$validArgs[$k] = $v;
					}
				}
				if ( $callBack !== 0 && $identifier === true ) {
					if ( ! Core::isLoaded( $callBack ) ) {
						if ( file_exists( $IP . '/extensions/FlexForm/Modules/customJS/' . $callBack . '.js' ) ) {
							$lf             = file_get_contents(
								$IP . '/extensions/FlexForm/Modules/customJS/' . $callBack . '.js'
							);
							$additionalHtml .= "<script>$lf</script>\n";
							Core::addAsLoaded( $callBack );
						}
					}
				}

				if ( $identifier ) {
					$validArgs['onclick'] = 'wsform(this,' . $callBack . ',' . $beforeCallBack . ');';
				}

				$output = $this->themeStore->getFormTheme()->getFieldRenderer()->render_submit(
					$validArgs,
					$identifier
				);

				return [
					$output . $additionalHtml,
					'markerType' => 'nowiki'
				];

				break;
			case 'button':
				if ( isset( $args['buttontype'] ) ) {
					$buttonType = $args['buttontype'];
					unset( $args['buttontype'] );
				} else {
					$buttonType = 'button';
				}

				$additionalArguments = [];
				foreach ( $args as $name => $value ) {
					if ( ! Validate::validButtonParameters( $name ) && ! Validate::validParameters( $name ) ) {
						continue;
					}
					$additionalArguments[$name] = $value;
				}

				$ret = $this->themeStore->getFormTheme()->getFieldRenderer()->render_button(
					$input,
					$buttonType,
					$additionalArguments
				);

				break;
			case 'reset':
				$additionalArguments = [];
				foreach ( $args as $name => $value ) {
					if ( ! Validate::validParameters( $name ) ) {
						continue;
					}

					$additionalArguments[$name] = $value;
				}

				$ret = $renderer->render_reset( $additionalArguments );

				break;
			case 'textarea':
				if ( isset( $args['value'] ) ) {
					// Use the value attribute as the input of the tag
					$input = $args['value'];
					unset( $args['value'] );
				}

				//FIXME: Where comes this from @Marijn?
				if ( isset( $args['name'] ) ) {
					$tagName = $args['name'];
					unset( $args['name'] );
				} else {
					//$tagName = bin2hex( random_bytes( 16 ) );
					$tagName = "";
				}

				if ( isset( $args['class'] ) ) {
					$class = $args['class'];
					unset( $args['class'] );
				} else {
					$class = null;
				}

				if ( isset( $args['editor'] ) ) {
					$editor = $args['editor'];
					unset( $args['editor'] );
				} else {
					$editor = null;
				}

				$additionalArguments = [];
				foreach ( $args as $name => $value ) {
					if ( ! Validate::validParameters( $name ) || Validate::check_disable_readonly_required_selected(
							$name,
							$value
						) ) {
						continue;
					}

					$additionalArguments[$name] = $value;
				}

				$htmlType = Validate::validHTML( $args );



				if ( $input !== '' ) {

					/*
					if ( $noParse === false ) {
						$input = $parser->recursiveTagParse(
							$input,
							$frame
						);
					}
					*/
					// We want to purify the input based on the form's HTML type
					//echo "<pre>";
					//var_dump( $input );
					$input = Protect::purify(
						$input,
						$htmlType,
						Config::isSecure()
					);
					//var_dump( $input );
					//echo "</pre>";
				} elseif ( Core::getValue( $tagName ) !== '' ) {
					// No input is given in the field, but we might have input through GET parameters
					$input = Protect::purify(
						Core::getValue( $tagName ),
						$htmlType,
						Config::isSecure()
					);
				}
				$ret = '';
				if ( isset( $editor ) && $editor === "ve" ) {
					if ( ExtensionRegistry::getInstance()->isLoaded( 'VEForAll' ) ) {
						$parser->getOutput()->addModules( 'ext.veforall.main' );
						$class .= ' load-editor ';
						$ret = '<span class="ve-area-wrapper">';
					}
				}
				if ( isset( $args['source'] ) ) {
					$render       = new Render();
					$source = $render->getSlotContent( $args['source'] );
					$input = $source['content'];
				}

				Core::addCheckSum(
					'textarea',
					$tagName,
					$input,
					$htmlType
				);

				$ret .= $this->themeStore->getFormTheme()->getFieldRenderer()->render_textarea(
					$input,
					$tagName,
					$class,
					$editor,
					$additionalArguments,
					$htmlType
				);
				if ( isset( $editor ) && $editor === "ve" ) {
					if ( ExtensionRegistry::getInstance()->isLoaded( 'VEForAll' ) ) {
						$ret .= '</span>' . PHP_EOL;
						Core::includeInlineScript( 'var WSFormEditor = "VE";' );
						global $wgScript;
						$gifUrl = str_replace( '/index.php', '', $wgScript ) . '/extensions/FlexForm/Modules/load-editor.gif';
						$cssVE = '.load-editor{ 
								background: url("' . $gifUrl . '") no-repeat bottom right #fff;
								background-size: 50px; 
							}';
						Core::includeInlineCSS( $cssVE );
					}
				}






				break;
			case 'signature':
				$uploadDetails = [];
				if ( isset( $args['fname'] ) ) {
					$fileName = $args['fname'];
				} else {
					return [ 'Missing attribute "fname" for signature field.' ];
				}

				if ( isset( $args['name'] ) ) {
					$name = General::makeUnderscoreFromSpace( trim( $args['name'] ) );
				} else {
					return [ 'Missing attribute "name" for signature field.' ];
				}

				if ( isset( $args['pagecontent'] ) ) {
					$pageContent = $args['pagecontent'];
				} else {
					return [ 'Missing attribute "pagecontent" for signature field.' ];
				}

				$fileType         = $args['ftype'] ?? 'svg';
				$class            = $args['class'] ?? null;
				$clearButtonClass = $args['clearbuttonclass'] ?? null;
				$clearButtonText  = $args['clearbuttontext'] ?? 'Clear';
				$parseContent     = $args['parsecontent'] ?? null;
				$pageTemplate     = $args['template'] ?? null;
				$required         = isset( $args['required'] ) && $args['required'] === 'required';

				$javascriptOptions = [
					'syncField: "#' . $name . '_signature_data"',
					'syncFormat: "' . htmlspecialchars( strtoupper( $fileType ) ) . '"'
				];

				if ( isset( $args['background'] ) ) {
					$javascriptOptions[] = sprintf(
						'background: "%s"',
						htmlspecialchars(
							$args['background'],
							ENT_QUOTES
						)
					);
				}

				if ( isset( $args['drawcolor'] ) ) {
					$javascriptOptions[] = sprintf(
						'color: "%s"',
						htmlspecialchars(
							$args['drawcolor'],
							ENT_QUOTES
						)
					);
				}

				if ( isset( $args['thickness'] ) ) {
					$javascriptOptions[] = sprintf(
						'thickness: "%s"',
						htmlspecialchars(
							$args['thickness'],
							ENT_QUOTES
						)
					);
				}

				if ( isset( $args['guideline'] ) && $args['guideline'] === 'true' ) {
					$javascriptOptions[] = sprintf( 'guideline: true' );

					if ( isset( $args['guidelineoffset'] ) ) {
						$javascriptOptions[] = sprintf(
							'guidelineOffset: "%s"',
							htmlspecialchars(
								$args['guidelineoffset'],
								ENT_QUOTES
							)
						);
					}

					if ( isset( $args['guidelineindent'] ) ) {
						$javascriptOptions[] = sprintf(
							'guidelineIndent: "%s"',
							htmlspecialchars(
								$args['guidelineindent'],
								ENT_QUOTES
							)
						);
					}

					if ( isset( $args['guidelinecolor'] ) ) {
						$javascriptOptions[] = sprintf(
							'guidelineColor: "%s"',
							htmlspecialchars(
								$args['guidelinecolor'],
								ENT_QUOTES
							)
						);
					}

					if ( isset( $args['notavailablemessage'] ) ) {
						$javascriptOptions[] = sprintf(
							'notAvailable: "%s"',
							htmlspecialchars(
								$args['notavailablemessage'],
								ENT_QUOTES
							)
						);
					}
				}


				$javascriptOptions = implode(
					',',
					$javascriptOptions
				);
				/*
				$jsOptions = [];
				foreach ( $javascriptOptions as $singleOption ) {
					foreach ( $singleOption as $k => $v ) {
						$jsOptions[$k] = $v;
					}
				}

				$jsOptions = json_encode( $jsOptions );
				*/
				Core::includeJavaScriptConfig( 'ff_signature', [ 'name' => $name ] );
				Core::includeInlineScript(
					<<<SCRIPT
                    function signature_$name() {
                        $("#$name-signature").signature({
                            $javascriptOptions
                        });
                        
                        $("#$name-signature-clear").click(function() {
                            $("#$name-signature").signature("clear");
                        });
                    }
                SCRIPT
				);

				if ( !Core::isLoaded( 'jquery.signature.css' ) ) {
					Core::addAsLoaded( 'jquery.signature.css' );
					Core::includeTagsCSS( Core::getRealUrl() . '/Modules/signature/css/jquery.signature.css' );
				}

				if ( !Core::isLoaded( 'jquery.ui.css' ) ) {
					Core::addAsLoaded( 'jquery.ui.css' );
					Core::includeTagsCSS(
						'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/south-street/jquery-ui.css'
					);
				}

				if ( !Core::isLoaded( 'do-signature.js' ) ) {
					Core::addAsLoaded( 'do-signature.js' );
					Core::includeTagsScript( Core::getRealUrl() . '/Modules/signature/js/do-signature.js' );
				}

				$uploadDetails['wsform_signature_filename'] = $fileName;
				$uploadDetails['wsform_signature_type'] = $fileType;
				$uploadDetails['wsform_signature_page_content'] = $pageContent;
				$uploadDetails['parsecontent'] = $parseContent ?? false;
				$uploadDetails['pagetemplate'] = $pageTemplate ?? false;
				$uploadDetails['type'] = 'signature';

				$signatureDataAttributes = [
					'id'   => $name . '_signature_data',
					'type' => 'hidden'
				];

				if ( $required ) {
					$signatureDataAttributes['required'] = 'required';
				}

				$ret = \Xml::input(
					$name,
					false,
					'',
					$signatureDataAttributes
				);
				$ret .= \Xml::tags(
					'div',
					[
						'id'    => $name . '-signature',
						'class' => 'wsform-signature ' . $class ?? ''
					],
					''
				);
				$ret .= \Xml::tags(
					'button',
					[
						'type'  => 'button',
						'id'    => $name . '-signature-clear',
						'class' => 'wsform-signature-clear ' . $clearButtonClass ?? ''
					],
					htmlspecialchars( $clearButtonText )
				);
				$actionFields = [];
				$actionFields[$name] = $uploadDetails;
				Core::includeFileAction( $actionFields );

				// TODO: Make this theme-able

				break;
			case 'mobilescreenshot': // TODO: Implement 'mobilescreenshot'
			default:
				return [ 'The field type "' . htmlspecialchars( $fieldType ) . '" is currently not supported.' ];
		}

		if ( $parsePost === true && isset( $parseName ) ) {
			$ret .= '<input type="hidden" name="wsparsepost[]" value="' . htmlspecialchars( $parseName ) . "\">\n";
		}

		self::addInlineJavaScriptAndCSS();

		return array(
			$ret,
			"markerType" => 'nowiki'
		);
	}

	/**
	 * @brief This is the initial call from the MediaWiki parser for the WSFieldset
	 *
	 * @param string $input Received from parser from begin till end
	 * @param array $args List of argmuments for the Fieldset
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderFieldset( $input, array $args, Parser $parser, PPFrame $frame ) {
		$input = $parser->recursiveTagParse(
			$input,
			$frame
		);

		$args = $this->filterInputTags( $args );

		foreach ( $args as $name => $value ) {
			if ( ( strpos(
					   $value,
					   '{'
				   ) !== false ) && ( strpos(
										  $value,
										  "}"
									  ) !== false ) ) {
				$args[$name] = $parser->recursiveTagParse(
					$value,
					$frame
				);
			}
		}

		$output = $this->themeStore->getFormTheme()->getFieldsetRenderer()->render_fieldset(
			$input,
			$args
		);

		return [
			$output,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief renderes the html legend (for use with fieldset)
	 *
	 * @param string $input Received from parser from begin till end
	 * @param array $args List of argmuments for the Legend
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderLegend( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( isset( $args['class'] ) ) {
			$class = $parser->recursiveTagParse(
					$args['class'],
					$frame
				) ?? '';
		} else {
			$class = '';
		}
		if ( isset( $args['align'] ) ) {
			$align = $parser->recursiveTagParse(
					$args['align'],
					$frame
				) ?? '';
		} else {
			$align = '';
		}
		$input = $parser->recursiveTagParse(
			$input,
			$frame
		);

		$output = $this->themeStore->getFormTheme()->getLegendRenderer()->render_legend(
			$input,
			$class,
			$align
		);

		return [
			$output,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @param string $content
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return string
	 */
	private function tagParseIfNeeded( $content, Parser $parser, PPFrame $frame ) {
		if ( ( strpos(
				   $content,
				   '{'
			   ) !== false ) && ( strpos(
									  $content,
									  '}'
								  ) !== false ) ) {
			return $parser->recursiveTagParse(
				$content,
				$frame
			);
		} else {
			return $content;
		}
	}

	/**
	 * @param $tags
	 *
	 * @return array
	 */
	private function filterInputTags( array $tags ): array {
		$skipped = [
			'src',
			'value'
		];
		if ( Config::isFilterTags() ) {
			foreach ( $tags as $k => $v ) {
				if ( !in_array( $k, $skipped ) && ( substr( $k, 0, 4 ) !== 'data' ) ) {
					$k        = Protect::purify(
						$k,
						'nohtml',
						true
					);
					$v        = Protect::purify(
						$v,
						'nohtml',
						true
					);
					$tags[$k] = $v;
				}
			}
		}
		return $tags;
	}

	/**
	 * @brief renders the html label
	 *
	 * @param string $input Received from parser from begin till end
	 * @param array $args List of arguments for a Label
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderLabel( $input, array $args, Parser $parser, PPFrame $frame ) {
		$args = $this->filterInputTags( $args );
		if ( isset( $args['for'] ) ) {
			$for = $args['for'];
			unset( $args['for'] );
		} else {
			// A label MUST have a for according to the HTML specification
			$for = '';
		}
		$for = self::tagParseIfNeeded( $for, $parser, $frame );
		$inputArguments = [];
		foreach ( $args as $name => $value ) {
			if ( Validate::validParameters( $name ) ) {
				$inputArguments[$name] = self::tagParseIfNeeded( $value, $parser, $frame );
			}
		}

		// We always parse the input, unless noparse is set.
		if ( ! isset( $args['noparse'] ) ) {
			$noParse = false;
			$input = self::recursiveParseMe( $parser, $frame, $input );
		} else {
			unset( $args['noparse'] );
			$noParse = true;
		}

		$output = $this->themeStore->getFormTheme()->getLabelRenderer()->render_label(
			$input,
			$for,
			$inputArguments
		);

		return [
			$output,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief This is the initial call from the MediaWiki parser for the WSSelect
	 *
	 * @param $input string Received from parser from begin till end
	 * @param array $args List of argmuments for the selectset
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderSelect( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( isset( $args['placeholder'] ) ) {
			$placeholder = $parser->recursiveTagParse(
				$args['placeholder'],
				$frame
			);
			unset( $args['placeholder'] );
		} else {
			$placeholder = null;
		}

		Core::setSeparator( $this->getSeparator( $args ) );

		if ( isset( $args['selected'] ) ) {
			$args['selected'] = $parser->recursiveTagParse(
				$args['selected'],
				$frame
			);
			$selectedValues = explode(
				Core::$separator,
				$args['selected']
			);
			$selectedValues = array_map(
				'trim',
				$selectedValues
			);
			unset( $args['selected'] );
		} else {
			$selectedValues = [];
		}

		if ( isset( $args['options'] ) ) {
			$args['options'] = $parser->recursiveTagParse(
				$args['options'],
				$frame
			);
			$options = explode(
				Core::$separator,
				$args['options']
			);
			$options = array_map(
				'trim',
				$options
			);
			unset( $args['options'] );
		} else {
			$options = [];
		}

		$additionalArgs = [];
		foreach ( $args as $name => $value ) {
			if ( ! Validate::validParameters( $name ) ) {
				continue;
			}

			if ( $name === "name" && strpos(
										 $value,
										 '[]'
									 ) === false ) {
				$value .= '[]';
			}

			$additionalArgs[$name] = $parser->recursiveTagParse(
				$value,
				$frame
			);
		}

		$input  = $parser->recursiveTagParseFully(
			$input,
			$frame
		);
		$select = $this->themeStore->getFormTheme()->getSelectRenderer()->render_select(
			$input,
			$placeholder,
			$selectedValues,
			$options,
			$additionalArgs
		);

		return [
			$select,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief This is the initial call from the MediaWiki parser for the _token field
	 *
	 * @param $input string Received from parser from begin till end
	 * @param array $args List of arguments for the Tokens
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderToken( $input, array $args, Parser $parser, PPFrame $frame ) {
		$parsedInput = $parser->recursiveTagParseFully(
			$input,
			$frame
		);

		if ( isset( $args['placeholder'] ) ) {
			$placeholder = $parser->recursiveTagParse(
				$args['placeholder'],
				$frame
			);
			unset( $args['placeholder'] );
		} else {
			$placeholder = null;
		}

		if ( isset( $args['multiple'] ) ) {
			$multiple = $parser->recursiveTagParse(
					$args['multiple'],
					$frame
				) === "multiple";
			unset( $args['multiple'] );
		} else {
			$multiple = false;
		}

		if ( isset( $args['id'] ) ) {
			$id = $parser->recursiveTagParse(
				$args['id'],
				$frame
			);

			// Make sure ID is valid
			if ( ! $this->checkValidInput( $id ) ) {
				return [
					'Invalid ID as it does not match pattern',
					'noparse' => true
				];
			}

			unset( $args['id'] );
		} else {
			try {
				// Generate a random fallback ID
				$id = bin2hex( random_bytes( 16 ) );
			} catch ( \Exception $exception ) {
				return [
					'Could not get enough entropy to generate random ID',
					'noparse' => true
				];
			}
		}

		if ( isset( $args['input-length-trigger'] ) ) {
			$inputLengthTrigger = $parser->recursiveTagParse(
				$args['input-length-trigger'],
				$frame
			);
			$inputLengthTrigger = intval( trim( $inputLengthTrigger ) );
			unset( $args['input-length-trigger'] );
		} else {
			$inputLengthTrigger = 3;
		}
		if ( isset( $args['query'] ) ) {
			$smwQuery = $parser->replaceVariables( $args['query'], $frame, true );
			unset( $args['query'] );
		} else {
			$smwQuery = null;
		}

		if ( isset( $args['json'] ) ) {
			$json = strpos(
				$args['json'],
				'semantic_ask'
			) ? $args['json'] : $parser->recursiveTagParse(
				$args['json'],
				$frame
			);
			unset( $args['json'] );
		} else {
			$json = null;
		}

		if ( isset( $args['callback'] ) ) {
			$callback = $parser->recursiveTagParse(
				$args['callback'],
				$frame
			);

			// Make sure callback is valid
			if ( ! $this->checkValidInput( $callback ) ) {
				return [
					'Invalid callback as it does not match pattern [a-zA-Z0-9_]+',
					'noparse' => true
				];
			}

			unset( $args['callback'] );
		} else {
			$callback = null;
		}

		if ( isset( $args['template'] ) ) {
			$template = $parser->recursiveTagParse(
				$args['template'],
				$frame
			);

			// Make sure template is valid
			if ( ! $this->checkValidInput( $template ) ) {
				return [
					'Invalid template as it does not match pattern [a-zA-Z0-9_ ]+',
					'noparse' => true
				];
			}

			unset( $args['template'] );
		} else {
			$template = null;
		}

		if ( isset( $args['allowtags'] ) ) {
			$allowTags = true;
			unset( $args['allowtags'] );
		} else {
			$allowTags = false;
		}

		if ( isset( $args['allowclear'] ) ) {
			$allowClear = true;
			unset( $args['allowclear'] );
		} else {
			$allowClear = false;
		}

		if ( isset( $args['allowsort'] ) ) {
			$allowSort = true;
			unset( $args['allowsort'] );
		} else {
			$allowSort = false;
		}

		Core::setSeparator( $this->getSeparator( $args ) );


		if ( isset( $args['selected'] ) ) {
			$args['selected'] = $parser->recursiveTagParse(
				$args['selected'],
				$frame
			);
			$selectedValues = explode(
				Core::$separator,
				$args['selected']
			);
			$selectedValues = array_map(
				'trim',
				$selectedValues
			);
			unset( $args['selected'] );
		} else {
			$selectedValues = [];
		}

		if ( isset( $args['options'] ) ) {
			$args['options'] = $parser->recursiveTagParse(
				$args['options'],
				$frame
			);
			$options = explode(
				Core::$separator,
				$args['options']
			);
			$options = array_map(
				'trim',
				$options
			);
			unset( $args['options'] );
		} else {
			$options = [];
		}

		$additionalArguments = [];

		foreach ( $args as $name => $value ) {
			if ( Validate::validParameters( $name ) ) {
				if ( Validate::check_disable_readonly_required_selected( $name, $value ) ) {
					continue;
				}
				$additionalArguments[$name] = $parser->recursiveTagParse( $value, $frame );
			}
		}

		$output = $this->themeStore->getFormTheme()->getTokenRenderer()->render_token(
			$parsedInput,
			$id,
			$inputLengthTrigger,
			$placeholder,
			$smwQuery,
			$json,
			$callback,
			$template,
			$multiple,
			$allowTags,
			$allowClear,
			$allowSort,
			$selectedValues,
			$options,
			$additionalArguments
		);

		self::addInlineJavaScriptAndCSS();

		return [
			$output,
			"markerType" => 'nowiki'
		];
	}

	/**
	 * @param string $input
	 *
	 * @return bool
	 */
	private function checkValidInput( string $input ) : bool {
		if ( ! preg_match(
			'/[\w\-\:\.]+$/',
			$input
		) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @brief Function to render the Page Edit options.
	 *
	 * This function will call its subfunction render_edit()
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderEdit( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( ! isset( $args['target'] ) || $args['target'] === '' ) {
			return [
				'No valid target for edit',
				'noparse' => true
			];
		}

		$target = $parser->recursiveTagParse(
			$args['target'],
			$frame
		);

		if ( ! isset( $args['template'] ) && ! isset( $args['mwtemplate'] ) ) {
			return [
				'No valid template for edit',
				'noparse' => true
			];
		}

		$template = isset( $args['mwtemplate'] ) ? $args['mwtemplate'] : $args['template'];
		$template = str_replace(
			' ',
			'_',
			$parser->recursiveTagParse(
				$template,
				$frame
			)
		);

		if ( ! isset( $args['formfield'] ) || $args['formfield'] === '' ) {
			return [
				'No valid formfield for edit',
				'noparse' => true
			];
		}

		$formfield = $parser->recursiveTagParse(
			$args['formfield'],
			$frame
		);

		$usefield = isset( $args['usefield'] ) ? $parser->recursiveTagParse(
			$args['usefield'],
			$frame
		) : '';
		$slot     = isset( $args['mwslot'] ) ? $parser->recursiveTagParse(
			$args['mwslot'],
			$frame
		) : '';
		$value    = isset( $args['value'] ) ? $parser->recursiveTagParse(
			$args['value'],
			$frame
		) : '';

		$format  = isset( $args['format'] ) ? $parser->recursiveTagParse(
			$args['format'],
			$frame
		) : 'wiki';

		$output = $this->themeStore->getFormTheme()->getEditRenderer()->render_edit(
			$target,
			$template,
			$formfield,
			$usefield,
			$slot,
			$value,
			$format
		);

		return [
			$output,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief Function to render the Page Create options.
	 *
	 * This function will call its subfunction render_create()
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderCreate( $input, array $args, Parser $parser, PPFrame $frame ) {
		$template = isset( $args['mwtemplate'] ) ? $parser->recursiveTagParse(
			$args['mwtemplate'],
			$frame
		) : null;
		$createId = isset( $args['id'] ) ? $parser->recursiveTagParse(
			$args['id'],
			$frame
		) : null;
		$write    = isset( $args['mwwrite'] ) ? $parser->recursiveTagParse(
			$args['mwwrite'],
			$frame
		) : null;
		$slot     = isset( $args['mwslot'] ) ? $parser->recursiveTagParse(
			$args['mwslot'],
			$frame
		) : null;
		$option   = isset( $args['mwoption'] ) ? $parser->recursiveTagParse(
			$args['mwoption'],
			$frame
		) : null;
		$fields   = isset( $args['mwfields'] ) ? $parser->recursiveTagParse(
			$args['mwfields'],
			$frame
		) : null;
		$follow   = isset( $args['mwfollow'] ) ? $parser->recursiveTagParse(
			$args['mwfollow'],
			$frame
		) : null;
		$format   = isset( $args['mwformat'] ) ? $parser->recursiveTagParse(
			$args['mwformat'],
			$frame
		) : 'wiki';

		$leadingZero = isset( $args['mwleadingzero'] );

		$noOverWrite = isset( $args['nooverwrite'] );

		$skipSEO = isset( $args['noseo'] );

		if ( $fields !== null && $template === null ) {
			return [
				'No valid template for creating a page.',
				'noparse' => true
			];
		}

		if ( $fields !== null && $write === null ) {
			return [
				'No valid title for creating a page.',
				'noparse' => true
			];
		}

		$output = $this->themeStore->getFormTheme()->getCreateRenderer()->render_create(
			$follow,
			$template,
			$createId,
			$write,
			$slot,
			$option,
			$fields,
			$leadingZero,
			$noOverWrite,
			$skipSEO,
			$format
		);

		return [
			$output,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief Function to render the Page Create options.
	 *
	 * This function will call its subfunction render_create()
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderCreateUser( $input, array $args, Parser $parser, PPFrame $frame ) {
		$canWedCreateUser = Config::getConfigVariable( 'can_create_user' );
		if ( $canWedCreateUser !== true ) {
			return [
				wfMessage( 'flexform-createuser-disabled' )->text(),
				'noparse' => true
			];
		}
		$username = isset( $args['username'] ) ? $parser->recursiveTagParse(
			$args['username'],
			$frame
		) : null;
		$emailAddress = isset( $args['email'] ) ? $parser->recursiveTagParse(
			$args['email'],
			$frame
		) : null;
		$realName = isset( $args['realname'] ) ? $parser->recursiveTagParse(
			$args['realname'],
			$frame
		) : null;
		//formFields

		if ( $username === null || $emailAddress === null ) {
			return [
				'No username or email address',
				'noparse' => true
			];
		}

		if ( !MediaWikiServices::getInstance()->getUserNameUtils()->isValid( $username ) ) {
			return [
				'Not a valid username according to MediaWiki',
				'noparse' => true
			];
		}

		$output = $this->themeStore->getFormTheme()->getCreateUserRenderer()->render_createUser(
			$username,
			$emailAddress,
			$realName
		);

		return [
			$output,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief Function to render the email options.
	 *
	 * This function will call its subfunction render_mail()
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser or
	 * @throws FlexFormException
	 */
	public function renderEmail( $input, array $args, Parser $parser, PPFrame $frame ): array {
		$mailArguments = Email::getEmailParameters( $args, $parser, $frame );
		$output        = $this->themeStore->getFormTheme()->getEmailRenderer()->render_mail(
			$mailArguments,
			''
		);

		return [
			$output,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief Function to render a WSInstance.
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser or
	 * @throws FlexFormException
	 */
	public function renderInstance( $input, array $args, Parser $parser, PPFrame $frame ): array {
		global $IP, $wgScript;

		// Add move, delete and add button with classes
		//$parser->getOutput()->addModuleStyles( 'ext.FlexForm.Instance.styles' );
		Core::includeTagsCSS( Core::getRealUrl() . '/Modules/instances/instance-style.css' );

		if ( ! Core::isLoaded( 'wsinstance-initiated' ) ) {
			Core::addAsLoaded( 'wsinstance-initiated' );
		}

		$content = trim( $parser->recursiveTagParseFully(
			$input,
			$frame
		) );

		/*
		if( isset( $args['default-content'] ) ) {
			//var_dump( "parsing content", $args['default-content'] );
			$args['default-content'] = $parser->recursiveTagParse(
				$args['default-content'],
				$frame
			);
		}
		*/

		// TODO: Move some of the logic from "render_instance" to here
		$ret = $this->themeStore->getFormTheme()->getInstanceRenderer()->render_instance(
			$parser,
			$frame,
			$content,
			$args
		);

		Core::removeAsLoaded( 'wsinstance-initiated' );

		if ( ! Core::isLoaded( 'multipleinstance' ) && file_exists(
				$IP . '/extensions/FlexForm/Modules/instances/wsInstance.js'
			) ) {
			Core::includeTagsScript( Core::getRealUrl() . '/Modules/instances/wsInstance.js' );
			/*
			$scriptPath = $realUrl . '/extensions/FlexForm/Modules/instances/wsInstance.js';
			$scriptTag  = \Xml::tags(
				'script',
				[
					'type'    => 'text/javascript',
					'charset' => 'UTF-8',
					'src'     => $scriptPath
				],
				''
			);
			*/

			//$ret = $scriptTag . $ret;

			Core::addAsLoaded( 'multipleinstance' );
		}

		return [
			$ret,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * Helper function to add the currently configured inline JavaScript and CSS to the OutputPage.
	 *
	 * @param bool $parentConfig
	 */
	private function addInlineJavaScriptAndCSS( $parentConfig = false ) {
		return;
		$scripts   = array_unique( Core::getJavaScriptToBeIncluded() );
		$csss      = array_unique( Core::getCSSToBeIncluded() );
		$jsconfigs = Core::getJavaScriptConfigToBeAdded();
		$out       = RequestContext::getMain()->getOutput();

		if ( ! empty( $scripts ) ) {
			foreach ( $scripts as $js ) {
				$out->addInlineScript( $js );
			}

			Core::cleanJavaScriptList();
		}

		if ( ! empty( $csss ) ) {
			foreach ( $csss as $css ) {
				$out->addInlineStyle( $css );
			}

			Core::cleanCSSList();
		}

		if ( ! empty( $jsconfigs ) ) {
			if ( $parentConfig ) {
				$out->addJsConfigVars( array( $jsconfigs ) );
			} else {
				$out->addJsConfigVars( array( 'wsformConfigVars' => $jsconfigs ) );
			}

			Core::cleanJavaScriptConfigVars();
		}
	}

	/**
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param string|null $input
	 *
	 * @return string
	 */
	private function recursiveParseMe( Parser $parser, PPFrame $frame, ?string $input ): string {
		if ( $input !== null ) {
			return $parser->recursiveTagParse( $input,
				$frame );
		} else {
			return "";
		}
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public function getSeparator( array $args ): string {
		if ( isset( $args['separator'] ) ) {
			return trim( $args['separator'] );
		} else {
			return ',';
		}
	}

	/**
	 * @param string $separator
	 *
	 * @return string
	 */
	public function createSeparatorField( string $separator ): string {
		Core::includeJavaScriptConfig(
			'ff_separator', $separator
		);
		return Core::createHiddenField(
			'ff_separator',
			$separator
		);
	}

	/**
	 * @param array $args
	 *
	 * @return array|string
	 */
	private function renderFileUpload( array $args ) {
		// FIXME: Can you (attempt to) rewrite this @Charlot?

		$filesCore = new FilesCore();

		$result             = [];
		$ret                = '<input type="file" ';
		$br                 = "\n";
		$attributes         = [];
		$hiddenFiles        = [];
		$uploadDetails      = [];
		//$attributes['name'] = FilesCore::FILENAME . '[]';
		$name				= false;
		$id                 = false;
		$target             = false;
		$drop               = false;
		$verbose_id         = false;
		$error_id           = false;
		$comment            = false;
		$presentor          = false; // Holds name of external presentor, e.g. Slim
		$pagecontent        = "";
		$use_label          = false;
		$force              = false;
		$parseContent       = false;
		$actionFields       = false;
		$action				= '';
		$template			= false;
		$multiple			= 'files';
		$canvasSourceId     = false;
		$canvasRenderId     = uniqid();
		$canvasDiv			= '';
		$mobileScreenshot   = '';
		foreach ( $args as $k => $v ) {
			if ( validate::validParameters( $k ) || validate::validFileParameters( $k ) ) {
				// going through specific extra's.
				switch ( $k ) {
					case "presentor":
						$presentor = $v;
						break;
					case "pagecontent" :
						$pagecontent = $v;
						break;
					case "parsecontent" :
						$parseContent = true;
						break;
					case "dropzone" :
						$drop = true;
						break;
					case "comment" :
						$comment = $v;
						break;
					case "target":
						$target = $v;
						break;
					case "use_label":
						$use_label = true;
						break;
					case "force":
						$force = $v;
						break;
					case "action":
						$action = $v;
						break;
					case "id":
						$id               = $v;
						$attributes['id'] = $v;
						break;
					case "name":
						$name = $v;
						if ( strpos( $v, '[]' ) === false ) {
							$v .= '[]';
						}
						$attributes[$k] = $v;
						break;
					case "verbose_id":
						$verbose_id = $v;
						break;
					case "error_id":
						$error_id = $v;
						break;
					case "canvas_source_id":
						$canvasSourceId = $v;
						break;
					case "canvas_render_id":
						$canvasRenderId = $v;
						break;
					case "template":
						$template = $v;
						break;
					case "multiple":
						$multiple = 'files';
					default:
						$attributes[$k] = $v;
				}
			}
		}
		global $IP;
		if ( !$id ) {
			$ret = 'You cannot upload files without adding an unique id.';

			return $ret;
		}
		if ( !$name ) {
			$ret = 'Uploading files without a name will not work.';
			return $ret;
		}
		if ( !$target ) {
			$ret = 'You cannot upload files without a target.';

			return $ret;
		} else {
			//$hiddenFiles[] = '<input type="hidden" name="wsform_file_target" value="' . $target . '">';
			//$hiddenFiles[] = Core::createHiddenField( "wsform_file_target", $target );
			$uploadDetails['wsform_file_target'] = $target;
		}
		if ( $pagecontent ) {
			//$hiddenFiles[] = '<input type="hidden" name="wsform_page_content" value="' . $pagecontent . '">';
			//$hiddenFiles[] = Core::createHiddenField( "wsform_page_content", $pagecontent );
			$uploadDetails["wsform_page_content"] = $pagecontent;
		}
		if ( $comment ) {
			//$hiddenFiles[] = '<input type="hidden" name="wsform-upload-comment" value="' . $comment . '">';
			$uploadDetails["wsform-upload-comment"] = $comment;
		}
		if ( $parseContent ) {
			//$hiddenFiles[] = '<input type="hidden" name="wsform_parse_content" value="true">';
			$uploadDetails["wsform_parse_content"] = true;
		}
		if ( $template ) {
			//$hiddenFiles[] = '<input type="hidden" name="wsform_file_template" value="' . $template . '">';
			$uploadDetails["wsform_file_template"] = $template;
		}
		if ( $force ) {
			//$hiddenFiles[] = '<input type="hidden" name="wsform_image_force" value="' . $force . '">';
			$uploadDetails["wsform_image_force"] = $force;
		}
		// When using convert, set accepted files to be the same
		if ( $action ) {
			/*
			if ( isset( $attributes['accept'] ) ) {
				$attributes['accept'] .= ', .' . $convertFrom;
			} else {
				$attributes['accept'] = '.' . $convertFrom;
			}
			*/
			//$hiddenFiles[] = '<input type="hidden" name="wsform_convert_from" value="' . $convertFrom . '">';
			$uploadDetails["wsform_action"] = $action;
		}

		// Normal file upload. No presentor
		if ( !$presentor ) {
			$uploadDetails['type'] = 'file';
			// If we do not have a verbose id, then create our own preview from the form ID
			if ( $verbose_id === false ) {
				$verbose_id       = 'verbose_' . $id;
				$verboseDiv['id'] = $verbose_id;
				// If we also have a dropzone, then turn the verbose element into a dropzone
				if ( $drop && !$use_label ) {
					$verboseDiv['class'][] = 'wsform-dropzone';
				}
				$verboseDiv['class'][] = 'wsform-verbose';
				// $ret .= '<div id="' . $verbose_id . '" class="wsform-verbose"></div>';
			} else {
				// If we have our own verbose element, then set the create verbose element to false
				$verboseDiv['id']    = false;
				$verboseDiv['class'] = false;
			}

			// If we do not have an error id, then create our own error element from the form id.
			if ( !$error_id ) {
				$error_id          = 'error_' . $id;
				$errorDiv['id']    = $error_id;
				$errorDiv['class'] = [ "wsform-error" ];
				//$ret      .= '<div id="' . $error_id . '" class="wsform-error"></div>';
			} else {
				// If we do have a error element, then set create new error element to false.
				$errorDiv['id']    = false;
				$errorDiv['class'] = false;
			}
			$random         = round( microtime( true ) * 1000 );
			$onChangeScript = 'function WSFile' . $random . '(){' . "\n" . '$("#' . $id . '").on("change", function(){' . "\n" . 'wsfiles( "';
			$onChangeScript .= $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label;
			$onChangeScript .= '");' . "\n" . '});' . "\n";
			if ( $drop && !$use_label ) {
				$onChangeScript .= "\n" . '$("#' . $verbose_id . '").on("dragleave", function(e) {
				event.preventDefault();
    			$(".br_dropzone").removeClass("dragover");
    			})';
				$onChangeScript .= "\n" . '$("#' . $verbose_id . '").on("dragover drop", function(e) { 
				e.preventDefault();  
				$("#' . $verbose_id . '").addClass("dragover")
			}).on("drop", function(e) {
				
				$("#' . $id . '").prop("files", e.originalEvent.dataTransfer.' . $multiple . ')
				$("#' . $id . '").trigger("change"); 
		});';
			}
			// If we are using a dropzone AND we have an input file field label replacement
			if ( $drop && $use_label ) {
				$onChangeScript .= "\n";
				$onChangeScript .= 'var label = $("label[for=\'' . $id . '\']");';
				$onChangeScript .= "\n" . 'label.on("dragover drop", function(e) { 
				e.preventDefault();  
				$("#' . $id . '").addClass("dragover")
			}).on("drop", function(e) {
				
				$("#' . $id . '").prop("files", e.originalEvent.dataTransfer.' . $multiple . ')
				$("#' . $id . '").trigger("change"); 
		});';
			}
			$onChangeScript .= '};';
			$jsChange       = $onChangeScript . "\n";
			//$ret .= "<script>\n" . $onChangeScript . "\n";
			$jsChange .= "\n" . "wachtff(WSFile" . $random . ");\n";
			if ( !Core::isLoaded( 'ffNoFileSelected' ) ) {
				$addjsChange = "\n" . 'var ffNoFileSelected = "';
				$addjsChange .= wfMessage( "flexform-fileupload-no-files-selected" )->plain() . '";' . "\n";
			} else {
				$addjsChange = '';
			}
			$jsChange = $addjsChange . $jsChange;
			Core::includeInlineScript( $jsChange );
			//$ret     .= '<script>$( document ).ready(function() { $("#' . $random . '").on("change", function(){ wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '", "' . $verbose_custom . '", "' . $error_custom . '");});});</script>';
			$css     = file_get_contents( "$IP/extensions/FlexForm/Modules/WSForm_upload.css" );
			$replace = [
				'{{verboseid}}',
				'{{errorid}}',
				'{{dropfiles}}',
				'<style>',
				'</style>'
			];
			$with    = [
				$verbose_id,
				$error_id,
				wfMessage( "flexform-fileupload-dropfiles" )->plain(),
				'',
				''
			]; //wsfiles( "file-upload2", "hiddendiv2", "error_file-upload2", "", "yes", "none");
			$css     = str_replace(
				$replace,
				$with,
				$css
			);
			Core::includeInlineCSS( $css );
			//$ret     .= $css;
			if ( !Core::isLoaded( 'WSFORM_upload.js' ) ) {
				Core::addAsLoaded( 'WSFORM_upload.js' );
				Core::includeTagsScript( Core::getRealUrl() . '/Modules/WSForm_upload.js' );
				//$js = file_get_contents( "$IP/extensions/FlexForm/Modules/WSForm_upload.js" );
				$js = '';
				//Core::includeInlineScript( $js );
			} else {
				$js = '';
			}
			// As of MW 1.35+ we get errors here. It's replacing spaces with &#160; So now we put the js in the header
			//echo "\n<script>" . $js . "</script>";

			$js           = "";
			$wsFileScript = "\nfunction wsfilesFunc" . $random . "(){\n";
			$wsFileScript .= "\n" . 'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");' . "\n";
			$wsFileScript .= "}\n";
			//$ret .= '<script>'. "\n".'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");</script>';

			Core::includeInlineScript( "\n" . $wsFileScript . "\n" . 'wachtff(wsfilesFunc' . $random . ');' );
		} elseif ( $presentor == "slim" ) {
			/*
			if ( $slim_image !== false ) {
				$slim_image = '<img src="' . $slim_image . '">';
			} else {
				$slim_image = "";
			}
			$ret = $slim . $ret . $slim_image . "</div>$br";

			// TODO: Move this logic to the caller
			$parser->getOutput()->addModuleStyles( 'ext.wsForm.slim.styles' );
			$parser->getOutput()->addModules( 'ext.wsForm.slim.scripts' );
			*/
		} elseif ( $presentor === "canvas" ) {
			if ( !$canvasSourceId || !$canvasRenderId ) {
				return "Missing canvas_source_id and/or canvas_render_id";
			}
			$uploadDetails['type'] = 'canvas';
			$verboseDiv = '';
			$errorDiv = '';
			if ( ! Core::isLoaded( 'WSFORM_upload.js' ) ) {
				Core::addAsLoaded( 'WSFORM_upload.js' );
				Core::includeTagsScript( Core::getRealUrl() . '/Modules/WSForm_upload.js' );
			}
			if ( ! Core::isLoaded( 'htmltocanvas' ) ) {
				Core::addAsLoaded( 'htmltocanvas' );
				Core::includeTagsScript( Core::getRealUrl() . '/Modules/htmlToCanvas/html2canvas.min.js' );
			}
			$canvasDiv = '<div style="display:none;" data-canvas-source="';
			$canvasDiv .= $canvasSourceId . '" id="canvas_' . $canvasRenderId . '" ';
			$canvasDiv .= 'data-canvas-name="' . $name . '"></div>';
		} elseif ( $presentor === 'mobilescreenshot' ) {
			$verboseDiv = '';
			$errorDiv = '';
			$uploadDetails['type'] = 'mobile-screenshot';
			$mobileScreenshot = MobileScreenShot::renderHtml( $args );

		}
		$result['verbose_div'] = $verboseDiv;
		$result['error_div']   = $errorDiv;
		$result['attributes']  = $attributes;
		//$result['function_fields'] = $hiddenFiles;
		$actionFields        = [];
		$actionFields[$name] = $uploadDetails;
		Core::includeFileAction( $actionFields );
		//$result['action_fields'] = Core::createHiddenField( "ff_upload_actions", json_encode( $actionFields ) );
		$result['canvas']           = $canvasDiv;
		$result['mobileScreenshot'] = $mobileScreenshot;

		return $result;
	}
}