<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : i
 * Filename    : flexform.class.php
 * Description :
 * Date        : 11/04/2019
 * Time        : 21:55
 */

namespace FlexForm\Core;

use MediaWiki\MediaWikiServices;

class Core {

	private const FORMPERMISSION = [ 'can-edit', 'can-create' ];

	public const DIVIDER = '-^^-';

	/**
	 * @var bool
	 */
	public static $showOnSelectSet = false;

	/**
	 * @var string
	 */
	public static $separator = ",";

	/**
	 * Globally set as a variable to check if the posting add a reCaptcha v3 and to disable ajax submit
	 */
	public static $reCaptcha = false;

	/**
	 * Will be set to true if FlexForm has already been initialize (when having multiple FlexForms on a page)
	 */
	static $haveIBeenRun = false;


	/**
	 * Array holding scripts already loaded by FlexForm
	 */
	static $loadedScripts = array();

	public static $gValues = true;

	/**
	 * @var array $chkSums
	 * Holds checksum for every field in the form
	 */
	public static $checksumKey = false;

	public static $chkSums = array();

	public static $securityId = "";

	public static $runAsUser = false;

	public static $wsConfig = false;

	private static $javaScript = array();

	private static $cssStyles = array();

	private static $javaScriptConfigVars = array();

	public static $presavedValues = [];


	/**
	 * @return string
	 * @throws \MWException
	 */
	public static function getAPIurl() {
		return \SpecialPage::getTitleFor( 'FlexForm' )->getFullUrlForRedirect();
	}

	/**
	 * @return void
	 */
	public static function setShowOnSelectActive() {
		self::$showOnSelectSet = true;
	}

	/**
	 * @param string $sep
	 *
	 * @return void
	 */
	public static function setSeparator( string $sep ) {
		self::$separator = $sep;
	}

	/**
	 * @param array $array
	 *
	 * @return void
	 */
	public static function addPreSaved( array $array ) {
		self::$presavedValues = $array;
	}

	/**
	 * @param string $name
	 *
	 * @return false|mixed
	 */
	public static function getPreSavedKey( string $name ) {
		if ( array_key_exists( $name, self::$presavedValues ) ) {
			return self::$presavedValues[$name];
		} else {
			return false;
		}
	}

	/**
	 * @param string $permission
	 *
	 * @return bool
	 */
	public static function isAllowedFormPermission( string $permission ): bool {
		return in_array( $permission, self::FORMPERMISSION );
	}

