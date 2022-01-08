<?php
# @Author: Sen-Sai
# @Date:   25-05-2018 -- 11:47:13
# @Last modified by:   Charlot
# @Last modified time: 18-06-2018 -- 10:48:39
# @License: Mine
# @Copyright: 2018
# @version : 0.6.9.3.7

//namespace wsform\special;
namespace WSForm\Specials;

use SpecialPage;
use wbApi;
use wsform;
use wsform\validate\validate as validate;
use WSFormHooks;

use function setcookie;
use function wfMessage;

use const PHP_EOL;

/**
 * Overview for the WSForm extension
 *
 * @file
 * @ingroup Extensions
 */
class SpecialWSForm extends \SpecialPage {


	public $allowEditDocs = true;
	public $showFormBuilder = true;
	public $app = array();
	private $config = false;
	private $configFile = '';
	private $config_default = false;

	public function __construct() {
		parent::__construct( 'WSForm' );
	}


	function getGroupName() {
		return 'Wikibase';
	}


	public function makeMessage( $msg, $type = "danger" ) {
		setcookie(
			"wsform[type]",
			$type,
			0,
			'/'
		);
		setcookie(
			"wsform[txt]",
			$msg,
			0,
			'/'
		);
	}

	public function MakeTitle() {
		$date      = new DateTime( '2000-01-01' );
		$dt        = date( 'd/m/Y H:i:s' );
		$pageTitle = time();

		return $pageTitle;
	}


	/**
	 * @brief Get and check $_POST variable
	 *
	 * @param $var string $_POST variable to check
	 *
	 * @return mixed Either false or the value of the $_POST variable
	 */
	public function getPostString( $var ) {
		if ( isset( $_POST[$var] ) && ! empty( $_POST[$var] ) ) {
			$template = $_POST[$var];
		} else {
			$template = false;
		}

		return $template;
	}

	public function is_valid_name( $file ) {
		return preg_match(
				   '/^([-\.\w]+)$/',
				   $file
			   ) > 0;
	}

	public function is_valid_type( $type ) {
		$formhooks = WSFormHooks::availableHooks();
		if ( in_array(
			$type,
			$formhooks
		) ) {
			return true;
		} else {
			return false;
		}
	}

	private function setConfigVar( $name, $config ) {
		if ( isset( $config[$name] ) ) {
			$this->app[$name] = $config[$name];
		} else {
			$this->app[$name] = "";
		}
	}


	/**
	 * @brief WSForm Docs menu.
	 * Builds and renders the WSForm Docs menu
	 *
	 * @param $path string Path to docs
	 * @param $examplePath string Path to examples
	 * @param $purl string uri to Docs
	 * @param $eurl string uri to Examples
	 * @param $wgServer object Wiki server information
	 * @param $out object Wiki Out variable
	 * @param $wsformpurl string WSForm url
	 * @param $ver string WSForm version
	 */
	public function renderMenu(
		$path,
		$examplePath,
		$purl,
		$eurl,
		$wgServer,
		$out,
		$wsformpurl,
		$ver,
		$newVersionAvailable
	) {
		$fileList = glob( $path . '*.json' );
		//echo "<pre>";
		//print_r($fileList);
		//$fileList = 'https://api.bitbucket.org/2.0/repositories/wikibasesolutions/mw-wsform/src/master/docs/?pagelen=100';
		//print_r(json_decode( file_get_contents( $fileList ), true ) );
		//die();
		$exampleList = glob( $examplePath . '*.json' );
		global $IP, $wgParser;
		$menuPath            = "$IP/extensions/WSForm/Modules/coreNav/";
		$data                = array();
		$exampleData         = array();
		$createExample       = $wgServer . '/index.php/Special:WSForm/Docs/Create Example';
		$createDocumentation = $wgServer . '/index.php/Special:WSForm/Docs/Create';
		$formBuilderUrl      = $wgServer . '/index.php/Special:WSForm/Formbuilder';
		if ( $this->showFormBuilder ) {
			$formBuilderHTML = '<li><a href="' . $formBuilderUrl . '"> Formbuilder</a></li>';
		} else {
			$formBuilderHTML = '';
		}
		$changeLogUrl = $wgServer . '/index.php/Special:WSForm/Docs/ChangeLog';
		$changeLogUrl = '<li><a href="' . $changeLogUrl . '"> ' . wfMessage( "wsform-docs-changelog" )->text(
			) . '</a></li>';
		$searchBtn    = '<li><a href="#openSearch">Search</a></li>';

		// Get normal documentation
		foreach ( $fileList as $file ) {
			$type       = explode(
				'_',
				basename( $file ),
				2
			);
			$t          = $type[0];
			$n          = $type[1];
			$data[$t][] = $n;
		}
		// Get examples
		foreach ( $exampleList as $example ) {
			$type              = explode(
				'_',
				basename( $example ),
				2
			);
			$t                 = $type[0];
			$n                 = $type[1];
			$exampleData[$t][] = $n;
		}
		//Load menu JavaScript
		//$ret = '<link rel="stylesheet" href="'.$wsformpurl.'Modules/coreNav/coreNavigation-1.1.3.css">';
		//$ret .= '<link rel="stylesheet" href="'.$wsformpurl.'Modules/coreNav/assets/css/custom.css">';
		$ret = '<style>';
		$ret .= file_get_contents( $menuPath . 'coreNavigation-1.1.3.css' );
		$ret .= file_get_contents( $menuPath . 'assets/css/custom.css' );
		$ret .= '</style>';
		//$ret .= '<script src="' . $wsformpurl . 'Modules/coreNav/coreNavigation-1.1.3.js"></script>';
		$ret .= '<script src="https://unpkg.com/feather-icons@4.7.3/dist/feather.min.js"></script>';
		$nav = file_get_contents( $menuPath . 'menu.php' );
		//%%wsformpurl%%
		$navItem = file_get_contents( $menuPath . 'menu-item-main.php' );
		$items   = '';
		$eitems  = '';

		foreach ( $data as $k => $v ) {
			$mItem = str_replace(
				'%%menuName%%',
				$k,
				$navItem
			);
			$sItem = '';
			foreach ( $v as $doc ) {
				$docname    = substr(
					$doc,
					0,
					-5
				);
				$docContent = json_decode(
					file_get_contents( $path . $k . '_' . $docname . '.json' ),
					true
				);
				//$sItem .= '<li><a href="'.$purl.'/'.$k.'_'.$docname.'">'.$docname.'</a>';
				//$sItem .= '<span>'.$docContent['doc']['synopsis'].'</span></p></li>';
				$sItem .= '<li class="nfo"><a href="' . $purl . '/' . $k . '_' . $docname . '"><i data-feather="list"></i>' . $docname . '<br>';
				$sItem .= '<span><strong>' . wfMessage( "wsform-docs-information" )->text(
					) . ': </strong>' . $docContent['doc']['synopsis'] . '</span></a></li>';
			}
			$mItem = str_replace(
				'%%items%%',
				$sItem,
				$mItem
			);
			$items .= $mItem;
		}
		$back = $wgServer . '/index.php/Special:WSForm/Docs';
		if ( $this->allowEditDocs ) {
			$new = '<li><a href="' . $createDocumentation . '">' . wfMessage( "wsform-docs-create-new-doc" )->text(
				) . '</a></li>';
			$new .= '<li><a href="' . $createExample . '">' . wfMessage( "wsform-docs-create-new-example" )->text(
				) . '</a></li>';
		} else {
			$new = '<li>' . wfMessage( "wsform-docs-editing-disabled" )->text() . '</li>';
		}

		if ( $newVersionAvailable === false ) {
			$changeLogUrl = '';
		}

		$index      = $wgServer . '/index.php/Special:WSForm/Docs/Index';
		$wsformpurl = $wgServer . "/extensions/WSForm/";
		$search     = array(
			'%items%',
			'%url%',
			'%back%',
			'%version%',
			'%new%',
			'%index%',
			'%%wsformpurl%%',
			'%fb%',
			'%changelog%',
			'%%search%%'
		);
		$replace    = array(
			$items,
			$wsformpurl . "WSForm-logo.png",
			$back,
			$ver,
			$new,
			$index,
			$wsformpurl,
			$formBuilderHTML,
			$changeLogUrl,
			$searchBtn
		);
		$nav        = str_replace(
			$search,
			$replace,
			$nav
		);

		//$out->addHTML($ret.$nav);
		//return;
		// Add example list
		foreach ( $exampleData as $k => $v ) {
			$mItem = str_replace(
				'%%menuName%%',
				$k,
				$navItem
			);
			$sItem = '';
			foreach ( $v as $example ) {
				$exampleName    = substr(
					$example,
					0,
					-5
				);
				$exampleContent = json_decode(
					file_get_contents( $examplePath . $k . '_' . $exampleName . '.json' ),
					true
				);
				$sItem          .= '<li class="nfo"><a href="' . $eurl . '/' . $k . '_' . $exampleName . '"><i data-feather="list"></i>' . $exampleName . '<br>';
				$sItem          .= '<span><strong>' . wfMessage( "wsform-docs-information" )->text(
					) . ': </strong>' . $exampleContent['example']['synopsis'] . '</span></a></li>';
			}
			$mItem  = str_replace(
				'%%items%%',
				$sItem,
				$mItem
			);
			$eitems .= $mItem;
		}
		$nav = str_replace(
			'%eitems%',
			$eitems,
			$nav
		);
		$out->addHTML( $ret . $nav );

		return;
	}

