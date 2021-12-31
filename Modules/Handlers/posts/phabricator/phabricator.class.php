<?php
/**
 * Created by  : Designburo.nl
 * Project     : i
 * Filename    : phabricator.class.php
 * Description :
 * Date        : 27/12/2018
 * Time        : 18:11
 */

class phabricator {
	/**
	 * @param $data
	 * @return mixed
	 */
	function apiPost( $transactions, $identifier ){
        include __DIR__ . '/phabricator.config.php';
        $data['api.token'] = $phabricatorToken;
		$data['transactions']=$transactions;
		if( $identifier !== false ) {
            $data['objectIdentifier'] = $identifier;
        }
		$data=http_build_query($data);
		$client = $phabricatorEditManifestURL;
		$curlOptions =
			array(
				CURLOPT_CONNECTTIMEOUT => 30,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_FOLLOWLOCATION => 1,
				CURLOPT_POST => true
			);
		$ch = curl_init();
		curl_setopt_array($ch, $curlOptions);

		curl_setopt($ch, CURLOPT_URL, $client);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result=curl_exec($ch);

		if(curl_errno($ch)) {
			die( curl_error( $ch ) );
		}
		return $result;
	}
}