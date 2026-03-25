<?php

namespace FlexForm\Processors\Convert;

use FlexForm\Core\Config;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\Save;
use FlexForm\Processors\Utilities\General;
use MWContentSerializationException;

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

		$convert = new SpreadsheetConverter();
		$convert->setReader( $fileAction );
		$convert->setFileName( $storedFile );
		$convert->setSheetByName( $excelSheetByName );
		$convert->setSheetById( $excelSheetById );

		$json = $convert->convertFile();

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
}