<?php
/**
 * Created by  : Designburo.nl
 * Project     : i
 * Filename    : query-handler.php
 * Description :
 * Date        : 27/12/2018
 * Time        : 18:42
 */

$tid = getGetString('tid');
$ret = array();
if($tid === false) {
	$ret = json_encode(createMsg('No task Id found.'));
} else {
	include_once( 'phabricator.class.php' );
	$phab = new phabricatorQuery();
	$result  = json_decode( $phab->apiPost( $tid ), true );
	if( $result['result']['error_code'] === null ) {
		$ret['status'] = 'ok';
		$ret['result']['title'] = $result['result']['title'];
		$ret['result']['description'] = $result['result']['description'];
		$ret['result']['url'] = $result['result']['uri'];
	} else {
		$ret['status'] = 'error';
		$ret['result'] = $result['error_code'];
	}
	$ret = json_encode($ret);
}