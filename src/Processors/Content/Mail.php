<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWWSForm
 * Filename    : Mail.php
 * Description :
 * Date        : 28-1-2022
 * Time        : 20:34
 */

namespace WSForm\Processors\Content;

use WSForm\Processors\Definitions;

/**
 * Class for mailings
 */
class Mail {

	/**
	 * @var array
	 */
	private $fields = [];

	/**
	 * @var false|string
	 */
	private $template = false;

	/**
	 * @return false|mixed|string
	 */
	public function getTemplate() {
		return $this->template;
	}

	public function __construct() {
		$this->fields = Definitions::mailFields();
		$this->template = $this->fields['mtemplate'];
	}

	public function handleTemplate(){
		$fields = ContentCore::getFields();
		if( $fields['parseLast'] === false ) {

		}
	}
}
