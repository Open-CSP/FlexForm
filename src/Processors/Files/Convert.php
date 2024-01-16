<?php

namespace FlexForm\Processors\Files;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\FlexFormException;
use Pandoc\Pandoc;
use Pandoc\PandocException;

class Convert {

	/**
	 * @var string
	 */
	private string $convertFrom;

	/**
	 * @var string
	 */
	private string $fileToConvert;

	/**
	 * @var string
	 */
	private string $pandocPathAdditions = '';

	/**
	 * @return string
	 */
	private function getPandocMediaPath(): string {
		return $this->getTempDir() . 'pandoc' . $this->pandocPathAdditions;
	}

	/**
	 * @return Pandoc
	 * @throws FlexFormException
	 */
	private function giveMePandoc(): Pandoc {
		$customInstallPath = Config::getConfigVariable( 'pandoc-install-path' );
		if ( empty( $customInstallPath ) ) {
			$customInstallPath = null;
		}
		try {
			$pandoc = new Pandoc( $customInstallPath );
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
	private function getFile() {
		if ( $this->fileExists() ) {
			return file_get_contents( $this->getTempDir() . $this->fileToConvert );
		} else {
			return false;
		}
	}

	/**
	 * @return string
	 * @throws FlexFormException
	 */
	public function convertFile(): string {
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
		$pandoc  = $this->giveMePandoc();
		$options = [
			'from'          => $this->convertFrom,
			'to'            => 'mediawiki',
			'extract-media' => $this->getPandocMediaPath()
		];
		try {
			$wiki = $pandoc->runWith( $this->getFile(), $options );
		} catch ( \Pandoc\PandocException $e ) {
			$params = [
				'file'  => $e->getFile(),
				'line'  => $e->getLine(),
				'trace' => $e->getTraceAsString()
			];
			unlink( $this->getTempDir() . $this->fileToConvert );
			throw new FlexFormException(
				'Pandoc Conversion Error: ' . $e->getMessage(),
				0,
				$e
			);
		}

		$this->cleanConvertedText( $wiki );

		return $wiki;
	}

	/**
	 * @return string
	 */
	public function pandocGetSearchFor(): string {
		return '[[File:' . $this->getPandocMediaPath() . '/';
	}

	/**
	 * @param string $newFileName
	 *
	 * @return string
	 */
	public function pandocGetReplaceWith( string $newFileName ): string {
		return '[[File:' . $newFileName;
	}

	/**
	 * @return array|false
	 */
	public function getPossibleImagesFromConversion() {
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Checking for Pandoc Media ',
							   [
								   'path' => $this->getPandocMediaPath()
							   ] );
		}
		if ( file_exists( $this->getPandocMediaPath() ) ) {
			$foundFiles = glob( $this->getPandocMediaPath() . '*.*' );
			if ( empty( $foundFiles ) ) {
				$foundFiles = glob( $this->getPandocMediaPath() . '/media/*.*' );
				if ( empty( $foundFiles ) ) {
					return false;
				}
				$this->pandocPathAdditions = '/media';
				if ( Config::isDebug() ) {
					Debug::addToDebug( 'Found Pandoc Media in extra media map ',
									   [
										   'path' => $this->getPandocMediaPath(),
										   'foundfiles' => $foundFiles
									   ] );
				}
			}
			return $foundFiles;
		} else {
			return false;
		}
	}

	/**
	 * Clean large empty spaces and other common conversion problems
	 * @param string &$content
	 *
	 * @return void
	 */
	private function cleanConvertedText( string &$content ) {
		// Remove any non-breaking space
		$content = str_replace( ' ', ' ', $content );
		// Remove empty lines
		$content = preg_replace( '/(\n=+) (<br \/>)\n/', '$1 ', $content );
		// Remove empty spans
		$content = preg_replace( '/\R+<span> <\/span>/', '', $content );
	}

}
