<?php
# @Author: Sen-Sai
# @Date:   25-05-2018 -- 11:47:13
# @Last modified by:   Charlot
# @Last modified time: 18-06-2018 -- 10:48:39
# @License: Mine
# @Copyright: 2018
# @version : 0.6.9.3.7

//namespace wsform\special;
namespace FlexForm\Specials;

use SpecialPage;

use FlexForm;
use FlexForm\validate\validate as validate;
use FlexFormHooks;

use function setcookie;
use function wfMessage;

use const PHP_EOL;

/**
 * Overview for the FlexForm extension
 *
 * @file
 * @ingroup Extensions
 */
class SpecialFlexForm extends \SpecialPage {


	public $allowEditDocs = true;
	public $showFormBuilder = false;
	public $app = array();
	private $config = false;
	private $configFile = '';
	private $config_default = false;

	public function __construct() {
		parent::__construct( 'FlexForm' );
	}


	function getGroupName() {
		return 'Wikibase';
	}


	public function makeMessage( $msg, $type = "danger" ) {
		$wR = new \WebResponse();
		$wR->setCookie( "wsform[type]",
						$type,
						0,
						[
							'path'   => '/',
							'prefix' => ''
						] );
		$wR->setCookie( "wsform[txt]",
						$msg,
						0,
						[
							'path'   => '/',
							'prefix' => ''
						] );
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
		$formhooks = FlexFormHooks::availableHooks();
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
	 * @brief FlexForm Docs menu.
	 * Builds and renders the FlexForm Docs menu
	 *
	 * @param $path string Path to docs
	 * @param $examplePath string Path to examples
	 * @param $purl string uri to Docs
	 * @param $eurl string uri to Examples
	 * @param $wgServer object Wiki server information
	 * @param $out object Wiki Out variable
	 * @param $wsformpurl string FlexForm url
	 * @param $ver string FlexForm version
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
		$menuPath            = "$IP/extensions/FlexForm/Modules/coreNav/";
		$data                = array();
		$exampleData         = array();
		$createExample       = $wgServer . '/index.php/Special:FlexForm/Docs/Create Example';
		$createDocumentation = $wgServer . '/index.php/Special:FlexForm/Docs/Create';
		$formBuilderUrl      = $wgServer . '/index.php/Special:FlexForm/Formbuilder';
		if ( $this->showFormBuilder ) {
			$formBuilderHTML = '<li><a href="' . $formBuilderUrl . '"> Formbuilder</a></li>';
		} else {
			$formBuilderHTML = '';
		}
		$changeLogUrl = $wgServer . '/index.php/Special:FlexForm/Docs/ChangeLog';
		$changeLogUrl = '<li><a href="' . $changeLogUrl . '"> ' . wfMessage( "flexform-docs-changelog" )->text(
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
				$sItem .= '<span><strong>' . wfMessage( "flexform-docs-information" )->text(
					) . ': </strong>' . $docContent['doc']['synopsis'] . '</span></a></li>';
			}
			$mItem = str_replace(
				'%%items%%',
				$sItem,
				$mItem
			);
			$items .= $mItem;
		}
		$back = $wgServer . '/index.php/Special:FlexForm/Docs';
		if ( $this->allowEditDocs ) {
			$new = '<li><a href="' . $createDocumentation . '">' . wfMessage( "flexform-docs-create-new-doc" )->text(
				) . '</a></li>';
			$new .= '<li><a href="' . $createExample . '">' . wfMessage( "flexform-docs-create-new-example" )->text(
				) . '</a></li>';
		} else {
			$new = '<li>' . wfMessage( "flexform-docs-editing-disabled" )->text() . '</li>';
		}

		if ( $newVersionAvailable === false ) {
			$changeLogUrl = '';
		}

