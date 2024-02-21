<?php

namespace FlexForm\Processors\Files;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\FlexFormException;


abstract class Convert {

	/**
	 * @return mixed
	 */
	abstract public function convertFile();

	/**
	 * @var string
	 */
	protected string $fileToConvert;

	/**
	 * @return string
	 */
	protected function getTempDir(): string {
		return rtrim(
				   Config::getConfigVariable( 'file_temp_path' ),
				   '/'
			   ) . '/';
	}

	/**
	 * @param string $fileName
	 *
	 * @return void
	 */
	public function setFileName( string $fileName ) {
		$this->fileToConvert = $fileName;
	}

	/**
	 *
	 * @return bool
	 */
	private function fileExists(): bool {
		if ( $this->fileToConvert === null ) {
			return false;
		}
		return file_exists( $this->getTempDir() . $this->fileToConvert );
	}

	/**
	 *
	 * @return bool|string
	 */
	protected function getFile() {
		if ( $this->fileExists() ) {
			return file_get_contents( $this->getTempDir() . $this->fileToConvert );
		} else {
			return false;
		}
	}

}
