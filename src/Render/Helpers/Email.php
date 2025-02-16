<?php
/**
 * Created by  : Designburo.nl
 * Project     : FlexForm
 * Filename    : Email.php
 * Description :
 * Date        : 15-2-2023
 * Time        : 14:44
 */

namespace FlexForm\Render\Helpers;

use Parser;
use PPFrame;

class Email {

	private static array $emailArgument = [
		'to'        => 'mwmailto',
		'subject'   => 'mwmailsubject',
		'job'       => 'mwmailjob',
		'template'  => 'mwmailtemplate',
		'parselast' => 'mwparselast'
	];

	/**
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return array
	 */
	public static function getEmailParameters( array $args, Parser $parser, PPFrame $frame ): array {
		$mailArguments = [];

		foreach ( self::$emailArgument as $emailArg => $formArg ) {
			if ( isset( $args[$emailArg] ) ) {
				if ( $emailArg === "parselast" ) {
					$mailArguments[$formArg] = "true";
				} else {
					$mailArguments[$formArg] = $parser->recursiveTagParse(
						$args[$emailArg],
						$frame
					);
				}
			}
		}
		return $mailArguments;
	}

}