<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : FlexForm
 * Filename    : recaptcha.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 14:10
 */

namespace FlexForm\Processors\Recaptcha;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Processors\Utilities\General;
use FlexForm\FlexFormException;

/**
 * Class recaptcha
 *
 * @package FlexForm\Processors\Recaptcha
 */
class Recaptcha {

	private const RECAPTCHA_V3_URL = 'https://www.google.com/recaptcha/api/siteverify';
	private const RECAPTCHA_ENTERPRISE_URL = 'https://recaptchaenterprise.googleapis.com/v1/projects/';

	/**
	 * @param string $token
	 * @param string $action
	 * @param string|bool $type
	 *
	 * @return array
	 */
	public static function googleSiteVerify( string $token, string $action, $type ) : array {
		if ( $type !== false ) {
			$project = Config::getConfigVariable( 'rce_project' );
			$siteKey = Config::getConfigVariable( 'rce_site_key' );
			$apiKey = Config::getConfigVariable( 'rce_api_key' );
			$jsonBody = self::createJSONBody( $token, $action, $siteKey );
			$url = self::RECAPTCHA_ENTERPRISE_URL . $project . '/assessments?key=' . $apiKey;
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Sending to recaptcha',
					[ "url" => $url,
						"json" => $jsonBody,
					"jsonFile" => '' ]
				);
			}
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $jsonBody );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json'] );
			$response = curl_exec( $ch );
			curl_close( $ch );
			$result = json_decode( $response, true );
			$result['score'] = $result['riskAnalysis']["score"];
			$result['error-codes'] = $result['riskAnalysis']["reasons"];
			if ( $result['tokenProperties']['valid'] === false ) {
				return [ "status" => false,	"result" => $result, ];
			} elseif ( $result["tokenProperties"]['action'] == $action && $result['riskAnalysis']["score"] >= 0.7 ) {
				return [ "status" => true, "result" => $result ];
			} else {
				return [ "status" => false,	"result" => $result ];
			}
		} else {
			$secret = Config::getConfigVariable( 'rc_secret_key' );
			$ch = curl_init();
			curl_setopt( $ch,
				CURLOPT_URL,
				self::RECAPTCHA_V3_URL );
			curl_setopt( $ch,
				CURLOPT_POST,
				1 );
			curl_setopt( $ch,
				CURLOPT_POSTFIELDS,
				http_build_query( array( 'secret' => $secret, 'response' => $token ) ) );
			curl_setopt( $ch,
				CURLOPT_RETURNTRANSFER,
				true );
			$response = curl_exec( $ch );
			curl_close( $ch );
			$result = json_decode( $response,
				true );
			// verify the response
			if ( $result["success"] == '1' && $result["action"] == $action && $result["score"] >= 0.7 ) {
				return array( "status" => true,
					"result" => $result );
			} else {
				return array( "status" => false,
					"result" => $result );
			}
		}
	}

	/**
	 * @param string $token
	 * @param string $action
	 * @param string $siteKey
	 *
	 * @return false|string
	 */
	private static function createJSONBody( string $token, string $action, string $siteKey ) {
		$event = [];
		$event['event'] = [ "token" => $token, "expectedAction" => $action, "siteKey" => $siteKey ];
		return json_encode( $event );
	}

	/**
	 * @return bool
	 * @throws FlexFormException
	 */
	public static function handleRecaptcha() : bool {
		$captchaAction = General::getPostString(
			'mw-captcha-action',
			false
		);
		$captchaToken  = General::getPostString(
			'mw-captcha-token',
			false
		);
		$captchaType  = General::getPostString(
			'mw-captcha-type',
			false
		);
		if ( $captchaAction === false ) {
			return true;
		}

		if ( $captchaToken === '' || $captchaAction === '' ) {
			throw new FlexFormException( wfMessage( 'flexform-captcha-missing-details' )->text() );
		}

		$captchaResult = self::googleSiteVerify(
			$captchaToken,
			$captchaAction,
			$captchaType
		);
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'RECAPTCHA RESULT',
				$captchaResult
			);
		}
		if ( $captchaResult['status'] === false ) {
			$msg = '';
			if ( isset( $captchaResult['result']['score'] ) ) {
				$msg = $captchaResult['result']['score'];
			}
			if ( isset( $captchaResult['result']['error-codes'] ) ) {
				$msg = implode( '<br>', $captchaResult['result']['error-codes'] );
			}
			throw new FlexFormException( wfMessage( 'flexform-captcha-score-to-low' )->text() . ' : ' . $msg );
		}

		return true;
	}
}