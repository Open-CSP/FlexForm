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

use MediaWiki\MediaWikiServices;
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

	public function __construct() {
		parent::__construct( 'FlexForm' );
	}

	/**
	 * @return string
	 */
	public function getGroupName(): string {
		return 'Wikibase';
	}

	/**
	 * @param string $msg
	 * @param string $type
	 *
	 * @return void
	 */
	public function makeMessage( string $msg, string $type = "danger" ) {
		die();
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

	/**
	 * @return int
	 */
	public function MakeTitle() {
		//$date      = new DateTime( '2000-01-01' );
		//$dt        = date( 'd/m/Y H:i:s' );
		return time();
	}

	/**
	 * @brief Get and check $_POST variable
	 *
	 * @param string $var $_POST variable to check
	 *
	 * @return mixed Either false or the value of the $_POST variable
	 */
	public function getPostString( string $var ) {
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
		global $IP, $wgExtensionCredits, $wgScript, $wgServer;
		$user = $this->getUser();

		if ( $this->getPostString(
				'mwtoken'
			) || ( isset ( $_GET['action'] ) && $_GET['action'] === 'handleExternalRequest' ) ) {
			// We need to handle api calls here
			//error_reporting( -1 );
			//ini_set( 'display_errors', 1 );
			include_once $IP . "/extensions/FlexForm/FlexForm.api.php";
			return;
		}

		$realUrl               = str_replace(
			'/index.php',
			'',
			$wgScript
		);
		$out = $this->getOutput();
		FlexForm\Core\Config::setConfigFromMW();
		$groups = FlexForm\Core\Config::getConfigVariable( 'allowedGroups' );
		$userAllowed = true;
		if ( empty( array_intersect(
			$groups,
			MediaWikiServices::getInstance()->getUserGroupManager()->getUserEffectiveGroups( $user )
		) ) ) {
			$userAllowed = false;
		}
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
				if ( $sourceVersion != $currentVersion ) {
					$ver                 .= ' <br><span style="font-size:11px; color:red;">';
					$ver .= $this->msg( 'flexform-special-new-version', $sourceVersion )->text() . '</span>';
				}
			}
		}
		$path            = "$IP/extensions/FlexForm/docs/";
		$wsformpurl      = $realUrl . "/extensions/FlexForm/";
		$homeUrl         = $realUrl . '/index.php/Special:FlexForm';
		$installUrl      = $realUrl . '/index.php/Special:FlexForm/Install_step-1/';
		$installUrl4real = $realUrl . '/index.php/Special:FlexForm/Install_step-2/';
		$purl            = $realUrl . "/index.php/Special:FlexForm/Docs";
		$approvedPageUrl = $realUrl . "/index.php/Special:FlexForm/valid_forms";
		$setupUrl        = $realUrl . "/index.php/Special:FlexForm/Setup";
		$statusUrl       = $realUrl . "/index.php/Special:FlexForm/Status";
		$eurl            = $realUrl . "/index.php/Special:FlexForm/Docs/examples";

		$out->addModuleStyles( [
			'ext.wsForm.general.styles'
		] );
		$docsLogo = '<img src="' . $wgServer . '/extensions/FlexForm/Modules/ff-docs-icon.png">';
		$vF = new FlexForm\Specials\SpecialHelpers\validForms( $realUrl );
		$headerPage = $vF->addResources();
		$headerPage  .= '<div class="flex-form-special-top"><div class="flex-form-special-top-left">';
		$headerPage .= '<div class="uk-inline"><a title="';
		$headerPage .= $this->msg( 'flexform-special-menu-home-title' )->text() . '" href="' . $homeUrl . '">';
		$headerPage  .= '<img src="' . $wgServer . "/extensions/FlexForm/FlexForm-logo.png" . '" /></a>';
		if ( $userAllowed ) {
			$headerPage .= '<div class="uk-card uk-card-body uk-card-default" uk-drop="pos: right-top">';
			$headerPage .= '<a href=" ' . $homeUrl . ' ">';
			$headerPage .= $this->msg( 'flexform-special-menu-home' )->text() . '</a><br>';
			$headerPage .= '<a href=" ' . $approvedPageUrl . ' ">';
			$headerPage .= $this->msg( 'flexform-special-menu-approved-forms' )->text() . '</a></div></div>';
		}
		$headerPage .= '<br>' . $this->msg( 'flexform-special-version', $currentVersion )->text();
		$headerPage .= '</div>';

		$headerPage .= '<div class="flex-form-special-top-right"><a target="_blank" title="';
		$headerPage .= $this->msg( 'flexform-special-documentation-title' )->text() . '"';
		$headerPage .= ' href="https://www.open-csp.org/DevOps:Doc/FlexForm">';
		$headerPage .= $docsLogo . '<br>' . $this->msg( 'flexform-special-documentation-text' )->text();
		$headerPage .= '</a></div></div>';
		$out->addHTML(
			$headerPage
		);

		// For MW 1.36+, use User::isRegistered
		$isUserRegistered = ( method_exists( 'User', 'isRegistered' ) ) ? $user->isRegistered() : $user->isLoggedIn();
		if ( ! $isUserRegistered ) {
			$out->addHTML( '<p>' . $this->msg( "flexform-docs-log-in" )->text() . '</p>' );

			return;
		}

		$args = $this->getArgumentsFromSpecialPage( $sub );
		if ( $args !== false ) {
			switch ( $args[0] ) {
				case "valid_forms":
					if ( $userAllowed ) {
						$pId = $this->getPostString( 'pId' );
						if ( $pId !== false ) {
							FlexForm\Core\Sql::removePageId( (int)$pId );
						}
						$pIdA = $this->getPostString( 'pIdA' );
						if ( $pIdA !== false ) {
							FlexForm\Core\Sql::addPageFromId( (int)$pIdA );
						}
						$pIdAll = $this->getPostString( 'pIdAll' );
						if ( $pIdAll !== false ) {
							FlexForm\Core\Sql::addPagesFromIds( $pIdAll );
						}
						$pIdAllOnlyForm = $this->getPostString( 'pIdAllOnlyForm' );
						if ( $pIdAllOnlyForm !== false ) {
							FlexForm\Core\Sql::addPagesFromIds( $pIdAllOnlyForm );
						}
						$out->addHTML( $vF->renderApprovedFormsInformation( $pId ) );
						$tag = [];
						$tag['wsform'] = $vF->doSearchQuery( '<wsform' );
						$tag['_form'] = $vF->doSearchQuery( '<_form' );
						$tag['form'] = $vF->doSearchQuery( '<form' );
						$results = [];
						foreach ( $tag as $name => $result ) {
							$results[$name] = $vF->getTitlesArray( $result, $name );
						}
						$rest = array_merge( $results['wsform'], $results['_form'], $results['form'] );
						$vF->arraySortByColumn( $rest, 'title' );
						$out->addHTML( $vF->renderAllFormsInWiki( $rest ) );
					}
					return true;
					break;
				case "survey":
					$path = "$IP/extensions/FlexForm/Modules/surveyBuilder";
					$ret = file_get_contents( $path . "/dist/index.html" );
					$out->addHTML( $ret );

					return true;
				case "Install_step-1":
					if ( $userAllowed ) {
						if ( isset( $_GET['v'] ) && $_GET['v'] !== '' ) {
							$getVersion = $_GET['v'];
						} else {
							$getVersion = false;
						}
						if ( $getVersion ) {
							$out->addHTML(
								$this->msg(
									'flexform-special-update-using-git-to-version',
									$getVersion
								)->text()
							);
							$install4real = '<form method="post" action="' . $installUrl4real . '?v=' . $getVersion;
							$install4real .= '">' . PHP_EOL;
						} else {
							$out->addHTML(
								$this->msg(
									'flexform-special-update-using-git-to-version',
									$sourceVersion
								)->text()
							);

							$install4real = '<form method="post" action="' . $installUrl4real . '">' . PHP_EOL;
						}
						$install4real .= '<input type="submit" value="';
						$install4real .= $this->msg( 'flexform-special-documentation-title' )->text(
							) . '" class="flex-form-special-install-btn">';
						$install4real .= '</form>' . PHP_EOL;
						$out->addHTML( $install4real );

						return true;
					}
					break;
				case "Install_step-2":
					if ( $userAllowed ) {
						if ( isset( $_GET['v'] ) && $_GET['v'] !== '' ) {
							$sourceVersion = $_GET['v'];
						}
						$git = new FlexForm\Core\Git( $IP . '/extensions/FlexForm' );
						if ( $git->isGitRepo() !== true ) {
							$out->addHTML(
								$this->msg( 'flexform-special-update-using-git-no-git' )->text()
							);

							return;
						}
						$terminalOutput = '';
						$result         = $git->executeGitCmd( 'fetch --all' );
						if ( $result === false ) {
							$out->addHTML( $this->msg( 'flexform-special-update-using-git-cmd-error' )->text() );

							return;
						}
						$terminalOutput .= $git->implodeResponse( $result['output'] );
						$cmd = 'checkout tags/v' . $sourceVersion;
						$result = $git->executeGitCmd( $cmd );
						$result['output'] = $git->implodeResponse( $result['output'] );
						if ( $git->checkResponseForError( $result['output'] ) !== 'ok' ) {
							switch ( $git->checkResponseForError( $result['output'] ) ) {
								case "error" :
									$out->addHTML(
										$this->msg( 'flexform-special-update-using-git-checkout-error' )->text()
									);
									$terminalOutput .= str_replace(
										'error:',
										'',
										$result['output']
									);
									break;
								case "fatal" :
									$out->addHTML(
										$this->msg( 'flexform-special-update-using-git-fatal-error' )->text()
									);
									$terminalOutput .= str_replace(
										'fatal:',
										'',
										$result['output']
									);
									break;
							}
						} else {
							$terminalOutput .= $result['output'];
							$out->addHTML(
								'<h2>' . $this->msg( 'flexform-special-update-using-git-result' )->text() . '</h2>'
							);
						}
						$out->addHTML( '<div class="flex-form-terminal"><pre><output>' );
						$out->addHTML( $terminalOutput );
						$out->addHTML( '</output></pre></div>' );

						return true;
					}
					break;
			}
		} else {
			if ( $userAllowed ) {
				if ( $sourceVersion !== $currentVersion ) {
					$installForm     = '<form method="post" action="' . $installUrl . '">' . PHP_EOL;
					$installForm     .= '<input type="submit" value="';
					$installForm     .= $this->msg( 'flexform-special-godo-update-btn' )->text();
					$installForm     .= '" class="flex-form-special-install-btn"></form>' . PHP_EOL;
					$changeLogText   = $this->msg(
						"flexform-docs-new-version-notice",
						$sourceVersion
					)->text();
					$changeLogText   .= " " . $this->msg( "flexform-docs-new-version-install" )->text();
					$changeLogText   .= $installForm;
					$tableHead       = $this->msg( "flexform-docs-new-version-table" )->text();
					$changelogDetail = $this->getChangeLog(
						$bitbucketChangelog,
						$currentVersion
					);
				} else {
					$changeLogText   = $this->msg( "flexform-docs-no-new-version-notice" )->text();
					$tableHead       = $this->msg( "flexform-docs-no-new-version-table" )->text();
					$changelogDetail = $this->getChangeLog(
						$bitbucketChangelog,
						''
					);
				}

				$changeLogTemplate = file_get_contents( $path . 'changelog.html' );
				$repl              = [
					'%changelogdescr%',
					'%tablehead%',
					'%version%',
					'%changelog%'
				];
				$with              = [
					$changeLogText,
					$tableHead,
					$sourceVersion,
					'<pre>' . $changelogDetail . '</pre>'
				];
				$out->addHTML(
					str_replace(
						$repl,
						$with,
						$changeLogTemplate
					)
				);
			}

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

}
