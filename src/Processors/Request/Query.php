<?php
/**
 * Created by  : OpenCSP
 * Project     : FlexForm
 * Filename    : external.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 13:21
 */


namespace FlexForm\Processors\Request;

use FlexForm\Core\Protect;
use FlexForm\Processors\Utilities\General;


/**
 * Class wsSecurity
 * <p>Handles requests</p>
 *
 * @package FlexForm\Processors\Request
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
			$extensionsFolder = $IP . "/extensions/FlexForm/Modules/handlers/queries/";
			if ( file_exists( $extensionsFolder . $external . '/query-handler.php' ) ) {
				include_once( $extensionsFolder . $external . '/query-handler.php' );
			} else {
				$ret = json_encode( createMsg( $i18n->wsMessage( 'flexform-query-handler-not-found' ) ) );
			}
		} else {
			$ret = json_encode( createMsg( $i18n->wsMessage( 'flexform-query-handler-not-found' ) ) );
		}
		return $ret;
	}


}