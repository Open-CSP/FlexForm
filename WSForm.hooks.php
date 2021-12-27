<?php
# @Author: Sen-Sai
# @Date:   15-05-2018
# @Last modified by:   Charlot
# @Last modified time: 04-07-2018 -- 10:03:36
# @License: Mine
# @Copyright: 2018

//error_reporting( -1 );
//ini_set( 'display_errors', 1 );
use MediaWiki\MediaWikiServices;


/**
 * Class WSFormHooks
 *
 * Hooks for WSForm extension
 *
 * @author Sen-Sai
 */
class WSFormHooks {



	/**
	 * List that returns an array of all WSForm hooks
	 *
	 * @return array with all WSForm hooks
	 */
	public static function availableHooks() {
		$data = array(
			'wsform',
			'wsfield',
			'wsfieldset',
			'wslegend',
			'wslabel',
			'wsselect',
			'wstoken',
			'wstoken2',
			'wsedit',
			'wscreate',
			'wsemail',
			'extension',
			'wsinstance'
		);

		return $data;
	}


	/**
	 * Implements AdminLinks hook from Extension:Admin_Links.
	 *
	 * @param ALTree &$adminLinksTree
	 * @return bool
	 */
	public static function addToAdminLinks( ALTree &$adminLinksTree ) {
        global $wgScript;
		$wsSection = $adminLinksTree->getSection( 'WikiBase Solutions' );
		if ( is_null( $wsSection ) ) {
			$section = new ALSection( 'WikiBase Solutions' );
			$adminLinksTree->addSection( $section, wfMessage( 'adminlinks_general' )->text() );
			$wsSection = $adminLinksTree->getSection( 'WikiBase Solutions' );
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}

		$extensionsRow = $wsSection->getRow( 'extensions' );

		if ( is_null( $extensionsRow) ) {
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}
		$realUrl = str_replace( '/index.php', '', $wgScript );
		$extensionsRow->addItem( ALItem::newFromExternalLink( $realUrl.'/index.php/Special:WSForm/Docs', 'WSForm documentation' ) );
		return true;
	}

