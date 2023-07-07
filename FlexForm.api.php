<?php
# @Author: Sen-Sai <Charlot>
# @Date:   15-05-2018 -- 10:46:23
# @Last modified by:   Charlot
# @Last modified time: 27-06-2018 -- 13:04:18
# @License: Mine
# @Copyright: 2018

/*
 *    What : FlexForm api tasks
 *  Author : Sen-Sai
 *    Date : October 2017/January 2021 (rewrite)
 */

use FlexForm\Core\Debug;
use FlexForm\Core\DebugTimer;
use FlexForm\Core\HandleResponse;
use FlexForm\Core\Config;
use FlexForm\Processors\Content\ContentCore;
use FlexForm\Processors\Files\FilesCore;
use FlexForm\Processors\Recaptcha\Recaptcha;
use FlexForm\Processors\Request\External;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\Processors\Utilities\General;
use FlexForm\FlexFormException;

// Are we inside the MediaWiki FrameWork ?
if ( ! defined( 'MEDIAWIKI' ) ) {
	if ( General::getGetString(
			'version',
			false
		) !== false ) {
		//echo getVersion();
		exit();
	}
	die( 'no no no sir' );
}

$ret = false;

$removeList = [];

// Handle to final response
$responseHandler = new HandleResponse;
try {
	Config::setConfigFromMW();
} catch ( FlexFormException $e ) {
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'setConfigError' );
	$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
}

if ( Config::isDebug() ) {
	$fullTimer = new DebugTimer();
}

if ( Config::isDebug() ) {
	ERROR_REPORTING( E_ALL );
	ini_set(
		'display_errors',
		1
	);
	Debug::addToDebug(
		'$_POST before checks',
		$_POST
	);
}

$getAction = General::getGetString( 'action', true, false );

if ( Config::isDebug() ) {
	$externalTimer = new DebugTimer();
}
if ( $getAction === 'handleExternalRequest' ) {
	try {

		External::handle( $responseHandler );
	} catch ( FlexFormException $e ) {
		$responseHandler->setReturnData( $e->getMessage() );
		$responseHandler->setReturnStatus( 'external request error' );
		$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
		$responseHandler->setIdentifier( 'ajax' );
		try {
			$responseHandler->exitResponse();
			return;
		} catch ( FlexFormException $e ) {
			die( $e->getMessage() );
		}
	}
}
if ( Config::isDebug() ) {
	Debug::addToDebug(
		'handleExternalRequest duration',
		[],
		$externalTimer->getDuration()
	);
}

if ( Config::isDebug() ) {
	$timer = new DebugTimer();
}

try {
	$securityResult = wsSecurity::resolvePosts();
	if ( Config::isDebug() ) {
		Debug::addToDebug(
			'$_POST after checks',
			$_POST,
			$timer->getDuration()
		);
	}
} catch ( FlexFormException $e ) {
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'resolve posts error' );
	$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
}

$responseHandler->setIdentifier( General::getPostString( "mwidentifier" ) );
$responseHandler->setMwReturn( urldecode( General::getPostString( "mwreturn" ) ) );
$responseHandler->setPauseBeforeRefresh( General::getPostString( 'mwpause' ) );

if ( Config::isDebug() ) {
	Debug::addToDebug(
		'first set of mwreturn',
		[ "mwreturn" => $responseHandler->getMwReturn(), "returnstatus" => $responseHandler->getReturnStatus() ]
	);
}

// Do we have any errors so far ?
if ( $responseHandler->getReturnType() === $responseHandler::TYPE_ERROR ) {
	try {
		$responseHandler->setMwReturn( false );
		$responseHandler->exitResponse();
		return false;
	} catch ( FlexFormException $e ) {
		return $e->getMessage();
	}
}

// Setup messages and responses
try {
	Recaptcha::handleRecaptcha();
} catch ( FlexFormException $e ) {
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'recaptch error' );
	$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
	try {
		$responseHandler->exitResponse();
		return false;
	} catch ( FlexFormException $e ) {
		return $e->getMessage();
	}
}

