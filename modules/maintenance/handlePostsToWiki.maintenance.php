<?php
/**
 * Created by  : Designburo.nl
 * Project     : wsformWikiBaseNL
 * Filename    : handlePostsToWiki.maintenance.php
 * Description :
 * Date        : 06/04/2020
 * Time        : 13:33
 */

#error_reporting( -1 );
#ini_set( 'display_errors', 1 );

use MediaWiki\MediaWikiServices;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class handlePostsToWiki extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'WSForm' );
		$this->mDescription = "WSForm handling of posts\n";
		$this->addOption( 'summary', 'Additional text that will be added to the files imported History. [optional]', false, true, "s" );
		$this->addOption( 'action', 'What to do', true, true, "a" );
		$this->addOption( 'content', 'Page content', true, true );
		$this->addOption( 'title', 'Page title', true, true );
		$this->addOption( 'fip', "Filename and path", false, true );
		$this->addOption( 'user', 'Your username. Will be added to the import log. [mandatory]', true, true, "u" );
		$this->addOption( 'rc', 'Place revisions in RecentChanges.' );
	}

	public function returnMsg( $msg, $status = true ){
		if( $status === true ){
			$status = "ok";
		} else $status = "error";
		echo $status . "|" . $msg;
		return;
	}

	public function createMsg( $msg, $status = false ){
		$ret = array();
		$ret['status'] = $status;
		$ret['msg'] = $msg;
		return $ret;
	}

	public function uploadFileToWiki( $filePath, $filename, $uname, $content, $summary, $timestamp ) {
		$ret = array();
		global $wgUser;
		if( !file_exists( $filePath ) ) {
			return $this->createMsg( 'Cannot find file' );
		}
		$user = $this->getUser( $uname );
		if( $user === false ){
			return $this->createMsg( 'Cannot find user' );
		}
		$wgUser = $user;
		$base = UtfNormal\Validator::cleanUp( wfBaseName( $filename ) );
		# Validate a title
		$title = Title::makeTitleSafe( NS_FILE, $base );
		if ( !is_object( $title ) ) {
			return $this->createMsg( "{$base} could not be imported; a valid title cannot be produced" );
		}

		$image = wfLocalFile( $title );
		$mwProps = new MWFileProps( MediaWiki\MediaWikiServices::getInstance()->getMimeAnalyzer() );
		$props = $mwProps->getPropsFromPath( $filePath, true );
		$flags = 0;
		$publishOptions = [];
		$handler = MediaHandler::getHandler( $props['mime'] );
		if ( $handler ) {
			$metadata = Wikimedia\quietCall( 'unserialize', $props['metadata'] );

			$publishOptions['headers'] = $handler->getContentHeaders( $metadata );
		} else {
			$publishOptions['headers'] = [];
		}
		$archive = $image->publish( $filePath, $flags, $publishOptions );

		if ( !$archive->isGood() ) {
			return $this->createMsg( $archive->getWikiText( false, false, 'en' ) );
		}
		$image->recordUpload2(
			$archive->value,
			$summary,
			$content,
			$props,
			$timestamp
		);
		return $this->createMsg('ok', true);

	}

	public function savePageToWiki( $pageName, $content, $summary, $timestamp, $bot, $rc, $uname ) {
		$ret = array();
		$user = $this->getUser( $uname );
		if( $user === false ){
			return $this->createMsg( 'Cannot find user' );
		}
		$title = Title::newFromText( $pageName );
		if ( !$title || $title->hasFragment() ) {
			return $this->createMsg( "Invalid title $pageName." );
		}
		$exists = $title->exists();
		$oldRevID = $title->getLatestRevID();
		$oldRev = $oldRevID ? Revision::newFromId( $oldRevID ) : null;
		$rev = new WikiRevision( MediaWikiServices::getInstance()->getMainConfig() );
		$rev->setText( rtrim( $content ) );
		$rev->setTitle( $title );
		$rev->setUserObj( $user );
		$rev->setComment( $summary );
		$rev->setTimestamp( $timestamp );
		if ( $exists && $rev->getContent()->equals( $oldRev->getContent() ) ) {
			return $this->createMsg( "Page has no changes from the current", true );
		}
		$status = $rev->importOldRevision();
		$newId = $title->getLatestRevID();
		if( $rc ){
			$this->addToRecentChanges( $exists, $oldRev, $timestamp, $rev, $user, $summary, $oldRevID, $bot, $newId, $title );
		}
		return $this->createMsg('ok', true);

	}

	public function addToRecentChanges( $exists, $oldRev, $timestamp, $rev, $user, $summary, $oldRevID, $bot, $newId, $title ){

		if ( $exists ) {
			if ( is_object( $oldRev ) ) {
				$oldContent = $oldRev->getContent();
				RecentChange::notifyEdit(
					$timestamp,
					$title,
					$rev->getMinor(),
					$user,
					$summary,
					$oldRevID,
					$oldRev->getTimestamp(),
					$bot,
					'',
					$oldContent ? $oldContent->getSize() : 0,
					$rev->getContent()->getSize(),
					$newId,
					1 /* the pages don't need to be patrolled */
				);
			}
		} else {
			RecentChange::notifyNew(
				$timestamp,
				$title,
				$rev->getMinor(),
				$user,
				$summary,
				$bot,
				'',
				$rev->getContent()->getSize(),
				$newId,
				1
			);
		}
	}

	public function getUser( $name ) {
		$user = User::newFromId( $name );

		if ( !$user ) {
			return false;
		}

		if ( $user->isAnon() ) {
			return false;
		}

		return $user;
	}


	public function execute(){

		if ( wfReadOnly() ) {
			$this->returnMsg( "Wiki is in read-only mode; you'll need to disable it for import to work.", false );
			return;
		}

		$bot = false;

		$IP = getenv( 'MW_INSTALL_PATH' );

		if ( $IP === false ) {
			$IP = __DIR__ . '/../..';
		}

		$summary = $this->getOption( 'summary', 'WSForm' );

		if( $this->hasOption( 'user' ) ) {
			$user = $this->getOption( 'user' );
			if( (int)$user < 1 ) {
				$this->returnMsg( "User argument is mandatory.", false );
			}
		} else {
			$this->returnMsg( "User argument is mandatory.", false );
			return;
		}

		if( $this->hasOption( 'title' ) ) {
			$title = $this->getOption( 'title' );
		} else {
			$this->returnMsg( "Title argument is mandatory.", false );
			return;
		}

		if( $this->hasOption( 'content' ) ) {
			$content = base64_decode( $this->getOption( 'content' ) );
		} else {
			$this->returnMsg( "Content argument is mandatory.", false );
			return;
		}

		if( $this->hasOption( 'action' ) ) {
			$action = $this->getOption( 'action' );
		} else {
			$this->returnMsg( "Action argument is mandatory.", false );
			return;
		}

		$fileAndPath = $this->getOption( 'fip', false );

		$timestamp = wfTimestampNow();

		$rc = $this->hasOption( 'rc' );

		switch( $action ) {
			case "addPageToWiki" :
				//$pageName, $content, $summary, $timestamp, $bot, $rc, $uname
				$result = $this->savePageToWiki( $title, $content, $summary, $timestamp, $bot, $rc, $user );
				break;
			case "uploadFileToWiki" :
				if( $fileAndPath === false ) {
					$result = $this->createMsg( 'No file and path given (fip)' );
				} else {
					//uploadFileToWiki( $filePath, $filename, $uname, $content, $summary, $timestamp )
					$result = $this->uploadFileToWiki( $fileAndPath, $title, $user, $content, $summary, $timestamp );
				}
				break;
			default:
				$result = $this->createMsg( 'No recognized action' );
				break;
		}

		$this->returnMsg( $result['msg'], $result['status'] );
	}

}
$maintClass = handlePostsToWiki::class;
require_once RUN_MAINTENANCE_IF_MAIN;