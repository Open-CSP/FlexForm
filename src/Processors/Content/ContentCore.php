<?php

namespace WSForm\Processors\Content;

use MWException;
use RequestContext;
use WSForm\Core\Config;
use WSForm\Core\Debug;
use WSForm\Core\HandleResponse;
use WSForm\Processors\Security\wsSecurity;
use WSForm\Processors\Definitions;
use WSForm\Processors\Utilities\General;
use WSForm\Processors\Files\FilesCore;
use WSForm\WSFormException;

/**
 * Class Content core
 * Handles content creating or editing
 *
 * @package WSForm\Processors\Content
 */
class ContentCore {

	private static $fields = array(); // Post fields we get

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
	 * Check and Set some default fields we need
	 *
	 * @return void
	 */
	private static function checkFields() {
		if ( self::$fields['summary'] === false ) {
			self::$fields['summary'] = self::setSummary();
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
					$_POST[$pp] = $filesCore->parseTitle( $_POST[$pp] );
				}
			}
		}
	}

	/**
	 * @param HandleResponse $response_handler
	 * @param string|bool $email
	 *
	 * @return HandleResponse
	 * @throws MWException
	 * @throws WSFormException
	 * @throws \MWContentSerializationException
	 */
	public static function saveToWiki( HandleResponse $response_handler, $email = false ) : HandleResponse {
		self::$fields = Definitions::createAndEditFields();
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'createandeditfields',
				self::$fields
			);
		}

		self::checkFields();
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'checkfields',
				self::$fields
			);
		}

		// WSCreate single
		if ( self::$fields['template'] !== false && self::$fields['writepage'] !== false ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Writing single page',
					[]
				);
			}
			$create = new Create();
			try {
				$result = $create->writePage();
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'writepage result',
						$result
					);
				}
			} catch ( WSFormException $e ) {
				echo "damn";
				throw new WSFormException(
					$e->getMessage(),
					0,
					$e
				);
			}
			if ( false === self::$fields['slot'] ) {
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
					self::$fields['summary']
				);
			} catch ( WSFormException $e ) {
				throw new WSFormException(
					$e->getMessage(),
					0,
					$e
				);
			}
			self::checkFollowPage( $result['title'] );
			if ( ! self::$fields['mwedit'] && ! $email && ! self::$fields['writepages'] ) {
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'finished 1 wscreate value returnto is',
						self::$fields['returnto']
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

		// We need to do multiple edits
		if ( self::$fields['writepages'] !== false ) {
			$create = new Create();
			try {
				$finalPages = $create->writePages();
			} catch ( WSFormException $e ) {
				throw new WSFormException(
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
							$pContent[0]['summary']
						);
					} catch ( WSFormException $e ) {
						throw new WSFormException(
							$e->getMessage(),
							0,
							$e
						);
					}

				}
				if ( $nrOfEdits > 1 ) {
					$slotsToSend = array();
					foreach ( $pContent as $singleCreate ) {
						$slotName               = key( $singleCreate['slot'] );
						$slotValue              = $singleCreate['slot'][$slotName];
						$slotsToSend[$slotName] = $slotValue;
					}

					try {
						$save->saveToWiki(
							$pTitle,
							$slotsToSend,
							$pContent[0]['summary']
						);
					} catch ( WSFormException $e ) {
						throw new WSFormException(
							$e->getMessage(),
							0,
							$e
						);
					}
				}
			}

			if ( ! self::$fields['mwedit'] && ! $email ) {
				$response_handler->setMwReturn( self::$fields['returnto'] );
				$response_handler->setReturnType( HandleResponse::TYPE_SUCCESS );
				if ( self::$fields['msgOnSuccess'] !== false ) {
					$response_handler->setReturnData( self::$fields['msgOnSuccess'] );
				}

				return $response_handler;
			}
		}
		if ( self::$fields['mwedit'] !== false ) {
			$save         = new Save();
			$edit         = new Edit();
			$pageContents = $edit->editPage();
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'PageContent ',
					$pageContents
				);
			}
			foreach ( $pageContents as $slotName => $singlePage ) {
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
				} catch ( WSFormException $e ) {
					throw new WSFormException(
						$e->getMessage(),
						0,
						$e
					);
				}
			}
		}
		$response_handler->setMwReturn( self::$fields['returnto'] );
		if( $email === 'get' ){
			$get = new Get();
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
		$title     = ltrim(
			$title,
			'/'
		);
		$serverUrl = wfGetServerUrl( null ) . '/' . 'index.php';
		if ( self::$fields['mwfollow'] !== false ) {
			if ( self::$fields['mwfollow'] === 'true' ) {
				if ( strpos(
						 $title,
						 '--id--'
					 ) === false && strpos(
										$title,
										'::id::'
									) === false ) {
					self::$fields['returnto'] = $serverUrl . '/' . $title;
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
	 * Create content
	 *
	 * @return string
	 */
	public static function createContent() : string {
		$ret        = '';
		$noTemplate = false;

		if ( self::$fields['template'] === strtolower( 'wsnone' ) ) {
			$noTemplate = true;
		}
		if ( ! $noTemplate ) {
			$ret = "{{" . self::$fields['template'] . "\n";
		}
		foreach ( $_POST as $k => $v ) {
			if ( is_array( $v ) && ! Definitions::isWSFormSystemField( $k ) ) {
				$ret .= "|" . General::makeSpaceFromUnderscore( $k ) . "=";
				foreach ( $v as $multiple ) {
					$ret .= wsSecurity::cleanBraces( $multiple ) . ',';
				}
				$ret = rtrim(
						   $ret,
						   ','
					   ) . PHP_EOL;
			} else {
				if ( ! Definitions::isWSFormSystemField( $k ) && $v != "" ) {
					if ( ! $noTemplate ) {
						$ret .= '|' . General::makeSpaceFromUnderscore( $k ) . '=' . wsSecurity::cleanBraces(
								$v
							) . "\n";
					} else {
						$ret = $v . PHP_EOL;
					}
				}
			}
		}
		if ( ! $noTemplate ) {
			$ret .= "}}";
		}

		return $ret;
	}

	/**
	 * @return int
	 */
	public static function createRandom() : int {
		return time();
	}

	/**
	 * @param string $title
	 *
	 * @return array|mixed|string|string[]
	 */
	public static function parseTitle( string $title ) {
		$tmp = General::get_all_string_between(
			$title,
			'[',
			']'
		);
		foreach ( $tmp as $fieldname ) {
			if ( isset( $_POST[General::makeUnderscoreFromSpace( $fieldname )] ) ) {
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
					if ( Config::getConfigVariable( 'create-seo-titles' ) === true ) {
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
			if ( $fieldname == 'mwrandom' ) {
				$title = str_replace(
					'[' . $fieldname . ']',
					MakeTitle(),
					$title
				);
			}
		}

		return $title;
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
	 * @param $nameStartsWith
	 *
	 * @return array|string[]
	 * @throws MWException
	 */
	public static function getNextAvailable( $nameStartsWith ) : array {
		$render   = new Render();
		$postdata = [
			"action"          => "wsform",
			"format"          => "json",
			"what"            => "nextAvailable",
			"titleStartsWith" => $nameStartsWith
		];
		$result   = $render->makeRequest( $postdata );
		if( Config::isDebug() ) {
			Debug::addToDebug( 'NextAvailable result ' . time(), $result );
		}
		if ( isset( $result['wsform']['error'] ) ) {
			return ( array(
				'status'  => 'error',
				'message' => $result['wsform']['error']['message']
			) );
		} elseif ( isset( $result['error'] ) ) {
			return ( array(
				'status'  => 'error',
				'message' => $result['error']['code'] . ': ' . $result['received']['error']['info']
			) );
		} else {
			return ( array(
				'status' => 'ok',
				'result' => $result['wsform']['result']
			) );
		}
		die();
	}

	/** TODO: Test this!
	 * @param $nameStartsWith
	 * @param $range
	 *
	 * @return array
	 */
	public static function getFromRange( $nameStartsWith, $range ) {
		$postdata = [
			"action"          => "wsform",
			"format"          => "json",
			"what"            => "getRange",
			"titleStartsWith" => $nameStartsWith,
			"range"           => $range
		];
		$render   = new Render();
		$result   = $render->makeRequest( $postdata );

		if ( isset( $result['wsform']['error'] ) ) {
			return ( array(
				'status'  => 'error',
				'message' => $result['wsform']['error']['message']
			) );
		} else {
			return ( array(
				'status' => 'ok',
				'result' => $result['wsform']['result']
			) );
		}
		die();
	}


}