<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : Pandoc.php
 * Description :
 * Date        : 21-2-2024
 * Time        : 18:22
 */

namespace FlexForm\Processors\Convert;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\FlexFormException;
use FlexForm\Processors\Files\Convert;
use Pandoc\Pandoc;
use Pandoc\PandocException;

class PandocConverter extends Convert {
	/**
	 * @var string
	 */
	private string $convertFrom;

	/**
	 * @var string
	 */
	private string $convertTo;

	/**
	 * @var array
	 */
	private array $pandocAdditionalArguments = [];

	/**
	 * @var string
	 */
	private string $pandocPathAdditions = '';

	/**
	 * @var array|string[]
	 */
	private array $binaryFormats = [
		"pdf",
		"docx",
		"docbook",
		"docbook5",
		"pptx",
		"epub",
		"odt"
	];

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
	public function setConvertFrom( string $from ): void {
		$this->convertFrom = $from;
	}

	/**
	 * @param array $arguments
	 *
	 * @return void
	 */
	public function setAdditionalArguments( array $arguments ): void {
		global $IP;
		$path = $IP . '/';
		if ( !empty( $arguments ) ) {
			foreach ( $arguments as $k => $argument ) {
				$arguments[ $k ] = str_replace( '[path]', $path, $argument );
			}
		}
		$this->pandocAdditionalArguments = $arguments;
	}

	/**
	 * @param string $from
	 *
	 * @return void
	 */
	public function setConvertTo( string $from ): void {
		$this->convertTo = $from;
	}

	/**
	 * @return bool
	 */
	public function isBinaryTarget(): bool {
		return in_array( strtolower( $this->convertTo ), $this->binaryFormats );
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

		if ( $this->convertTo === null ) {
			$this->convertTo = 'mediawiki';
		}

		$allowedFrom = Config::getConfigVariable( 'pandoc-convert-from' );
		$allowedTo = Config::getConfigVariable( 'pandoc-convert-to' );

		if ( !in_array( strtolower( $this->convertFrom ), $allowedFrom, true ) ) {
			throw new FlexFormException(
				wfMessage( 'flexform-fileupload-file-convert-from-error', $this->convertFrom )->parse(),
				0
			);
		}
		if ( !in_array( strtolower( $this->convertTo ), $allowedTo, true ) ) {
			throw new FlexFormException(
				wfMessage( 'flexform-fileupload-file-convert-to-error', $this->convertFrom )->parse(),
				0
			);
		}

		$pandoc  = $this->giveMePandoc();
		$options = [
			'from'          => $this->convertFrom,
			'to'            => $this->convertTo,
			'extract-media' => $this->getPandocMediaPath()
		];
		$options = array_merge( $options, $this->pandocAdditionalArguments );
		Debug::addToDebug( 'Pandoc Conversion options', $options );
		try {
			$wiki = $pandoc->runWith( $this->getFile(), $options );
		} catch ( PandocException $e ) {
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
		if ( !$this->isBinaryTarget() ) {
			$this->cleanConvertedText( $wiki );
		}

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
		$content = str_replace( ' ', ' ', $content );
		// Remove empty lines
		$content = preg_replace( '/(\n=+) (<br \/>)\n/', '$1 ', $content );
		// Remove empty spans
		$content = preg_replace( '/\R+<span> <\/span>/', '', $content );
	}
}
