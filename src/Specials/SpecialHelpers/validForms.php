<?php
/**
 * Created by  : Wikibase Solutions BV
 * Project     : MWWSForm
 * Filename    : validForms.php
 * Description :
 * Date        : 19-12-2022
 * Time        : 21:07
 */

namespace FlexForm\Specials\SpecialHelpers;

use FlexForm\Core\Sql;
use FlexForm\Processors\Content\Render;
use MediaWiki\MediaWikiServices;
use MWNamespace;
use NamespaceInfo;
use Title;
use Wikimedia\Rdbms\IResultWrapper;
use WikiPage;

class validForms {

	/**
	 * @var string
	 */
	private string $homeUrl;

	/**
	 * @var string
	 */
	private array $uiKit;

	/**
	 * @param string $realUrl
	 */
	public function __construct( string $realUrl ) {
		$flexFormInstallUrl = $realUrl . "/extensions/FlexForm/";
		$this->homeUrl = $realUrl . "/index.php/Special:FlexForm";
		$this->uiKit['css'] = $flexFormInstallUrl . 'Modules/uikit/css/uikit.min.css';
		$this->uiKit['jsDefault'] = $flexFormInstallUrl . 'Modules/uikit/js/uikit.min.js';
		$this->uiKit['jsIcons'] = $flexFormInstallUrl . 'Modules/uikit/js/uikit-icons.min.js';
	}

