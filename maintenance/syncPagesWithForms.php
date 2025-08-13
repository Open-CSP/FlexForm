<?php

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
		$this->mDescription = "Go through all pages in the Wiki and sync pages with FlexForms.\n";
		$this->addOption(
			'dry-run',
			'Show details but do not actual sync. [optional]',
			false,
			false
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		if ( MediaWikiServices::getInstance()->getReadOnlyMode()->isReadOnly() ) {
			$this->fatalError( "Wiki is in read-only mode; you'll need to disable it for import to work." );
		}
		$allForms = \FlexForm\Core\Sql::getAllApprovedForms( false, true );
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
		echo "We have " . count( $allForms ) . " synced forms.\n";
		echo "Of which " . count( $data['valid'] ) . " validated forms.\n";
		echo "Of which " . count( $data['invalid'] ) . " unvalidated forms.\n";
		$dryRun = false;
		if ( $this->hasOption( 'dry-run' ) ) {
			$dryRun = true;
		}

		$validForms = new \FlexForm\Specials\SpecialHelpers\validForms( '' );
		$tag = [];
		echo "Now checking for wsform tag in all wiki pages... \n";
		$tag['wsform'] = $validForms->doSearchQuery( '<wsform', true );
		echo "Now checking for _form tag in all wiki pages... \n";
		$tag['_form'] = $validForms->doSearchQuery( '<_form', true );
		echo "Now checking for form tag in all wiki pages... \n";
		$tag['form'] = $validForms->doSearchQuery( '<form', true );
		echo "\nChecks done... \n";
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
		echo "We have found " . count( $dataAll ) . " pages with forms not synced.\n";
		$difference = array_diff( $dataAll, $data['invalid'] );
		echo "\nSyncing: \n";
		foreach ( $difference as $pId ) {
			$page = WikiPage::newFromId( $pId );
			if ( $page === false || $page === null ) {
				$title = "invalid page";
			} else {
				$title = $page->getTitle()->getFullText();
			}
			if ( $dryRun ) {
				$txt = " .. No Sync Dry-run";
			} else {
				try {
					$result = \FlexForm\Core\Sql::addPageFromId( $pId, false );
				} catch ( \Exception $e ) {
					$txt = " .. Error Syncing : " . $e->getMessage() . " ( probably nothing )";
				}
				if ( $result === true ) {
					$txt = " .. successfully synced";
				}

			}
			echo "Page ID: " . $pId . " :: Title : " . $title . " $txt\n";

		}

		echo "\n\nAll done\n";
	}
}

$maintClass = syncPagesWithForms::class;
require_once RUN_MAINTENANCE_IF_MAIN;