		$index      = $wgServer . '/index.php/Special:FlexForm/Docs/Index';
		$wsformpurl = $wgServer . "/extensions/FlexForm/";
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
			$wsformpurl . "FlexForm-logo.png",
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
				$sItem          .= '<span><strong>' . wfMessage( "flexform-docs-information" )->text(
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

		if ( $this->getPostString(
				'mwtoken'
			) || ( isset ( $_GET['action'] ) && $_GET['action'] === 'handleExternalRequest' ) ) {
			// We need to handle api calls here
			//error_reporting( -1 );
			//ini_set( 'display_errors', 1 );
			include_once $IP . "/extensions/FlexForm/FlexForm.api.php";

			return;
		}

		$config_default = false;
		$config         = false;

		$this->configFile = $IP . '/extensions/FlexForm/config/config.php';

		if ( file_exists( $IP . '/extensions/FlexForm/config/config_default.php' ) ) {
			include( $IP . '/extensions/FlexForm/config/config_default.php' );
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
		// Temporarily removing Formbuilder
		$this->showFormBuilder = false;
		$realUrl               = str_replace(
			'/index.php',
			'',
			$wgScript
		);
		$ver                   = "";
		//TODO: Needs to be set to final destination
		$bitbucketSource    = 'https://raw.githubusercontent.com/WikibaseSolutions/FlexForm/main/extension.json';
		$bitbucketChangelog = 'https://raw.githubusercontent.com/WikibaseSolutions/FlexForm/main/README.md';
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

		$myVersionJson = "$IP/extensions/FlexForm/extension.json";
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
		$extensionFile = json_decode( file_get_contents( $IP . '/extensions/FlexForm/extension.json' ), true);
		foreach ( $wgExtensionCredits['parserhook'] as $ext ) {
			if ( $ext['name'] == 'FlexForm' ) {
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
		$path        = "$IP/extensions/FlexForm/docs/";
		$wsformpurl  = $realUrl . "/extensions/FlexForm/";
		$homeUrl  =   $realUrl . '/index.php/Special:FlexForm';
		$installUrl  = $realUrl . '/index.php/Special:FlexForm/Install_step-1/';
		$installUrl4real  = $realUrl . '/index.php/Special:FlexForm/Install_step-2/';
		$purl        = $realUrl . "/index.php/Special:FlexForm/Docs";
		$setupUrl    = $realUrl . "/index.php/Special:FlexForm/Setup";
		$statusUrl   = $realUrl . "/index.php/Special:FlexForm/Status";
		$eurl        = $realUrl . "/index.php/Special:FlexForm/Docs/examples";
		$out         = $this->getOutput();
		$out->addModuleStyles( [
			'ext.wsForm.general.styles'
		] );
		$docsLogo = '<img src="' . $wgServer . '/extensions/FlexForm/Modules/ff-docs-icon.png">';
		$headerPage  = '<div class="flex-form-special-top"><div class="flex-form-special-top-left">';
		$headerPage .= '<a title="FlexForm Special Page - Home" href="' . $homeUrl . '">';
		$headerPage  .= '<img src="' . $wgServer . "/extensions/FlexForm/FlexForm-logo.png" . '" /></a><br>Your version: v' . $currentVersion;
		$headerPage .= '</div><div class="flex-form-special-top-right"><a target="_blank" title="FlexForm Documentation"';
		$headerPage .= ' href="https://www.open-csp.org/DevOps:Doc/FlexForm">';
		$headerPage .= $docsLogo . '<br>Documentation</a></div></div>';
		$out->addHTML(
			$headerPage
		);

		if ( ! $wgUser->isLoggedIn() ) {
			$out->addHTML( '<p>' . wfMessage( "flexform-docs-log-in" )->text() . '</p>' );

			return;
		}

		$args = $this->getArgumentsFromSpecialPage( $sub );
		if ( $args !== false ) {
			switch ( $args[0] ) {
				case "survey":
					$path = "$IP/extensions/FlexForm/Modules/surveyBuilder";
					$ret = file_get_contents( $path . "/dist/index.html" );
					$out->addHTML( $ret );

					return true;
				case "Install_step-1":
					if ( isset( $_GET['v'] ) && $_GET['v'] !== '' ) {
						$getVersion = $_GET['v'];
					} else {
						$getVersion = false;
					}
					if ( $getVersion ) {
						$out->addHTML( 'Click the button to perform a git update to version ' . $getVersion );
						$install4real = '<form method="post" action="' . $installUrl4real . '?v=' . $getVersion . '">' . PHP_EOL;
					} else {
						$out->addHTML( 'Click the button to perform a git update to version ' . $sourceVersion );
						$install4real = '<form method="post" action="' . $installUrl4real . '">' . PHP_EOL;
					}
					$install4real .= '<input type="submit" value="update using Git" class="flex-form-special-install-btn"></form>' . PHP_EOL;
					$out->addHTML( $install4real );
					return true;
				case "Install_step-2":
					if ( isset( $_GET['v'] ) && $_GET['v'] !== '' ) {
						$sourceVersion = $_GET['v'];
					}
					$git = new FlexForm\Core\Git( $IP . '/extensions/FlexForm' );
					if ( $git->isGitRepo() !== true ) {
						$out->addHTML( 'This installation of FlexForm is not Git based. We cannot update your version of FlexForm. Please contact the site admin to do this for you.' );
						return;
					}
					$terminalOutput = '';
					$result = $git->executeGitCmd( 'fetch --all' );
					if ( $result === false ) {
						$out->addHTML( 'Could not execute git command' );
						return;
					}
					$terminalOutput .= $git->implodeResponse( $result['output'] );
					$cmd = 'checkout tags/v' . $sourceVersion;
					$result = $git->executeGitCmd( $cmd );
					$result['output'] = $git->implodeResponse( $result['output'] );
					if ( $git->checkResponseForError( $result['output'] ) !== 'ok' ) {
						switch( $git->checkResponseForError( $result['output'] ) ) {
							case "error" :
								$out->addHTML( '<h2>Git checkout error</h2><p>Please ask the website admin to fix this problem.</p>' );
								$terminalOutput .= str_replace( 'error:', '', $result['output'] );
								break;
							case "fatal" :
								$out->addHTML( '<h2>Git fatal error</h2><p>Please ask the website admin to fix this problem.</p>' );
								$terminalOutput .= str_replace( 'fatal:', '', $result['output'] );
								break;
						}
					} else {
						$terminalOutput .= $result['output'];
						$out->addHTML( '<h2>Git result:</h2>' );
					}
					$out->addHTML('<div class="flex-form-terminal"><pre><output>' );
					$out->addHTML( $terminalOutput );
					$out->addHTML( '</output></pre></div>' );
					return true;
			}
		} else {
			if ( $sourceVersion !== $currentVersion ) {
				$installForm = '<form method="post" action="' . $installUrl . '">' . PHP_EOL;
				$installForm .= '<input type="submit" value="Go to update page" class="flex-form-special-install-btn"></form>' . PHP_EOL;
				$changeLogText   = wfMessage( "flexform-docs-new-version-notice", $sourceVersion )->text();
				$changeLogText .= " " . wfMessage( "flexform-docs-new-version-install" );
				$changeLogText .= $installForm;
				$tableHead       = wfMessage( "flexform-docs-new-version-table" )->text();
				$changelogDetail = $this->getChangeLog(
					$bitbucketChangelog,
					$currentVersion
				);
			} else {
				$changeLogText   = wfMessage( "flexform-docs-no-new-version-notice" )->text();
				$tableHead       = wfMessage( "flexform-docs-no-new-version-table" )->text();
				$changelogDetail = $this->getChangeLog(
					$bitbucketChangelog,
					''
				);
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

			return true;
		}

		return true;
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
			$ret = '<h2>FlexForm (calculated config)</h2>';
		} else {
			$ret = '<h2>FlexForm config file status</h2><p>Check FlexForm <strong>docs -> wsform -> config</strong> for information</p>';
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

	private function setupFlexForm( $path, $purl ) {
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

	/*
	private function executeCmd( $cmd ) {

		$cmd .= ' 2>&1';
		$output = null;
		$resultCode = null;
		exec( $cmd, $output, $resultCode );

		return [
			'exit_status'  => $resultCode,
			'output'       => implode( '<br>', $output )
		];
	}
	*/

	private function getConfigSetting( $name ) {
		if ( isset( $this->config[$name] ) ) {
			return $this->config[$name];
		} else {
			return "";
		}
	}

}
