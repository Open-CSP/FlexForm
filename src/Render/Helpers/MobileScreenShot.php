<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : MobileScreenShot.php
 * Description :
 * Date        : 14-2-2023
 * Time        : 15:16
 */

namespace FlexForm\Render\Helpers;

use FlexForm\Core\Validate;
use FlexForm\Processors\Utilities\General;

class MobileScreenShot {

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function getFields( array $args ): array {
		$returnArguments = [];
		$validFileMobileParameters = Validate::validFileMobileScreenshotParameters( '', true );
		foreach ( $validFileMobileParameters as $parameter ) {
			$value = General::getArgs( $parameter, $args );
			switch ( $parameter ) {
				case "capture-button-class":
				case "video-class" :
					$returnArguments[$parameter] = ( $value !== false ) ? $value : "";
					break;
				case "preview-width" :
					$returnArguments[$parameter] = ( $value !== false ) ? $value : 320;
					break;
				case "preview-height" :
					$returnArguments[$parameter] = ( $value !== false ) ? $value : 250;
					break;
				case "capture-button-text" :
					$returnArguments[$parameter] = ( $value !== false ) ? $value : "Capture";
					break;
			}

		}
		$returnArguments['video-id'] = $args['id'] . "-ff-ms-player";
		$returnArguments['button-id'] = $args['id'] . "-ff-ms-btn";
		$returnArguments['canvas-id'] = $args['id'] . "-ff-ms-canvas";
		return $returnArguments;
	}
}