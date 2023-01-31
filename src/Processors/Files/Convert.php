<?php

namespace FlexForm\Processors\Files;

use FlexForm\Core\Config;
use FlexForm\FlexFormException;
use Pandoc\Pandoc;
use Pandoc\PandocException;

class Convert {

	private string $convertFrom;

	private string $fileToConvert;

	/**
	 * @return Pandoc
	 * @throws FlexFormException
	 */
	private function giveMePandoc(): Pandoc {
		try {
			$pandoc = new Pandoc();
		} catch ( PandocException $e ) {
			throw new FlexFormException(
				$e->getMessage(),
				0,
				$e
			);
		}
		return $pandoc;
	}

	/**
	 * @param string $from
	 *
	 * @return void
	 */
	public function setConvertFrom( string $from ) {
		$this->convertFrom = $from;
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
	 * @return string
	 */
	private function getTempDir(): string {
		return rtrim(
				   Config::getConfigVariable( 'file_temp_path' ),
				   '/'
			   ) . '/';
	}

	/**
	 *
	 * @return bool
	 */
	public function fileExists(): bool {
		if ( $this->fileToConvert === null ) {
			return false;
		}
		return file_exists( $this->getTempDir() . $this->fileToConvert );
	}

	/**
	 *
	 * @return bool|string
	 */
	public function getFile() {
		if ( $this->fileExists( $this->fileToConvert ) ) {
			return file_get_contents( $this->getTempDir() . $this->fileToConvert );
		} else {
			return false;
		}
	}

	/**
	 * @return void
	 * @throws FlexFormException
	 */
	public function convertFile() {
		if ( $this->convertFrom === null ) {
			throw new FlexFormException(
				'Missing convert to option for conversion',
				0
			);
		}
		if ( $this->fileToConvert === null ) {
			throw new FlexFormException(
				'Missing Filename option for conversion',
				0
			);
		}
		$pandoc = $this->giveMePandoc();
		$options = [
			'from' => $this->convertFrom,
			'to' => 'mediawiki',
			'extract-media' => $this->getTempDir() . 'media'
		];
	}

}