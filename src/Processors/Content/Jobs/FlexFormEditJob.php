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

use Job;

class FlexFormEditJob extends Job {

	private const JOB_NAME = "FlexFormEdit";

	/**
	 * @inheritDoc
	 */
	public function __construct( $params ) {
		parent::__construct( self::JOB_NAME, $params );
	}

	/**
	 * @inheritDoc
	 */
	public function run() {
		// TODO: add run job info
	}
}