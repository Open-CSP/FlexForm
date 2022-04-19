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

class Rights {

	/**
	 * @var bool
	 */
	private static bool $allowed = true;

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
	 * @param SkinTemplate $sktemplate
	 * @param array $links
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function hideSource( SkinTemplate &$sktemplate, array &$links ) : bool {
		// always remove viewsource tab
		$user     = RequestContext::getMain()->getUser();
		$content = $sktemplate->getWikiPage()->getContent(
			RevisionRecord::FOR_THIS_USER,
			$user
		)->getWikitextForTransclusion();
		// grab user permissions
		$userGroups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $user );
		if ( strpos(
			$content,
			'<_form'
		) ) {
			$allowedUserGroups = self::getConfigVariable( 'allowedCreateAndEdit' );

			if ( !in_array(
				$allowedUserGroups,
				$userGroups
			) ) {
				self::$allowed = false;
			}
		}
		if ( self::$allowed === false ) {
			// User is not allowed to edit or create a form
			$removeUs = [ 'edit', 'form_edit', 'history' ];
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
	public static function disableActions( &$title, &$wgUser, $action, &$result ) {
		if ( in_array(
			'edit',
			$wgUser->getRights(),
			true
		) ) {
			return true;
		} else {
			// define the actions to be blocked
			$actionNotAllowed = array(
				'edit',
				'move',
				'history',
				'info',
				'raw',
				'delete',
				'revert',
				'revisiondelete',
				'rollback',
				'markpatrolled'
			);
			// Also disable the version difference options
			if ( isset( $_GET['diff'] ) ) {
				return false;
			}
			if ( isset( $_GET['action'] ) ) {
				$actie = $_GET['action'];
				if ( in_array(
					$actie,
					$actionNotAllowed
				) ) {
					return false;
				}
			}

			// Any other action is fine
			return true;
		}
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