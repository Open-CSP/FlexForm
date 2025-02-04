<?php
/**
 * Created by  : Designburo.nl
 * Project     : FlexForm
 * Filename    : MobileScreenShot.php
 * Description :
 * Date        : 14-2-2023
 * Time        : 15:16
 */

namespace FlexForm\Render\Helpers;

use FlexForm\Core\Core;
use FlexForm\Core\Validate;
use FlexForm\Processors\Utilities\General;

class MobileScreenShot {

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	private static function getFields( array $args ): array {
		$returnArguments = [];
		$validFileMobileParameters = Validate::validFileMobileScreenshotParameters( '', true );
		foreach ( $validFileMobileParameters as $parameter ) {
			$value = General::getArgs( $parameter, $args );
			switch ( $parameter ) {
				case "capture-button-class":
					$value = General::getArgs( $parameter, $args, false );
					$returnArguments[$parameter] = ( $value !== false ) ? $value : "ff-ms-btn";
					break;
				case "video-class" :
					$value = General::getArgs( $parameter, $args, false );
					$returnArguments[$parameter] = ( $value !== false ) ? $value : "ff-ms-video";
					break;
				case "screenshot-width" :
					$returnArguments[$parameter] = ( $value !== false ) ? ' width="' . $value . '" ' : '';
					break;
				case "screenshot-height" :
					$returnArguments[$parameter] = ( $value !== false ) ? ' height="' . $value . '" ' : '';
					$returnArguments['video-height-wrapper'] = ( $value !== false ) ? ' style="height:' . $value . 'px;" ' : '';
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

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public static function renderHtml( array $args ): string {
		global $IP;
		$arguments = self::getFields( $args );
		$template = file_get_contents( "$IP/extensions/FlexForm/src/Templates/mobileScreenShot.tpl" );
		$find = [
			'%%video-id%%',
			'%%video-class%%',
			'%%button-id%%',
			'%%button-class%%',
			'%%button-text%%',
			'%%canvas-id%%',
			'%%video-width%%',
			'%%video-height%%',
			'%%video-height-wrapper%%'
		];
		$replace = [
			$arguments['video-id'],
			$arguments['video-class'],
			$arguments['button-id'],
			$arguments['capture-button-class'],
			$arguments['capture-button-text'],
			$arguments['canvas-id'],
			$arguments['screenshot-width'],
			$arguments['screenshot-height'],
			$arguments['video-height-wrapper']
		];
		self::renderJavaScript( $arguments );

		return str_replace( $find, $replace, $template );
	}

	/**
	 * @param array $arguments
	 *
	 * @return void
	 */
	private static function renderJavaScript( array $arguments ): void {
		if ( !Core::isLoaded( 'mobileScreenshot.js' ) ) {
			Core::addAsLoaded( 'mobileScreenshot.js' );
			Core::includeTagsScript( Core::getRealUrl() . '/Modules/mobileScreenShot/mobileScreenShot.js' );
		}
		$js = "\nrenderMobileShot( \"" . $arguments['video-id'] . "\", ";
		$js .= "\"" . $arguments['canvas-id'] . "\", ";
		$js .= "\"" . $arguments['button-id'] . "\" )\n";
		Core::includeInlineScript( $js );
	}

}
