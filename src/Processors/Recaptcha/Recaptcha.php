<?php
/**
 * Created by  : Designburo.nl
 * Project     : wsformWikiBaseNL
 * Filename    : recaptcha.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 14:10
 */

namespace WSForm\Processors\Recaptcha;

use WSForm\Core\Config;
use WSForm\Processors\Utilities\General;

/**
 * Class recaptcha
 *
 * @package wsform\processors\recaptcha
 */
class Recaptcha {

	/**
	 * @param $secret
	 * @param $token
	 * @param $action
	 *
	 * @return array
	 */
	public static function googleSiteVerify( $secret, $token, $action ) : array {
		$ch = curl_init();
		curl_setopt(
			$ch,
			CURLOPT_URL,
			"https://www.google.com/recaptcha/api/siteverify"
		);
		curl_setopt(
			$ch,
			CURLOPT_POST,
			1
		);
		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			http_build_query(
				array(
					'secret'   => $secret,
					'response' => $token
				)
			)
		);
		curl_setopt(
			$ch,
			CURLOPT_RETURNTRANSFER,
			true
		);
		$response = curl_exec( $ch );
		curl_close( $ch );
		$result = json_decode(
			$response,
			true
		);
		// verify the response
		if ( $result["success"] == '1' && $result["action"] == $action && $result["score"] >= 0.5 ) {
			return array(
				"status" => true,
				"result" => $result
			);
		} else {
			return array(
				"status" => false,
				"result" => $result
			);
		}
	}

	/**
	 * @param $api
	 *
	 * @return bool
	 */
	public static function handleRecaptcha( $api, $messages ) : bool {
		$captchaAction = wsUtilities::getPostString(
			'mw-captcha-action',
			false
		);
		$captchaToken  = wsUtilities::getPostString(
			'mw-captcha-token',
			false
		);
		if ( $captchaAction === false ) {
			return true;
		}

		$i18n     = new wsi18n();
		if ( $captchaToken === '' || $captchaAction === '' ) {
			$messages->setReturnData( $i18n->wsMessage( 'wsform-captcha-missing-details' ) );
			$messages->setReturnStatus( 'error' );

			return false;
		}

		$rc_secret_key = $api->getConfigVariable( 'rc_secret_key' );
		$captchaResult = self::googleSiteVerify(
			$rc_secret_key,
			$captchaToken,
			$captchaAction
		);
		if ( $captchaResult['status'] === false ) {
			$messages->setReturnData(
				$i18n->wsMessage( 'wsform-captcha-score-to-low' ) . ' : ' . $captchaResult['results']['score']
			);
			$messages->setReturnStatus( 'error' );

			return false;
		}

		return true;
	}
}