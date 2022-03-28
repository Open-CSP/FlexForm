<?php

namespace FlexForm\Processors\Content;

use FlexForm\Core\HandleResponse;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\Processors;
use FlexForm\Processors\Utilities\General;

class CreateUser {

	private $userName;
	private $emailAddress;
	private $additionalArgument;

	/**
	 * @param array $fields
	 *
	 * @return HandleResponse
	 */
	public function __construct() {
		$fields    = ContentCore::getFields();
		$explodedContent = explode( '-^^-', $fields['createuser'] );
		if ( isset( $explodedContent[0] ) && isset( $explodedContent[1] ) ) {
			$this->userName     = $explodedContent[0];
			$this->emailAddress = $explodedContent[1];
		}
	}
}