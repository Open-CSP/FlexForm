<?php
/**
 * Overview for the WSForm extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialWSForm extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WSForm' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:HelloWorld/subpage]].
	 */
	public function execute( $sub ) {
	    global $IP;
		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'WSForm-title' ) );


		$out->addWikiMsg( 'WSForm-intro' );
		// US States


	}

	static function trySubmit( $formData ) {
		if ( $formData['myfield1'] == 'Fleep' ) {
			return true;
		}

		return 'HAHA FAIL';
	}

	protected function getGroupName() {
		return 'other';
	}
}
