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
	 * @param array $formInfo
	 * @param bool|int $pid
	 *
	 * @return string
	 */
	private function renderTable( array $formInfo, $pid ): string {
		$table  = '<h2>Managed approved forms</h2><br>';
		if ( $pid !== false ) {
			$table .= '<div class="uk-alert-success" uk-alert>';
			$table .= '<a class="uk-alert-close" uk-close></a>';
			$table .= '<p>Successfully delete approved form(s) from page <strong>'.$this->getTitleFromId( $pid ).'</strong>';
			$table .= ' ( PageID: '. $pid .' )</p></div>';
		}
		$table .= '<table class="uk-table uk-table-small uk-table-divider uk-table-middle">' . PHP_EOL;
		$table .= '<caption>There are ' . count( $formInfo ) . ' Pages with approved Forms</caption>' . PHP_EOL;
		$table .= '<thead><tr><th>Page ID</th><th>Page Title</th><th>Nr of Forms</th><th class="uk-text-center">Action</th></tr></thead>';
		$table .= PHP_EOL . '<tbody>' . PHP_EOL;
		$counter = 0;
		$formHeader = '<form style="display:inline-block;" method="post">';
		foreach ( $formInfo as $id=>$count ) {
			$form = $formHeader . '<input type="hidden" name="pId" value="' . $id . '">';
			$form .= '<button style="border:none;" type="submit" class="uk-button uk-button-default ff-del"><span class="uk-icon-button" uk-icon="minus-circle" title="delete"></span></button></form> ';
			$counter = $counter + $count;
			$table .= '<tr>' . PHP_EOL;
			$table .= '<td>' . $id. '</td>';
			$table .= '<td>' . $this->getTitleFromId( $id ). '</td>';
			$table .= '<td>' . $count . '</td><td class="uk-text-center">' . $form . '</td></tr>' . PHP_EOL;
		}
		$table .= '</tbody>' . PHP_EOL;
		$table .= '<tfoot><tr><td></td><td></td><td>Total of ' . $counter . ' approved forms</td></tr></tfoot>';
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
			foreach( $formTags as $k=>$singleForm ) {
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
	 * @return int[]|string[]
	 */
	private function getNamespaces() {
		$canonical            = MediaWikiServices::getInstance()->getNamespaceInfo()->getCanonicalNamespaces();
		$canonical[ NS_MAIN ] = "_";

		return array_flip( $canonical );
	}
}