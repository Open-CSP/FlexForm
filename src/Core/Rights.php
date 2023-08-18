<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : Rights.php
 * Description :
 * Date        : 19-4-2022
 * Time        : 20:13
 */

namespace FlexForm\Core;

use Exception;
use FlexForm\FlexFormException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use RequestContext;
use SkinTemplate;
use Title;
use User;
use WikiPage;

class Rights {

	/**
	 * @var bool
	 */
	private static $allowed = true;

	// Dive into the skin. Check is a user may edit. If not, remove tabs.
	//*

	/**
	 * @param string $name
	 *
	 * @return false|mixed
	 * @throws FlexFormException
	 */
	private static function getConfigVariable( string $name ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( $config->has( 'FlexFormConfig' ) ) {
			$ffConfig = $config->get( 'FlexFormConfig' );

			return $ffConfig[$name] ?? false;
		} else {
			throw new FlexFormException(
				'No config set',
				1
			);
		}
	}

	/**
	 * @param WikiPage $wikiPageObject
	 * @param User $user
	 *
	 * @return bool
	 */
	private static function isThereAFlexFormInThePageContent( WikiPage $wikiPageObject, User $user ) {
		if ( $wikiPageObject === null ) {
			return false;
		}
		$title = $wikiPageObject->getTitle();
		if ( !$title->isKnown() ) {
			return false;
		}
		$content = $wikiPageObject->getContent(
			RevisionRecord::RAW,
			$user
		)->getWikitextForTransclusion();
		$formTags = [ '<wsform', '<_form', '<form' ];
		foreach ( $formTags as $tag ) {
			if ( strpos( $content, $tag ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param User|null $user
	 *
	 * @return bool
	 * @throws FlexFormException
	 */
	public static function isUserAllowedToEditorCreateForms( User $user = null ) {
		if ( $user === null ) {
			$user = RequestContext::getMain()->getUser();
		}
		$userGroups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $user );
		$allowedUserGroups = self::getConfigVariable( 'allowedGroups' );
		if ( array_intersect(
			$allowedUserGroups,
			$userGroups
		) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param SkinTemplate $sktemplate
	 * @param array $links
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function hideSource( SkinTemplate &$sktemplate, array &$links ) : bool {
		// always remove viewsource tab

		if ( self::getConfigVariable( 'hideEdit' ) !== true ) {
			return true;
		}
		$title = $sktemplate->getTitle();
		if ( $title->isSpecialPage() || ! $title->exists() ) {
			return true;
		}
		$user = RequestContext::getMain()->getUser();
		// grab user permissions
		if ( self::isThereAFlexFormInThePageContent(
			$sktemplate->getWikiPage(),
			$user
		) ) {
			self::$allowed = self::isUserAllowedToEditorCreateForms();
			/*
			if ( self::isUserAllowedToEditorCreateForms() === false ) {
				self::$allowed = false;
			}
			*/
		}
		if ( self::$allowed === false ) {
			// User is not allowed to edit or create a form
			$removeUs = [
				'edit',
				'form_edit',
				'history'
			];
			//echo "<pre>";
			//var_dump( $links['views'] );
			//echo "</pre>";
			foreach ( $removeUs as $view ) {
				if ( isset( $links['views'][$view] ) ) {
					unset( $links['views'][$view] );
				}
			}
		}

		return true;
	}

	// If a user has no edit rights, then make sure it is hard for him to view
	// the source of a document
	public static function disableActions( Title $title, User $user, $action, &$result ) {
		if ( $title->isSpecialPage() || ! $title->exists() ) {
			return true;
		}

		$wikipage = WikiPage::newFromID( $title->getArticleID() );

		if ( self::isThereAFlexFormInThePageContent(
			$wikipage,
			$user
		) ) {
			if ( self::isUserAllowedToEditorCreateForms( $user ) ) {
				return true;
			}
			$actionNotAllowed = [
				'edit'
			];
			// Also disable the version difference options
			if ( isset( $_GET['diff'] ) ) {
				$result = "flexform-rights-not";

				return false;
			}
			if ( isset( $_GET['veaction'] ) ) {
				$result = "flexform-rights-not";

				return false;
			}
			if ( isset( $_GET['action'] ) ) {
				$actie = $_GET['action'];
				if ( in_array(
					$actie,
					$actionNotAllowed
				) ) {
					$result = "flexform-rights-not";

					return false;
				}
			}
		}

		return true;
	}

	// prevent ShowReadOnly form to be shown
	// We should never get here anymore, but just in case.
	public static function doNotShowReadOnlyForm( EditPage $editPage, OutputPage $output ) {
		if ( method_exists(
			$editPage,
			'getTitle'
		) ) {
			$title = $editPage->getTitle();
		} else {
			$title = $editPage->mTitle;
		}
		$user_can_edit = $title->userCan( 'edit' );
		if ( ! $user_can_edit ) {
			$output->redirect( $editPage->getContextTitle() );
		}

		return $output;
	}

}