if ( Config::isDebug() ) {
	$timer = new DebugTimer();
}

wsSecurity::cleanPosts();

if ( Config::isDebug() ) {
	Debug::addToDebug(
		'$_POST after cleaned html',
		$_POST,
		$timer->getDuration()
	);
}

General::handleDefaultValues();
if ( Config::isDebug() ) {
	Debug::addToDebug(
		'$_POST after wsdefault changes',
		$_POST
	);
}

$wsuid = General::getPostString( 'wsuid' );

if ( $wsuid !== false ) {
	unset( $_POST['wsuid'] );
}

if ( Config::isDebug() ) {
	$timer = new DebugTimer();
}

$fileHandler = new FilesCore();

try {
	$fileHandler->handleFileUploads();
} catch ( FlexFormException $e ) {
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'file upload error' );
	$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
	try {
		$responseHandler->exitResponse();
		return false;
	} catch ( FlexFormException $e ) {
		die( $e->getMessage() );
	}
}

if ( Config::isDebug() ) {
	Debug::addToDebug(
		'Handling files duration',
		[],
		$timer->getDuration()
	);
}

// Add default action is addToWiki
$action = General::getPostString( 'mwaction' );
if ( $action === false ) {
	$action = "addToWiki";
}

unset( $_POST['mwaction'] );

if ( Config::isDebug() ) {
	$addToWikiTimer = new DebugTimer();
}

switch ( $action ) {
	case "addToWiki" :
	case "email" :
		try {
			if ( $action === 'email' ) {
				$responseHandler = ContentCore::saveToWiki( $responseHandler, "yes" );
			} else {
				$responseHandler = ContentCore::saveToWiki( $responseHandler );
			}
		} catch ( FlexFormException | MWException | Exception $e ) {
			$responseHandler->setReturnData( $e->getMessage() );
			$responseHandler->setReturnStatus( 'saveToWiki error' );
			$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
		}
		break;
	case "get" :
		try {
			$responseHandler = ContentCore::saveToWiki(
				$responseHandler,
				"get"
			);
		} catch ( FlexFormException | MWException $e ) {
			$responseHandler->setReturnData( $e->getMessage() );
			$responseHandler->setReturnStatus( 'GET error' );
			$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
			try {
				$responseHandler->exitResponse();
				return false;
			} catch ( FlexFormException $e ) {
				return $e->getMessage();
			}
		}
		break;
	default:
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'running main functions fail',
				[ 'action' => General::getPostString( 'mwaction' ) ]
			);
		}
		break;
}

if ( Config::isDebug() ) {
	Debug::addToDebug(
		'Handling action duration',
		[],
		$addToWikiTimer->getDuration()
	);
}

// Handle extensions
if ( General::getPostString( 'mwextension' ) !== false ) {
	if ( Config::isDebug() ) {
		Debug::addToDebug(
			'We have an extension to run',
			General::getPostString( 'mwextension' )
		);
	}
	try {
		External::handlePost( $responseHandler );
	} catch ( FlexFormException $e ) {
		$responseHandler->setReturnData( $e->getMessage() );
		$responseHandler->setReturnStatus( 'Extension error' );
		$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
		try {
			$responseHandler->exitResponse();
			return false;
		} catch ( FlexFormException $e ) {
			die( $e->getMessage() );
		}
	}
}

if ( Config::isDebug() ) {
	if ( $responseHandler->getReturnStatus() !== "ok" ) {
		Debug::addToDebug(
			'RETURN STATUS',
			$responseHandler->getReturnStatus()
		);
		Debug::addToDebug(
			'ERROR MESSAGES',
			$responseHandler->getReturnData()
		);
	}
	Debug::addToDebug(
		'Total time passed',
		[],
		$fullTimer->getDuration()
	);
}

try {
	$responseHandler->exitResponse();
	$this->getOutput()->redirect( html_entity_decode( $responseHandler->getMwReturn() ) );
} catch ( FlexFormException $e ) {
	echo $e->getMessage();
}