	/**
	 * MediaWiki hook when WSForm extension is initiated
	 *
	 * @param Parser $parser Sets a list of all WSForm hooks
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		global $wgAbsoluteWikiPath, $IP;
		if( php_sapi_name() !== 'cli' ) {
			$serverName = strtolower( $_SERVER['SERVER_NAME'] );
		}

		include_once( 'classes/loader.php' );
		\wsform\WSClassLoader::register();

		// Load config settings
		if ( file_exists( __DIR__ . '/config/config.php' ) ) {
			include( __DIR__ . '/config/config.php' );
			if( isset( $config['sec'] ) && $config['sec'] === true ) {
				\wsform\wsform::$secure = true;
			}
			if( isset( $config['use-api-user-only'] ) && $config['use-api-user-only'] !== "yes" ) {
				\wsform\wsform::$runAsUser = true;
			}
			if( isset( $config['sec-key'] ) && !empty( $config['sec-key'] ) ) {
				\wsform\wsform::$checksumKey = $config['sec-key'];
			}
			\wsform\wsform::$wsConfig = $config;
		}

		$parser->setHook( 'wsform', 'WSFormHooks::WSForm' );
		$parser->setHook( 'wsfield', 'WSFormHooks::WSField' );
		$parser->setHook( 'wsfieldset', 'WSFormHooks::WSFieldset' );
		$parser->setHook( 'wslegend', 'WSFormHooks::WSLegend' );
		$parser->setHook( 'wslabel', 'WSFormHooks::WSLabel' );
		$parser->setHook( 'wsselect', 'WSFormHooks::WSSelect' );
		$parser->setHook( 'wstoken', 'WSFormHooks::WSToken' );
        $parser->setHook( 'wstoken2', 'WSFormHooks::WSToken2' );
        $parser->setHook( 'wstoken3', 'WSFormHooks::WSToken3' );
		$parser->setHook( 'wsedit', 'WSFormHooks::WSEdit' );
		$parser->setHook( 'wscreate', 'WSFormHooks::WSCreate' );
		$parser->setHook( 'wsemail', 'WSFormHooks::WSEmail' );
		$parser->setHook( 'wsinstance', 'WSFormHooks::WSInstance' );


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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function WSField( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderField( $input, $args, $parser, $frame );
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
	public static function WSEdit( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderEdit( $input, $args, $parser, $frame );
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
	public static function WSCreate( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderCreate( $input, $args, $parser, $frame );
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
	public static function WSEmail( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderEmail( $input, $args, $parser, $frame );
	}

	public static function WSInstance( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderInstance( $input, $args, $parser, $frame );
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
     *
     * @return array|string send to the MediaWiki Parser or send to the MediaWiki Parser with the message not a valid function
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
	public static function WSForm( $input, array $args, Parser $parser, PPFrame $frame ) {
	    $renderer = MediaWikiServices::getInstance()->get('WSForm.Renderer');

	    // Back up the previous theme
        $previousTheme = $renderer->getFormThemeName();

	    try {
	        if ( isset( $args['theme'] ) ) {
	            // Set the new form theme
                $renderer->setFormThemeName( $args['theme'] );
            }

            return $renderer->getCurrentTheme()->renderForm( $input, $args, $parser, $frame );
        } finally {
	        // Restore the previous theme
	        $renderer->setFormThemeName($previousTheme);
        }
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
	public static function WSFieldset( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderFieldset( $input, $args, $parser, $frame );
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
	public static function WSSelect( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderSelect( $input, $args, $parser, $frame );
	}

    /**
     * @brief This is the initial call from the MediaWiki parser for the WSToken
     *
     * @param $input string Received from parser from begin till end
     * @param array $args List of argmuments for the Fieldset
     * @param Parser $parser MediaWiki parser
     * @param PPFrame $frame MediaWiki pframe
     *
     * @return array with full rendered html for the parser to add
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
	public static function WSToken( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderToken( $input, $args, $parser, $frame );
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
	public static function WSLegend( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderLegend( $input, $args, $parser, $frame );
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
	public static function WSLabel( $input, array $args, Parser $parser, PPFrame $frame ) {
        return MediaWikiServices::getInstance()
            ->get('WSForm.Renderer')
            ->getFormTheme()
            ->renderLabel( $input, $args, $parser, $frame );
	}


	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value. If no = is provided,
	 * true is assumed like this: [name] => true
	 *
	 * @param array $options
	 *
	 * @return array $results
	 */
	public static function extractOptions( array $options ) {
		$results = array();
		foreach ( $options as $option ) {
			$pair = explode( '=', $option, 2 );
			if ( count( $pair ) === 2 ) {
				$name             = trim( $pair[0] );
				$value            = trim( $pair[1] );
				$results[ $name ] = $value;
			}

			if ( count( $pair ) === 1 ) {
				$name             = trim( $pair[0] );
				$results[ $name ] = true;
			}
		}
		return $results;
	}

	private static function addInlineJavaScriptAndCSS( $parentConfig = false ) {
		$scripts = array_unique( \wsform\wsform::getJavaScriptToBeIncluded() );
		$csss = array_unique( \wsform\wsform::getCSSToBeIncluded() );
		$jsconfigs = \wsform\wsform::getJavaScriptConfigToBeAdded();
		$out = \RequestContext::getMain()->getOutput();
		if( !empty( $scripts ) ) {
			foreach ( $scripts as $js ) {
				$out->addInlineScript( $js );
			}
			wsform\wsform::cleanJavaScriptList();
		}
		if( !empty( $csss ) ) {
			foreach ( $csss as $css ) {
				$out->addInlineStyle( $css );
			}
			wsform\wsform::cleanCSSList();
		}
		if( !empty( $jsconfigs ) ) {
			if( $parentConfig ) {
				$out->addJsConfigVars( array( $jsconfigs ) );
			} else {
				$out->addJsConfigVars( array( 'wsformConfigVars' => $jsconfigs ) );
			}
			wsform\wsform::cleanJavaScriptConfigVars();
		}
	}

}
