<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : SpreadsheetRuleConverter.php
 * Description : Extracts specific cells, lists, key-values, and tables from Excel based on JSON rules.
 */

namespace FlexForm\Processors\Convert;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\FlexFormException;
use FlexForm\Processors\Files\Convert;
use MediaWiki\MediaWikiServices;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use TextContent;

class SpreadsheetRuleConverter extends Convert {

	private const VALUE_RAW = 'raw';
	private const VALUE_CALCULATED = 'calculated';
	private const VALUE_FORMATTED = 'formatted';

	/**
	 * @var string|bool
	 */
	private string|bool $sheetByName = false;

	/**
	 * @var mixed
	 */
	private mixed $sheetById = false;

	/**
	 * @var string
	 */
	private string $mappingRules = '';

	/**
	 * @throws FlexFormException
	 */
	public function __construct() {
		if ( !method_exists( 'PhpOffice\PhpSpreadsheet\IOFactory', 'createReaderForFile' ) ) {
			throw new FlexFormException( wfMessage( 'flexform-fileupload-file-convert-no-excel-convertor' ) );
		}
	}

	/**
	 * @param bool|int $id
	 *
	 * @return void
	 */
	public function setSheetById( bool|int $id ): void {
		$this->sheetById = $id;
	}

	/**
	 * @param bool|string $name
	 *
	 * @return void
	 */
	public function setSheetByName( bool|string $name ): void {
		$this->sheetByName = $name;
	}

	/**
	 * @param string $mappingRules JSON string or Wiki Page Title
	 *
	 * @return void
	 */
	public function setMappingRules( string $mappingRules ): void {
		$this->mappingRules = $mappingRules;
	}