	private function get_string_between( $string, $start, $end = "" ) {
		$r = explode(
			$start,
			$string
		);
		if ( isset( $r[1] ) ) {
			if ( ! empty( $end ) ) {
				$r = explode(
					$end,
					$r[1]
				);

				return $r[0];
			} else {
				return $r[1];
			}
		}

		return "";
	}

	private function saveConfig() {
		$ret = '<?php' . PHP_EOL;
		$ret .= '$config = ';
		$ret .= var_export(
					$this->config,
					true
				) . ';' . PHP_EOL;
		file_put_contents(
			$this->configFile,
			$ret
		);
	}


	private function getChangeLog( $bitbucketChangelog, $currentVersion ) {
		$readme = file_get_contents( $bitbucketChangelog );
		if ( $readme === false ) {
			return "not found";
		}
		if ( $currentVersion === '' ) {
			$changeLog = $this->get_string_between(
				$readme,
				'### Changelog',
				$currentVersion
			);
		} else {
			$changeLog = $this->get_string_between(
				$readme,
				'### Changelog',
				'* ' . $currentVersion
			);
		}
		$changeLog = ltrim(
			$changeLog,
			"\n"
		);

		//$changeLog = str_replace( "\n", "<br>", $changeLog);
		return $changeLog;
	}

