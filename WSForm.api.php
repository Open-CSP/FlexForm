<?php
# @Author: Sen-Sai <Charlot>
# @Date:   15-05-2018 -- 10:46:23
# @Last modified by:   Charlot
# @Last modified time: 27-06-2018 -- 13:04:18
# @License: Mine
# @Copyright: 2018


/*
 *    What : WSForm api tasks
 *  Author : Sen-Sai
 *    Date : October 2017
 */
//use \MediawikiApi\Api\ApiUser;
//$cookieParams = session_get_cookie_params();
//$cookieParams['samesite'] = "Lax";
//session_set_cookie_params($cookieParams);
//session_start();
use MediaWiki\MediaWikiServices;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use WSForm\Core\Debug;
use WSForm\Core\HandleResponse;
use WSForm\Core\Config;
use WSForm\Processors\Content\ContentCore;
use WSForm\Processors\Recaptcha\Recaptcha;
use WSForm\Processors\Request\External;
use WSForm\Processors\Security\wsSecurity;
use WSForm\Processors\Utilities\General;
use WSForm\WSFormException;


//setcookie("wsform[type]", "danger", 0, '/');
//setcookie("wsform[txt]", "test", 0, '/');

// Are we inside the MediaWiki FrameWork ?
if ( ! defined( 'MEDIAWIKI' ) ) {
	if ( General::getGetString(
			'version',
			false
		) !== false ) {
		echo getVersion();
		exit();
	}
	die( 'no no no sir' );
}

$ret = false;

$removeList = array();

// Handle to final response
$responseHandler = new HandleResponse;
try {
	Config::setConfigFromMW();
} catch ( WSFormException $e ){
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'error' );
};


if( Config::isDebug() ) {
	ERROR_REPORTING(E_ALL);
	ini_set('display_errors', 1);
	Debug::addToDebug( '$_POST before checks', $_POST );
}



try {
	$securityResult = wsSecurity::resolvePosts();
	if ( Config::isDebug() ) {
		Debug::addToDebug( '$_POST after checks', $_POST );
	}
} catch ( WSFormException $e ) {
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'error' );
};


$responseHandler->setIdentifier( General::getPostString( "mwidentifier" ) );
$responseHandler->setMwReturn( urldecode( General::getPostString( "mwreturn" ) ) );
$responseHandler->setPauseBeforeRefresh( General::getPostString( 'mwpause' ) );

if( Config::isDebug() ) {
	Debug::addToDebug( 'mwreturn', $responseHandler->getMwReturn() );
}

// Do we have any errors so far ?
if( $responseHandler->getReturnStatus() === "error" ) {
	try {
		$responseHandler->exitResponse();
	}  catch ( WSFormException $e ){
		return $e->getMessage();
	};

}




/* TODO: Will be added later
//********* START Handle functions that need no further actions
$actionGet = General::getGetString( 'action' );
// Handle get requests
if ( $actionGet !== false ) {
	try {
		switch ( $actionGet ) {
			case "renderWiki":
				$render = new render();
				$result = $render->renderWikiTxt();
				wbHandleResponses::JSONResponse( $result );
				break;
			case "handleExternalRequest":
				$result = external::handle();
				wbHandleResponses::JSONResponse( $result );
				break;
			case "handleQuery":
				$result = query::handle();
				$responseHandler->JSONResponse( $result );
				break;
		}
	} catch( WSFormException $e ) {
		$responseHandler->setReturnData( $e->getMessage() );
		$responseHandler->setReturnStatus( 'error' );
	}
}
*/
$title = "";

//include_once('WSForm.api.include.php');
//$api = new wbApi();



/* TODO: Add later
if(isset( $_GET['action']) && $_GET['action'] === 'renderWiki' ) {
    $ret = renderWiki();
    header('Content-Type: application/json');
    if (isset($_GET['pp'])) {
        echo json_encode($ret, JSON_PRETTY_PRINT);
    } else {
        echo json_encode($ret);
    }
    exit;
}

if( isset( $_GET['action'] ) && $_GET['action'] === 'handleExternalRequest' ) {
    $external = getGetString('script');
    if($external !== false) {
        // a way to try and keep unwanted requests out (v 0.8.0.1.5)
        if( isset( $_GET['mwdb'] ) && $_GET['mwdb'] !== '' ) {
            $cok = $_GET['mwdb'] . 'UserID';
            if( isset( $_COOKIE[$cok] ) && $_COOKIE[$cok] != "0" ) {
                // ok
            } else die( 'not identified' );
        } else die();
        $api = new wbApi();
        if( $api->getStatus() === false ){
	        $ret = createMsg( $api->getStatus( true ) );
        } else {
	        $res = $api->logMeIn();
	        if ( $res === false ) {
		        $ret = createMsg( $res );
	        } else {
		        $IP = $api->app['IP'];

		        if ( file_exists( $IP . '/extensions/WSForm/modules/handlers/' . basename( $external ) . '.php' ) ) {
			        include_once( $IP . '/extensions/WSForm/modules/handlers/' . basename( $external ) . '.php' );
		        } else {
		        	$ret = createMsg($i18n->wsMessage( 'wsform-external-request-not-found' ) );
		        }
	        }
        }

    } else {
        $ret = createMsg($i18n->wsMessage( 'wsform-external-request-not-found' ) );
    }
    header('Content-Type: application/json');
    if (isset($_GET['pp'])) {
        echo json_encode($ret, JSON_PRETTY_PRINT);
    } else {
        echo json_encode($ret);
    }
    exit;
}

if( isset( $_GET['action'] ) && $_GET['action'] === 'handleQuery' ) {
	$external = getGetString('handler');
	if($external !== false) {
		$extensionsFolder = getcwd()."/modules/handlers/queries/";
		if (file_exists($extensionsFolder . $external . '/query-handler.php')) {
			include_once($extensionsFolder . $external . '/query-handler.php');
		} else {
			$ret = json_encode( createMsg( $i18n->wsMessage( 'wsform-query-handler-not-found' ) ) );
		}
	} else {
		$ret = json_encode( createMsg( $i18n->wsMessage( 'wsform-query-handler-not-found' ) ) );
	}
	header('Content-Type: application/json');
	echo $ret;
	exit;
}
*/
// Setup messages and responses
try {
	Recaptcha::handleRecaptcha();
} catch ( WSFormException $e ){
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'error' );
	try {
		$responseHandler->exitResponse();
	}  catch ( WSFormException $e ){
		return $e->getMessage();
	};
};

