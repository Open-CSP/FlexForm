<?php

use FlexForm\Core\Sql;
use FlexForm\Specials\SpecialHelpers\validForms;
use MediaWiki\MediaWikiServices;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Created by  : OpenCSP
 * Project     : FlexForm
 * Filename    : syncPagesWithForms.php
 * Description :
 * Date        : 6-8-2025
 * Time        : 14:45
 */
class syncPagesWithForms extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Go through all pages in the Wiki and sync pages with FlexForms.\n" );
		$this->addOption(
			'dry-run',
			'Show details but do not actual sync. [optional]'
		);
	}

	/**
	 * @inheritDoc
	 * @throws \MediaWiki\Maintenance\MaintenanceFatalError
	 */
	public function execute() {
		if ( MediaWikiServices::getInstance()->getReadOnlyMode()->isReadOnly() ) {
			$this->fatalError( "Wiki is in read-only mode; you'll need to disable it for import to work." );
		}
		$allForms = Sql::getAllApprovedForms( false, true );
		$data = [];
		$data['valid'] = [];
		$data['invalid'] = [];
		foreach ( $allForms as $pId => $details ) {
			if ( $details['valid'] === "1" ) {
				$data['valid'][] = $pId;
			} else {
				$data['invalid'][] = $pId;
			}
		}
		$this->output( "We have " . count( $allForms ) . " synced forms.\n" );
		$this->output( "Of which " . count( $data['valid'] ) . " validated forms.\n" );
		$this->output( "Of which " . count( $data['invalid'] ) . " unvalidated forms.\n" );
		$dryRun = false;
		if ( $this->hasOption( 'dry-run' ) ) {
			$dryRun = true;
		}

		$validForms = new validForms( '' );
		$tag = [];
		$this->output( "Now checking for wsform tag in all wiki pages... \n" );
		$tag['wsform'] = $validForms->doSearchQuery( '<wsform', true );
		$this->output( "Now checking for _form tag in all wiki pages... \n" );
		$tag['_form'] = $validForms->doSearchQuery( '<_form', true );
		$this->output( "Now checking for form tag in all wiki pages... \n" );
		$tag['form'] = $validForms->doSearchQuery( '<form', true );
		$this->output( "\nChecks done... \n" );
		$dataAll = [];
		foreach ( $tag as $name => $result ) {
			foreach ( $result as $row ) {
				$pId = $row->page_id;
				if ( in_array( $pId, $data['valid'] ) ) {
					continue;
				}
				if ( in_array( $pId, $data['invalid'] ) ) {
					continue;
				}
				$dataAll[] = $pId;
			}
		}
		$this->output( "We have found " . count( $dataAll ) . " pages with forms not synced.\n" );
		$difference = array_diff( $dataAll, $data['invalid'] );
		$this->output( "\nSyncing: \n" );
		foreach ( $difference as $pId ) {
			$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromID( $pId );
			if ( $page === false || $page === null ) {
				$title = "invalid page";
			} else {
				$title = $page->getTitle()->getFullText();
			}
			if ( $dryRun ) {
				$txt = " .. No Sync Dry-run";
			} else {
				try {
					$result = Sql::addPageFromId( $pId, false );
				} catch ( \Exception $e ) {
					$txt = " .. Error Syncing : " . $e->getMessage() . " ( probably nothing )";
				}
				if ( $result === true ) {
					$txt = " .. successfully synced";
				}

			}
			$this->output( "Page ID: " . $pId . " :: Title : " . $title . " $txt\n" );

		}

		$this->output( "\n\nAll done\n" );
	}
}

$maintClass = syncPagesWithForms::class;
require_once RUN_MAINTENANCE_IF_MAIN;
