<?php

namespace FlexForm\Processors\Content;

use FlexForm\Core\DebugTimer;
use MediaWiki\MediaWikiServices;
use MWException;
use RequestContext;
use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Utilities\General;
use FlexForm\Processors\Files\FilesCore;
use FlexForm\FlexFormException;
use Title;
use User;

/**
 * Class Content core
 * Handles content creating or editing
 *
 * @package FlexForm\Processors\Content
 */
class ContentCore {

	private static $fields = array(); // Post fields we get
	private static $instances = []; // Any post fields that are labelled as an instance

	/**
	 * @return array
	 */
	public static function getFields() : array {
		return self::$fields;
	}

	/**
	 * Set userpage in Summary if not summary is available.
	 *
	 * @param bool $onlyName
	 *
	 * @return string
	 */
	private static function setSummary( bool $onlyName = false ) : string {
		$user = RequestContext::getMain()->getUser();
		if ( $user->isAnon() === false ) {
			if ( $onlyName === true ) {
				return ( $user->getName() );
			} else {
				return ( '[[User:' . $user->getName() . ']]' );
			}
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];

			return ( 'Anon user: ' . $ip );
		}
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function isInstance( string $name ):bool {
		return in_array( $name, self::$instances );
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function getAllInstances():array {
		return self::$instances;
	}

	/**
	 * @return void
	 */
	private static function checkInstances() {
		$lookFor = 'isinstance_';
		foreach ( $_POST as $k => $v ) {
			if ( !Definitions::isFlexFormSystemField( $k ) ) {

				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'checkInstance for ' . $k,
						$_POST
					);
				}
				$temp = $lookFor . General::makeSpaceFromUnderscore( $k );
				if ( isset( $_POST[ $temp ] ) ) {

					self::$instances[] = $k;
					unset( $_POST[ $temp ] );
					if ( Config::isDebug() ) {
						Debug::addToDebug(
							'deleting instance for ' . $temp,
							$_POST
						);
					}
				}
			}
		}
	}

	/**
	 * Check and Set some default fields we need
	 *
	 * @return void
	 */
	private static function checkFields() {
		if ( self::$fields['summary'] === false ) {
			self::$fields['summary'] = self::setSummary();
		} else {
			self::$fields['summary'] = self::parseTitle( self::$fields['summary'] );
		}

		if ( self::$fields['nooverwrite'] === false ) {
			self::$fields['overwrite'] = true;
		} else {
			self::$fields['overwrite'] = false;
		}

		if ( self::$fields['separator'] === false ) {
			self::$fields['separator'] = ',';
		}

		if ( isset( $_POST['mwleadingzero'] ) ) {
			self::$fields['leadByZero'] = true;
		}


		self::$fields['returnto'] = urldecode( self::$fields['returnto'] );

		if ( self::$fields['parsePost'] !== false && is_array( self::$fields['parsePost'] ) ) {
			$filesCore = new FilesCore();
			foreach ( self::$fields['parsePost'] as $pp ) {
				$pp = General::makeUnderscoreFromSpace( $pp );
				if ( isset( $_POST[$pp] ) ) {
					$_POST[$pp] = self::parseTitle( $_POST[$pp] );
				}
			}
		}

		self::checkInstances();
	}

	/**
	 * @param string $title
	 *
	 * @return string|null
	 * @throws FlexFormException
	 */
	public static function letMWCheckTitle( string $title ) {
		$titleObject = Title::newFromText( $title );
		if ( $titleObject === null ) {
			throw new FlexFormException(
				wfMessage( 'flexform-error-could-not-create-page',
						   $title,
						   "Title is null" ),
				0,
				null
			);
		}
		return $titleObject->getFullText();
	}

	/**
	 * @param HandleResponse $response_handler
	 * @param string|bool $email
	 *
	 * @return HandleResponse
	 * @throws MWException
	 * @throws FlexFormException
	 * @throws \MWContentSerializationException
	 */
	public static function saveToWiki( HandleResponse $response_handler, $email = false ) : HandleResponse {
		if ( Config::isDebug() ) {
			$timer = new DebugTimer();
		}
		self::$fields = Definitions::createAndEditFields();
		if ( Config::isDebug() ) {
			$debugTitle = '<b>' . get_class() . '<br>Function: ' . __FUNCTION__ . '<br></b>';
			Debug::addToDebug(
				$debugTitle . 'createandeditfields',
				self::$fields,
				$timer->getDuration()
			);
		}

		if ( Config::isDebug() ) {
			$timer = new DebugTimer();
		}
		// Check and set default self::$fields. Also check for instances input
		self::checkFields();
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				$debugTitle . 'checkfields',
				self::$fields,
				$timer->getDuration()
			);
		}

		// mwcreateuser
		if ( self::$fields['createuser'] !== false && self::$fields['createuser'] !== '' ) {
			if ( Config::isDebug() ) {
				$timer = new DebugTimer();
			}
			$createUser = new CreateUser();
			$user       = $createUser->addUser();
			$createUser->sendPassWordAndConfirmationLink( $user );
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Handling creeate user duration',
					[],
					$timer->getDuration()
				);
			}
		}

		if ( Config::isDebug() ) {
			$timer = new DebugTimer();
		}
		// WSCreate single
		if ( self::$fields['template'] !== false && self::$fields['writepage'] !== false ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug( $debugTitle . 'Writing single page',
								   [] );
			}
			if ( self::$fields['writepages'] !== false ) {
				throw new FlexFormException(
					wfMessage( 'flexform-mwcreate-mixed_creates' ),
					0,
					null
				);
			}
			$create = new Create();
			try {
				$result = $create->writePage();
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						$debugTitle . 'writepage result',
						[],
						$timer->getDuration()
					);
				}
			} catch ( FlexFormException $e ) {
				throw new FlexFormException(
					$e->getMessage(),
					0,
					$e
				);
			}
			if ( Config::isDebug() ) {
				Debug::addToDebug( $debugTitle . 'Result creating single page',
					$result, $timer->getDuration() );
			}
			if ( self::$fields['slot'] === false ) {
				$slot = "main";
			} else {
				$slot = self::$fields['slot'];
			}
			$result['content'] = self::createSlotArray(
				$slot,
				$result['content']
			);
			$save              = new Save();

			try {
				$save->saveToWiki(
					$result['title'],
					$result['content'],
					self::$fields['summary'],
					self::$fields['overwrite']
				);
			} catch ( FlexFormException $e ) {
				throw new FlexFormException(
					$e->getMessage(),
					0,
					$e
				);
			}
			self::checkFollowPage( $result['title'] );
			if ( !self::$fields['mwedit'] && !$email && !self::$fields['writepages'] ) {
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						$debugTitle . 'finished 1 wscreate value returnto is',
						self::$fields['returnto'], $timer->getDuration()
					);
				}
				$response_handler->setMwReturn( self::$fields['returnto'] );
				$response_handler->setReturnType( HandleResponse::TYPE_SUCCESS );
				if ( self::$fields['msgOnSuccess'] !== false ) {
					$response_handler->setReturnData( self::$fields['msgOnSuccess'] );
				}

				return $response_handler;
			}
		}

		// WSCreate multiple
		if ( self::$fields['writepages'] !== false ) {
			$create = new Create();
			try {
				$finalPages = $create->writePages();
			} catch ( FlexFormException $e ) {
				throw new FlexFormException(
					$e->getMessage(),
					0,
					$e
				);
			}

			$save = new Save();
			foreach ( $finalPages as $pTitle => $pContent ) {
				$nrOfEdits = count( $pContent );
				if ( $nrOfEdits === 1 ) {
					$slotName = key( $pContent[0]['slot'] );
					try {
						$save->saveToWiki(
							$pTitle,
							self::createSlotArray(
								$slotName,
								$pContent[0]['slot'][$slotName]
							),
							$pContent[0]['summary'],
							$pContent[0]['overwrite']
						);
					} catch ( FlexFormException $e ) {
						throw new FlexFormException(
							$e->getMessage(),
							0,
							$e
						);
					}
				}
				if ( $nrOfEdits > 1 ) {
					$slotsToSend = [];
					$overWrite   = true;
					foreach ( $pContent as $singleCreate ) {
						$slotName               = key( $singleCreate['slot'] );
						$slotValue              = $singleCreate['slot'][$slotName];
						$slotsToSend[$slotName] = $slotValue;
						if ( $singleCreate['overwrite'] === false ) {
							$overWrite = false;
						}
					}

					try {
						$save->saveToWiki(
							$pTitle,
							$slotsToSend,
							$pContent[0]['summary'],
							$overWrite
						);
					} catch ( FlexFormException $e ) {
						throw new FlexFormException(
							$e->getMessage(),
							0,
							$e
						);
					}
				}
			}

			if ( !self::$fields['mwedit'] && !$email ) {
				$response_handler->setMwReturn( self::$fields['returnto'] );
				$response_handler->setReturnType( HandleResponse::TYPE_SUCCESS );
				if ( self::$fields['msgOnSuccess'] !== false ) {
					$response_handler->setReturnData( self::$fields['msgOnSuccess'] );
				}
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'Handling WSCreate multiple duration',
						[],
						$timer->getDuration()
					);
				}
				return $response_handler;
			}
		}

		// WSEdits
		if ( self::$fields['mwedit'] !== false ) {
			$save         = new Save();
			$edit         = new Edit();
			$pageContents = $edit->editPage();
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					$debugTitle . 'PageContent ',
					$pageContents,
					$timer->getDuration()
				);
			}
			foreach ( $pageContents as $pageContent ) {
				foreach ( $pageContent as $slotName => $singlePage ) {
					$slotContents = $singlePage['content'];
					$pTitle       = $singlePage['title'];

					try {
						$save->saveToWiki(
							$pTitle,
							self::createSlotArray(
								$slotName,
								$slotContents
							),
							self::$fields['summary']
						);
					} catch ( FlexFormException $e ) {
						throw new FlexFormException(
							$e->getMessage(),
							0,
							$e
						);
					}
				}
			}
		}
		$response_handler->setMwReturn( self::$fields['returnto'] );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Handling Edits duration',
				[],
				$timer->getDuration()
			);
		}

		if ( $email === "yes" ) {
			$mail = new Mail();
			// Handling template
			if ( $mail->getTemplate() !== false ) {
				try {
					$mail->handleTemplate();
				} catch ( FlexFormException $e ) {
					throw new FlexFormException(
						$e->getMessage(),
						0,
						$e
					);
				}
			}
		}
		if ( $email === 'get' ) {
			$get              = new Get();
			$response_handler = $get->createGet( $response_handler );
		}

		$response_handler->setReturnType( HandleResponse::TYPE_SUCCESS );
		if ( self::$fields['msgOnSuccess'] !== false ) {
			$response_handler->setReturnData( self::$fields['msgOnSuccess'] );
		}

		return $response_handler;
	}

	/**
	 * Check if we need to change to returnto url to return to newly created page.
	 *
	 * @param string $title
	 *
	 * @return void
	 */
	public static function checkFollowPage( $title ) : void {
		$title = '/' . ltrim(
			$title,
			'/'
		);
		//$serverUrl = wfGetServerUrl( null ) . '/' . 'index.php';
		if ( self::$fields['mwfollow'] !== false ) {
			if ( self::$fields['mwfollow'] === 'true' ) {
				if ( strpos(
						 $title,
						 '--id--'
					 ) === false && strpos(
										$title,
										'::id::'
									) === false ) {
					$title = ltrim( $title, '/' );
					$titleObject = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $title );
					self::$fields['returnto'] = $titleObject->getFullUrlForRedirect();
				}
			} else {
				if ( strpos(
					self::$fields['returnto'],
					'?'
				) ) {
					self::$fields['returnto'] = self::$fields['returnto'] . '&' . self::$fields['mwfollow'] . '=' . $title;
				} else {
					self::$fields['returnto'] = self::$fields['returnto'] . '?' . self::$fields['mwfollow'] . '=' . $title;
				}
			}
		}
	}

	/**
	 * @param string $slot
	 * @param string $value
	 *
	 * @return array
	 */
	private static function createSlotArray( string $slot, string $value ) : array {
		return array( $slot => $value );
	}

	/**
	 * @param mixed $JSONValue
	 *
	 * @return mixed
	 */
	public static function checkJsonValues( $JSONValue ) {
		return $JSONValue;
		switch ( $JSONValue ) {
			case "true" :
				return true;
			case "false" :
				return false;
			default :
				if ( is_numeric( $JSONValue ) ) {
					return (int)$JSONValue;
				} else {
					return $JSONValue;
				}
		}
	}

	/**
	 * @param array $arrayToTest
	 *
	 * @return bool
	 */
	public static function hasAssignedKeys( array $arrayToTest ): bool {
		foreach ( $arrayToTest as $key => $value ) {
			if ( is_string( $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Create content
	 *
	 * @return string
	 */
	public static function createContent() : string {
		$ret        = '';
		$fret = [];
		$cleanedBracesArray = [];
		$fk = false;
		$noTemplate = false;
		$format = self::$fields['format'];

		if ( self::$fields['template'] === strtolower( 'wsnone' ) ) {
			$noTemplate = true;
		}
		if ( !$noTemplate ) {
			$fk = self::$fields['template'];
			$cleanedBracesArray['ffID'] = self::createRandom();
			$ret = "{{" . self::$fields['template'] . "\n";
		}
		foreach ( $_POST as $k => $v ) {
			if ( is_array( $v ) && !Definitions::isFlexFormSystemField( $k, false ) ) {
				$uk = General::makeSpaceFromUnderscore( $k );
				$ret .= "|" . $uk . "=";
				if ( self::hasAssignedKeys( $v ) ) {
					$cleanedBracesArray[$uk]['ffID'] = self::createRandom();
				}
				foreach ( $v as $multiple ) {
					$cleanedBraces = wsSecurity::cleanBraces( $multiple );
					$cleanedBracesArray[$uk][] = self::checkJsonValues( $cleanedBraces );
					$ret .= $cleanedBraces . self::$fields['separator'];
				}
				$ret = rtrim(
						   $ret,
						   self::$fields['separator']
					   ) . PHP_EOL;
			} else {
				if ( !Definitions::isFlexFormSystemField( $k, false ) && $v != "" ) {
					$uk = General::makeSpaceFromUnderscore( $k );
					if ( !$noTemplate ) {
						$cleanedBraces = wsSecurity::cleanBraces( $v );
						if ( in_array( $k, self::$instances ) && $format === 'json' ) {
							$cleanedBraces = json_decode( $cleanedBraces, true );
							$cleanedBracesArray[$uk] = $cleanedBraces;
							$ret .= '|' . $uk . '=' . json_encode( $cleanedBraces, JSON_PRETTY_PRINT ) . "\n";
						} else {
							$cleanedBracesArray[$uk] = self::checkJsonValues( $cleanedBraces );
							$ret .= '|' . $uk . '=' . $cleanedBraces . "\n";
						}
					} else {
						if ( in_array( $k, self::$instances ) && $format === 'json' ) {
							$cleanedBracesArray[ $uk ] = json_decode( $v, true );
						} else {
							$cleanedBracesArray[ $uk ] = self::checkJsonValues( $v );
						}
						$ret = $v . PHP_EOL;
					}
				}
			}
		}
		if ( !$noTemplate ) {
			$ret .= "}}";
		}
		if ( $fk !== false ) {
			$fret[$fk] = $cleanedBracesArray;
		} else {
			$fret = $cleanedBracesArray;
		}

		if ( !$format || $format === 'wiki' ) {
			return $ret;
		} else {
			return json_encode( $fret, JSON_PRETTY_PRINT );
		}
	}

	/**
	 * @param bool $mtRand
	 *
	 * @return int
	 */
	public static function createRandom( bool $mtRand = false ) : int {
		if ( !$mtRand ) {
			return time();
		} else {
			return mt_rand( 10, 10 );
		}
	}


	/**
	 * @param string $title
	 *
	 * @return string
	 */
	public static function checkCapitalTitle( string $title ): string {
		$titleObject = Title::newFromText( $title );
		if ( $titleObject === null ) {
			return $title;
		}
		$ns = $titleObject->getNamespace();
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( $config->has( 'CapitalLinks' ) ) {
			$capLinks = $config->get( 'CapitalLinks' );
		} else {
			$capLinks = true;
		}
		if ( $config->has( 'CapitalLinkOverrides' ) ) {
			$capLinkOverrides = $config->get( 'CapitalLinkOverrides' );
		} else {
			$capLinkOverrides = [];
		}
		if ( $capLinks === true ) {
			if ( isset( $capLinkOverrides[$ns] ) ) {
				if ( $capLinkOverrides[$ns] === false ) {
					return $title;
				}
			}
			return ucfirst( $title );
		}
		return $title;
	}

	/**
	 * @param string $title
	 * @param bool $noSEO
	 *
	 * @return array|mixed|string|string[]
	 */
	public static function parseTitle( string $title, bool $noSEO = false ) {
		$tmp = General::get_all_string_between(
			$title,
			'[',
			']'
		);
		$t = time();
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Parsetitle ' . $title, $tmp );
		}
		foreach ( $tmp as $fieldname ) {
			if ( $fieldname == 'mwrandom' ) {
				$title = str_replace(
					'[' . $fieldname . ']',
					General::MakeTitle(),
					$title
				);
			} elseif ( isset( $_POST[General::makeUnderscoreFromSpace( $fieldname )] ) ) {
				$fn = $_POST[General::makeUnderscoreFromSpace( $fieldname )];
				if ( is_array( $fn ) ) {
					$imp   = implode(
						', ',
						$fn
					);
					$title = str_replace(
						'[' . $fieldname . ']',
						$imp,
						$title
					);
				} elseif ( $fn !== '' ) {
					if ( Config::getConfigVariable( 'create-seo-titles' ) === true && $noSEO === false ) {
						$fn = self::urlToSEO( $fn );
					}
					$title = str_replace(
						'[' . $fieldname . ']',
						$fn,
						$title
					);
				} else {
					$title = str_replace(
						'[' . $fieldname . ']',
						'',
						$title
					);
				}
			} else {
				$title = str_replace(
					'[' . $fieldname . ']',
					'',
					$title
				);
			}
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Parsetitle result' . $t, $title );
		}
		return $title;
	}

	/**
	 * @param string $template
	 * @param string $content
	 *
	 * @return string
	 */
	public static function setFileTemplate( string $template, string $content ): string {
		if ( strpos( $content, '[flexform-template]' ) !== false ) {
			$arrayS = [ '[flexform-template]', '[/flexform-template]', '|' ];
			$arrayR = [ '{{' . $template, "\n}}\n", "\n" .'|' ];
			$content = str_replace( $arrayS, $arrayR, $content );

		}
		return $content;
	}

	/**
	 * @param $string
	 *
	 * @return string
	 */
	public static function urlToSEO( $string ) : string {
		$separator     = '-';
		$accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
		$special_cases = array(
			'&' => 'and',
			"'" => ''
		);
		$string        = mb_strtolower(
			trim( $string ),
			'UTF-8'
		);
		$string        = str_replace(
			array_keys( $special_cases ),
			array_values( $special_cases ),
			$string
		);
		$string        = preg_replace(
			$accents_regex,
			'$1',
			htmlentities(
				$string,
				ENT_QUOTES,
				'UTF-8'
			)
		);
		$string        = preg_replace(
			"/[^a-z0-9]/u",
			"$separator",
			$string
		);
		$string        = preg_replace(
			"/[$separator]+/u",
			"$separator",
			$string
		);

		return trim(
			$string,
			'-'
		);
	}

	/** TODO: Test this!
	 *
	 * @param $nameStartsWith
	 *
	 * @return array|string[]
	 * @throws MWException
	 */
	public static function getNextAvailable( $nameStartsWith ) : array {
		$render   = new Render();
		$postdata = [
			"action"          => "flexform",
			"format"          => "json",
			"what"            => "nextAvailable",
			"titleStartsWith" => $nameStartsWith
		];
		$result   = $render->makeRequest( $postdata );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'NextAvailable result ',
				$result
			);
		}
		if ( isset( $result['flexform']['error'] ) ) {
			return ( array(
				'status'  => 'error',
				'message' => $result['flexform']['error']['message']
			) );
		} elseif ( isset( $result['error'] ) ) {
			return ( array(
				'status'  => 'error',
				'message' => $result['error']['code'] . ': ' . $result['received']['error']['info']
			) );
		} else {
			return ( array(
				'status' => 'ok',
				'result' => $result['flexform']['result']
			) );
		}
		die();
	}

	/** TODO: Test this!
	 *
	 * @param $nameStartsWith
	 * @param $range
	 *
	 * @return array
	 */
	public static function getFromRange( $nameStartsWith, $range ) {
		$postdata = [
			"action"          => "flexform",
			"format"          => "json",
			"what"            => "getRange",
			"titleStartsWith" => $nameStartsWith,
			"range"           => $range
		];
		$render   = new Render();
		$result   = $render->makeRequest( $postdata );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'getFromRange result ',
				$result
			);
		}

		if ( isset( $result['flexform']['error'] ) ) {
			return ( [
				'status'  => 'error',
				'message' => $result['flexform']['error']['message']
			] );
		} else {
			return ( [
				'status' => 'ok',
				'result' => $result['flexform']['result']
			] );
		}
		die();
	}


}