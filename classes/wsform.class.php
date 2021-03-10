<?php
/**
 * Created by  : Designburo.nl
 * Project     : i
 * Filename    : wsform.class.php
 * Description :
 * Date        : 11/04/2019
 * Time        : 21:55
 */

namespace wsform;


class wsform {


    /**
     * Globally set as a variable to check if the posting add a reCaptcha v3 and to disable ajax submit
     */
    public static $reCaptcha = false;

	/**
	 * Will be set to true if WSForm has already been initialize (when having multiple WSForms on a page)
	 */
	static $haveIBeenRun = false;


	/**
	 * Array holding scripts already loaded by WSForm
	 */
	static $loadedScripts = array();

	/**
	 * @var string $msg_unverified_email
	 * i18n Holds the text when a user has an unverified email address
	 */
	public static $msg_unverified_email = '';

	/**
	 * @var string $msg_anonymous_user
	 * i18n Holds the text for anonymous users
	 */
	public static $msg_anonymous_user = '';

	public static $gValues = true;

	/**
	 * @var array $chkSums
	 * Holds checksum for every field in the form
	 */
	public static $checksumKey = false;

	public static $chkSums = array();

	public static $formId = "";

	public static $secure = false;

	public static $runAsUser = false;

	public static $wsConfig = false;

	private static $javaScript = array();

	private static $cssStyles = array();

	private static $javaScriptConfigVars = array();

	/**
	 * @return string
	 */
	public static function getAPIurl() {
		global $wgScript;
		if (php_sapi_name() != "cli") {
		    $apiUrl = rtrim( $wgScript, 'index.php' );
			$dir = $apiUrl . 'extensions/WSForm/WSForm.api.php';
		} else $dir='/extensions/WSForm/WSForm.api.php';
		return $dir;
	}

	/**
	 * Simple function to get a string between string (start and end)
	 *
	 * @param $string String to search in
	 * @param $start Start element for searching
	 * @param $end End element for searching
	 * @return bool false when not found
	 * @return string text in between start and end
	 *
	 */
	public static function get_string_between( $string, $start, $end ) {
		$string = ' ' . $string;
		$ini    = strpos( $string, $start );
		if ( $ini == 0 ) {
			return '';
		}
		$ini += strlen( $start );
		$len = strpos( $string, $end, $ini ) - $ini;

		return substr( $string, $ini, $len );
	}

	/**
	 * This will store session information for the next time a page is reloaded.
	 * It well then show a message from the session on screen
	 *
	 * @param $type string with what kind of message (danger, success, etcc)
	 * @param $msg the Actual message to sow
	 */
	public static function makeMessage( $type, $msg ) {
		$_SESSION['wsform']['messages'][]['type'] = $type;
		$_SESSION['wsform']['messages'][]['txt']  = $msg;
	}

	/**
	 * Gets parser value for a key
	 *
	 * @param $k string key name
	 * @return string either the value of the key or an empty string
	 */
	public static function getValue( $k, $apo = false ) {
		if ( ! self::$gValues ) {
			return "";
		}
		$k = str_replace( " ", "_", $k );


		if ( isset( $_GET[ $k ] ) ) {
			return htmlentities( $_GET[ $k ] );
			//return $tmp;
			$tmp = str_replace( '"', "", $tmp );
			if( $apo ) {
				$tmp = str_replace( "'", "", $tmp );
			}

			return $tmp;
		} else {
			return "";
		}


	}

	/**
	 * @brief Add script to list of loaded script
	 *
	 * @param $name string JavaScript filename
	 */
	public static function addAsLoaded($name) {
		self::$loadedScripts[] = $name;
	}

	/**
	 * @brief Add script to be included
	 *
	 * @param $src string JavaScript source (without <script>)
	 */
	public static function includeInlineScript( $src ) {
		self::$javaScript[] = $src;
	}

	/**
	 * Retrieve list of JavaScript to be loaded inline
	 * @return array
	 */
	public static function getJavaScriptToBeIncluded(){
		return self::$javaScript;
	}

	/**
	 * Retrieve list of CSS to be loaded inline
	 * @return array
	 */
	public static function getCSSToBeIncluded(){
		return self::$cssStyles;
	}

	/**
	 * Clear CSS list to be loaded inline
	 */
	public static function cleanCSSList(){
		self::$cssStyles = array();
	}

	/**
	 * Clear JavaScript list to be loaded inline
	 */
	public static function cleanJavaScriptList(){
		self::$javaScript = array();
	}

	/**
	 * @brief Add css to be included
	 *
	 * @param $src string CSS source (without <style>)
	 */
	public static function includeInlineCSS( $src ) {
		self::$cssStyles[] = $src;
	}

	/**
	 * @brief Add javascript config variables included
	 *
	 * @param $k string JavaScript source name (without <script>)
	 * @param $v string id of the form
	 */
	public static function includeJavaScriptConfig( string $k, string $v ) {
		if( isset( self::$javaScriptConfigVars[$k] ) ) {
			if( is_array( self::$javaScriptConfigVars[$k] ) ) {
				self::$javaScriptConfigVars[$k][] = $v;
			} else {
				$tmpValue = self::$javaScriptConfigVars[$k];
				self::$javaScriptConfigVars[$k][] = $tmpValue;
			}
		}
		self::$javaScriptConfigVars[$k] = $v;
	}

	/**
	 * Retrieve list of JavaScript config to be loaded inline
	 * @return array
	 */
	public static function getJavaScriptConfigToBeAdded(): array {
		return self::$javaScriptConfigVars;
	}

	/**
	 * Clear JavaScript config vars list to be loaded inline
	 */
	public static function cleanJavaScriptConfigVars(){
		self::$javaScriptConfigVars = array();
	}

	/**
	 * @brief Check if a JavaScript is already loaded
	 * @param $name string Name of JavaScript file
	 * @return bool true or false
	 */
	public static function isLoaded($name) {
		if( in_array( $name, self::$loadedScripts ) ) {
			return true;
		} else return false;
	}

	/**
	 * @brief Set WSForm as being initiated
	 *
	 * @param $val bool true or false
	 */
	public static function setRun($val) {
		self::$haveIBeenRun = $val;
	}

	/**
	 * @brief Check if WSForm is already initialized
	 * @return bool true or false
	 */
	public static function getRun() {
		return self::$haveIBeenRun;
	}

	public static function createHiddenField( $name, $value ) {
		if( \wsform\wsform::$secure ) {
			\wsform\protect\protect::setCrypt( \wsform\wsform::$checksumKey );
			$name  = \wsform\protect\protect::encrypt( $name );
			$value = \wsform\protect\protect::encrypt( $value );
			\wsform\wsform::addCheckSum( 'secure', $name, $value, "all" );
		}
		return '<input type="hidden" name="' . $name . '" value="' . $value . '">' . "\n";
	}

	public static function addCheckSum( $type, $name, $value, $allowHTML = "default" ) {
		if ( \wsform\wsform::$secure ) {
			$formId = \wsform\wsform::$formId;
			if( $type === 'secure' ) {
				self::$chkSums[ $formId ][ $type ][] = array(
					"name"  => $name,
					"value" => $value,
					"html"  => $allowHTML
				);
			} else {
				self::$chkSums[ $formId ][ $name ] = array(
					"type"  => $type,
					"value" => $value,
					"html"  => $allowHTML
				);
			}
		}
	}



}