wsSecurity::cleanPosts();

if( Config::isDebug() ) {
	Debug::addToDebug( '$_POST after cleaned html', $_POST );
}
General::handleDefaultValues();
if( Config::isDebug() ) {
	Debug::addToDebug( '$_POST after wsdefault changes', $_POST );
}

$wsuid = General::getPostString( 'wsuid' );

if( $wsuid !== false ){
	unset( $_POST['wsuid'] );
}

// TODO: Later on
/*
if( isset( $_POST['wsform_signature'] ) ) {
	$res = signatureUpload();
	if ($res['status'] == 'error') {
		$messages->doDie( ' signature : '.$res['msg'] );
		$failed = true;
	}
	$ret = $res; // v0.7.0.3.3 added
}
if(isset($_FILES['wsformfile'])) {
	if (file_exists($_FILES['wsformfile']['tmp_name']) || is_uploaded_file($_FILES['wsformfile']['tmp_name'])) {
		$res = fileUpload();
		if( $api->isDebug() ) Debug::addToDebug( 'File Upload result', $res );
		if (isset( $res['status'] ) && $res['status'] == 'error') {
			$messages->doDie(' file : '.$res['msg']);
			$failed = true;
		}
		$ret = $res; // v0.7.0.3.3 added
	}
}

if( isset($_POST['wsformfile_slim']) ) {
	$ret=fileUploadSlim();
	if (isset($ret['status']) && $ret['status'] === 'error') {
		$messages->doDie( ' slim : '.$ret['msg'] );
		$failed = true;
	}

}
*/
if ( General::getPostString( 'mwaction' ) !== false ) {
	$action = General::getPostString( 'mwaction' );
	unset( $_POST['mwaction'] );


	//print_r($_POST);

	switch ( $action ) {

		case "addToWiki" :
			try {
				$responseHandler = ContentCore::saveToWiki( $responseHandler );
			} catch ( WSFormException|MWException $e ) {
				$responseHandler->setReturnData( $e->getMessage() );
				$responseHandler->setReturnStatus( 'error' );
			}
			break;

		case "get" :
			try {
				$responseHandler = ContentCore::saveToWiki( $responseHandler, "get" );
			} catch ( WSFormException $e ){
				$responseHandler->setReturnData( $e->getMessage() );
				$responseHandler->setReturnStatus( 'error' );
				try {
					$responseHandler->exitResponse();
				}  catch ( WSFormException $e ){
					return $e->getMessage();
				};
			};
			/*
			if( !is_array( $ret ) && $ret !== false ) {
				$messages->redirect( $ret );
				exit;
			} elseif( $ret === false ) {
				$messages->doDie( $i18n->wsMessage( 'wsform-noreturn-found' ) );
			}
			*/
			//print_r($ret);
			//die();
			//die();
		/*
			if ( $ret ) {
				$messages->redirect( $ret );
				exit;
			} else {
				$messages->doDie( $i18n->wsMessage( 'wsform-noreturn-found' ) );
			}
		*/
			break;

		case "mail" :
			$ret = saveToWiki('yes' );
			break;
	}
} else {
	if( Config::isDebug() ) Debug::addToDebug( 'running main functions fail', array('action'=>General::getPostString('mwaction') ) );
}


// TODO: Later on
/*
$extension = getPostString('mwextension' );

if( $extension !== false ) {
	$extensionsFolder = getcwd()."/modules/handlers/posts/";
	if( file_exists($extensionsFolder . $extension . '/post-handler.php') ) {
		$usrt = setSummary(true);
		$mwreturn = '';
		if ( isset( $_POST['mwreturn'] ) && $_POST['mwreturn'] !== "" ) {
			$mwreturn = $_POST['mwreturn'];
		}
		$wsPostFields = setWsPostFields();
		include($extensionsFolder . $extension . '/post-handler.php');
		if ( $mwreturn !== "" ) {
			$messages->redirect( $mwreturn );
			exit;
		} elseif($identifier !== 'ajax') {
			$messages->outputMsg( $i18n->wsMessage( 'wsform-noreturn-found' ) );
		}
	}
}
*/
if( Config::isDebug() ) {
	if ( $responseHandler->getReturnStatus() !== "ok" ) {
		Debug::addToDebug('ERROR MESSAGES', $responseHandler->getReturnData() );
	}
	echo Debug::createDebugOutput();
	die('testing..');
}

try {
	$responseHandler->exitResponse();
} catch ( WSFormException $e ) {
	return $e->getMessage();
}

