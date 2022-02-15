<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : Get.php
 * Description :
 * Date        : 28-1-2022
 * Time        : 13:54
 */

namespace FlexForm\Processors\Content;

use FlexForm\Core\HandleResponse;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\Processors;
use FlexForm\Processors\Utilities\General;

class Get {

	/**
	 * @param HandleResponse $responseHandler
	 *
	 * @return HandleResponse
	 */
	public function createGet( HandleResponse $responseHandler ): HandleResponse {
		$removeList = wsSecurity::getRemoveList();
		$returnto = $responseHandler->getMwReturn();
		if ( $returnto ) {
			$urlParsed = parse_url( $returnto );
			if( isset( $urlParsed['fragment'] ) ) {
				$fragment = '#' . $urlParsed['fragment'];
			} else $fragment = '';
			$ret = $returnto;
			foreach ( $_POST as $k => $v ) {
				if ( strpos( $ret, "?" ) ) {
					$delimiter = '&';
				} else {
					$delimiter = '?';
				}
				if ( is_array( $v ) ) {
					if( !Definitions::isWSFormSystemField( $k ) ) {
						$ret .= $delimiter . General::makeSpaceFromUnderscore( $k ) . "=";
						foreach ( $v as $multiple ) {
							$ret .= wsSecurity::cleanHTML( wsSecurity::cleanBraces( $multiple ) ) . ',';
						}
						$ret = rtrim( $ret,
									  ',' );
					}
				} else {
					$resultDelete = in_array( $k, $removeList );
					if ( $k !== "mwreturn" &&
						 $v != "" &&
						 $k !== 'mwdb' &&
						 ( Definitions::isWSFormSystemField( $k ) === false ) &&
						 !$resultDelete
					) {

						$html = wsSecurity::getHTMLType( $k );
						if( $html !== "all" ) {
							$ret .= $delimiter . General::makeSpaceFromUnderscore( $k ) . '=' . wsSecurity::cleanUrl( General::getPostString( $k ) );
						} else {
							$ret .= $delimiter . General::makeSpaceFromUnderscore( $k ) . '=' . General::getPostString( $k );
						}
					}
				}
			}

			$responseHandler->setMwReturn( $ret . $fragment );
		}
		return $responseHandler;
	}
}