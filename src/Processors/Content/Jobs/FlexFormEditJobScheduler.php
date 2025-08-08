<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : FlexFormJobScheduler.php
 * Description :
 * Date        : 7-8-2025
 * Time        : 14:04
 */

namespace FlexForm\Processors\Content\Jobs;

use MediaWiki\MediaWikiServices;

class FlexFormEditJobScheduler {

	/**
	 * @param array $edits
	 * @param string $summary
	 * @param string $user
	 *
	 * @return void
	 */
	public function addFlexFormEditJob( array $edits, string $summary, string $user ): void {
		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
		foreach ( $edits as $pId => $pData ) {
			FlexFormJobLogger::logInfo( 'CREATE: FlexFormEditJobScheduler.php: Creating job for Page ID: ' .
				$pId . '. For user: ' . $user . '. With data: ' . print_r( $pData, true ),
				$pData );
			$job = new FlexFormEditJob( "",
				[ 'pageId' => $pId, 'edits' => $pData, 'summary' => $summary, 'user' => $user ] );
			$jobQueueGroup->push( $job );
		}
	}
}
