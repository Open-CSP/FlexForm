<?php

namespace FlexForm\Processors\Content;

use FlexForm\Core\DebugTimer;
use FlexForm\Processors\Content\Jobs\FlexFormJobLogger;
use MediaWiki\MediaWikiServices;
use Exception;
use MWContentSerializationException;
use RequestContext;
use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Utilities\General;
use FlexForm\FlexFormException;
use Title;

/**
 * Class Content core
 * Handles content creating or editing
 *
 * @package FlexForm\Processors\Content
 */
class ContentCore {

	/**
	 * @var array
	 */
	private static array $fields = [];

	/**
	 * Any post fields that are labelled as an instance
	 * @var array
	 */
	private static array $instances = [];

	/**
	 * @var array
	 */
	public static array $jobData = [];

	/**
	 * @var bool
	 */
	public static bool $isJob = false;

	/**
	 * @var string
	 */
	public static string $jobSummary;

	/**
	 * @var string
	 */
	public static string $jobUser;

	/**
	 * @var int
	 */
	private static int $fileNumber = 0;

	/**
	 * @return array
	 */
	public static function getFields(): array {
		return self::$fields;
	}

	/**
	 * @param int $fCount
	 *
	 * @return void
	 */
	public static function setFileCount( int $fCount ): void {
		self::$fileNumber = $fCount;
	}

	/**
	 * @return int
	 */
	private static function getFileCount(): int {
		$fCount = self::$fileNumber;
		self::$fileNumber = 0;
		return $fCount;
	}

