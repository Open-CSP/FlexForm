<?php
/**
 * Example of an extension that handles posts from a form
 * This will be called at the very end, so any other WSForm command will already be processed (edit, create, mail, etc.)
 * if a page is created $title will hold the name of the new page
 * if a username is available, it will be in the $usrt variable
 * All defined fields in a form can be accessed through $wsPostFields or you can use the function "getFormValues(<variable>)"
 * This will be false if not available or empty
 */
//ERROR_REPORTING(E_ALL);
//ini_set('display_errors', 1);


/*
 * Example call
/api.php?action=customlogswrite
&format=json
&logtype=publiceren
&title=Charlot%20test
&summary=summary%20of%20log
&tags=log-publiceren
&publish=1
&custom-1=custom%20name%201
&custom-2=custome%20name%202
&custom-3=custome%20name%203
&token=bc84822bb5f317c88f4ec0276d74e3e5606d9cde%2B%5C

result :
{
    "customlogswrite": {
        "logid": 24291,
        "result": "Success!"
    }
}


publicatie / depublicatie
summary
custom name 2

*/



include_once( 'wslogger.class.php' );
$log = new wsLogger();

$cnt = $log->getCount();


for ($i = 0; $i < $cnt; $i++) {
	$flag = false;

	$logType = $log->getLogType( $i );

	$pageTitle = $log->getTitle( $i );
	if ( ! $pageTitle ) {
		echo "no title";
		$flag = true;
	}

	$user = $log->getUser( $i );
	if ( ! $user ) {
		echo "no user";
		$flag = true;
	}

	$tag = $log->getTag( $i );
	if ( ! $tag || $log->isAllowedTag( $tag ) === false ) {
		echo "no tag or not allowed tag";
		$flag = true;
	}


	$summary     = $log->getSummary( $i );
	$extraOption = $log->getOption1( $i );

	if ( ! $flag ) {
		$data   = $log->createDate( $logType, $pageTitle, $user, $tag, $extraOption, $summary );
		$result = $api->apiPost( $data );
	}
}