	/**
	 * @return string
	 */
	public function addResources(): string {
		$html = '<!-- UIkit CSS -->' . PHP_EOL;
		$html .= '<link rel="stylesheet" href="' . $this->uiKit['css'] . '" />';
		$html .= PHP_EOL;
		$html .= '<!-- UIkit JS -->' . PHP_EOL;
		$html .= '<script src="' . $this->uiKit['jsDefault'] . '"></script>';
		$html .= PHP_EOL;
		$html .= '<script src="' . $this->uiKit['jsIcons'] . '"></script>';
		$html .= PHP_EOL;
		return $html;
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	private function getTitleFromId( int $id ): string {
		$page = WikiPage::newFromId( $id );
		if ( $page === false || $page === null ) {
			return "invalid page";
		}
		return $page->getTitle()->getFullText();
	}

	/**
	 * @param string $title
	 *
	 * @return string
	 */
	private function makeLinkFromTitle( string $title ): string {
		global $wgScript;
		return '<a target="_blank" href="'. $wgScript . '/' . $title . '">' . $title . '</a>';
	}

	/**
	 * @param array $formInfo
	 * @param bool|int $pid
	 *
	 * @return string
	 */
	private function renderTable( array $formInfo, $pid ): string {
		global $wgScript;
		$title = wfMessage( 'flexform-validforms-valid-list-title' )->text();
		if ( $pid !== false ) {
			$alert = '<div class="uk-alert-success" uk-alert>';
			$alert .= '<a class="uk-alert-close" uk-close></a><p>';
			$alert .= wfMessage(
				'flexform-validforms-valid-list-action-response-delete',
				$this->getTitleFromId( $pid ),
				$pid
			)->text();
			$alert .= '</p></div>';
		} else {
			$alert = null;
		}
		$caption = wfMessage( 'flexform-validforms-valid-list-caption', count( $formInfo ) );
		$headers = [];
		$h1 = wfMessage( 'flexform-validforms-valid-list-table-page-id' )->text();
		$h2 = wfMessage( 'flexform-validforms-valid-list-table-page-title' )->text();
		$h3 = wfMessage( 'flexform-validforms-valid-list-table-nr-forms' )->text();
		$h4 = wfMessage( 'flexform-validforms-valid-list-table-action' )->text();
		$headers[$h1] = false;
		$headers[$h2] = false;
		$headers[$h3] = 'uk-text-center';
		$headers[$h4] = 'uk-text-center';
		$counter = 0;
		$rowCount = 0;
		$formHeader = '<form style="display:inline-block;" method="post">';
		$data = [];
		foreach ( $formInfo as $id => $count ) {
			$form = $formHeader . '<input type="hidden" name="pId" value="' . $id . '">';
			$form .= $this->renderGenericBtn(
				'',
				'minus-circle',
				wfMessage( 'flexform-validforms-invalid-list-action-delete' )->text(),
				''
			);
			$form .= '</form> ';
			$counter = $counter + $count;
			$data[$rowCount] = [];
			$data[$rowCount][0]['value'] = $id;
			$data[$rowCount][0]['class'] = false;
			$tTitle = $this->getTitleFromId( $id );
			$data[$rowCount][1]['value'] = $this->makeLinkFromTitle( $tTitle );
			$data[$rowCount][1]['class'] = false;
			$data[$rowCount][2]['value'] = $count;
			$data[$rowCount][2]['class'] ='uk-text-center';
			$data[$rowCount][3]['value'] = $form;
			$data[$rowCount][3]['class'] = 'uk-text-center';
			$rowCount++;
		}
		$footer = [];
		$footer[0] = '';
		$footer[1] = '';
		$footer[2] = '';
		$footer[3] = wfMessage( 'flexform-validforms-valid-list-footer', $counter )->text();
		return $this->renderDefaultTable( $title, $caption, $headers, $data, $footer, $alert );
	}

	/**
	 * @param string|null $title
	 * @param string|null $caption
	 * @param array|null $headers
	 * @param array $data
	 * @param array|null $footer
	 * @param string|null $alert
	 *
	 * @return string
	 */
	public function renderDefaultTable(
		?string $title,
		?string $caption,
		?array $headers,
		array $data,
		?array $footer,
		?string $alert
	) : string {
		$table = '';
		if ( $title !== null ) {
			$table .= '<h2>' . $title . '</h2><br>';
		}
		if ( $alert !== null ) {
			$table .= $alert;
		}
		$table .= '<table class="uk-table uk-table-small uk-table-divider uk-table-middle">' . PHP_EOL;
		if ( $caption !== null ) {
			$table .= '<caption>' . $caption . '</caption>' . PHP_EOL;
		}
		if ( $headers !== null ) {
			$table .= '<thead><tr>';
			foreach ( $headers as $header => $class ) {
				if ( $class !== false ) {
					$table .= '<th class="' . $class . '">';
				} else {
					$table .= '<th>';
				}
				$table .= $header . '</th>';
			}
			$table .= '</tr></thead>' . PHP_EOL;
		}
		$table .= '<tbody>' . PHP_EOL;
		foreach ( $data as $row ) {
			$table .= '<tr>' . PHP_EOL;
			foreach ( $row as $dt ) {
				if ( $dt['class'] !== false ) {
					$table .= '<td class="' . $dt['class'] . '">';
				} else {
					$table .= '<td>';
				}
				$table .= $dt['value'] . '</td>';
			}
			$table .= '</tr>' . PHP_EOL;
		}
		$table .= '</tbody>' . PHP_EOL;
		if ( $footer !== null ) {
			$table .= '<tfoot><tr>';
			foreach ( $footer as $column ) {
				$table .= '<td>' . $column . '</td>';
			}
			$table .= '</tr></tfoot>';
		}
		$table .= PHP_EOL . '</table>' . PHP_EOL;

		return $table;
	}

	/**
	 * @param $pid
	 *
	 * @return string
	 */
	public function renderApprovedFormsInformation( $pid = false ): string {
		$formInfo = Sql::getAllApprovedForms();
		return $this->renderTable( $formInfo, $pid );
	}

	/**
	 * @param string $search
	 *
	 * @return IResultWrapper
	 */
	public function doSearchQuery( string $search ): IResultWrapper {
		$namespaces = $this->getNamespaces();
		$dbr = wfGetDB( DB_REPLICA );
		$tables = [ 'page', 'revision', 'text', 'slots', 'content' ];
		$vars = [ 'page_id', 'page_namespace', 'page_title', 'old_text' ];
		$any = $dbr->anyString();
		$comparisonCond = 'old_text ' . $dbr->buildLike( $any, $search, $any );
		$conds = [
			$comparisonCond,
			'page_namespace' => $namespaces,
			'rev_id = page_latest',
			'rev_id = slot_revision_id',
			'slot_content_id = content_id',
			$dbr->buildIntegerCast( 'SUBSTR(content_address, 4)' ) . ' = old_id'
		];

		$options = [
			'ORDER BY' => 'page_namespace, page_title',
			'LIMIT' => 1000
		];

		return $dbr->select( $tables, $vars, $conds, __METHOD__, $options );
	}

	/**
	 * @param IResultWrapper $res
	 * @param string $name
	 *
	 * @return array
	 */
	public function getTitlesArray( IResultWrapper $res, string $name ): array {
		$ret = [];
		$t = 0;
		foreach ( $res as $row ) {
			$title = Title::makeTitleSafe( $row->page_namespace, $row->page_title );
			if ( $title == null ) {
				continue;
			}
			$content = $row->old_text;
			$formTags = sql::getAllFormTags( $content, $name );
			$hashes = [];
			$id = $title->getArticleID();
			$ret[$t]['title'] = $title->getFullText();
			$ret[$t]['id'] = $id;
			$ret[$t]['tag'] = $name;
			$ret[$t]['numberOfForms'] = count( $formTags );
			foreach ( $formTags as $k => $singleForm ) {
				$hash = sql::createHash( trim( $singleForm ) );
				if ( !sql::exists( $id, $hash ) ) {
					$ret[$t]['forms'][$k]['tag'] = $name;
					$ret[$t]['forms'][$k]['isValid'] = "no";
				} else {
					$ret[$t]['forms'][$k]['tag'] = $name;
					$ret[$t]['forms'][$k]['isValid'] = "yes";
				}
			}
			$t++;
		}
		return $ret;
	}

	/**
	 * @param $arr
	 * @param string $col
	 * @param int $dir
	 *
	 */
	public function arraySortByColumn( &$arr, string $col, int $dir = SORT_ASC ) {
		$sort_col = [];
		foreach ( $arr as $key => $row ) {
			$sort_col[$key] = $row[$col];
		}
		array_multisort( $sort_col, $dir, $arr );
	}

	public function renderAllFormsInWiki( $formsData ) {
		$headers = [];
		$headers['#'] = false;
		//$headers['Validated'] = 'uk-text-center';
		$h1 = wfMessage( 'flexform-validforms-valid-list-table-page-id' )->text();
		$h2 = wfMessage( 'flexform-validforms-valid-list-table-page-title' )->text();
		$h3 = wfMessage( 'flexform-validforms-valid-list-table-tag_used' )->text();
		$h4 = wfMessage( 'flexform-validforms-valid-list-table-nr-forms' )->text();
		$h5 = wfMessage( 'flexform-validforms-valid-list-table-action' )->text();
		$headers[$h1] = false;
		$headers[$h2] = false;
		$headers[$h3] = 'uk-text-center';
		$headers[$h4] = 'uk-text-center';
		$headers[$h5] = 'uk-text-center';
		$title = wfMessage( 'flexform-validforms-invalid-list-title' )->text();
		$caption = wfMessage( 'flexform-validforms-invalid-list-caption', count( $formsData ) )->text();
		$data = [];
		$count = 1;
		$foundNrOfForms = 0;
		$formHeader = '<form style="display:inline-block;" method="post">';
		$pids = [];
		$pidsForm = [];
		foreach ( $formsData as $k => $pageInfo ) {
			$validated = true;
			foreach ( $pageInfo['forms'] as $formsInfo ) {
				if ( $formsInfo['isValid'] === 'no' ) {
					$validated = false;
				}
			}
			if ( $validated ) {
				//$foundNrOfForms = $foundNrOfForms + $pageInfo['numberOfForms'];
				//$count++;
				continue;
			}
			$pids[] = $pageInfo['id'];
			$formUnvalidate = $formHeader . '<input type="hidden" name="pId" value="' . $pageInfo['id'] . '">';
			$formUnvalidate .= $this->renderGenericBtn(
				'',
				'minus-circle',
				wfMessage( 'flexform-validforms-invalid-list-action-delete' )->text(),
				''
			);
			$formUnvalidate .= '</form> ';
			$formValidate = $formHeader . '<input type="hidden" name="pIdA" value="' . $pageInfo['id'] . '">';
			$formValidate .= $this->renderGenericBtn(
				'',
				'plus-circle',
				wfMessage( 'flexform-validforms-invalid-list-action-validate' )->text(),
				''
			);
			$formValidate .= '</form> ';
			$data[$k][0]['value'] = $count;

			$data[$k][0]['class'] = false;

			/*
			if ( $validated ) {
				$data[$k][1]['value'] = '<span class="uk-margin-small-right uk-text-success" uk-icon="check"></span>';
			} else {
				$data[$k][1]['value'] = '<span class="uk-margin-small-right uk-text-danger" uk-icon="ban"></span>';
			}
			$data[$k][1]['class'] = 'uk-text-center';
			*/
			$data[$k][1]['value'] = $pageInfo['id'];
			$data[$k][1]['class'] = false;
			$data[$k][2]['value'] = $this->makeLinkFromTitle( $pageInfo['title'] );
			$data[$k][2]['class'] = false;
			if ( $pageInfo['tag'] !== 'form' ) {
				$extraClass = ' uk-background-muted uk-text-danger';
			} else {
				$pidsForm[] = $pageInfo['id'];
				$extraClass = '';
			}
			$data[$k][3]['value'] = '<span class="uk-badge' . $extraClass . '">' . $pageInfo['tag'] . '</span>';
			$data[$k][3]['class'] = 'uk-text-center';
			$data[$k][4]['value'] = $pageInfo['numberOfForms'];
			$data[$k][4]['class'] = 'uk-text-center';
			if ( $validated ) {
				$data[$k][5]['value'] = $formUnvalidate;
			} else {
				$data[$k][5]['value'] = $formValidate;
			}
			$data[$k][5]['class'] = 'uk-text-center';
			$foundNrOfForms = $foundNrOfForms + $pageInfo['numberOfForms'];
			$count++;
		}
		$footer = [];
		$footer[0] = '';
		$footer[1] = '';
		$footer[2] = '';
		$footer[3] = '';
		$footer[4] = '';
		$footer[5] = wfMessage( 'flexform-validforms-invalid-list-footer', $foundNrOfForms, ( $count - 1 ) )->text();
		$footer[5] .= '<br>';
		$footer[5] .= $formHeader;
		$footer[5] .= '<input type="hidden" name="pIdAll" value="' . json_encode( $pids ) . '">';
		$footer[5] .= $this->renderGenericBtn(
			wfMessage( 'flexform-validforms-invalid-list-action-validate-all' )->text(),
			'check',
			wfMessage( 'flexform-validforms-invalid-list-action-validate' )->text(),
			'uk-text-danger'
		);
		$footer[5] .= '</form> ';
		$footer[5] .= $formHeader;
		$footer[5] .= '<input type="hidden" name="pIdAllOnlyForm" value="' . json_encode( $pidsForm ) . '">';
		$footer[5] .= $this->renderGenericBtn(
			wfMessage( 'flexform-validforms-invalid-list-action-validate-form' )->text(),
			'check',
			wfMessage( 'flexform-validforms-invalid-list-action-validate' )->text(),
			'uk-text-success'
		);
		$footer[5] .= '</form> ';

		return $this->renderDefaultTable( $title, $caption, $headers, $data, $footer, null );
	}

	/**
	 * @param string $text
	 * @param string $icon
	 * @param string $title
	 * @param string $class
	 *
	 * @return string
	 */
	private function renderGenericBtn(
		string $text = '',
		string $icon = '',
		string $title = '',
		string $class = ''
	) : string {
		$ret = '<button style="border:none;" type="submit" class="uk-button uk-button-default ff-del">';
		$ret .= '<span class="uk-icon-button ' . $class . '" uk-icon="' . $icon . '" title="' . $title . '"></span>';

		if ( $text !== '' ) {
			$ret .= ' ' . $text;
		}
		$ret .= '</button>';

		return $ret;
	}

	/**
	 * @return int[]|string[]
	 */
	private function getNamespaces() {
		$canonical            = MediaWikiServices::getInstance()->getNamespaceInfo()->getCanonicalNamespaces();
		$canonical[ NS_MAIN ] = "_";

		return array_flip( $canonical );
	}
}