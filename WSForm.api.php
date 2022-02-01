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
 *    Date : October 2017/January 2021 (rewrite)
 */

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
} catch ( WSFormException $e ) {
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'setConfigError' );
	$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
};

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

try {
	$securityResult = wsSecurity::resolvePosts();
	if ( Config::isDebug() ) {
		Debug::addToDebug(
			'$_POST after checks',
			$_POST
		);
	}
} catch ( WSFormException $e ) {
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'resolve posts error' );
	$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
};

$responseHandler->setIdentifier( General::getPostString( "mwidentifier" ) );
$responseHandler->setMwReturn( urldecode( General::getPostString( "mwreturn" ) ) );
$responseHandler->setPauseBeforeRefresh( General::getPostString( 'mwpause' ) );

if ( Config::isDebug() ) {
	Debug::addToDebug(
		'first set of mwreturn',
		$responseHandler->getMwReturn()
	);
}

// Do we have any errors so far ?
if ( $responseHandler->getReturnStatus() === "error" ) {
	try {
		$responseHandler->exitResponse();
	} catch ( WSFormException $e ) {
		return $e->getMessage();
	};
}

// Setup messages and responses
try {
	Recaptcha::handleRecaptcha();
} catch ( WSFormException $e ) {
	$responseHandler->setReturnData( $e->getMessage() );
	$responseHandler->setReturnStatus( 'recaptch error' );
	$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
	try {
		$responseHandler->exitResponse();
	} catch ( WSFormException $e ) {
		return $e->getMessage();
	};
};

wsSecurity::cleanPosts();

if ( Config::isDebug() ) {
	Debug::addToDebug(
		'$_POST after cleaned html',
		$_POST
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

if ( General::getPostString( 'mwaction' ) !== false ) {
	$action = General::getPostString( 'mwaction' );
	unset( $_POST['mwaction'] );

	switch ( $action ) {
		case "addToWiki" :
		case "email" :
			try {
				if( $action === 'email' ) {
					$responseHandler = ContentCore::saveToWiki( $responseHandler, "yes" );
				} else {
					$responseHandler = ContentCore::saveToWiki( $responseHandler );
				}
			} catch ( WSFormException | MWException $e ) {
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
			} catch ( WSFormException | MWException $e ) {
				$responseHandler->setReturnData( $e->getMessage() );
				$responseHandler->setReturnStatus( 'GET error' );
				$responseHandler->setReturnType( $responseHandler::TYPE_ERROR );
				try {
					$responseHandler->exitResponse();
				} catch ( WSFormException $e ) {
					return $e->getMessage();
				}
			}

			break;
	}
} else {
	if ( Config::isDebug() ) {
		Debug::addToDebug( 'running main functions fail',
						   array( 'action' => General::getPostString( 'mwaction' ) ) );
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
}

try {
	$responseHandler->exitResponse();
} catch ( WSFormException $e ) {
	return $e->getMessage();
}