	/**
	 * Set userpage in Summary if not summary is available.
	 *
	 * @param bool $onlyName
	 *
	 * @return string
	 */
	private static function setSummary( bool $onlyName = false ): string {
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
	public static function isInstance( string $name ): bool {
		return in_array( $name, self::$instances );
	}

	/**
	 * @return array
	 */
	public static function getAllInstances(): array {
		return self::$instances;
	}

	/**
	 * @return void
	 */
	private static function checkInstances() {
		self::$instances = [];
		$lookFor = 'isinstance_';
		foreach ( $_POST as $k => $v ) {
			if ( !Definitions::isFlexFormSystemField( $k ) ) {
				Debug::addToDebug(
					'checkInstance for ' . $k,
					$_POST
				);

				$temp = $lookFor . $k;
				if ( isset( $_POST[ $temp ] ) ) {

					self::$instances[] = $k;
					unset( $_POST[ $temp ] );
					Debug::addToDebug(
						'deleting instance for ' . $temp,
						$_POST
					);
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

		if ( self::$fields['skipSeo'] === "true" ) {
			self::$fields['skipSeo'] = true;
		}

		if ( isset( $_POST['mwleadingzero'] ) ) {
			self::$fields['leadByZero'] = true;
		}

		self::$fields['returnto'] = urldecode( self::$fields['returnto'] );

		if ( self::$fields['parsePost'] !== false && is_array( self::$fields['parsePost'] ) ) {
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
	 * @return void
	 */
	private static function handleSaveToWikiDefaults(): void {
		$timer = new DebugTimer();
		self::$fields = Definitions::createAndEditFields();
		if ( self::$fields['msgOnSuccess'] !== false ) {
			self::$fields['msgOnSuccess'] = self::parseTitle( self::$fields['msgOnSuccess'], true );
		}
		$debugTitle = '<b>::' . static::class . '::</b> ';
		Debug::addToDebug(
			$debugTitle . 'createandeditfields',
			self::$fields,
			$timer->getDuration()
		);

		$timer = new DebugTimer();

		// Check and set default self::$fields. Also check for instances input
		self::checkFields();
		Debug::addToDebug(
			$debugTitle . 'checkfields',
			self::$fields,
			$timer->getDuration()
		);

	}

	/**
	 * @return void
	 * @throws FlexFormException
	 */
	private static function handleSaveToWikiCreateUser(): void {
		$timer = new DebugTimer();
		$debugTitle = '<b>::' . static::class . '::</b> ';

		$createUser = new CreateUser();
		$user = $createUser->addUser();
		$createUser->sendPassWordAndConfirmationLink( $user );
		Debug::addToDebug(
			$debugTitle . 'Handling create user duration',
			[],
			$timer->getDuration()
		);

	}

	/**
	 * @param DebugTimer $timer
	 *
	 * @return void
	 * @throws FlexFormException
	 * @throws MWContentSerializationException
	 * @throws Exception
	 */
	private static function handleSaveToWikiCreateSingle( DebugTimer $timer ): void {
		$debugTitle = '<b>::' . static::class . '::</b> ';
		Debug::addToDebug(
			$debugTitle . 'Writing single page', []
		);

		if ( self::$fields['writepages'] !== false ) {
			throw new FlexFormException(
				wfMessage( 'flexform-mwcreate-mixed_creates' ), 0, null
			);
		}
		$create = new Create();
		try {
			$result = $create->writePage();
			Debug::addToDebug(
				$debugTitle . 'writepage result',
				[],
				$timer->getDuration()
			);
		} catch ( FlexFormException $e ) {
			throw new FlexFormException(
				$e->getMessage(), 0, $e
			);
		}
		Debug::addToDebug(
			$debugTitle . 'Result creating single page',
			$result,
			$timer->getDuration()
		);
		if ( self::$fields['slot'] === false ) {
			$slot = "main";
		} else {
			$slot = self::$fields['slot'];
		}
		$result['content'] = self::createSlotArray(
			$slot,
			$result['content']
		);
		$save = new Save();

		try {
			$save->saveToWiki(
				$result['title'],
				$result['content'],
				self::$fields['summary'],
				self::$fields['overwrite']
			);
		} catch ( FlexFormException $e ) {
			throw new FlexFormException(
				$e->getMessage(), 0, $e
			);
		}
		self::checkFollowPage( $result['title'] );
		if ( !self::$fields['mwedit'] && !self::$fields['writepages'] ) {
			Debug::addToDebug( $debugTitle . 'finished 1 wscreate value returnto is',
				self::$fields['returnto'],
				$timer->getDuration() );
		}
	}

	/**
	 * @param DebugTimer $timer
	 *
	 * @return void
	 * @throws FlexFormException
	 * @throws MWContentSerializationException
	 * @throws Exception
	 */
	private static function handleSaveToWikiCreateMultiple( DebugTimer $timer ): void {
		$debugTitle = '<b>::' . static::class . '::</b> ';
		$create = new Create();
		try {
			$finalPages = $create->writePages();
		} catch ( FlexFormException $e ) {
			throw new FlexFormException(
				$e->getMessage(), 0, $e
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
						$e->getMessage(), 0, $e
					);
				}
			}
			if ( $nrOfEdits > 1 ) {
				$slotsToSend = [];
				$overWrite = true;
				foreach ( $pContent as $singleCreate ) {
					$slotName = key( $singleCreate['slot'] );
					$slotValue = $singleCreate['slot'][$slotName];
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
						$e->getMessage(), 0, $e
					);
				}
			}
		}

		if ( !self::$fields['mwedit'] ) {
			Debug::addToDebug( $debugTitle . 'Handling WSCreate multiple duration',
				[],
				$timer->getDuration() );
		}
	}

	/**
	 * @param HandleResponse $response_handler
	 * @param string|bool $email
	 *
	 * @return HandleResponse
	 * @throws Exception
	 * @throws FlexFormException
	 * @throws MWContentSerializationException
	 */
	public static function saveToWiki( HandleResponse $response_handler, string|bool $email = false ): HandleResponse {
		if ( self::$isJob === false ) {
			self::handleSaveToWikiDefaults();
			$debugTitle = '<b>::' . static::class . '::</b> ';

			// mwcreateuser
			if ( self::$fields['createuser'] !== false && self::$fields['createuser'] !== '' ) {
				self::handleSaveToWikiCreateUser();
			}
			$timer = new DebugTimer();

			// WSCreate single
			if ( self::$fields['template'] !== false && self::$fields['writepage'] !== false ) {
				self::handleSaveToWikiCreateSingle( $timer );
				if ( !self::$fields['mwedit'] && !$email && !self::$fields['writepages'] ) {
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
				self::handleSaveToWikiCreateMultiple( $timer );

				if ( !self::$fields['mwedit'] && !$email ) {
					$response_handler->setMwReturn( self::$fields['returnto'] );
					$response_handler->setReturnType( HandleResponse::TYPE_SUCCESS );
					if ( self::$fields['msgOnSuccess'] !== false ) {
						$response_handler->setReturnData( self::$fields['msgOnSuccess'] );
					}
					return $response_handler;
				}
			}
		}

		// WSEdits
		if ( self::$isJob || self::$fields['mwedit'] !== false ) {
			$timer = new DebugTimer();
			$debugTitle = '<b>::' . get_class() . '::</b> ';

			$save = new Save();
			if ( isset( self::$fields['ffJob'] ) ) {
				$edit = new Edit( self::$fields['ffJob'], [ 'summary' => self::$fields['summary'] ] );
			} elseif ( self::$isJob ) {
				FlexFormJobLogger::logInfo( 'Handling job inside ContentCore',	self::$jobData );
				$edit = new Edit( 'jobRun', self::$jobData );
			} else {
				$edit = new Edit();
			}

			$pageContents = $edit->editPage();

			if ( self::$isJob ) {
				FlexFormJobLogger::logInfo( 'JOB: ContentCore.php: Edits done. Received contents.', self::$jobData );
			}
			Debug::addToDebug(
				$debugTitle . 'PageContent ',
				$pageContents,
				$timer->getDuration()
			);
			if ( !empty( $pageContents ) ) {
				foreach ( $pageContents as $pageContent ) {
					$slotContentArray = [];
					foreach ( $pageContent as $slotName => $singlePage ) {
						$slotContents = $singlePage['content'];
						$pTitle = $singlePage['title'];
						$slotContentArray[$slotName] = $slotContents;
					}
					if ( self::$isJob ) {
						self::$fields['summary'] = self::$jobSummary;
						FlexFormJobLogger::logInfo( 'ContentCore.php: Starting saveToWiki for pageId: ' .
							print_r( $slotContentArray, true ),
							self::$jobData );
					}
					try {
						$save->saveToWiki(
							$pTitle,
							$slotContentArray,
							self::$fields['summary']
						);
					} catch ( FlexFormException $e ) {
						throw new FlexFormException(
							$e->getMessage(), 0, $e
						);
					}
				}
			} elseif ( self::$fields['ffJob'] === 'jobCreate' ) {
				Debug::addToDebug(
					$debugTitle . 'This is a createJob. No further actions',
					[],
					$timer->getDuration()
				);
			}
		}
		if ( self::$isJob ) {
			return $response_handler;
		}
		$response_handler->setMwReturn( self::$fields['returnto'] );
		Debug::addToDebug(
			'Handling Edits duration',
			[],
			$timer->getDuration()
		);


		if ( $email === "yes" ) {
			$mailActions = General::getPostArray( 'mwmail' );
			if ( $mailActions ) {
				foreach ( $mailActions as $mailAction ) {

					$mailConfiguration = json_decode( base64_decode( $mailAction ), true );
					if ( $mailConfiguration === false || !is_array( $mailConfiguration ) ) {
						continue;
					}
					$mail = new Mail( false, $mailConfiguration );
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
			}
		}
		if ( $email === 'get' ) {
			$get              = new Get();
			$response_handler = $get->createGet( $response_handler );
		}

		$response_handler->setReturnType( HandleResponse::TYPE_SUCCESS );
		if ( isset( self::$fields['msgOnSuccess'] ) && self::$fields['msgOnSuccess'] !== false ) {
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
		if ( self::$fields['mwfollow'] !== false ) {
			if ( self::$fields['mwfollow'] === 'true' ) {
				if ( !str_contains( $title, '--id--' ) && !str_contains( $title, '::id::' ) ) {
					$title = ltrim( $title, '/' );
					$titleObject = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $title );
					self::$fields['returnto'] = $titleObject->getFullUrlForRedirect();
				}
			} else {
				if ( strpos( self::$fields['returnto'], '?' ) ) {
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
		return [ $slot => $value ];
	}

	/**
	 * For later use
	 * @param mixed $JSONValue
	 *
	 * @return mixed
	 */
	public static function checkJsonValues( $JSONValue ) {
		return $JSONValue;
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
	public static function createContent(): string {
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
	public static function createRandom( bool $mtRand = false ): int {
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
		Debug::addToDebug( 'checkCapitalTitle function', [ "title" => $title ] );

		$titleObject = Title::newFromText( $title );
		if ( $titleObject === null ) {
			return $title;
		}
		$ns = $titleObject->getNamespace();

		Debug::addToDebug( 'checkCapitalTitle function', [ "namespace" => $ns ] );

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
	 * @param string $pageTitle
	 *
	 * @return bool
	 */
	public static function doesPageExistsByName( string $pageTitle ): bool {
		return MediaWikiServices::getInstance()
			->getTitleFactory()
			->newFromText( $pageTitle )
			->exists();
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
				$fCount = self::getFileCount();
				$randomNumber = General::MakeTitle();
				if ( $fCount > 0 ) {
					$randomNumber .= '-' . $fCount;
				}
				$title = str_replace(
					'[' . $fieldname . ']',
					$randomNumber,
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
		Debug::addToDebug( 'Parsetitle result' . $t, $title );
		return $title;
	}

	/**
	 * @param string $template
	 * @param string $content
	 *
	 * @return string
	 */
	public static function setFileTemplate( string $template, string $content ): string {
		if ( str_contains( $content, '[flexform-template]' ) ) {
			$arrayS = [ '[flexform-template]', '[/flexform-template]', '|' ];
			$arrayR = [ '{{' . $template, "\n}}\n", "\n" . '|' ];
			$content = str_replace( $arrayS, $arrayR, $content );

		}
		return $content;
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public static function urlToSEO( string $string ): string {
		$separator     = '-';
		$accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
		$special_cases = [
			'&' => 'and',
			"'" => ''
		];
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
	 * @param string $nameStartsWith
	 *
	 * @return array|string[]
	 * @throws Exception
	 */
	public static function getNextAvailable( string $nameStartsWith ): array {
		$render   = new Render();
		$postdata = [
			"action"          => "flexform",
			"format"          => "json",
			"what"            => "nextAvailable",
			"titleStartsWith" => $nameStartsWith
		];
		$result   = $render->makeRequest( $postdata );
		Debug::addToDebug(
				'NextAvailable result ',
				$result
			);
		if ( isset( $result['flexform']['error'] ) ) {
			return ( [
				'status'  => 'error',
				'message' => $result['flexform']['error']['message']
			] );
		} elseif ( isset( $result['error'] ) ) {
			return ( [
				'status'  => 'error',
				'message' => $result['error']['info']
			] );
		} else {
			return ( [
				'status' => 'ok',
				'result' => $result['flexform']['result']
			] );
		}
		die();
	}

	/**
	 * @param string $nameStartsWith
	 * @param string $range
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getFromRange( string $nameStartsWith, string $range ): array {
		$postdata = [
			"action"          => "flexform",
			"format"          => "json",
			"what"            => "getRange",
			"titleStartsWith" => $nameStartsWith,
			"range"           => $range
		];
		$render   = new Render();
		$result   = $render->makeRequest( $postdata );

		Debug::addToDebug( 'getFromRange result ', $result );


		if ( isset( $result['flexform']['error'] ) ) {
			return ( [
				'status'  => 'error',
				'message' => $result['flexform']['error']['message']
			] );
		} elseif ( isset( $result['error'] ) ) {
			return ( [
				'status'  => 'error',
				'message' => $result['error']['info']
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
