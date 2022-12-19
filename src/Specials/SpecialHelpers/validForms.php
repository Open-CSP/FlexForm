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
	private function addResources(): string {
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
	 *
	 * @return string
	 */
	private function renderTable( array $formInfo ): string {
		$table = '<table class="uk-table uk-table-small uk-table-divider">' . PHP_EOL;
		$table .= '<caption>There are ' . count( $formInfo ) . ' Pages with approved Forms</caption>' . PHP_EOL;
		$table .= '<thead><tr><th>Page ID</th><th>Page Title</th><th>Nr of Forms</th></tr></thead>' . PHP_EOL;
		$table .= '<tbody>' . PHP_EOL;
		$counter = 0;
		foreach ( $formInfo as $id=>$count ) {
			$counter = $counter + $count;
			$table .= '<tr>' . PHP_EOL;
			$table .='<td>' . $id. '</td>';
			$table .='<td>' . $this->getTitleFromId( $id ). '</td>';
			$table .='<td>' . $count . '</td></tr>' . PHP_EOL;
		}
		$table .= '</tbody>' . PHP_EOL;
		$table .= '<tfoot><tr><td></td><td></td><td>Total of ' . $counter . ' approved forms</td></tr></tfoot>';
		$table .= PHP_EOL . '</table>' . PHP_EOL;
		return $table;
	}

	/**
	 * @return string
	 */
	public function renderApprovedFormsInformation(): string {
		$html = $this->addResources();
		$formInfo = Sql::getAllApprovedForms();
		$html .= $this->renderTable( $formInfo );
		return $html;
	}
}