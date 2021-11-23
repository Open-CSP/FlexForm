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
use MediaWiki\Session;
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
		$this->addOption( 'content', 'Page content', true, true ); // including slots
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
		$this->refreshSMWProperties( $title );
		return $this->createMsg('ok', true);

	}

	/**
	 * @param Title $title
	 */
	public function refreshSMWProperties( Title $title ){
		sleep( 1 );
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' ) ) {
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


	/**
	 * @param int $id
	 *
	 * @return array|false
	 */
	private static function getSlotNamesForPageAndRevision( $id ) {
		$id   = (int) ( $id );
		$page = WikiPage::newFromId( $id );
		if ( $page === false || $page === null ) {
			return false;
		}
		$latest_revision = $page->getRevisionRecord();
		if ( $latest_revision === null ) {
			return false;
		}

		return array(
			"slots"           => $latest_revision->getSlotRoles(),
			"latest_revision" => $latest_revision
		);
	}

	/**
	 * @param int $id
	 *
	 * @return array|false|null
	 */
	public static function getSlotsContentForPage( $id ) {
		$slot_result = self::getSlotNamesForPageAndRevision( $id );
		if ( $slot_result === false ) {
			return false;
		}
		$slot_roles      = $slot_result['slots'];
		$latest_revision = $slot_result['latest_revision'];

		$slot_contents = [];

		foreach ( $slot_roles as $slot_role ) {
			//echo "\ngetSlotsContentForPage for slot : $slot_role";
			if ( strtolower( self::$config['contentSlotsToBeSynced'] ) !== 'all' ) {
				if ( ! array_key_exists(
					$slot_role,
					self::$config['contentSlotsToBeSynced']
				) ) {
					continue;
				}
			}
			if ( ! $latest_revision->hasSlot( $slot_role ) ) {
				continue;
			}

			$content_object = $latest_revision->getContent( $slot_role );

			if ( $content_object === null || ! ( $content_object instanceof TextContent ) ) {
				continue;
			}

			$contentOfSLot = ContentHandler::getContentText( $content_object );

			if( empty( $contentOfSLot ) && $slot_role !== 'main' ) continue;

			$slot_contents[ $slot_role ] = ContentHandler::getContentText( $content_object );
		}

		return $slot_contents;

	}


	private function extensionInstalled ( $name ) {
		return extensionRegistry::getInstance()->isLoaded( $name );
	}

	/**
	 * @param User $user The user that performs the edit
	 * @param WikiPage $wikipage_object The page to edit
	 * @param array $text $key is slotname and value is the text to insert
	 * @param string $summary The summary to use
	 *
	 * @return true|array True on success, and an error message with an error code otherwise
	 *
	 * @throws \MWContentSerializationException Should not happen
	 * @throws \MWException Should not happen
	 */
	public static function editSlots(
		User $user,
		WikiPage $wikipage_object,
		array $text,
		string $summary
	) {
		$status = true;
		$errors = array();
		$title_object        = $wikipage_object->getTitle();
		$page_updater        = $wikipage_object->newPageUpdater( $user );
		$old_revision_record = $wikipage_object->getRevisionRecord();
		$slot_role_registry  = MediaWikiServices::getInstance()->getSlotRoleRegistry();

		// loop through all slots we need to edit/create
		foreach( $text as $slot_name => $content ) {
			// Make sure the slot we are editing is defined in MW else skip this slot
			if ( ! $slot_role_registry->isDefinedRole( $slot_name ) ) {
				$status = false;
				$errors[] = wfMessage(
					"wsform-unkown-slot",
					$slot_name
				); // TODO: Update message name
				unset( $text[$slot_name] );
				continue;
			}
			if ( $content === "" && $slot_name !== SlotRecord::MAIN ) {
				// Remove the slot if $content is empty and the slot name is not MAIN
				$page_updater->removeSlot( $slot_name );
			} else {
				// Set the content for the slot we want to edit

				// If the page exists and has this slot
				if ( $old_revision_record !== null && $old_revision_record->hasSlot( $slot_name ) ) {
					$model_id = $old_revision_record->getSlot( $slot_name )->getContent()->getContentHandler()->getModelID();
				} else {
					$model_id = $slot_role_registry->getRoleHandler( $slot_name )->getDefaultModel( $title_object );
				}

				$slot_content = ContentHandler::makeContent(
					$content,
					$title_object,
					$model_id
				);
				$page_updater->setContent(
					$slot_name,
					$slot_content
				);
				/*
				if ( $slot_name !== SlotRecord::MAIN ) {
					$page_updater->addTag( 'wsslots-slot-edit' ); // TODO: Update message name
				}
				*/
			}
		}

		// Are we creating a new page while filling a slot other than main?
		if ( $old_revision_record === null && !isset( $text[SlotRecord::MAIN] ) ) {
			// The 'main' content slot MUST be set when creating a new page
			$main_content = ContentHandler::makeContent(
				"",
				$title_object
			);
			$page_updater->setContent(
				SlotRecord::MAIN,
				$main_content
			);
		}

		$comment = CommentStoreComment::newUnsavedComment( $summary );
		$page_updater->saveRevision(
			$comment,
			EDIT_INTERNAL
		);

		if( true === $status ) {
			return array(
				"result"  => true,
				"changed" => $page_updater->isUnchanged()
			);
		} else {
			return array(
				'result' => false,
				'errors' => $errors
			);
		}

	}


	/**
	 * @param Title $pTitle
	 * @param string $content
	 * @param string $summary
	 * @param string $slot
	 * @param User $user
	 *
	 * @return mixed
	 */
	private function editSlot( Title $pTitle, string $content, string $summary, string $slot, User $user ) {
		if ( ! $this->extensionInstalled( 'WSSlots' ) ) {
			return $this->createMsg( "WSSlots extension is not installed!" );
		}

		if ( method_exists(
			'WSSlots\WSSlots',
			'editSlot'
		) ) {
			try {
				$wikiPageObject = WikiPage::factory( $pTitle );
			} catch ( MWException $e ) {
				return $this->createMsg( "Could not create a WikiPage Object from title " . $pTitle->getText() .
				'. Message ' . $e->getMessage() );
			}
			if ( is_null( $wikiPageObject ) ) {
				return $this->createMsg( "Could not create a WikiPage Object from Article Id " . $pTitle );
			}
			$result = WSSlots\WSSlots::editSlot(
				$user,
				$wikiPageObject,
				$content,
				$slot,
				$summary,
				false
			);
			if ( true !== $result ) {
				list( $message, $code ) = $result;

				return $this->createMsg( $message );
			}

			return true;
		} else {
			return $this->createMsg( "Could not find class WSSlots or method editSlot" );
		}
	}

	private function checkContentValue( $content ){
		$content = base64_decode( $content );
		$slotKeyValueSeparator = '_-_-_';
		$slotSeparator = '-_-_-';
		$slots = explode( $slotSeparator, $content );
		$data = array();
		foreach( $slots as $slot ){
			$xpl = explode( $slotKeyValueSeparator, $slot );
			$k = $xpl[0];
			$v = $xpl[1];
			$data[$k] = $v;
		}
		return $data;
	}

	public function savePageToWiki( $pageName, $content, $summary, $timestamp, $bot, $rc, $uname ) {

		$user = $this->getUser( $uname );
		if( $user === false ){
			return $this->createMsg( 'Cannot find user' );
		}
		$title = Title::newFromText( $pageName );
		if ( !$title || $title->hasFragment() ) {
			return $this->createMsg( "Invalid title $pageName." );
		}

		// $slot is now an array as of v0.8.0.9.8.8
		try {
			$wikiPageObject = WikiPage::factory( $title );
		} catch ( MWException $e ) {
			return $this->createMsg( "Could not create a WikiPage Object from title " . $title->getText() .
									 '. Message ' . $e->getMessage() );
		}
		if ( is_null( $wikiPageObject ) ) {
			return $this->createMsg( "Could not create a WikiPage Object from Article Id " . $title );
		}

		$slotEditResult = $this->editSlots( $user, $wikiPageObject, $content, $summary );
		if( true !== $slotEditResult ) {
			return $slotEditResult;
		} else return $this->createMsg(
			'ok',
			true
		);

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
			$content = $this->checkContentValue( $this->getOption( 'content' ) );
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