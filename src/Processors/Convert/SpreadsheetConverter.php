<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
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
	 * @var bool
	 */
	private $sheetByName = false;

	/**
	 * @var mixed
	 */
	private $sheetById = false;

	/**
	 * @throws FlexFormException
	 */
	public function __construct() {
		if ( !method_exists( 'PhpOffice\PhpSpreadsheet\IOFactory', 'createReaderForFile' ) ) {
			throw new FlexFormException( wfMessage( 'flexform-fileupload-file-convert-no-excel-convertor' ) );
		}
	}

	/**
	 * @param int|bool $id
	 *
	 * @return void
	 */
	public function setSheetById( $id ) {
		$this->sheetById = $id;
	}

	/**
	 * @param string|bool $name
	 *
	 * @return void
	 */
	public function setSheetByName( $name ) {
		$this->sheetByName = $name;
	}

	/**
	 * @param string $reader
	 *
	 * @return void
	 */
	public function setReader( string $reader ) {
		if ( strtolower( $reader ) === 'xls' ) {
			$this->reader = 'Xls';
		}
		if ( strtolower( $reader ) === 'xlsx' ) {
			$this->reader = 'Xlsx';
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
					'file and path'      => $this->getFile( true ),
					'sheetbyid' => $this->sheetById,
					'sheetbyname' => $this->sheetByName
				]
			);
		}
		try {
			$reader = IOFactory::createReaderForFile( $this->getFile( true ) );
			$reader->setReadDataOnly( true );
			$spreadsheet = $reader->load( $this->getFile( true ) );
			$sheetData = $spreadsheet->getActiveSheet()->toArray( null, true, true, true );
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Excel converting',
					[
						'sheetById' => $this->sheetById,
						'sheetByName' => $this->sheetByName,
						'sheetNames' => $spreadsheet->getSheetNames(),
						'sheetCount' => $spreadsheet->getSheetCount(),
					]
				);
			}

			if ( $this->sheetByName !== false ) {
				$worksheet = $spreadsheet->getSheetByName( $this->sheetByName );
			} elseif ( $this->sheetById !== false ) {
				$worksheet = $spreadsheet->getSheet( $this->sheetById );
			} else {
				throw new FlexFormException(
					wfMessage( 'flexform-fileupload-file-convert-excel-not-found' ), 0
				);
			}
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Excel Worksheet Information',
					[
						'title' => $worksheet->getTitle(),
						'sheetByName'      => $worksheet->getHighestDataColumn()
					]
				);
			}
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