	/**
	 * @param string|array $permissions
	 *
	 * @return bool
	 */
	public static function isAllowedToOverideEdit( $permissions ): bool {
		if ( strpos( $permissions, 'can-edit' ) !== false ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string|array $permissions
	 *
	 * @return bool
	 */
	public static function isAllowedToOverideCreate( $permissions ): bool {
		if ( strpos( $permissions, 'can-create' ) !== false ) {
			return true;
		} else {
			return false;
		}
	}

	public static function isShowOnSelectActive() {
		return self::$showOnSelectSet;
	}

	/**
	 * @param string $string
	 * @param string $start
	 * @param string $end
	 *
	 * @return false|string
	 */
	public static function get_string_between( string $string, string $start, string $end ) {
		$string = ' ' . $string;
		$ini    = strpos(
			$string,
			$start
		);
		if ( $ini == 0 ) {
			return '';
		}
		$ini += strlen( $start );
		$len = strpos(
				   $string,
				   $end,
				   $ini
			   ) - $ini;

		return substr(
			$string,
			$ini,
			$len
		);
	}

	/**
	 * This will store session information for the next time a page is reloaded.
	 * It well then show a message from the session on screen
	 *
	 * @param string $type with what kind of message (danger, success, etcc)
	 * @param string $msg Actual message to sow
	 */
	public static function makeMessage( string $type, string $msg ) {
		$_SESSION['wsform']['messages'][]['type'] = $type;
		$_SESSION['wsform']['messages'][]['txt']  = $msg;
	}

	/**
	 * Gets parser value for a key
	 *
	 * @param string $k key name
	 *
	 * @return string either the value of the key or an empty string
	 */
	public static function getValue( $k, $apo = false ): string {
		if ( !self::$gValues ) {
			return "";
		}

		$k = str_replace(
			" ",
			"_",
			$k
		);
		$possiblePreSavedValue = self::getPreSavedKey( $k );

		if ( isset( $_GET[$k] ) ) {
			return htmlentities( $_GET[$k] );
		} elseif ( $possiblePreSavedValue !== false ) {
			return $possiblePreSavedValue;
		} else {
			return "";
		}
	}

	/**
	 * @param string $input
	 *
	 * @return string
	 */
	public static function checkForShowOnSelectValueAndType( string $input ): string {
		if ( strpos(
			$input,
			'show-on-select-trigger='
		) ) {
			$input = str_replace(
				'show-on-select-trigger=',
				'data-wssos-value=',
				$input
			);
		}
		if ( strpos(
			$input,
			'show-on-select-type='
		) ) {
			$input = str_replace(
				'show-on-select-type=',
				'data-wssos-type=',
				$input
			);
		}
		if ( strpos(
			$input,
			'show-on-select='
		) ) {
			$input = str_replace(
				'show-on-select=',
				'data-wssos-show=',
				$input
			);
		}
		return $input;
	}

	/**
	 * @return string
	 */
	public static function addShowOnSelectJS(): string {
		if ( ! self::isLoaded( 'ShowOnSelect' ) ) {
			$out = \RequestContext::getMain()->getOutput();
			$out->addJsConfigVars( array( "WSFormShowOnSelect" => true ) );
			$js = 'wachtff( WsShowOnSelect, true );';
			//wsform::includeInlineScript( $js );
			$jsFile  = '<script type="text/javascript" charset="UTF-8" src="' . Core::getRealUrl() . '/Modules/showOnSelect/WSShowOnSelect.js"></script>' . "\n";
			$jsFile  .= '<script>' . $js . '</script>';
			self::addAsLoaded( 'ShowOnSelect' );

			return $jsFile;
		} else {
			return '';
		}
	}

	/**
	 * @brief Add script to list of loaded script
	 *
	 * @param string $name JavaScript filename
	 */
	public static function addAsLoaded( string $name ) {
		self::$loadedScripts[] = $name;
	}

	/**
	 * @brief Add script to list of loaded script
	 *
	 * @param string $name JavaScript filename
	 */
	public static function removeAsLoaded( string $name ) {
		if ( $key = array_search(
						$name,
						self::$loadedScripts
					) !== false ) {
			unset( self::$loadedScripts[$key] );
		}
	}

	/**
	 * @brief Add script to be included
	 *
	 * @param string $src JavaScript source (without <script>)
	 */
	public static function includeInlineScript( string $src ) {
		global $wgFlexFormConfig;
		//echo round( microtime( true ) * 1000 ) . "_add <pre>$src</pre>";
		$wgFlexFormConfig['loaders']['javascript'][] = $src;
		//self::$javaScript[] = $src;
	}


	/**
	 * @brief Add Files upload information
	 *
	 * @param string $fileInfo File information array
	 */
	public static function includeFileAction( array $fileInfo ) {
		global $wgFlexFormConfig;
		//echo round( microtime( true ) * 1000 ) . "_add <pre>$src</pre>";
		$wgFlexFormConfig['loaders']['files'][] = $fileInfo;
		//self::$javaScript[] = $src;
	}

	/**
	 * @brief Add JavaScript tags to be included
	 *
	 * @param string $src JavaScript source url
	 */
	public static function includeTagsScript( string $src ) {
		global $wgFlexFormConfig;
		//echo round( microtime( true ) * 1000 ) . "_add <pre>$src</pre>";
		$wgFlexFormConfig['loaders']['javascripttag'][] = $src;
		//self::$javaScript[] = $src;
	}

	/**
	 * @brief Add CSS tags to be included
	 *
	 * @param string $src CSS source url
	 */
	public static function includeTagsCSS( string $src ) {
		global $wgFlexFormConfig;
		//echo round( microtime( true ) * 1000 ) . "_add <pre>$src</pre>";
		$wgFlexFormConfig['loaders']['csstag'][] = $src;
		//self::$javaScript[] = $src;
	}

	/**
	 * Retrieve list of JavaScript to be loaded inline
	 *
	 * @return array
	 */
	public static function getJavaScriptToBeIncluded(): array {
		global $wgFlexFormConfig;
		return $wgFlexFormConfig['loaders']['javascript'] ?? [];
	}

	/**
	 * Retrieve list of Files to be loaded added
	 *
	 * @return array
	 */
	public static function getFileActions():array {
		global $wgFlexFormConfig;
		return $wgFlexFormConfig['loaders']['files'] ?? [];
	}

	/**
	 * Retrieve list of JavaScript to be loaded as script tags
	 *
	 * @return array
	 */
	public static function getJavaScriptTagsToBeIncluded():array {
		global $wgFlexFormConfig;
		return $wgFlexFormConfig['loaders']['javascripttag'] ?? [];
	}

	/**
	 * Retrieve list of JavaScript to be loaded as script tags
	 *
	 * @return array
	 */
	public static function getCSSTagsToBeIncluded():array {
		global $wgFlexFormConfig;
		return $wgFlexFormConfig['loaders']['csstag'] ?? [];
	}

	/**
	 * Retrieve list of CSS to be loaded inline
	 *
	 * @return array
	 */
	public static function getCSSToBeIncluded():array {
		global $wgFlexFormConfig;
		return $wgFlexFormConfig['loaders']['css'] ?? [];
	}

	/**
	 * Clear CSS list to be loaded inline
	 */
	public static function cleanCSSList() {
		global $wgFlexFormConfig;
		$wgFlexFormConfig['loaders']['css'] = [];
		//self::$cssStyles = array();
	}

	/**
	 * Clear CSS tags list to be loaded
	 */
	public static function cleanCSSTagsList() {
		global $wgFlexFormConfig;
		$wgFlexFormConfig['loaders']['csstag'] = [];
		//self::$cssStyles = array();
	}

	/**
	 * Clear JavaScript list to be loaded inline
	 */
	public static function cleanJavaScriptList() {
		global $wgFlexFormConfig;
		$wgFlexFormConfig['loaders']['javascript'] = [];
		//self::$javaScript = array();
	}

	public static function cleanFileActions(){
		global $wgFlexFormConfig;
		$wgFlexFormConfig['loaders']['files'] = [];
	}

	/**
	 * Clear JavaScript tags list to be loaded
	 */
	public static function cleanJavaScriptTagsList() {
		global $wgFlexFormConfig;
		$wgFlexFormConfig['loaders']['javascripttag'] = [];
		//self::$javaScript = array();
	}

	/**
	 * @brief Add css to be included
	 *
	 * @param string $src CSS source (without <style>)
	 */
	public static function includeInlineCSS( string $src ) {
		global $wgFlexFormConfig;
		$wgFlexFormConfig['loaders']['css'][] = $src;
		//self::$cssStyles[] = $src;
	}

	/**
	 * @brief Add javascript config variables included
	 *
	 * @param string $k JavaScript source name (without <script>)
	 * @param mixed $v value
	 */
	public static function includeJavaScriptConfig( string $k, $v ) {
		global $wgFlexFormConfig;
		if ( isset( $wgFlexFormConfig['loaders']['jsconfigvars'][$k] ) ) {
			if ( is_array( $wgFlexFormConfig['loaders']['jsconfigvars'][$k] ) ) {
				$wgFlexFormConfig['loaders']['jsconfigvars'][$k][] = $v;
			} else {
				$tmpValue                                          = $wgFlexFormConfig['loaders']['jsconfigvars'][$k];
				$wgFlexFormConfig['loaders']['jsconfigvars'][$k][] = $tmpValue;
				$wgFlexFormConfig['loaders']['jsconfigvars'][$k][] = $v;
			}
		} else {
			$wgFlexFormConfig['loaders']['jsconfigvars'][$k][] = $v;
		}
	}

	/**
	 * Retrieve list of JavaScript config to be loaded inline
	 *
	 * @return array
	 */
	public static function getJavaScriptConfigToBeAdded() : array {
		global $wgFlexFormConfig;
		return $wgFlexFormConfig['loaders']['jsconfigvars'];
		//return self::$javaScriptConfigVars;
	}

	/**
	 * @return string
	 */
	public static function getRealUrl(): string {
		global $wgExtensionAssetsPath;
		$uri = MediaWikiServices::getInstance()->getUrlUtils()->getServer( null );
		$uri .= $wgExtensionAssetsPath . '/FlexForm';
		/*
		$uri = str_replace(
			'/index.php',
			'',
			$wgScript
		);
		*/
		//var_dump( $uri );
		return $uri;
	}

	/**
	 * Clear JavaScript config vars list to be loaded inline
	 */
	public static function cleanJavaScriptConfigVars() {
		global $wgFlexFormConfig;
		$wgFlexFormConfig['loaders']['jsconfigvars'] = [];
	}

	/**
	 * @brief Check if a JavaScript is already loaded
	 *
	 * @param string $name Name of JavaScript file
	 *
	 * @return bool true or false
	 */
	public static function isLoaded( string $name ): bool {
		if ( in_array(
			$name,
			self::$loadedScripts
		) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @brief Set FlexForm as being initiated
	 *
	 * @param $val bool true or false
	 */
	public static function setRun( $val ) {
		self::$haveIBeenRun = $val;
	}

	/**
	 * @brief Check if FlexForm is already initialized
	 * @return bool true or false
	 */
	public static function getRun() {
		return self::$haveIBeenRun;
	}

	/**
	 * @param string $url
	 *
	 * @return mixed|\SimpleXMLElement
	 */
	public static function getMWReturn( string $url ) {
		if ( strpos(
				 $url,
				 'http'
			 ) === false ) {
			return $url;
		}
		libxml_use_internal_errors( true );
		try {
			$xml = new \SimpleXMLElement( $url );
		} catch ( \Exception $exception ) {
			return $url;
		}

		if ( isset( $xml['href'] ) ) {
			return $xml['href'];
		} else {
			return $url;
		}
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return string
	 * @throws \FlexForm\FlexFormException
	 */
	public static function createHiddenField( string $name, $value ): string {
		if ( Config::isSecure() ) {
			Protect::setCrypt( self::$checksumKey );
			$name  = Protect::encrypt( $name );
			$value = Protect::encrypt( $value );
			self::addCheckSum(
					'secure',
					$name,
					$value,
					"all"
				);
		}
		return '<input type="hidden" name="' . $name . '" value="' . $value . '">' . "\n";
	}

	/**
	 * @param string $type
	 * @param string $name
	 * @param mixed $value
	 * @param string|array $allowHTML
	 *
	 * @return void
	 */
	public static function addCheckSum( string $type, string $name, $value, $allowHTML = "default" ) {
		if ( Config::isSecure() ) {
			$formId = self::$securityId;
			if ( $type === 'secure' ) {
				self::$chkSums[$formId][$type][] = [
					"name"  => $name,
					"value" => $value,
					"html"  => $allowHTML
				];
			} else {
				self::$chkSums[$formId][$name] = [
					"type"  => $type,
					"value" => $value,
					"html"  => $allowHTML
				];
			}
		}
	}

}