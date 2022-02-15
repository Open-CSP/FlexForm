<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWWSForm
 * Filename    : Definitions.php
 * Description :
 * Date        : 28-12-2021
 * Time        : 12:35
 */

namespace FlexForm\Processors;

use FlexForm\Processors\Utilities\General;

class Definitions {


	/**
	 * @return array
	 */
	public static function getImageHandler() : array {
		return [
			IMAGETYPE_JPEG => [
				'load'    => 'imagecreatefromjpeg',
				'save'    => 'imagejpeg',
				'quality' => 100
			],
			IMAGETYPE_PNG  => [
				'load'    => 'imagecreatefrompng',
				'save'    => 'imagepng',
				'quality' => 0
			],
			IMAGETYPE_GIF  => [
				'load' => 'imagecreatefromgif',
				'save' => 'imagegif'
			]
		];
	}

	/**
	 * Return fields needed for sending emails
	 *
	 * @return array
	 */
	public static function mailFields() : array {
		return [
			'to'         => General::getPostString( 'mwmailto' ),
			'content'    => General::getPostString( 'mwmailcontent' ),
			'header'     => General::getPostString( 'mwmailheader' ),
			'footer'     => General::getPostString( 'mwmailfooter' ),
			'mtemplate'  => General::getPostString( 'mwmailtemplate' ),
			'mjob'       => General::getPostString( 'mwmailjob' ),
			'html'       => General::getPostString( 'mwmailhtml' ),
			'attachment' => General::getPostString( 'mwmailattachment' ),
			'from'       => false,
			'cc'         => false,
			'bcc'        => false,
			'reply-to'   => false,
			'subject'    => false
		];
	}

	/**
	 * Get all the fields needed to edit or create a page
	 *
	 * @return array
	 */
	public static function createAndEditFields() : array {
		return [
			'parsePost'    => General::getPostString( 'wsparsepost' ),
			'parseLast'    => General::getPostString( 'mwparselast' ),
			'etoken'       => General::getPostString( 'wsedittoken' ),
			'template'     => General::getPostString( 'mwtemplate' ),
			'writepage'    => General::getPostString( 'mwwrite' ),
			'option'       => General::getPostString( 'mwoption' ),
			'returnto'     => General::getPostString(
				'mwreturn',
				false
			),
			'returnfalse'  => General::getPostString( 'mwreturnfalse' ),
			'mwedit'       => General::getPostArray( 'mwedit' ),
			'writepages'   => General::getPostArray( 'mwcreatemultiple' ),
			'msgOnSuccess' => General::getPostString( 'mwonsuccess' ),
			'mwfollow'     => General::getPostString( 'mwfollow' ),
			'leadByZero'   => false,
			'summary'      => General::getPostString( 'mwwikicomment' ),
			'slot'         => General::getPostString( 'mwslot' )
		];
	}

	/**
	 * Check to see if a variable is a WSForm variable
	 *
	 * @param string $field field to check
	 *
	 * @return bool true or false
	 */
	public static function isWSFormSystemField( string $field ) : bool {
		$WSFormSystemFields = [
			"mwtemplate",
			"mwoption",
			"mwwrite",
			"mwreturn",
			"mwidentifier",
			"mwedit",
			"wsform_file_target",
			"wsform_page_content",
			"wsformfile",
			"wsform_image_force",
			"mwmailto",
			"mwmailfrom",
			"mwmailcc",
			"mwmailbcc",
			"mwmailsubject",
			"mwmailfooter",
			"mwmailheader",
			"mwmailcontent",
			"mwmailhtml",
			"mwmailattachment",
			"mwmailtemplate",
			"mwmailjob",
			"mwcreatemultiple",
			"mwonsuccess",
			"mwdb",
			"mwextension",
			"wsedittoken",
			"mwfollow",
			"wsparsepost",
			"mwtoken",
			"wsuid",
			"mwwikicomment",
			"mwleadingzero",
			"showonselect"
		];
		if ( in_array(
			strtolower( $field ),
			$WSFormSystemFields
		) ) {
			return true;
		} else {
			return false;
		}
	}
}