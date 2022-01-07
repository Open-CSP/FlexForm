<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : WSForm
 * Filename    : external.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 13:21
 */


namespace WSForm\Processors\Request;

use WSForm\Core\Protect;
use WSForm\Processors\Utilities\General;


/**
 * Class wsSecurity
 * <p>Handles requests</p>
 *
 * @package wsform\processors\request
 */
class Query {

	/**
	 * @return string|false
	 */
	public static function handle() {
		global $IP;
		$i18n     = new wsi18n();
		$external = wsUtilities::getGetString( 'handler' );
		if ( $external !== false ) {
			$extensionsFolder = $IP . "/extensions/WSForm/modules/handlers/queries/";
			if ( file_exists( $extensionsFolder . $external . '/query-handler.php' ) ) {
				include_once( $extensionsFolder . $external . '/query-handler.php' );
			} else {
				$ret = json_encode( createMsg( $i18n->wsMessage( 'wsform-query-handler-not-found' ) ) );
			}
		} else {
			$ret = json_encode( createMsg( $i18n->wsMessage( 'wsform-query-handler-not-found' ) ) );
		}
		return $ret;
	}


}