	/**
	 * @return string
	 * @throws FlexFormException
	 * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	public function convertFile(): string {
		$rules = $this->resolveRules( $this->mappingRules );
		$needsFormatting = $this->rulesRequireFormatting( $rules );

		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Preparing to Convert Excel with Rules',
				[
					'file and path' => $this->getFile( true ),
					'defaultSheetId' => $this->sheetById,
					'defaultSheetName' => $this->sheetByName,
					'needsFormatting' => $needsFormatting,
					'rules' => $rules
				]
			);
		}

		try {
			$filePath = $this->getFile( true );
			$reader = IOFactory::createReaderForFile( $filePath );

			// ReadDataOnly when we do not need formatted values
			$reader->setReadDataOnly( !$needsFormatting );

			$spreadsheet = $reader->load( $filePath );
			$output = [];

			foreach ( $rules as $targetKey => $rule ) {
				if ( !is_array( $rule ) || !isset( $rule['type'] ) ) {
					continue;
				}

				$worksheet = $this->resolveWorksheet( $spreadsheet, $rule );

				switch ( strtolower( $rule['type'] ) ) {
					case 'cell':
						$output[$targetKey] = $this->extractCell( $worksheet, $rule );
						break;

					case 'list':
						$output[$targetKey] = $this->extractList( $worksheet, $rule );
						break;

					case 'key_value':
						$output[$targetKey] = $this->extractKeyValue( $worksheet, $rule );
						break;

					case 'table':
						$output[$targetKey] = $this->extractTable( $worksheet, $rule );
						break;

					default:
						throw new FlexFormException( "Unknown Excel mapping rule type: {$rule['type']}" );
				}
			}

			return json_encode( $output, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		} catch ( Exception $e ) {
			throw new FlexFormException( $e->getMessage(), 0, $e );
		}
	}

	/**
	 * 'formatted' used
	 *
	 * @param array $rules
	 *
	 * @return bool
	 */
	private function rulesRequireFormatting( array $rules ): bool {
		foreach ( $rules as $rule ) {
			if ( !isset( $rule['value_type'] ) ) {
				continue;
			}
			if ( $rule['value_type'] === self::VALUE_FORMATTED ) {
				return true;
			}
			if ( is_array( $rule['value_type'] ) && in_array( self::VALUE_FORMATTED, $rule['value_type'], true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Cell $cell
	 * @param string $valueType
	 * @param string $dateFormat
	 *
	 * @return float|int|mixed|string
	 * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
	 */
	private function readCellData(
		Cell $cell,
		string $valueType = self::VALUE_CALCULATED,
		string $dateFormat = 'd-m-Y'
	) {
		switch ( strtolower( $valueType ) ) {
			case self::VALUE_RAW:
				return $cell->getValue() ?? '';

			case self::VALUE_FORMATTED:
				// check if cell is Excel-date/time
				if ( ExcelDate::isDateTime( $cell ) && is_numeric( $cell->getCalculatedValue() ) ) {
					$val = (float)$cell->getCalculatedValue();
					if ( $val >= 1 ) {
						return ExcelDate::excelToDateTimeObject( $val )->format( $dateFormat );
					}
				}
				return $cell->getFormattedValue() ?? '';

			case self::VALUE_CALCULATED:
			default:
				return $cell->getCalculatedValue() ?? '';
		}
	}

	/**
	 * @param Worksheet $sheet
	 * @param int $col
	 * @param int $row
	 * @param string $valueType
	 *
	 * @return mixed|string
	 * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function getCellValueByColRow(
		Worksheet $sheet,
		int $col,
		int $row,
		string $valueType = self::VALUE_CALCULATED
	): mixed {
		$cellAddress = Coordinate::stringFromColumnIndex( $col ) . $row;
		$cell = $sheet->getCell( $cellAddress );

		return $this->readCellData( $cell, $valueType );
	}

	/**
	 * Single Cell: { "type": "cell", "row": 3, "col": 5, "value_type": "formatted" } (or "cell": "E3")
	 *
	 * @param Worksheet $sheet
	 * @param array $rule
	 *
	 * @return mixed|string
	 * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function extractCell( Worksheet $sheet, array $rule ): mixed {
		$valueType = is_string( $rule['value_type'] ?? null ) ? $rule['value_type'] : self::VALUE_CALCULATED;

		if ( isset( $rule['cell'] ) ) {
			$cell = $sheet->getCell( (string)$rule['cell'] );

			return $this->readCellData( $cell, $valueType );
		}

		$col = $this->normalizeColIndex( $rule['col'] ?? 1 );
		$row = (int)( $rule['row'] ?? 1 );

		return $this->getCellValueByColRow( $sheet, $col, $row, $valueType );
	}

	/**
	 * 1D Array: { "type": "list", "row_start": 20, "row_end": 25, "col": 3, "value_type": "calculated" }
	 * Or specific rows: { "type": "list", "rows": [20, 22, 25], "col": 3 }
	 *
	 * @param Worksheet $sheet
	 * @param array $rule
	 *
	 * @return array
	 * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function extractList( Worksheet $sheet, array $rule ): array {
		$col       = $this->normalizeColIndex( $rule['col'] ?? 1 );
		$skipEmpty = $rule['skip_empty'] ?? true;
		$valueType = is_string( $rule['value_type'] ?? null ) ? $rule['value_type'] : self::VALUE_CALCULATED;
		$rows      = $this->resolveTargetRows( $sheet, $rule, 1 );

		$list = [];
		foreach ( $rows as $r ) {
			$val = $this->getCellValueByColRow( $sheet, $col, $r, $valueType );
			if ( $skipEmpty && $val === '' ) {
				continue;
			}
			$list[] = $val;
		}

		return $list;
	}

	/**
	 * Key-Value: { "type": "key_value", "key_col": "A", "val_col": "B", "row_start": 5, "row_end": 10 }
	 * Or with specific rows: { "type": "key_value", "key_col": "D", "val_col": "E", "rows": [5, 6, 7, 12] }
	 *
	 * @param Worksheet $sheet
	 * @param array $rule
	 *
	 * @return array
	 * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function extractKeyValue( Worksheet $sheet, array $rule ): array {
		$keyCol    = $this->normalizeColIndex( $rule['key_col'] ?? 1 );
		$valCol    = $this->normalizeColIndex( $rule['val_col'] ?? 2 );
		$valueType = is_string( $rule['value_type'] ?? null ) ? $rule['value_type'] : self::VALUE_CALCULATED;
		$rows      = $this->resolveTargetRows( $sheet, $rule, 1 );

		$kv = [];
		foreach ( $rows as $r ) {
			// Keys always as calculated/formatted
			$key = trim( (string)$this->getCellValueByColRow( $sheet, $keyCol, $r, self::VALUE_CALCULATED ) );
			if ( $key !== '' ) {
				$kv[$key] = $this->getCellValueByColRow( $sheet, $valCol, $r, $valueType );
			}
		}

		return $kv;
	}

	/**
	 * What rows to read? A specific array with rows or a range
	 *
	 * @param Worksheet $sheet
	 * @param array $rule
	 * @param int $defaultStart
	 * @return array<int>
	 */
	private function resolveTargetRows( Worksheet $sheet, array $rule, int $defaultStart = 1 ): array {
		if ( !empty( $rule['rows'] ) && is_array( $rule['rows'] ) ) {
			return array_map( 'intval', $rule['rows'] );
		}

		$rowStart = (int)( $rule['row_start'] ?? $defaultStart );
		$rowEnd   = isset( $rule['row_end'] ) ? (int)$rule['row_end'] : $sheet->getHighestDataRow();

		if ( $rowEnd < $rowStart ) {
			return [];
		}

		return range( $rowStart, $rowEnd );
	}

	/**
	 * Table with headers: { "type": "table", "header_row": 15, "row_start": 16, "row_end": 24 }
	 *
	 * @param Worksheet $sheet
	 * @param array $rule
	 *
	 * @return array
	 * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function extractTable( Worksheet $sheet, array $rule ): array {
		$headerRow = (int)( $rule['header_row'] ?? 1 );
		$rowStart = (int)( $rule['row_start'] ?? ( $headerRow + 1 ) );
		$rowEnd = isset( $rule['row_end'] ) ? (int)$rule['row_end'] : $sheet->getHighestRow();
		$colStart = $this->normalizeColIndex( $rule['col_start'] ?? 1 );
		$colEnd = isset( $rule['col_end'] ) ? $this->normalizeColIndex( $rule['col_end'] )
			: Coordinate::columnIndexFromString( $sheet->getHighestColumn( $headerRow ) );

		// Get headers from $headerRow
		$headers = [];
		for ( $c = $colStart; $c <= $colEnd; $c++ ) {
			$hVal = trim( (string)$this->getCellValueByColRow( $sheet, $c, $headerRow, self::VALUE_CALCULATED ) );
			if ( $hVal !== '' ) {
				$headers[$c] = $hVal;
			}
		}

		// Read Data rows
		$table = [];
		for ( $r = $rowStart; $r <= $rowEnd; $r++ ) {
			$rowData = [];
			$hasValue = false;

			foreach ( $headers as $colIndex => $headerName ) {
				$colValueType = $this->resolveTableColumnValueType( $rule, $headerName );
				$val = $this->getCellValueByColRow( $sheet, $colIndex, $r, $colValueType );

				if ( $val !== '' ) {
					$rowData[$headerName] = $val;
					$hasValue = true;
				}
			}

			if ( !$hasValue ) {
				if ( !empty( $rule['stop_on_empty_row'] ) ) {
					break;
				}
				continue;
			}

			$table[] = $rowData;
		}

		return $table;
	}

	/**
	 * @param array $rule
	 * @param string $headerName
	 *
	 * @return string
	 */
	private function resolveTableColumnValueType( array $rule, string $headerName ): string {
		if ( !isset( $rule['value_type'] ) ) {
			return self::VALUE_CALCULATED;
		}
		if ( is_string( $rule['value_type'] ) ) {
			return $rule['value_type'];
		}
		if ( is_array( $rule['value_type'] ) ) {
			return $rule['value_type'][$headerName] ?? $rule['value_type']['default'] ?? self::VALUE_CALCULATED;
		}

		return self::VALUE_CALCULATED;
	}

	/**
	 * @param string $mappingInput
	 *
	 * @return array
	 * @throws FlexFormException
	 */
	private function resolveRules( string $mappingInput ): array {
		$mappingInput = trim( $mappingInput );

		if ( !str_starts_with( $mappingInput, '{' ) ) {
			$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $mappingInput );
			if ( $title !== null && $title->exists() ) {
				$wikiPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
				$content = $wikiPage->getContent();
				if ( $content instanceof TextContent ) {
					$mappingInput = $content->getText();
				}
			}
		}

		$decoded = json_decode( $mappingInput, true );
		if ( json_last_error() !== JSON_ERROR_NONE || !is_array( $decoded ) ) {
			throw new FlexFormException( 'Invalid JSON in excel_mapping: ' . json_last_error_msg() );
		}

		return $decoded;
	}

	/**
	 * @param Spreadsheet $spreadsheet
	 * @param array $rule
	 *
	 * @return Worksheet
	 * @throws FlexFormException
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function resolveWorksheet( Spreadsheet $spreadsheet, array $rule ): Worksheet {
		$worksheet = null;

		if ( !empty( $rule['sheet_by_name'] ) ) {
			$worksheet = $spreadsheet->getSheetByName( (string)$rule['sheet_by_name'] );
		} elseif ( isset( $rule['sheet_by_id'] ) ) {
			$worksheet = $spreadsheet->getSheet( (int)$rule['sheet_by_id'] );
		} elseif ( $this->sheetByName !== false ) {
			$worksheet = $spreadsheet->getSheetByName( (string)$this->sheetByName );
		} elseif ( $this->sheetById !== false ) {
			$worksheet = $spreadsheet->getSheet( (int)$this->sheetById );
		}

		if ( $worksheet === null ) {
			throw new FlexFormException(
				wfMessage( 'flexform-fileupload-file-convert-excel-not-found' ), 0
			);
		}

		return $worksheet;
	}

	/**
	 * convert column letters ('E') or numbers (5) to 1-based column index (5)
	 * @param mixed $col
	 *
	 * @return int
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function normalizeColIndex( mixed $col ): int {
		if ( is_numeric( $col ) ) {
			return (int)$col;
		}

		return Coordinate::columnIndexFromString( (string)$col );
	}
}
