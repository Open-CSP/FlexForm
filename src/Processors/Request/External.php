<?php
/**
 * Created by  : Designburo.nl
 * Project     : wsformWikiBaseNL
 * Filename    : external.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 13:21
 */


namespace WSForm\Processors\Request;

use WSForm\Core\Protect;
use WSForm\Processors\Utilities\General;



/**
 * Class external
 * <p>Handles external request</p>
 *
 * @package wsform\processors\request
 */
class External {

	/**
	 * @param $api
	 *
	 * @return array
	 */
	public static function handle(): array {
		global $IP;
		$i18n     = new wsi18n();
		$message  = new wbHandleResponses( api::isAjax() );
		$external = wsUtilities::getGetString( 'script' );
		if ( $external !== false ) {
			if ( file_exists( $IP . '/extensions/WSForm/modules/handlers/' . basename( $external ) . '.php' ) ) {
				include_once( $IP . '/extensions/WSForm/modules/handlers/' . basename( $external ) . '.php' );
			} else {
				$ret = $message->createMsg( $i18n->wsMessage( 'wsform-external-request-not-found' ) );
			}
		} else {
			$ret = $message->createMsg( $i18n->wsMessage( 'wsform-external-request-not-found' ) );
		}
		return $ret;
	}


}