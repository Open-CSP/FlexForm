<?php
# @Author: Sen-Sai
# @Date:   15-05-2018
# @Last modified by:   Charlot
# @Last modified time: 04-07-2018 -- 10:03:36
# @License: Mine
# @Copyright: 2018

use MediaWiki\MediaWikiServices;
use FlexForm\Core\Core;
use FlexForm\Render\TagHooks;
use FlexForm\Render\Validate;
use FlexForm\FlexFormException;
use Wikimedia\Services\NoSuchServiceException;

/**
 * @class FlexFormHooks
 * @brief Hooks for FlexForm extension
 *
 * MediaWiki hooks for FlexForm
 *
 * @author Sen-Sai
 */
class FlexFormHooks {

	/**
	 * List that returns an array of all FlexForm hooks
	 *
	 * @return array with all FlexForm hooks
	 */
	public static function availableHooks() {
		$data = array(
			'form',
			'input',
			'fieldset',
			'legen',
			'label',
			'select',
			'_token',
			'_edit',
			'_create',
			'_email',
			'extension',
			'_instance'
		);

		return $data;
	}


	/**
	 * Implements AdminLinks hook from Extension:Admin_Links.
	 *
	 * @param ALTree &$adminLinksTree
	 *
	 * @return bool
	 */
	public static function addToAdminLinks( ALTree &$adminLinksTree ) {
		global $wgScript;
		$wsSection = $adminLinksTree->getSection( 'WikiBase Solutions' );
		if ( is_null( $wsSection ) ) {
			$section = new ALSection( 'WikiBase Solutions' );
			$adminLinksTree->addSection(
				$section,
				wfMessage( 'adminlinks_general' )->text()
			);
			$wsSection     = $adminLinksTree->getSection( 'WikiBase Solutions' );
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}

		$extensionsRow = $wsSection->getRow( 'extensions' );

		if ( is_null( $extensionsRow ) ) {
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}

		$realUrl = str_replace(
			'/index.php',
			'',
			$wgScript
		);
		$extensionsRow->addItem(
			ALItem::newFromExternalLink(
				$realUrl . '/index.php/Special:FlexForm',
				'FlexForm'
			)
		);

		return true;
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return true
	 */
	public static function onAfterFinalPageOutput( OutputPage $out ) {
		$scripts    = array_unique( Core::getJavaScriptToBeIncluded() );
		$scriptTags = array_unique( Core::getJavaScriptTagsToBeIncluded() );
		$csss       = array_unique( Core::getCSSToBeIncluded() );
		$cssTags    = array_unique( Core::getCSSTagsToBeIncluded() );
		$jsconfigs  = Core::getJavaScriptConfigToBeAdded();
		$jsTags     = '';
		$cssTagsOut = '';
		$jsOut      = '';
		$csOut      = '';
		if ( ! empty( $scriptTags ) ) {
			foreach ( $scriptTags as $scriptTag ) {
				$jsTags .= \Xml::tags(
						'script',
						[
							'type'    => 'text/javascript',
							'charset' => 'UTF-8',
							'src'     => $scriptTag
						],
						''
					) . "\n";
			}

			Core::cleanJavaScriptTagsList();
		}

		if ( ! empty( $cssTags ) ) {
			foreach ( $cssTags as $cssTag ) {
				$cssTagsOut .= \Xml::tags(
						'link',
						[
							'rel'  => 'stylesheet',
							'href' => $cssTag
						],
						''
					) . "\n";
			}

			Core::cleanJavaScriptTagsList();
		}

		if ( ! empty( $jsconfigs ) ) {
			foreach ( $jsconfigs as $name => $jsConfig ) {
				$jsOut .= 'var ' . $name . ' = ' . json_encode( $jsConfig ) . "\n";
			}

			Core::cleanJavaScriptConfigVars();
		}

		if ( ! empty( $scripts ) ) {
			foreach ( $scripts as $js ) {
				$jsOut .= $js . "\n";
			}

			Core::cleanJavaScriptList();
		}

		if ( ! empty( $csss ) ) {
			foreach ( $csss as $css ) {
				$csOut .= $css . "\n";
			}

			Core::cleanCSSList();
		}

		$out = ob_get_clean();
		$out .= $cssTagsOut . $jsTags;
		if ( ! empty( $csOut ) ) {
			$out .= "<style>\n" . $csOut . "\n</style>\n";
		}
		if ( ! empty( $jsOut ) ) {
			$out .= "<script>\n" . $jsOut . "\n</script>\n";
		}
		ob_start();
		echo $out;

		return true;
	}

	/**
	 * MediaWiki hook when FlexForm extension is initiated
	 * \deprecated { wsform will be removed soon }
	 * \deprecated { wsfield will be removed soon }
	 * \deprecated { wsfieldset will be removed soon }
	 * \deprecated { wslegend will be removed soon }
	 * \deprecated { wslabel will be removed soon }
	 * \deprecated { wsselect will be removed soon }
	 * \deprecated { wstoken will be removed soon }
	 * \deprecated { wsedit will be removed soon }
	 * \deprecated { wscreate will be removed soon }
	 * \deprecated { wsemail will be removed soon }
	 * \deprecated { wsinstance will be removed soon }
	 *
	 * @param Parser $parser Sets a list of all FlexForm hooks
	 *
	 * @throws MWException
	 * @throws FlexFormException
	 * @throws NoSuchServiceException
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		if ( !\FlexForm\Core\Config::getConfigStatus() ) {
			\FlexForm\Core\Config::setConfigFromMW();
		}
		global $wgFlexFormConfig;
		$wgFlexFormConfig['loaders']                  = [];
		$wgFlexFormConfig['loaders']['css']           = [];
		$wgFlexFormConfig['loaders']['javascript']    = [];
		$wgFlexFormConfig['loaders']['jsconfigvars']  = [];
		$wgFlexFormConfig['loaders']['javascripttag'] = [];
		$wgFlexFormConfig['loaders']['csstag']        = [];
		$wgFlexFormConfig['loaders']['files']         = [];

		$formTags = [
			'wsform',
			'_form',
			'form'
		];

		$tagHooks = new TagHooks( MediaWikiServices::getInstance()->getService( 'FlexForm.ThemeStore' ) );

		$parser->setHook(
			'wsform',
			[
				$tagHooks,
				'renderForm'
			]
		);

		$parser->setHook(
			'wsfield',
			[
				$tagHooks,
				'renderField'
			]
		);
		/*
		 * \deprecated { wsfieldset will be removed soon }
		 */
		$parser->setHook(
			'wsfieldset',
			[
				$tagHooks,
				'renderFieldset'
			]
		);
		/*
		 * \deprecated { wslegend will be removed soon }
		 */
		$parser->setHook(
			'wslegend',
			[
				$tagHooks,
				'renderLegend'
			]
		);
		/*
		 * \deprecated { wslabel will be removed soon }
		 */
		$parser->setHook(
			'wslabel',
			[
				$tagHooks,
				'renderLabel'
			]
		);
		/*
		 * \deprecated { wsselect will be removed soon }
		 */
		$parser->setHook(
			'wsselect',
			[
				$tagHooks,
				'renderSelect'
			]
		);
		/*
		 * \deprecated { wstoken will be removed soon }
		 */
		$parser->setHook(
			'wstoken',
			[
				$tagHooks,
				'renderToken'
			]
		);
		/*
		 * \deprecated { wsedit will be removed soon }
		 */
		$parser->setHook(
			'wsedit',
			[
				$tagHooks,
				'renderEdit'
			]
		);
		/*
		 * \deprecated { wscreate will be removed soon }
		 */
		$parser->setHook(
			'wscreate',
			[
				$tagHooks,
				'renderCreate'
			]
		);
		/*
		 * \deprecated { wsemail will be removed soon }
		 */
		$parser->setHook(
			'wsemail',
			[
				$tagHooks,
				'renderEmail'
			]
		);
		/*
		 * \deprecated { wsinstance will be removed soon }
		 */
		$parser->setHook(
			'wsinstance',
			[
				$tagHooks,
				'renderInstance'
			]
		);
		$parser->setHook(
			'_form',
			[
				$tagHooks,
				'renderForm'
			]
		);
		$parser->setHook(
			'_input',
			[
				$tagHooks,
				'renderField'
			]
		);
		$parser->setHook(
			'_fieldset',
			[
				$tagHooks,
				'renderFieldset'
			]
		);
		$parser->setHook(
			'_legend',
			[
				$tagHooks,
				'renderLegend'
			]
		);
		$parser->setHook(
			'_label',
			[
				$tagHooks,
				'renderLabel'
			]
		);
		$parser->setHook(
			'_select',
			[
				$tagHooks,
				'renderSelect'
			]
		);
		$parser->setHook(
			'_token',
			[
				$tagHooks,
				'renderToken'
			]
		);
		$parser->setHook(
			'_edit',
			[
				$tagHooks,
				'renderEdit'
			]
		);
		$parser->setHook(
			'_create',
			[
				$tagHooks,
				'renderCreate'
			]
		);
		$parser->setHook(
			'_createuser',
			[
				$tagHooks,
				'renderCreateUser'
			]
		);
		$parser->setHook(
			'_email',
			[
				$tagHooks,
				'renderEmail'
			]
		);
		$parser->setHook(
			'_instance',
			[
				$tagHooks,
				'renderInstance'
			]
		);
		$parser->setHook(
			'form',
			[
				$tagHooks,
				'renderForm'
			]
		);
		$parser->setHook(
			'input',
			[
				$tagHooks,
				'renderField'
			]
		);
		$parser->setHook(
			'fieldset',
			[
				$tagHooks,
				'renderFieldset'
			]
		);
		$parser->setHook(
			'legend',
			[
				$tagHooks,
				'renderLegend'
			]
		);
		$parser->setHook(
			'label',
			[
				$tagHooks,
				'renderLabel'
			]
		);
		$parser->setHook(
			'select',
			[
				$tagHooks,
				'renderSelect'
			]
		);
		$parser->setHook(
			'textarea',
			[
				$tagHooks,
				'renderTextarea'
			]
		);
		$parser->setHook(
			'button',
			[
				$tagHooks,
				'renderButton'
			]
		);
		$parser->setHook(
			'option',
			[
				$tagHooks,
				'renderOption'
			]
		);
	}
}
