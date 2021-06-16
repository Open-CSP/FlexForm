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
use MediaWiki\Revision\SlotRecord;
use SMW\ApplicationFactory;
use SMW\Options;
use SMW\Store;
use SMW\StoreFactory;



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
		$this->addOption( 'slot', "Slot to save content to", false, true );
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

	/**
	 * @param Title $title
	 */
	public function refreshSMWProperties( Title $title ){

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' ) ) {
			return;
		}
		try {
			if( !MediaWikiServices::getInstance()->getMainConfig()->get( 'RPImmediateSMWUpdate' ) ) {
				return;
			}
		} catch ( ConfigException $e ){
			return;
		}



		if ( !ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' ) &&
		     !MediaWikiServices::getInstance()->getMainConfig()->get( 'RPImmediateSMWUpdate' )
		) {
			return;
		}

		$store = StoreFactory::getStore();
		$store->setOption( Store::OPT_CREATE_UPDATE_JOB, false );

		$rebuilder = new \SMW\Maintenance\DataRebuilder(
			$store,
			ApplicationFactory::getInstance()->newTitleFactory()
		);

		$rebuilder->setOptions(
		// Tell SMW to only rebuild the current page
			new Options( [ 'page' => $title ] )
		);

		$rebuilder->rebuild();

	}

	public function savePageToWiki( $pageName, $content, $summary, $timestamp, $bot, $rc, $uname, $slot = false ) {
		$ret = array();


		if( $slot !== false) {
			define( "CUSTOMSLOT", $slot );
		}
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
		if ( version_compare( $GLOBALS['wgVersion'], "1.35" ) < 0 ) {
			$oldRev = $oldRevID ? Revision::newFromId( $oldRevID ) : null;
		} else {
			$revLookup = MediaWikiServices::getInstance()->getRevisionLookup();
			$oldRev = $oldRevID ? $revLookup->getRevisionById( $oldRevID ) : null;
		}

		$parser = MediaWikiServices::getInstance()->getParser();

		$content = $parser->preSaveTransform( $content, $title, $user, ParserOptions::newCanonical() );

		$rev = new WikiRevision( MediaWikiServices::getInstance()->getMainConfig() );
		if ( version_compare( $GLOBALS['wgVersion'], "1.35" ) < 0 ) {
			$rev->setTitle( $title );
			$rev->setModel( $rev->getModel() ); // Fix for 1.35; $model must not be NULL and getModel() retrieves the correct model iff it is not available
			$rev->setText( rtrim( $content ) );
			$rev->setUserObj( $user );
			$rev->setComment( $summary );
			$rev->setTimestamp( $timestamp );
		} else {
			$content = ContentHandler::makeContent( rtrim( $content ), $title );
			if( $slot === false ) {
				$rev->setContent( SlotRecord::MAIN, $content );
			} else {
				$rev->setContent( $slot, $content );
				$rev->setContent( SlotRecord::MAIN, $oldRev->getContent( SlotRecord::MAIN ) );
			}
			$rev->setTitle( $title );
			$rev->setUserObj( $user );
			$rev->setComment( $summary );
			$rev->setTimestamp( $timestamp );
		}
		if ( version_compare( $GLOBALS['wgVersion'], "1.35" ) < 0 ) {
			if ( $exists && $rev->getContent()->equals( $oldRev->getContent() ) ) {
				return $this->createMsg( "Page has no changes from the current", true );
			}
		} else {
			if( $slot === false ) {
				if ( $exists && $rev->getContent()->equals( $oldRev->getContent( SlotRecord::MAIN ) ) ) {
					return $this->createMsg( "Page has no changes from the current", true );
				}
			} else {
				//TODO find out why this does not work!
				/*
				if ( $exists && $rev->getContent( CUSTOMSLOT )->equals( $oldRev->getContent( CUSTOMSLOT ) ) ) {
					return $this->createMsg( "Page has no changes from the current", true );
				}
				*/
			}
		}
		$status = $rev->importOldRevision();
		$newId = $title->getLatestRevID();
		$this->refreshSMWProperties( $title );
		if( $rc && $slot = false ){
			$this->addToRecentChanges( $exists, $oldRev, $timestamp, $rev, $user, $summary, $oldRevID, $bot, $newId, $title, $slot );
		}
		return $this->createMsg('ok', true);

	}

	public function addToRecentChanges( $exists, $oldRev, $timestamp, $rev, $user, $summary, $oldRevID, $bot, $newId, $title, $slot ){
		if( $slot !== false) {
			define( "CUSTOMSLOT", $slot );
		}
		if ( $exists ) {
			if ( is_object( $oldRev ) ) {
				if ( version_compare( $GLOBALS['wgVersion'], "1.35" ) < 0 ) {
					$oldContent = $oldRev->getContent();
				} else {
					if( $slot === false ) {
						$oldContent = $oldRev->getContent( SlotRecord::MAIN );
					} else {
						//TODO: Same here
						//$oldContent = $oldRev->getContent( CUSTOMSLOT );
						$oldContent = false;
					}
				}
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

		$slot = false;

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

		if( $this->hasOption( 'slot' ) ) {
			$slot = $this->getOption( 'slot' );
		}

		$fileAndPath = $this->getOption( 'fip', false );

		$timestamp = wfTimestampNow();

		$rc = $this->hasOption( 'rc' );

		switch( $action ) {
			case "addPageToWiki" :
				//$pageName, $content, $summary, $timestamp, $bot, $rc, $uname
				$result = $this->savePageToWiki( $title, $content, $summary, $timestamp, $bot, $rc, $user, $slot );
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