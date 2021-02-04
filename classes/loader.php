<?php
/**
 * Created by  : Designburo.nl
 * Project     : i
 * Filename    : Autoloader.php
 * Description :
 * Date        : 12/04/2019
 * Time        : 20:48
 */

namespace wsform;


abstract class WSClassLoader {
	/**
	 * register the classes
	 */
	public static function register() {
		global $IP;
		$classPath = $IP . '/extensions/WSForm/classes/*.class.php';
		foreach ( glob($classPath) as $fileName ) {
			include_once $fileName;
		}
	}

}