<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : WSForm
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
			$extensionsFolder = $IP . "/extensions/WSForm/Modules/handlers/queries/";
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