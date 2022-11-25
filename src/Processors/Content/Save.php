<?php

namespace FlexForm\Processors\Content;

use CommentStoreComment;
use ContentHandler;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MWContentSerializationException;
use MWException;
use RequestContext;
use Title;
use User;
use WikiPage;
use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\FlexFormException;
use SMW\ApplicationFactory;
use SMW\Options;
use SMW\Store;
use SMW\StoreFactory;

class Save {

	/**
	 * @param string $content
	 *
	 * @return array|string|string[]
	 */
	private function removeCarriageReturnFromContent( string $content ) {
		return str_replace(
			"\r",
			'',
			$content
		);
	}

	/**
	 * This function will save a page to the Wiki and all slots in one go.
	 *
	 * @param User $user The user that performs the edit
	 * @param WikiPage $wikipage_object The page to edit
	 * @param array $text $key is slotname and value is the text to insert
	 * @param string $summary The summary to use
	 *
	 * @return true|array True on success, and an error message with an error code otherwise
	 *
	 * @throws MWContentSerializationException Should not happen
	 * @throws MWException Should not happen
	 */
	private function editSlots(
		User $user,
		WikiPage $wikipage_object,
		array $text,
		string $summary
	) {
		$status              = true;
		$errors              = [];
		$title_object        = $wikipage_object->getTitle();
		$page_updater        = $wikipage_object->newPageUpdater( $user );
		$old_revision_record = $wikipage_object->getRevisionRecord();
		$slot_role_registry  = MediaWikiServices::getInstance()->getSlotRoleRegistry();
		$mainContentText     = '';

		// loop through all slots we need to edit/create
		foreach ( $text as $slot_name => $content ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'edit slot slot name',
					$slot_name
				);
				Debug::addToDebug(
					'edit slot slot content',
					$content
				);
			}
			$content = $this->removeCarriageReturnFromContent( $content );
			// Make sure the slot we are editing is defined in MW else skip this slot
			if ( !$slot_role_registry->isDefinedRole( $slot_name ) ) {
				$status   = false;
				$errors[] = wfMessage(
					"flexform-unkown-slot",
					$slot_name
				);
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
					$model_id = $old_revision_record->getSlot( $slot_name )->getContent()->getContentHandler()
													->getModelID();
				} else {
					$model_id = $slot_role_registry->getRoleHandler( $slot_name )->getDefaultModel( $title_object );
				}
				if ( $slot_name === SlotRecord::MAIN ) {
					$mainContentText = $content;
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
					$page_updater->addTag( 'wsslots-slot-edit' ); // TODO: Update message name and if tags are needed
				}
				*/
			}
		}

		// Are we creating a new page while filling a slot other than main?
		if ( $old_revision_record === null && !isset( $text[SlotRecord::MAIN] ) ) {
			// The 'main' content slot MUST be set when creating a new page
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'We only have a slot to write, we need to create main as well! -- ' . time(),
					$slot_name
				);
			}
			$mainContentText = '';
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
		$result = $page_updater->saveRevision(
			$comment,
			EDIT_INTERNAL
		);


		if ( Config::isDebug() ) {
			$res = "true";
			if ( $result === false ) {
				$res = "false";
			}
			Debug::addToDebug(
				'SaveRevision result -- ' . time(),
				[ 'true or false' => $res ]
			);
		}

		if ( !$page_updater->getStatus()->isOK() ) {
			// If the update failed, reflect this in the status
			$status = false;
			$errors[] = $page_updater->getStatus()->getMessage()->toString();
		}

		if ( Config::isDebug() ) {
			if ( $page_updater->isUnchanged() ) {
				Debug::addToDebug(
					'EDIT SLOTS PAGE SAVED IS UNCHANGED ' . time(),
					$page_updater->isUnchanged()
				);
			} else {
				Debug::addToDebug(
					'EDIT SLOTS PAGE SAVED IS CHANGED ' . time(),
					$page_updater->isUnchanged()
				);
			}
		}
		/*
		 * Some testing to see if any of these functions trigger DisplayTitle property update
		 *
		$wikipage_object->setTimestamp( wfTimestampNow() );
		$wikipage_object->updateParserCache( [
												 'causeAction' => 'api-purge',
												 'causeAgent' => $user->getName(),
											 ] );
		$wikipage_object->doSecondaryDataUpdates( [
													  'recursive' => true,
													  'causeAction' => 'api-purge',
													  'causeAgent' => $user->getName(),
													  'defer' => DeferredUpdates::PRESEND,
												  ] );
		$wikipage_object->doPurge();
*/

		if ( !$page_updater->isUnchanged() ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Page has changed, lets do a null edit! ' . time(),
					"no further info"
				);
			}
			$title = $wikipage_object->getTitle();

			// Refresh SMW properties if applicable
			$this->refreshSMWProperties( $title );

			// Perform an additional null-edit to make sure all page properties are up-to-date
			$this->doNullEdit( $user, $wikipage_object, $mainContentText );
		}

		if ( $status === true ) {
			return true;
		} else {
			return $errors;
		}
	}

	/**
	 * @param User $user
	 * @param WikiPage $wikiPageObject
	 *
	 * @return void
	 * @throws MWException
	 */
	private function doNullEdit( User $user, WikiPage $wikiPageObject, $content ) {
		$title = $wikiPageObject->getTitle()->getFullText();
		$titleObject = Title::newFromText( $title );
		$wikiPageObject = WikiPage::factory( $titleObject );
		//https://nw-wsform.wikibase.nl/api.php?action=edit&format=json&title=Displaytitle_test&appendtext=%20ola&token=4eed6b0237ae86236a9640a795bb52bb6256cbbe%2B%5C
		/*
		$content = rtrim( $content, " " );
		$render   = new Render();
		$csrf = $user->getEditToken();
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Get CSRF token ' . time(), $csrf );
		}
		$postdata = [
			"action"     => "edit",
			"format"     => "json",
			"title"      => $wikiPageObject->getTitle()->getFullText(),
			"text" 		 => $content . " ",
			"token"      => $csrf
		];
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Space Edit request data ' . time(), $postdata );
		}
		$result = $render->makeRequest( $postdata );
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Space Edit request ' . time(), $result );
		}

		*/
		$comment = CommentStoreComment::newUnsavedComment( "" );
		$wikiPageObject->doPurge();
		$page_updater = $wikiPageObject->newPageUpdater( $user );

		$result = $page_updater->saveRevision(
			$comment,
			EDIT_SUPPRESS_RC | EDIT_AUTOSUMMARY
		);
		if ( Config::isDebug() ) {
			$res = "success";
			if ( $result === false ) {
				$res = "error";
			}
			Debug::addToDebug(
				'Null edit result -- ' . time(),
				$res
			);
		}
	}

	/**
	 * @param Title $title
	 */
	private function refreshSMWProperties( Title $title ) {
		sleep( 1 );
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' ) ) {
			return;
		}

		$store = StoreFactory::getStore();
		$store->setOption(
			Store::OPT_CREATE_UPDATE_JOB,
			false
		);

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
	 * @param string $title
	 * @param array $contentArray
	 * @param string $summary
	 * @param string $overWrite
	 *
	 * @return void
	 * @throws MWException
	 * @throws FlexFormException
	 * @throws MWContentSerializationException
	 */
	public function saveToWiki( string $title, array $contentArray, string $summary, bool $overWrite = true ) {
		$user        = RequestContext::getMain()->getUser();
		$titleObject = Title::newFromText( $title );
		if ( !$titleObject || $titleObject->hasFragment() ) {
			throw new FlexFormException( "Invalid title $title." );
		}
		if ( !$overWrite && $titleObject->exists() ) {
			throw new FlexFormException( wfMessage( 'flexform-mwcreate-page-exists' )->text() . ' : ' . $title );
		}
		// $slot is now an array as of v0.8.0.9.8.8
		try {
			$wikiPageObject = WikiPage::factory( $titleObject );
		} catch ( MWException $e ) {
			throw new FlexFormException(
				"Could not create a WikiPage Object from title " . $titleObject->getText(
				) . '. Message ' . $e->getMessage(),
				0,
				$e
			);
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'sending to edit slots $contentArray',
				$contentArray
			);
		}
		$saveResult = $this->editSlots(
			$user,
			$wikiPageObject,
			$contentArray,
			$summary
		);
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Save result',
				$saveResult
			);
		}
		if ( $saveResult !== true ) {
			throw new FlexFormException(
				"Save Result error: " . print_r(
					$saveResult,
					true
				)
			);
		}
	}
}
