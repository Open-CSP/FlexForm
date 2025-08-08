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
	 *
	 * @return void
	 */
	public function addFlexFormEditJob( array $edits ) {
		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
		foreach ( $edits as $pId => $pData ) {
			$job = new FlexFormEditJob( "", [ 'pageId' => $pId, 'edits' => $pData ] );
			$jobQueueGroup->push( $job );
		}
	}
}
