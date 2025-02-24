<?php
/**
 * Created by  : OpenCSP
 * Project     : MWWSForm
 * Filename    : SpreadsheetConverter.php
 * Description :
 * Date        : 21-2-2024
 * Time        : 19:10
 */

namespace FlexForm\Processors\Convert;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\FlexFormException;
use FlexForm\Processors\Files\Convert;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

class SpreadsheetConverter extends Convert {

	/**
	 * @var string
	 */
	private string $reader;

	/**
	 * @var string
	 */
	private string $slot;

	/**
	 * @param string $reader
	 *
	 * @return void
	 */
	public function setReader( string $reader ) {
		if ( strtolower( $reader ) === 'xls' ) {
			$this->reader = 'Xls';
			//$this->reader = IOFactory::READER_XLS;
		}
		if ( strtolower( $reader ) === 'xlsx' ) {
			$this->reader = 'Xlsx';
			//$this->reader = IOFactory::READER_XLSX;
		}
	}

	/**
	 * @param string $slot
	 *
	 * @return void
	 */
	public function setSlot( string $slot ) {
		$this->slot = $slot;
	}

	/**
	 * @return string
	 * @throws FlexFormException
	 */
	public function convertFile(): string {
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Preparing to Convert',
				[
					'reader' => $this->reader,
					'file and path'      => $this->getFile( true )
				]
			);
		}
		try {
			$reader = IOFactory::createReaderForFile( $this->getFile( true ) );
			$reader->setReadDataOnly( true );
			$spreadsheet = $reader->load( $this->getFile( true ) );
			$sheetData = $spreadsheet->getActiveSheet()->
			toArray( null, true, true, true );
			$worksheet = $spreadsheet->getSheet( 0 );
			$highestRow = $worksheet->getHighestRow();
			$highestColumn = $worksheet->getHighestColumn();
			$highestColumnIndex = Coordinate::columnIndexFromString( $highestColumn );
			$data = [];
			$keys = [];

			for ( $row = 1; $row <= $highestRow; $row++ ) {
				$riga = [];
				for ( $col = 1; $col <= $highestColumnIndex; $col++ ) {
					$riga[] = $worksheet->getCellByColumnAndRow( $col, $row )->getValue() ?? "";
				}
				if ( 1 === $row ) {
					// Header row. Save it in "$keys".
					$keys = $riga;
					continue;
				}
				// This is not the first row; so it is a data row.
				// Transform $riga into a dictionary and add it to $data.
				$data[] = array_combine( $keys, $riga );
			}
			foreach ( $data as $key => $entry ) {
				foreach ( $entry as $k => $v ) {
					if ( $v === "" ) {
						unset( $data[$key][$k] );
					}
				}
			}
			return json_encode( $data, JSON_PRETTY_PRINT );

		} catch ( Exception | \PhpOffice\PhpSpreadsheet\Exception $e ) {
			throw new FlexFormException(
				$e->getMessage(),
				0,
				$e
			);
		}
	}
}