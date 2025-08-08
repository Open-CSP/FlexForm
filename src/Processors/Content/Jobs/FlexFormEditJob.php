<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : Jobs.php
 * Description :
 * Date        : 7-8-2025
 * Time        : 13:56
 */

namespace FlexForm\Processors\Content\Jobs;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\ContentCore;
use FlexForm\Processors\Content\Edit;
use FlexForm\Processors\Content\Save;
use Job;
use MediaWiki\Extension\WikiApiary\data\ResponseHandler;

class FlexFormEditJob extends Job {

	private const JOB_NAME = "FlexFormEdit";

	/**
	 * @inheritDoc
	 */
	public function __construct( string $dummy, array $params ) {
		parent::__construct( self::JOB_NAME, $params );
	}

	/**
	 * @inheritDoc
	 */
	public function run() {
		$pageId = $this->params['pageId'];
		$pData = $this->params['edits'];
		ContentCore::$isJob = true;
		ContentCore::$jobData = [ $pageId => $pData ];
		ContentCore::$jobSummary = $this->params['summary'];
		ContentCore::$jobUser = $this->params['user'];
		FlexFormJobLogger::logInfo( 'CREATE: FlexFormEditJob.php: Running job for Page ID : ' . $pageId, $pData );
		$responseHandler = new handleResponse();
		try {
			ContentCore::saveToWiki( $responseHandler );
		} catch ( \Throwable $e ) {
			FlexFormJobLogger::logError( 'CREATE: FlexFormEditJob.php: Running job error for PageId : '
				. $pageId . '. Error: ' . $e->getMessage() );
		}
	}
}
