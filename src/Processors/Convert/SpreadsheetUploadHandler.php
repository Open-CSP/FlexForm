<?php

namespace FlexForm\Processors\Convert;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\Save;
use FlexForm\Processors\Files\Upload;
use FlexForm\Processors\Utilities\General;
use MWContentSerializationException;
use RequestContext;

class SpreadsheetUploadHandler {

	/**
	 * Executes the Spreadsheet conversion and saves the JSON to the wiki.
	 *
	 * @param string $fileAction The reader type (e.g., 'xls' or 'xlsx')
	 * @param string $storedFile The temporary file on disk
	 * @param string $titleName The target title in the wiki
	 * @param array $fileDetails The array containing form options
	 * @param string $imageComment The summary/comment for the edit
	 *
	 * @throws FlexFormException|MWContentSerializationException
	 */
	public function process(
		string $fileAction,
		string $storedFile,
		string $titleName,
		array $fileDetails,
		string $imageComment
	): void {
		$excelSheetByName = General::getJsonValue( 'wsform_sheetbyname', $fileDetails );
		$excelSheetById   = General::getJsonValue( 'wsform_sheetbyid', $fileDetails );

		if ( $excelSheetByName === false && $excelSheetById === false ) {
			$excelSheetById = 0;
		}

		$fileSlot = General::getJsonValue( 'wsform_slot', $fileDetails );
		if ( $fileSlot === false ) {
			$fileSlot = 'main';
		}

		$excelMapping = General::getJsonValue( 'wsform_excel_mapping', $fileDetails );

		if ( $excelMapping !== false && !empty( $excelMapping ) ) {
			$convert = new SpreadsheetRuleConverter();
			$convert->setMappingRules( $excelMapping );
		} else {
			$convert = new SpreadsheetConverter();
			$convert->setReader( $fileAction );
		}

		$convert->setFileName( $storedFile );
		$convert->setSheetByName( $excelSheetByName );
		$convert->setSheetById( $excelSheetById );

		$json = $convert->convertFile();

		if ( $convert instanceof SpreadsheetRuleConverter ) {
			$this->processExtractedImages(
				$convert,
				$titleName,
				$fileDetails,
				$imageComment,
				$json
			);
		}

		// Now create the page in the wiki
		if ( !Config::isDebug() ) {
			$save = new Save();
			try {
				$save->saveToWiki(
					$titleName,
					[ $fileSlot => $json ],
					$imageComment
				);
			} catch ( FlexFormException $e ) {
				throw new FlexFormException( $e->getMessage(), 0, $e );
			}
		}
	}

	/**
	 * Upload images extracted from Excel mapping rules and replace placeholders in the JSON.
	 *
	 * @param SpreadsheetRuleConverter $convert
	 * @param string $titleName
	 * @param array $fileDetails
	 * @param string $imageComment
	 * @param string $jsonContent
	 *
	 * @return void
	 * @throws FlexFormException
	 * @throws MWContentSerializationException
	 */
	private function processExtractedImages(
		SpreadsheetRuleConverter $convert,
		string $titleName,
		array $fileDetails,
		string $imageComment,
		string &$jsonContent
	): void {
		$possibleImages = $convert->getPossibleImagesFromConversion();
		if ( $possibleImages === false ) {
			return;
		}

		$user            = RequestContext::getMain()->getUser();
		$uploadProcessor = new Upload( '', $fileDetails );
		$safeTitlePrefix = str_replace( [ '/', ':' ], '-', $titleName );
		$fCount          = 1;

		foreach ( $possibleImages as $singleImage ) {
			$newFname = $safeTitlePrefix . '-' . basename( $singleImage );

			if ( Config::isDebug() ) {
				Debug::addToDebug(
					$fCount . ' - Preparing to upload image file from Excel',
					[
						'$newFname'    => $newFname,
						'$singleImage' => $singleImage,
						'comment'      => $imageComment
					]
				);
			} else {
				$resultFileUpload = $uploadProcessor->uploadFileToWiki(
					$singleImage,
					$newFname,
					$user,
					'',
					$imageComment,
					wfTimestampNow()
				);

				if ( $resultFileUpload !== true ) {
					throw new FlexFormException( (string)$resultFileUpload, 0 );
				}
			}

			$search          = $convert->excelGetSearchFor() . basename( $singleImage );
			$replace         = $convert->excelGetReplaceWith( $newFname );
			$jsonSafeReplace = trim( json_encode( $replace, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), '"' );
			$jsonContent     = str_replace( $search, $jsonSafeReplace, $jsonContent );

			if ( file_exists( $singleImage ) ) {
				unlink( $singleImage );
			}
			$fCount++;
		}
	}
}