	/**
	 * @brief Show the page to the user.
	 * Also used for handling Documentation
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:HelloWorld/subpage]].
	 */
	public function execute( $sub ) {
		global $IP, $wgUser, $wgExtensionCredits, $wgScript, $wgServer;

		if ( $this->getPostString( 'mwaction' ) ) {
			// We need to handle api calls here
			//error_reporting( -1 );
			//ini_set( 'display_errors', 1 );
			include_once $IP . "/extensions/WSForm/WSForm.api.php";

			return true;
		}

		$config_default = false;
		$config         = false;

		$this->configFile = $IP . '/extensions/WSForm/config/config.php';

		if ( file_exists( $IP . '/extensions/WSForm/config/config_default.php' ) ) {
			include( $IP . '/extensions/WSForm/config/config_default.php' );
			$this->config_default = $config;
		}
		if ( file_exists( $this->configFile ) ) {
			include( $this->configFile );
			$this->config = $config;
		}
		$editDocs = $this->getConfigSetting( 'allow-edit-docs' );
		if ( $editDocs ) {
			$this->allowEditDocs = true;
		} else {
			$this->allowEditDocs = false;
		}

		$allowFB = $this->getConfigSetting( 'use-formbuilder' );
		if ( $allowFB ) {
			$this->showFormBuilder = true;
		} else {
			$this->showFormBuilder = false;
		}

		$realUrl            = str_replace(
			'/index.php',
			'',
			$wgScript
		);
		$ver                = "";
		$bitbucketSource    = 'https://gitlab.wikibase.nl/community/mw-wsform/-/raw/wsform-rewrite/extension.json';
		$bitbucketChangelog = 'https://gitlab.wikibase.nl/community/mw-wsform/-/raw/wsform-rewrite/README.md';
		$extJson            = file_get_contents( $bitbucketSource );
		$sourceVersion      = false;
		if ( $extJson !== false ) {
			$extJson = json_decode(
				$extJson,
				true
			);
			if ( isset( $extJson['version'] ) ) {
				$sourceVersion = $extJson['version'];
			} else {
				$sourceVersion = false;
			}
		}

		$myVersionJson = "$IP/extensions/WSForm/extension.json";
		if ( file_exists( $myVersionJson ) ) {
			$extFile = file_get_contents( $myVersionJson );
			if ( $extFile !== false ) {
				$myJson              = json_decode(
					$extFile,
					true
				);
				$currentVersion      = $myJson['version'];
				$ver                 = "v<strong>" . $currentVersion . "</strong>";
				$newVersionAvailable = true;
				if ( $sourceVersion != $currentVersion ) {
					$newVersionAvailable = true;
					$ver                 .= ' <br><span style="font-size:11px; color:red;">NEW : v' . $sourceVersion . '</span>';
					//$ver .= $this->getChangeLog( $bitbucketChangelog, $ext['version'] );
				} else {
					$newVersionAvailable = false;
				}
			}
		}
		/*
		$extensionFile = json_decode( file_get_contents( $IP . '/extensions/WSForm/extension.json' ), true);
		foreach ( $wgExtensionCredits['parserhook'] as $ext ) {
			if ( $ext['name'] == 'WSForm' ) {
				$ver = "v<strong>" . $ext['version'] . "</strong>";
				if ( $sourceVersion !== false ) {
					if ( $sourceVersion != $ext['version'] ) {
						$newVersionAvailable = true;
						$currentVersion      = $ext['version'];
						$ver                 .= ' <br><span style="font-size:11px; color:red;">NEW : v' . $sourceVersion . '</span>';
						//$ver .= $this->getChangeLog( $bitbucketChangelog, $ext['version'] );
					} else {
						$newVersionAvailable = false;
						$currentVersion      = $ext['version'];
					}
				}
			}
		};
		*/
		$path        = "$IP/extensions/WSForm/docs/";
		$wsformpurl  = $realUrl . "/extensions/WSForm/";
		$examplePath = $path . 'examples/';
		$purl        = $realUrl . "/index.php/Special:WSForm/Docs";
		$setupUrl    = $realUrl . "/index.php/Special:WSForm/Setup";
		$statusUrl   = $realUrl . "/index.php/Special:WSForm/Status";
		$eurl        = $realUrl . "/index.php/Special:WSForm/Docs/examples";
		$out         = $this->getOutput();
		$out->addHTML(
			'<h1 style="text-align:center;"><a href="' . $wgServer . '/index.php/Special:WSForm/Docs"><img style="width:50px; margin:5px 15px;" src="'.$wgServer . "/extensions/WSForm/WSForm-logo.png".'" /></a>WSForm ' . wfMessage(
				"wsform-docs-documentation"
			)->text() . '</h1>'
		);
		$this->renderMenu(
			$path,
			$examplePath,
			$purl,
			$eurl,
			$realUrl,
			$out,
			$wsformpurl,
			$ver,
			$newVersionAvailable
		);
		if ( ! $wgUser->isLoggedIn() ) {
			$out->addHTML( '<p>' . wfMessage( "wsform-docs-log-in" )->text() . '</p>' );

			return;
		}
		$back = '<div class="ws-documentation-back"><a href="' . $realUrl . '/index.php/Special:WSForm/Docs">' . wfMessage(
				"wsform-docs-back-documentation"
			)->text() . '</a></div>';
		$args = $this->getArgumentsFromSpecialPage( $sub );
		if ( $args !== false ) {
			if ( strtolower( $args[0] ) == 'formbuilder' ) {
				$path = "$IP/extensions/WSForm/formbuilder/";
				$out->addHTML( $back );
				include( $path . 'php/formbuilder.php' );

				return true;
			}

			$loadJS = '<script src="' . $wsformpurl . 'docs/waitForJQ.js"></script>';
			$loadJS .= '<link rel="stylesheet" href="' . $wsformpurl . 'Modules/wysiwyg/ui/trumbowyg.min.css">';
			$loadJS .= '<link rel="stylesheet" href="' . $wsformpurl . 'Modules/wysiwyg/plugins/emoji/ui/trumbowyg.emoji.min.css">';
			$loadJS .= '<link rel="stylesheet" href="' . $wsformpurl . 'Modules/wysiwyg/plugins/table/ui/trumbowyg.table.min.css">';
			$loadJS .= "
<script>
    function getTrumbo() {
	    $.getScript( '" . $wsformpurl . "Modules/wysiwyg/trumbowyg.js' ).done( function () {
		    $.when(
			    $.getScript( '" . $wsformpurl . "Modules/wysiwyg/plugins/colors/trumbowyg.colors.min.js' ),
			    $.getScript( '" . $wsformpurl . "Modules/wysiwyg/plugins/emoji/trumbowyg.emoji.min.js' ),
			    $.getScript( '" . $wsformpurl . "Modules/wysiwyg/plugins/fontfamily/trumbowyg.fontfamily.min.js' ),
			    $.getScript( '" . $wsformpurl . "Modules/wysiwyg/plugins/fontsize/trumbowyg.fontsize.min.js' ),
			    $.getScript( '" . $wsformpurl . "Modules/wysiwyg/plugins/table/trumbowyg.table.min.js' ),
			    //$.getScript( '" . $wsformpurl . "Modules/wysiwyg/plugins/cleanpaste/trumbowyg.cleanpaste.min.js' ),
			    $.getScript( '" . $wsformpurl . "Modules/wysiwyg/plugins/preformatted/trumbowyg.preformatted.min.js' ),
			    //$.getScript( '" . $wsformpurl . "Modules/wysiwyg/plugins/wiki/trumbowyg.wiki.min.js' ),
		    ).done( function () {
			    $( 'textarea.wsdocs-wysiwyg' ).trumbowyg( {
				    svgPath: '" . $wsformpurl . "Modules/wysiwyg/ui/icons.svg', autogrow: true, removeformatPasted: false,
				    btns: [['viewHTML'], ['undo', 'redo'], ['formatting'], ['strong', 'em', 'del'], ['superscript', 'subscript'], ['foreColor', 'backColor'], ['link'], ['insertImage'], ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'], ['unorderedList', 'orderedList'], ['horizontalRule'],['table'], ['removeformat'],['preformatted'], ['emoji'], ['fullscreen']]
			    } )
		    } )
	    } );
    }";
			$loadJS .= '

    document.addEventListener("DOMContentLoaded", function() {
	    wachtff(getTrumbo);
	    wachtff(initializeMenu, "menus");
    });
</script>';
			$out->addHTML( $loadJS );

			$back = '<div class="ws-documentation-back"><a href="' . $realUrl . '/index.php/Special:WSForm/Docs">' . wfMessage(
					"wsform-docs-back-documentation"
				)->text() . '</a></div>';

			if ( strtolower( $args[0] ) == 'setup' ) {
				$allowed = $this->getConfigSetting( 'allow-special-page-setup' );
				if ( $allowed !== false ) {
					$css = $this->getDocsCSS(
						$path,
						$wsformpurl
					);
					$out->addHTML( '<style>' . $css . '</style>' );
					//$out->addHTML('<HR><pre>');
					$out->addHTML(
						$this->setupWSForm(
							$path,
							$setupUrl
						)
					);

					//$out->addHTML('</pre><HR>');
					return;
				} else {
					$out->addHTML( '<p>Setup through Special page disabled</p>' );
				}
			}

			if ( strtolower( $args[0] ) == 'status' ) {
				if ( in_array(
					'sysop',
					$wgUser->getGroups()
				) ) {
					include __DIR__ . "/../WSForm.api.class.php";
					$api       = new wbApi();
					$debugPost = $this->getPostString( 'debugToggle' );
					if ( false !== $debugPost ) {
						if ( $debugPost === 'on' ) {
							$this->config['debug'] = true;
						} else {
							$this->config['debug'] = false;
						}
						$this->saveConfig();
					}
					$debugSetting = $this->getConfigSetting( 'debug' );
					if ( $debugSetting === "" ) {
						$debugSetting = false;
					}
					$css = $this->getDocsCSS(
						$path,
						$wsformpurl
					);
					$out->addHTML( '<style>' . $css . '</style>' );
					//$out->addHTML('<HR><pre>');
					$out->addHTML( $this->showStatus( $debugSetting ) );
					$out->addHTML(
						$this->showStatus(
							$debugSetting,
							$api->app
						)
					);

					//$out->addHTML('</pre><HR>');
					return;
				}
			}

			if ( strtolower( $args[0] ) == 'docs' ) {
				$css = $this->getDocsCSS(
					$path,
					$wsformpurl
				);
				$out->addHTML( '<style>' . $css . '</style>' );
				// Did we have Posts ????
				$create = $this->getPostString( 'create' );
				if ( $create && $create === 'create' ) {
					unset( $_POST['create'] );

					$name = $this->getPostString( 'name' );
					$type = $this->getPostString( 'type' );

					if ( ( $name === false || ! $this->is_valid_name(
								$name
							) ) || ( $type === false || ! $this->is_valid_type( $type ) ) ) {
						$out->addHTML(
							'<p>Sorry we need a valid name for this function (no spaces and other special characters).</p>'
						);

						return;
					}
					if ( file_exists( $path . $type . "_" . $name . '.json' ) ) {
						$out->addHTML(
							'<p>The Create Documentation page is for creating documentation.</p><p>This function (' . $name . ') is already in our documentation.</p>' . $back
						);

						return;
					}
					$data['doc']               = $_POST;
					$data['doc']['created']    = date( "d-m-Y H:i:s" );
					$data['doc']['created_by'] = $wgUser->getName();

					if ( file_put_contents(
						$path . $type . "_" . $name . '.json',
						json_encode(
							$data,
							JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
						)
					) ) {
						$this->makeMessage(
							'Documentation for <strong>' . $name . '</strong> stored.',
							'success'
						);
						$out->redirect( $realUrl . '/index.php/Special:WSForm/Docs' );
					} else {
						$out->addHTML(
							'<p>Could NOT store documentation for <strong>' . $name . '</strong>.</p>' . $back
						);
					}

					return;
				}
				if ( $create && $create == 'createExample' ) {
					unset( $_POST['create'] );
					unset( $_POST['pf'] );

					$name = $this->getPostString( 'name' );
					$type = $this->getPostString( 'type' );

					if ( ( $name === false || ! $this->is_valid_name(
								$name
							) ) || ( $type === false || ! $this->is_valid_type( $type ) ) ) {
						$out->addHTML(
							'<p>Sorry we need a valid name for this example (no spaces and other special characters).</p>'
						);

						return;
					}
					if ( file_exists( $path . $type . "_" . $name . '.json' ) ) {
						$out->addHTML(
							'<p>The Create Example page is for creating documentation.</p><p>This example (' . $name . ') is already in our list of examples.</p>' . $back
						);

						return;
					}
					$data['example']               = $_POST;
					$data['example']['created']    = date( "d-m-Y H:i:s" );
					$data['example']['created_by'] = $wgUser->getName();
					if ( file_put_contents(
						$examplePath . $type . "_" . $name . '.json',
						json_encode(
							$data,
							JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
						)
					) ) {
						$this->makeMessage(
							'Example for <strong>' . $name . '</strong> stored.',
							'success'
						);
						$out->redirect( $realUrl . '/index.php/Special:WSForm/Docs' );
					} else {
						$out->addHTML( '<p>Could NOT store example for <strong>' . $name . '</strong>.</p>' . $back );
					}

					return;
				}
				if ( $create && $create === 'edit' ) {
					unset( $_POST['create'] );

					$name = $this->getPostString( 'name' );
					$type = $this->getPostString( 'type' );
					$pf   = $this->getPostString( 'pf' );

					if ( ( $name === false || ! $this->is_valid_name(
								$name
							) ) || ( $type === false || ! $this->is_valid_type( $type ) ) ) {
						$out->addHTML(
							'<p>Sorry we need a valid name for this function (no spaces and other special characters)[' . $name . '].</p>'
						);

						return;
					}
					//echo "<BR><BR><BR><BR><BR><BR>".$path.$pf.'.json';
					if ( ! file_exists( $path . $pf . '.json' ) ) {
						$out->addHTML( '<p>We cannot find this documentation (' . $name . ').</p>' . $back );

						return;
					}
					$delete = $this->getPostString( 'delete' );
					if ( $delete == 'delete' ) {
						unlink( $path . $type . "_" . $name . '.json' );
						$this->makeMessage(
							'Documentation for <strong>' . $name . '</strong> deleted.',
							'notice'
						);
						$out->redirect( $realUrl . '/index.php/Special:WSForm/Docs' );

						return;
					}
					unlink( $path . $pf . '.json' );
					unset( $_POST['pf'] );

					$data['doc']                  = $_POST;
					$data['doc']['last modified'] = date( "d-m-Y H:i:s" );
					$data['doc']['modified by']   = $wgUser->getName();
					if ( file_put_contents(
						$path . $type . "_" . $name . '.json',
						json_encode(
							$data,
							JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
						)
					) ) {
						$this->makeMessage(
							'Documentation for <strong>' . $name . '</strong> stored.',
							'success'
						);
						$out->redirect( $realUrl . '/index.php/Special:WSForm/Docs' );
						$out->addHTML( '<p>Documentation for <strong>' . $name . '</strong> stored.</p>' . $back );

						return;
					} else {
						$out->addHTML(
							'<p>Could NOT store documentation for <strong>' . $name . '</strong>.</p>' . $back
						);

						return;
					}
				}
				if ( $create && $create == 'editExample' ) {
					unset( $_POST['create'] );

					$name = $this->getPostString( 'name' );
					$type = $this->getPostString( 'type' );
					$pf   = $this->getPostString( 'pf' );

					if ( ( $name === false || ! $this->is_valid_name(
								$name
							) ) || ( $type === false || ! $this->is_valid_type( $type ) ) ) {
						$out->addHTML(
							'<p>Sorry we need a valid name for this example (no spaces and other special characters)[' . $name . '].</p>'
						);

						return;
					}
					//echo "<BR><BR><BR><BR><BR><BR>".$path.$pf.'.json';
					if ( ! file_exists( $examplePath . $pf . '.json' ) ) {
						$out->addHTML( '<p>We cannot find this example (' . $name . ').</p>' . $back );

						return;
					}
					$delete = $this->getPostString( 'delete' );
					if ( $delete == 'delete' ) {
						unlink( $examplePath . $type . "_" . $name . '.json' );
						$this->makeMessage(
							'Example for <strong>' . $name . '</strong> deleted.',
							'notice'
						);
						$out->redirect( $realUrl . '/index.php/Special:WSForm/Docs' );

						return;
					}
					unlink( $examplePath . $pf . '.json' );
					unset( $_POST['pf'] );

					$data['example']                  = $_POST;
					$data['example']['last modified'] = date( "d-m-Y H:i:s" );
					$data['example']['modified by']   = $wgUser->getName();
					if ( file_put_contents(
						$examplePath . $type . "_" . $name . '.json',
						json_encode(
							$data,
							JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
						)
					) ) {
						$this->makeMessage(
							'Example for <strong>' . $name . '</strong> stored.',
							'success'
						);
						$out->redirect( $realUrl . '/index.php/Special:WSForm/Docs' );
						$out->addHTML( '<p>Example for <strong>' . $name . '</strong> stored.</p>' . $back );

						return;
					} else {
						$out->addHTML( '<p>Could NOT store example for <strong>' . $name . '</strong>.</p>' . $back );

						return;
					}
				}
				// Are we on the subpage CREATE ?
				if ( isset( $args[1] ) && strtolower( $args[1] ) == 'create' ) {
					$back = '<div class="ws-documentation-back"><a href="' . $realUrl . '/index.php/Special:WSForm/Docs">' . wfMessage(
							"wsform-docs-documentation"
						)->text() . '</a></div>';
					$out->addHTML( $back );
					$form      = file_get_contents( $path . 'create.html' );
					$form      = str_replace(
						'%%url%%',
						$purl,
						$form
					);
					$formhooks = WSFormHooks::availableHooks();
					$options   = "";
					foreach ( $formhooks as $option ) {
						$options .= '<option value="' . $option . '">' . $option . '</option>';
					}
					$form = str_replace(
						'%%options%%',
						$options,
						$form
					);
					$out->addHTML( $form );

					return;
				}
				// Are we on the subpage CREATEEXAMPLE ?
				if ( isset( $args[1] ) && strtolower( $args[1] ) == 'create_example' ) {
					$back = '<div class="ws-documentation-back"><a href="' . $realUrl . '/index.php/Special:WSForm/Docs">' . wfMessage(
							"wsform-docs-documentation"
						)->text() . '</a></div>';
					$out->addHTML( $back );
					$form   = file_get_contents( $path . 'example.html' );
					$form   = str_replace(
						'%%url%%',
						$purl,
						$form
					);
					$form   = str_replace(
						'%%exampleaction%%',
						'createExample',
						$form
					);
					$fields = array(
						'name',
						'type',
						'synopsis',
						'description',
						'example',
						'note',
						'links',
						'delete',
						'created',
						'created_by'
					);
					foreach ( $fields as $field ) {
						$form = str_replace(
							'%%' . $field . '%%',
							'',
							$form
						);
					}
					$formhooks = WSFormHooks::availableHooks();
					$options   = "";
					foreach ( $formhooks as $option ) {
						$options .= '<option value="' . $option . '">' . $option . '</option>';
					}
					$form = str_replace(
						'%%options%%',
						$options,
						$form
					);
					$out->addHTML( $form );

					return;
				}
				// Are we on the subpage EDIT ?
				if ( isset( $args[1] ) && strtolower( $args[1] ) == 'edit' ) {
					$example = false;
					$name    = $this->getPostString( 'name' );
					$type    = $this->getPostString( 'type' );
					if ( $type == 'example' ) {
						$example = true;
						if ( $name === false || ! file_exists( $examplePath . $name . '.json' ) ) {
							$out->addHTML( '<p>Example for <strong>' . $name . '</strong> not found.</p>' . $back );

							return;
						}
						$form = file_get_contents( $path . 'example.html' );
						$form = str_replace(
							'%%exampleaction%%',
							'editExample',
							$form
						);
						$doc  = json_decode(
							file_get_contents( $examplePath . $name . '.json' ),
							true
						);
					} else {
						if ( $name === false || ! file_exists( $path . $name . '.json' ) ) {
							$out->addHTML(
								'<p>Documentation for <strong>' . $name . '</strong> not found.</p>' . $back
							);

							return;
						}
						$form = file_get_contents( $path . 'edit.html' );
						$doc  = json_decode(
							file_get_contents( $path . $name . '.json' ),
							true
						);
					}

					$form      = str_replace(
						'%%url%%',
						$purl,
						$form
					);
					$form      = str_replace(
						'%%pf%%',
						$name,
						$form
					);
					$formhooks = WSFormHooks::availableHooks();
					$options   = "";
					$type      = explode(
						'_',
						basename( $name ),
						2
					);
					$type      = $type[0];
					$fields    = array(
						'name',
						'type',
						'synopsis',
						'description',
						'parameters',
						'example',
						'note',
						'links',
						'created',
						'created_by'
					);

					foreach ( $fields as $field ) {
						if ( $field !== 'created_by' ) {
							if ( $example ) {
								$tmp = $doc['example'][$field];
							} else {
								$tmp = $doc['doc'][$field];
							}
							$form = str_replace(
								'%%' . $field . '%%',
								$tmp,
								$form
							);
						}
					}

					$form = str_replace(
						'%%created_by%%',
						$doc['doc']['created_by'],
						$form
					);

					foreach ( $formhooks as $option ) {
						if ( $type == $option ) {
							$options .= '<option value="' . $option . '" selected>' . $option . '</option>';
						} else {
							$options .= '<option value="' . $option . '">' . $option . '</option>';
						}
					}
					$form = str_replace(
						'%%' . $field . '%%',
						$options,
						$form
					);

					$form = str_replace(
						'%%options%%',
						$options,
						$form
					);
					if ( $example ) {
						$rpl  = '<fieldset style="padding:20px;">
        <legend>Delete</legend>
        <label style="display:inline-block !important; margin-top:0px !important;" for="delete">Delete this example</label>
        <input style="display:inline-block !important; width:auto;" type="checkbox" name="delete" id="delete" value="delete">
    </fieldset>';
						$form = str_replace(
							'%%delete%%',
							$rpl,
							$form
						);
					}
					$out->addHTML( $form . $back );

					return;
				}
				// Changelog new version info
				if ( isset( $args[1] ) && strtolower( $args[1] ) == 'changelog' ) {
					if ( $sourceVersion !== false ) {
						if ( $sourceVersion !== $currentVersion ) {
							$changeLogText   = wfMessage( "wsform-docs-new-version-notice" )->text();
							$tableHead       = wfMessage( "wsform-docs-new-version-table" )->text();
							$changelogDetail = $this->getChangeLog(
								$bitbucketChangelog,
								$currentVersion
							);
						} else {
							$changeLogText   = wfMessage( "wsform-docs-no-new-version-notice" )->text();
							$tableHead       = wfMessage( "wsform-docs-no-new-version-table" )->text();
							$changelogDetail = $this->getChangeLog(
								$bitbucketChangelog,
								''
							);
						}
					}
					$changeLogTemplate = file_get_contents( $path . 'changelog.html' );
					$repl              = array(
						'%changelogdescr%',
						'%tablehead%',
						'%version%',
						'%changelog%'
					);
					$with              = array(
						$changeLogText,
						$tableHead,
						$sourceVersion,
						'<pre>' . $changelogDetail . '</pre>'
					);
					$out->addHTML(
						str_replace(
							$repl,
							$with,
							$changeLogTemplate
						)
					);

					return;
				}

				if ( ! isset( $args[1] ) ) {
					//$out->addHTML( '<div><p><BR><BR><BR></p></div>' );
					$args[1] = 'index';
				}

				// Show the index page
//if( isset($args[1]) && strtolower($args[1] ) == 'create' ) {
				if ( isset( $args[1] ) && strtolower( $args[1] ) == 'index' ) {
					$fileList    = glob( $path . '*.json' );
					$exampleList = glob( $examplePath . '*.json' );

					foreach ( $fileList as $file ) {
						$type       = explode(
							'_',
							basename( $file ),
							2
						);
						$t          = $type[0];
						$n          = $type[1];
						$data[$t][] = $n;
					}
					foreach ( $exampleList as $example ) {
						$type              = explode(
							'_',
							basename( $example ),
							2
						);
						$t                 = $type[0];
						$n                 = $type[1];
						$exampleData[$t][] = $n;
					}
					$ret = '<script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>';
					$ret .= '<div class="grid ws-documentation-show-index" data-masonry=\'{ "itemSelector": ".grid-item", "columnWidth": 200 }\'>';
					foreach ( $data as $k => $v ) {
						$ret .= '<div class="ws-documentation-index grid-item">';
						$ret .= '<h3>' . $k . '</h3>';
						foreach ( $v as $doc ) {
							$docname    = substr(
								$doc,
								0,
								-5
							);
							$docContent = json_decode(
								file_get_contents( $path . $k . '_' . $docname . '.json' ),
								true
							);
							$ret        .= '<p><a href="' . $purl . '/' . $k . '_' . $docname . '">' . $docname . '</a><BR>';
							$ret        .= '<span>' . $docContent['doc']['synopsis'] . '</span></p>';
						}
						$ret .= '</div>';
					}
					// Add example list
					$ret .= '<div class="ws-documentation-index grid-item">';
					$ret .= '<h3>Complete examples</h3>';
					foreach ( $exampleData as $k => $v ) {
						foreach ( $v as $example ) {
							$exampleName    = substr(
								$example,
								0,
								-5
							);
							$exampleContent = json_decode(
								file_get_contents( $examplePath . $k . '_' . $exampleName . '.json' ),
								true
							);
							$ret            .= '<p><a href="' . $eurl . '/' . $k . '_' . $exampleName . '">' . $exampleName . '</a><BR>';
							$ret            .= '<span>' . $exampleContent['example']['synopsis'] . '</span></p>';
						}
					}
					if ( $this->allowEditDocs ) {
						$createExample = '<div class="ws-documentation-back"><a href="' . $realUrl . '/Special:WSForm/Docs/Create Example">Create a new example</a></div>';
						$ret           .= $createExample;
					}
					$ret .= '</div>';
					// End example list
					$ret .= '</div>';
					if ( $this->allowEditDocs ) {
						$ret .= '<div class="ws-documentation-back"><a href="' . $realUrl . '/Special:WSForm/Docs/Create">Create Documentation</a></div>';
					}
					$out->addHTML( $ret );

					return;
				}

				/*
				if ( !isset( $args[1] ) ) {
					$out->addHTML( '<div><p><BR><BR><BR></p></div>' );
				}
*/
				if ( isset( $args[1] ) && strlen( $args[1] ) > 2 ) {
					if ( strtolower( $args[1] ) == 'examples' ) {
						$searchForArgument = 3;
						if ( isset( $args[2] ) && strlen( $args[2] ) > 2 ) {
							if ( file_exists( $examplePath . $args[2] . '.json' ) ) {
								$doc = json_decode(
									file_get_contents( $examplePath . $args[2] . '.json' ),
									true
								);
								if ( $this->allowEditDocs ) {
									$editButton = '<div class="ws-documentation-edit"><form action="' . $purl . '/Edit" method="post">';
									$editButton .= '<input type="hidden" name="name" value="' . $args[2] . '">';
									$editButton .= '<input type="hidden" name="type" value="example">';
									$editButton .= '<input type="submit" value="edit"></form></div>';
								} else {
									$editButton = '';
								}
								$documentation = '<div class="ws-documentation-show">';
								foreach ( $doc['example'] as $k => $v ) {
									if ( $k == "example" ) {
										if ( method_exists(
											$out,
											'parseAsContent'
										) ) {
											$documentation .= $out->parseAsContent(
												'<h3>' . ucfirst( $k ) . '</h3>' . "\n<p><pre>" . $v . "</pre></p>\n"
											);
											$documentation .= $out->parseAsContent(
												'<h3>Rendered example</h3>' . "\n<p>" . $v . "</p>\n"
											);
										} else {
											$documentation .= $out->parse(
												'<h3>' . ucfirst( $k ) . '</h3>' . "\n<p><pre>" . $v . "</pre></p>\n"
											);
											$documentation .= $out->parse(
												'<h3>Rendered example</h3>' . "\n<p>" . $v . "</p>\n"
											);
										}
										//$documentation .= '<h3>Rendered example</h3>' . "\n<p>" . $v . "</p>\n";
									} elseif ( $k == 'last modified' || $k == 'created' || $k == 'created_by' || $k == 'created_by' || $k == 'modified by' ) {
										if ( $k == 'created_by' ) {
											$k = 'created_by';
										}
										$documentation .= '<p class="info"><strong>' . ucfirst(
												$k
											) . '</strong> : ' . "$v</p>\n";
									} else {
										$documentation .= '<h3>' . ucfirst( $k ) . '</h3>' . "\n<p>$v</p>\n";
									}
								}
							}
						} else {
							return;
						}
					} else {
						$searchForArgument = 2;
						if ( file_exists( $path . $args[1] . '.json' ) ) {
							$doc = json_decode(
								file_get_contents( $path . $args[1] . '.json' ),
								true
							);
							if ( $this->allowEditDocs ) {
								$editButton = '<div class="ws-documentation-edit"><form action="' . $purl . '/Edit" method="post">';
								$editButton .= '<input type="hidden" name="name" value="' . $args[1] . '">';
								$editButton .= '<input type="hidden" name="type" value="doc">';
								$editButton .= '<input type="submit" value="edit"></form></div>';
							} else {
								$editButton = '';
							}
							$documentation = '<div class="ws-documentation-show">';
							foreach ( $doc['doc'] as $k => $v ) {
								if ( $k == "example" ) {
									if ( method_exists(
										$out,
										'parseAsContent'
									) ) {
										$documentation .= $out->parseAsContent(
											'<h3>' . ucfirst( $k ) . '</h3>' . "\n<p><pre>" . $v . "</pre></p>\n"
										);
									} else {
										$documentation .= $out->parse(
											'<h3>' . ucfirst( $k ) . '</h3>' . "\n<p><pre>" . $v . "</pre></p>\n"
										);
									}
								} elseif ( $k == 'last modified' || $k == 'created' || $k == 'created_by' || $k == 'created_by' || $k == 'modified by' ) {
									if ( $k == 'created_by' ) {
										$k = 'created_by';
									}
									$documentation .= '<p class="info"><strong>' . ucfirst(
											$k
										) . '</strong> : ' . "$v</p>\n";
								} else {
									$documentation .= '<h3>' . ucfirst( $k ) . '</h3>' . "\n<p>$v</p>\n";
								}
							}
						} else {
							return;
						}
					}

					$documentation .= '</div>';
					$out->addHTML( $editButton );
					if ( isset( $args[$searchForArgument] ) && strlen( $args[$searchForArgument] ) > 2 ) {
						$highlight = $args[$searchForArgument];
					} else {
						$highlight = false;
					}

					if ( $highlight ) {
						$out->addHTML(
							$this->highlight(
								$documentation,
								$highlight
							)
						);
					} else {
						$out->addHTML( $documentation );
					}
					$out->addHTML(
						'<div class="ws-documentation-back"><a href="' . $realUrl . '/index.php/Special:WSForm/Docs">Back to Documentation</a></div>'
					);

					return;
				}
			}

			return;
		}

		if ( isset( $_POST['wstemplate'] ) && $_POST['wstemplate'] !== "" ) {
			$template = $_POST['wstemplate'];
		} else {
			$template = false;
		}

		if ( isset( $_POST['wswrite'] ) && $_POST['wswrite'] !== "" ) {
			$writepage = $_POST['wswrite'];
		} else {
			$writepage = false;
		}

		if ( isset( $_POST['wsaction'] ) && $_POST['wsaction'] !== "" ) {
			$action = $_POST['wsaction'];
		} else {
			$action = false;
		}

		if ( $template ) {
			$ret = "{{" . $_POST['wstemplate'] . "<BR>";
			$out->setPageTitle( "We got posts" );
			$out->addHTML( "<h2>Example of sourcecode written</h2>" );
			foreach ( $_POST as $k => $v ) {
				if ( is_array( $v ) ) {
					$ret .= "|" . $k . "=";
					foreach ( $v as $multiple ) {
						$ret .= $multiple . ',';
					}
					$ret = rtrim(
							   $ret,
							   ','
						   ) . "<BR>";
				} else {
					if ( $k !== "wstemplate" && $k !== "wsaction" && $k !== "wswrite" && $k !== "wsreturn" ) {
						$ret .= '|' . $k . '=' . $v . '<BR>';
					}
				}
			}
			$ret .= "}}";
			if ( $action == 'add_random' && $writepage ) {
				$title = $writepage . $this->MakeTitle();
				$out->addHTML( 'The new file to be written will be : ' . $title . "<BR>" );
			}
			$out->addHTML( $ret );
		} else {
			$out->redirect( $realUrl . '/index.php/Special:WSForm/Docs' );

			return;
			//echo $IP . '/extensions/WSForm/classes/validate.php';
			error_reporting( -1 );
			ini_set(
				'display_errors',
				1
			);
			//include_once( $IP . '/extensions/WSForm/classes/loader.php' );

			$table           = "<table class=\"table table-striped\"><tr><td class=\"center\" colspan=\"6\">Valid input types</td></tr>";
			$table           .= "<tr><td>Formparameters</td><td>Parameters</td><td>Input types</td><td>Form Hooks</td><td>File input</td><td>Email</td></tr>";
			$formParameters  = validate::validFormParameters(
				"",
				true
			);
			$emailParameters = wsform\validate\validate::validEmailParameters(
				"",
				true
			);
			$parameters      = validate::validParameters(
				"",
				true
			);
			$inputTypes      = validate::validInputTypes(
				"",
				true
			);
			$fInputTypes     = validate::validFileParameters(
				"",
				true
			);
			$formhooks       = WSFormHooks::availableHooks();
			$table           .= "<tr><td><table>";
			foreach ( $formParameters as $params ) {
				$table .= "<tr><td>$params</td></tr>";
			}
			$table .= "</table></td><td><table>";
			foreach ( $parameters as $params ) {
				$table .= "<tr><td>$params</td></tr>";
			}
			$table .= "</table></td><td><table>";
			foreach ( $inputTypes as $params ) {
				$table .= "<tr><td>$params</td></tr>";
			}
			$table .= "</table></td><td><table>";
			foreach ( $formhooks as $params ) {
				$table .= "<tr><td>$params</td></tr>";
			}
			$table .= "</table></td><td><table>";
			foreach ( $fInputTypes as $params ) {
				$table .= "<tr><td>$params</td></tr>";
			}
			$table .= "</table></td><td><table>";
			foreach ( $emailParameters as $params ) {
				$table .= "<tr><td>$params</td></tr>";
			}
			$table .= "</table></td></tr></table>";
			//$out->addHTML("<p>Move along.. nothing to see here..</p>");
			$out->addHTML( $table );
		}

		return;
	}

	public function getArgumentsFromSpecialPage( $sub ) {
		if ( isset( $sub ) && $sub !== "" ) {
			$args = explode(
				'/',
				$sub
			);

			return $args;
		} else {
			return false;
		}
	}

	private function getPostSetup( $name ) {
		$value = $this->getPostString( $name );
		if ( $value === false ) {
			return "";
		}

		return $value;
	}

	private function getValueType( $v ) {
		$type = '';
		if ( is_bool( $v ) ) {
			if ( true === $v ) {
				return "[bool] true";
			}
			if ( false === $v ) {
				return "[bool] false";
			}
		}
		if ( empty( $v ) ) {
			return '[empty]';
		}
		if ( is_numeric( $v ) ) {
			return '[number] ';
		} elseif ( is_string( $v ) ) {
			return '[string] ';
		}
		if ( is_array( $v ) ) {
			return '[array]';
		}

		return "[unknown]";
	}

	private function showStatus( $debugSetting, $apiSettings = false ) {
		if ( $apiSettings !== false ) {
			$ret = '<h2>WSForm (calculated config)</h2>';
		} else {
			$ret = '<h2>WSForm config file status</h2><p>Check WSForm <strong>docs -> wsform -> config</strong> for information</p>';
		}

		$ret .= "<table class='wsform-table'><thead><tr><th>Variable</th><th>Type</th><th>Value</th></tr></thead><tbody>";
		if ( ! $apiSettings ) {
			$ret .= '<tr><td>$_SERVER[\'HTTPS\']</td><td>system</td><td>' . $_SERVER['HTTPS'] . '</td></tr>';
			$ret .= '<tr><td>$_SERVER[\'SERVER_NAME\']</td><td>system</td><td>' . $_SERVER['SERVER_NAME'] . '</td></tr>';
			$ret .= '<tr><td>$_SERVER[\'REQUEST_URI\']</td><td>system</td><td>' . $_SERVER['REQUEST_URI'] . '</td></tr>';
		}

		$debugSet = false;
		if ( $apiSettings === false ) {
			foreach ( $this->config as $k => $v ) {
				$ret .= '<tr><td>' . $k . '</td><td>';
				$ret .= $this->getValueType( $v ) . '</td><td>';
				if ( $k === 'debug' ) {
					$debugSet = true;
					if ( false === $debugSetting ) {
						$ret .= '<form method="post"><input type="hidden" name="debugToggle" value="on"><input type="submit" value="Turn debug on"></form>';
					} else {
						$ret .= '<form method="post"><input type="hidden" name="debugToggle" value="off"><input type="submit" value="Turn debug off"></form>';
					}
				} else {
					$ret .= $v;
				}
				$ret .= '</td></tr>';
			}
			if ( ! $debugSet ) {
				$ret .= '<tr><td>debug</td><td>' . $this->getValueType( $debugSetting ) . '</td><td>';
				if ( false === $debugSetting ) {
					$ret .= '<form method="post"><input type="hidden" name="debugToggle" value="on"><input type="submit" value="Turn debug on"></form>';
				} else {
					$ret .= '<form method="post"><input type="hidden" name="debugToggle" value="off"><input type="submit" value="Turn debug off"></form>';
				}
				$ret .= '</td></tr>';
			}
		} else {
			foreach ( $apiSettings as $variable => $value ) {
				$ret .= '<tr><td>' . $variable . '</td><td>';
				$ret .= $this->getValueType( $value ) . '</td><td>';
				if ( is_array( $value ) ) {
					foreach ( $value as $subkey => $subval ) {
						$ret .= $subkey . '=' . $subval . '<br>';
					}
				} else {
					$ret .= $value;
				}
				$ret .= '</td></tr>';
			}
		}
		$ret .= '</tbody></table>';

		return $ret;
	}

	private function setupWSForm( $path, $purl ) {
		global $wgGroupPermissions, $wgAllowCopyUploads, $wgCopyUploadsFromSpecialUpload;

		$mwcheck = "<table><thead><th>MediaWiki option</th><th>status</th></thead><tbody>";
		if ( isset( $wgAllowCopyUploads ) && $wgAllowCopyUploads === true ) {
			$mwcheck .= '<tr><td>$wgAllowCopyUploads</td><td class="wsf-ok">ok</td></tr>';
		} else {
			$mwcheck .= '<tr><td>$wgAllowCopyUploads</td><td class="wsf-nok">Set this to true in your LocalSettings.php</td></tr>';
		}
		if ( isset( $wgCopyUploadsFromSpecialUpload ) && $wgCopyUploadsFromSpecialUpload === true ) {
			$mwcheck .= '<tr><td>$wgCopyUploadsFromSpecialUpload</td><td class="wsf-ok">ok</td></tr>';
		} else {
			$mwcheck .= '<tr><td>$wgCopyUploadsFromSpecialUpload</td><td class="wsf-nok">Set this to true in your LocalSettings.php</td></tr>';
		}
		$mwcheck .= '</tbody></table>';
		$action  = $this->getPostString( 'setup' );

		$tmp_uri = $url = "http" . ( ! empty( $_SERVER['HTTPS'] ) ? "s" : "" ) . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		$parts   = explode(
			'/',
			$tmp_uri
		);
		$dir     = "";
		for ( $i = 0; $i < count( $parts ) - 3; $i++ ) {
			$dir .= $parts[$i] . "/";
		}
		$api_url = $dir;

		if ( $action === 'step1' ) {
			$config                    = array();
			$config['api-cookie-path'] = $this->getPostString( 'api-cookie-path' );
			if ( $config['api-cookie-path'] === false ) {
				$config['api-cookie-path'] = '/tmp/WSCOOKIE';
			} else {
				$config['api-cookie-path'] = rtrim(
					$config['api-cookie-path'],
					'/'
				);
				$config['api-cookie-path'] .= '/WSCOOKIE';
			}
			$config['api-url-overrule'] = $this->getPostString( 'api-url-overrule' );
			if ( $config['api-url-overrule'] === false ) {
				$config['api-url-overrule'] = $dir;
			} else {
				$config['api-url-overrule'] = str_replace(
					"api.php",
					'',
					$api_url
				);
			}

			$config['api-username'] = $this->getPostString( 'api-username' );
			$config['api-password'] = $this->getPostString( 'api-password' );
			$ret                    = '<?php' . PHP_EOL;
			$ret                    .= '$config = ';
			$ret                    .= var_export(
										   $config,
										   true
									   ) . ';' . PHP_EOL;
			file_put_contents(
				$this->configFile,
				$ret
			);
			$this->config = $config;
		}

		if ( $action === 'general' ) {
			$config                    = array();
			$config['api-cookie-path'] = $this->getPostString( 'api-cookie-path' );
			if ( $config['api-cookie-path'] === false ) {
				$config['api-cookie-path'] = '/tmp/WSCOOKIE';
			} else {
				$config['api-cookie-path'] = rtrim(
					$config['api-cookie-path'],
					'/'
				);
				$config['api-cookie-path'] .= '/WSCOOKIE';
			}
			$config['api-url-overrule'] = $this->getPostString( 'api-url-overrule' );
			if ( $config['api-url-overrule'] === false ) {
				$config['api-url-overrule'] = $dir;
			} else {
				$config['api-url-overrule'] = str_replace(
					"api.php",
					'',
					$api_url
				);
			}
			$config['api-username']       = $this->getPostString( 'api-username' );
			$config['api-password']       = $this->getPostString( 'api-password' );
			$config['wgAbsoluteWikiPath'] = $this->getPostString( 'wgAbsoluteWikiPath' );
			if ( $config['wgAbsoluteWikiPath'] === false ) {
				$config['wgAbsoluteWikiPath'] = '';
			}
			$config['wgScript'] = $this->getPostString( 'wgScript' );
			if ( $config['wgScript'] === false ) {
				$config['wgScript'] = '';
			}
			$config['use-api-user-only'] = $this->getPostString( 'use-api-user-only' );
			$config['rc_site_key']       = $this->getPostString( 'rc_site_key' );
			if ( $config['rc_site_key'] === false ) {
				$config['rc_site_key'] = '';
			}
			$config['rc_secret_key'] = $this->getPostString( 'rc_secret_key' );
			if ( $config['rc_secret_key'] === false ) {
				$config['rc_secret_key'] = '';
			}
			//Autosave start
			$config['autosave-interval'] = $this->getPostString( 'autosave-interval' );
			if ( $config['autosave-interval'] === false ) {
				$config['autosave-interval'] = 30000;
			}
			$config['autosave-after-change'] = $this->getPostString( 'autosave-after-change' );
			if ( $config['autosave-after-change'] === false ) {
				$config['autosave-after-change'] = 3000;
			}
			$config['autosave-btn-on'] = $this->getPostString( 'autosave-btn-on' );
			if ( $config['autosave-btn-on'] === false ) {
				$config['autosave-btn-on'] = 'Autosave is on';
			}
			$config['autosave-btn-off'] = $this->getPostString( 'autosave-btn-off' );
			if ( $config['autosave-btn-off'] === false ) {
				$config['autosave-btn-off'] = 'Autosave is off';
			}
			//Autosave end
			$config['use-formbuilder'] = $this->getPostString( 'use-formbuilder' );
			if ( $config['use-formbuilder'] === "yes" ) {
				$config['use-formbuilder'] = true;
			} else {
				$config['use-formbuilder'] = false;
			}
			$config['allow-edit-docs'] = $this->getPostString( 'allow-edit-docs' );
			if ( $config['allow-edit-docs'] === "yes" ) {
				$config['allow-edit-docs'] = true;
			} else {
				$config['allow-edit-docs'] = false;
			}
			$config['sec'] = $this->getPostString( 'sec' );
			if ( $config['sec'] === "yes" ) {
				$config['sec'] = true;
			} else {
				$config['sec'] = false;
			}
			$config['sec-key']  = $this->getPostString( 'sec-key' );
			$config['use-smtp'] = $this->getPostString( 'use-smtp' );
			if ( $config['use-smtp'] === "yes" ) {
				$config['use-smtp'] = true;
			} else {
				$config['use-smtp'] = false;
			}
			$config['smtp-authentication'] = $this->getPostString( 'smtp-authentication' );
			if ( $config['smtp-authentication'] === "yes" ) {
				$config['smtp-authentication'] = true;
			} else {
				$config['smtp-authentication'] = false;
			}
			$config['smtp-host'] = $this->getPostString( 'smtp-host' );
			if ( $config['smtp-host'] === false ) {
				$config['smtp-host'] = '';
			}
			$config['smtp-username'] = $this->getPostString( 'smtp-username' );
			if ( $config['smtp-username'] === false ) {
				$config['smtp-username'] = '';
			}
			$config['smtp-password'] = $this->getPostString( 'smtp-password' );
			if ( $config['smtp-password'] === false ) {
				$config['smtp-password'] = '';
			}
			$config['smtp-secure'] = $this->getPostString( 'smtp-secure' );
			if ( $config['smtp-secure'] === false ) {
				$config['smtp-secure'] = '';
			}
			$config['smtp-port'] = $this->getPostString( 'smtp-port' );
			if ( $config['smtp-port'] === false ) {
				$config['smtp-port'] = '';
			}
			$config['form-timeout-limit'] = $this->getPostString( 'form-timeout-limit' );
			if ( $config['form-timeout-limit'] === false ) {
				$config['form-timeout-limit'] = 7200;
			}
			$ret = '<?php' . PHP_EOL;
			$ret .= '$config = ';
			$ret .= var_export(
						$config,
						true
					) . ';' . PHP_EOL;
			file_put_contents(
				$this->configFile,
				$ret
			);
			$this->config = $config;
		}

		if ( $this->config === false ) {
			// Here we are trying to create the url for the API.
			// Although this should work on most servers, it might not.
			// If you experience any problems, just uncomment the last line and fill it the correct
			// url for WSform.api.php.
			$tmp_uri = $url = "http" . ( ! empty( $_SERVER['HTTPS'] ) ? "s" : "" ) . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			$parts   = explode(
				'/',
				$tmp_uri
			);
			$dir     = "";
			for ( $i = 0; $i < count( $parts ) - 3; $i++ ) {
				$dir .= $parts[$i] . "/";
			}
			$api_url = $dir;

			$cookiefile = "/tmp";
			if ( ! is_writable( $cookiefile ) ) {
				$notWriteable = ' <span style="font-weight:normal; color:red;"> This is the default path to a folder to write cookies. It is not writeable on your system, please change!</span>';
			} else {
				$notWriteable = '';
			}

			$form    = file_get_contents( $path . 'setup_1.html' );
			$find    = array(
				'%%api-cookie-span%%',
				'%%api-cookie-path%%',
				'%%url%%',
				'%%api-url-overrule%%',
				'%%mwcheck%%'
			);
			$replace = array(
				$notWriteable,
				$cookiefile,
				$purl,
				$api_url,
				$mwcheck

			);
			$form    = str_replace(
				$find,
				$replace,
				$form
			);

			return $form;
		} else {
			$tmp_uri = $url = "http" . ( ! empty( $_SERVER['HTTPS'] ) ? "s" : "" ) . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			$parts   = explode(
				'/',
				$tmp_uri
			);
			$dir     = "";
			for ( $i = 0; $i < count( $parts ) - 3; $i++ ) {
				$dir .= $parts[$i] . "/";
			}
			$api_url = $dir;

			$useApiUserOnly = $this->getConfigSetting( 'use-api-user-only' );
			if ( strtolower( $useApiUserOnly ) === 'yes' || $useApiUserOnly === "" ) {
				$useApiUserOnlySelectedYes = 'selected="selected"';
				$useApiUserOnlySelectedNo  = "";
			} else {
				$useApiUserOnlySelectedYes = "";
				$useApiUserOnlySelectedNo  = 'selected="selected"';
			}
			//$apiCookiePath = $this->getConfigSetting('api-cookie-path');
			$apiCookiePath = substr(
				$this->config['api-cookie-path'],
				0,
				strrpos(
					$this->config['api-cookie-path'],
					'/'
				)
			);
			if ( ! is_writable( $apiCookiePath ) ) {
				$apiCookieSpan = ' <span style="font-weight:normal; color:red;"> This is the path to a folder to write cookies. It is not writeable on your system, please change!</span>';
			} else {
				$apiCookieSpan = '';
			}

			$apiUrlOverRule = $this->getConfigSetting( 'api-url-overrule' );
			if ( $apiUrlOverRule === "" ) {
				$apiUrlOverRulePlaceHolder = $api_url;
			} else {
				$apiUrlOverRulePlaceHolder = "";
			}

			$apiUsername        = $this->getConfigSetting( 'api-username' );
			$apiPassword        = $this->getConfigSetting( 'api-password' );
			$wgAbsoluteWikiPath = $this->getConfigSetting( 'wgAbsoluteWikiPath' );
			$wgScript           = $this->getConfigSetting( 'wgScript' );
			$rc_site_key        = $this->getConfigSetting( 'rc_site_key' );
			$rc_secret_key      = $this->getConfigSetting( 'rc_secret_key' );
			$useSMTP            = $this->getConfigSetting( 'use-smtp' );
			if ( $useSMTP === "" || $useSMTP === false ) {
				$useSMTPSelectedYes = "";
				$useSMTPSelectedNo  = 'selected="selected"';
			} else {
				$useSMTPSelectedYes = 'selected="selected"';
				$useSMTPSelectedNo  = "";
			}
			$SMTPHost           = $this->getConfigSetting( 'smtp-host' );
			$SMTPAuthentication = $this->getConfigSetting( 'smtp-authentication' );
			if ( $SMTPAuthentication === "" || $SMTPAuthentication === true ) {
				$SMTPAuthenticationSelectedYes = 'selected="selected"';
				$SMTPAuthenticationSelectedNo  = "";
			} else {
				$SMTPAuthenticationSelectedYes = "";
				$SMTPAuthenticationSelectedNo  = 'selected="selected"';
			}
			$SMTPUsername = $this->getConfigSetting( 'smtp-username' );
			$SMTPPassword = $this->getConfigSetting( 'smtp-password' );
			$SMTPSecure   = $this->getConfigSetting( 'smtp-secure' );
			if ( $SMTPSecure === '' ) {
				$SMTPSecure = 'TLS';
			}
			$SMTPPort = $this->getConfigSetting( 'smtp-port' );
			if ( $SMTPPort === '' ) {
				$SMTPPort = 587;
			}
			$sec = $this->getConfigSetting( 'sec' );
			if ( $sec === true ) {
				$secSelectedYes = 'selected="selected"';
				$secSelectedNo  = "";
			} else {
				$secSelectedYes = "";
				$secSelectedNo  = 'selected="selected"';
			}
			$secKey         = $this->getConfigSetting( 'sec-key' );
			$useFormbuilder = $this->getConfigSetting( 'use-formbuilder' );
			if ( $useFormbuilder === true || $useFormbuilder === "" ) {
				$useFormbuilderSelectedYes = 'selected="selected"';
				$useFormbuilderSelectedNo  = "";
			} else {
				$useFormbuilderSelectedYes = "";
				$useFormbuilderSelectedNo  = 'selected="selected"';
			}
			$allowEditDocs = $this->getConfigSetting( 'allow-edit-docs' );
			if ( $allowEditDocs === true || $allowEditDocs === "" ) {
				$allowEditDocsSelectedYes = 'selected="selected"';
				$allowEditDocsSelectedNo  = "";
			} else {
				$allowEditDocsSelectedYes = "";
				$allowEditDocsSelectedNo  = 'selected="selected"';
			}
			$autoSaveIncremental = $this->getConfigSetting( 'autosave-interval' );
			$autoSaveAfterChange = $this->getConfigSetting( 'autosave-after-change' );
			$autoSaveButtonOn    = $this->getConfigSetting( 'autosave-btn-on' );
			$autoSaveButtonOFF   = $this->getConfigSetting( 'autosave-btn-off' );
			$formTimeOut         = $this->getConfigSetting( 'form-timeout-limit' );

			$find    = array(
				'%%mwcheck%%',
				'%%api-cookie-span%%',
				'%%api-cookie-path%%',
				'%%url%%',
				'%%api-url-overrule%%',
				'%%api-url-overrule-placeholder%%',
				'%%api-username%%',
				'%%api-password%%',
				'%%wgAbsoluteWikiPath%%',
				'%%wgScript%%',
				'%%use-api-user-only-selected-yes%%',
				'%%use-api-user-only-selected-no%%',
				'%%rc_site_key%%',
				'%%rc_secret_key%%',
				'%%use-formbuilder-selected-yes%%',
				'%%use-formbuilder-selected-no%%',
				'%%allow-edit-docs-selected-yes%%',
				'%%allow-edit-docs-selected-no%%',
				'%%sec-selected-yes%%',
				'%%sec-selected-no%%',
				'%%use-smtp-selected-yes%%',
				'%%use-smtp-selected-no%%',
				'%%smtp-authentication-selected-yes%%',
				'%%smtp-authentication-selected-no%%',
				'%%smtp-host%%',
				'%%smtp-username%%',
				'%%smtp-password%%',
				'%%smtp-secure%%',
				'%%smtp-port%%',
				'%%autosave-interval%%',
				'%%autosave-after-change%%',
				'%%autosave-btn-on%%',
				'%%autosave-btn-off%%',
				'%%sec-key%%',
				'%%form-timeout-limit%%'
			);
			$replace = array(
				$mwcheck,
				$apiCookieSpan,
				$apiCookiePath,
				$purl,
				$apiUrlOverRule,
				$apiUrlOverRulePlaceHolder,
				$apiUsername,
				$apiPassword,
				$wgAbsoluteWikiPath,
				$wgScript,
				$useApiUserOnlySelectedYes,
				$useApiUserOnlySelectedNo,
				$rc_site_key,
				$rc_secret_key,
				$useFormbuilderSelectedYes,
				$useFormbuilderSelectedNo,
				$allowEditDocsSelectedYes,
				$allowEditDocsSelectedNo,
				$secSelectedYes,
				$secSelectedNo,
				$useSMTPSelectedYes,
				$useSMTPSelectedNo,
				$SMTPAuthenticationSelectedYes,
				$SMTPAuthenticationSelectedNo,
				$SMTPHost,
				$SMTPUsername,
				$SMTPPassword,
				$SMTPSecure,
				$SMTPPort,
				$autoSaveIncremental,
				$autoSaveAfterChange,
				$autoSaveButtonOn,
				$autoSaveButtonOFF,
				$secKey,
				$formTimeOut

			);

			$form = file_get_contents( $path . 'setup_all.html' );
			$form = str_replace(
				$find,
				$replace,
				$form
			);

			return $form;
		}
	}


	/**
	 * Highlighting matching string
	 *
	 * @param string $text subject
	 * @param string $words search string
	 *
	 * @return  string  highlighted text
	 */
	private function highlight( $text, $words ) {
		$words = str_replace(
			'_',
			' ',
			$words
		);
		preg_match_all(
			'~\w+~',
			$words,
			$m
		);
		if ( ! $m ) {
			return $text;
		}
		$re = '~\\b(' . implode(
				'|',
				$m[0]
			) . ')\\b~i';

		return preg_replace(
			$re,
			'<span class="ws-search-highlight">$0</span>',
			$text
		);
	}

	private function getDocsCSS( $path, $url ) {
		$css = file_get_contents( $path . 'docs.css' );

		return str_replace(
			'%%url%%',
			$url,
			$css
		);
	}

	private function getConfigSetting( $name ) {
		if ( isset( $this->config[$name] ) ) {
			return $this->config[$name];
		} else {
			return "";
		}
	}

}
