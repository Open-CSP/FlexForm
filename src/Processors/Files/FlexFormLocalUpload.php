<?php

namespace FlexForm\Processors\Files;

use UploadBase;
use WebRequest;

class FlexFormLocalUpload extends UploadBase {

	/**
	 * Initialize from an already-stored local temporary file.
	 *
	 * @param string $desiredDestName
	 * @param string $filePath
	 *
	 * @return void
	 */
	public function initializeFromLocalFile( string $desiredDestName, string $filePath ): void {
		$fileSize = file_exists( $filePath ) ? (int)filesize( $filePath ) : 0;
		$this->initializePathInfo( $desiredDestName, $filePath, $fileSize );
	}

	/**
	 * Required by abstract UploadBase, unused for local server-side files.
	 *
	 * @param WebRequest &$request
	 *
	 * @return void
	 */
	public function initializeFromRequest( &$request ): void {
		// Intentionally empty
	}
}
