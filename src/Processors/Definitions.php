<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : Definitions.php
 * Description :
 * Date        : 28-12-2021
 * Time        : 12:35
 */

namespace FlexForm\Processors;

use FlexForm\Processors\Files\FilesCore;
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
	 * @return array[]
	 */
	public static function alertColor(): array {
		return [
			'success' => [ 'background' => '#d4edda', 'color' => '#155724' ],
			'warn' => [ 'background' => '#fff3cd', 'color' => '#856404' ],
			'error' => [ 'background' => '#721c24;', 'color' => '#f8d7da' ],
			'info' => [ 'background' => '#d1ecf1', 'color' => '#0c5460' ]
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


	public static function fileUploadFields(): array {
		$files = $_FILES ?? false;
		$uploadActions = General::getPostString( 'ff_upload_actions', false );
		if ( $uploadActions !== false ) {
			$uploadActions = json_decode( base64_decode( $uploadActions ), true );
		} else {
			$uploadActions = null;
		}
		return [
			'files' => $files,
			'actions' => $uploadActions
		];
		/*
		return [
			'files'        => $files,
			'pagetemplate' => General::getPostString( 'wsform_file_template' ),
			'pagecontent'  => General::getPostString( 'wsform_page_content', false ),
			'parsecontent' => General::getPostString( 'wsform_parse_content' ),
			'comment'      => General::getPostString( 'wsform-upload-comment' ),
			'returnto'     => General::getPostString(
				'mwreturn',
				false
			),
			'target'       => General::getPostString( 'wsform_file_target' ),
			'force'        => General::getPostString( 'wsform_image_force' ),
			'convertFrom'  => General::getPostString( 'wsform_convert_from' )
		];
		*/
	}

	/**
	 * Get all the fields needed to edit or create a page
	 *
	 * @return array
	 */
	public static function createAndEditFields() : array {
		return [
			'parsePost'       => General::getPostArray( 'wsparsepost' ),
			'parseLast'       => General::getPostString( 'mwparselast' ),
			'etoken'          => General::getPostString( 'wsedittoken' ),
			'template'        => General::getPostString( 'mwtemplate' ),
			'writepage'       => General::getPostString( 'mwwrite' ),
			'option'          => General::getPostString( 'mwoption' ),
			'returnto'        => General::getPostString( 'mwreturn',
				false ),
			'returnfalse'     => General::getPostString( 'mwreturnfalse' ),
			'mwedit'          => General::getPostArray( 'mwedit' ),
			'writepages'      => General::getPostArray( 'mwcreatemultiple' ),
			'msgOnSuccess'    => General::getPostString( 'mwonsuccess' ),
			'mwfollow'        => General::getPostString( 'mwfollow' ),
			'leadByZero'      => false,
			'summary'         => General::getPostString( 'mwwikicomment' ),
			'slot'            => General::getPostString( 'mwslot' ),
			'createuser'      => General::getPostString( 'mwcreateuser' ),
			'nooverwrite'     => General::getPostString( 'mwnooverwrite' ),
			'format'          => General::getPostString( 'mwformat' ),
			'formpermissions' => General::getPostString( 'mwformpermissions' ),
			'separator'       => General::getPostString( 'ff_separator' ),
			'skipSeo'		  => General::getPostString( 'mwnoseo' )
		];
	}

	/**
	 * @param string $field
	 *
	 * @return bool
	 */
	public static function isFlexFormUploaderVariables( string $field, $checkFileUploadVars ) : bool {
		if ( $checkFileUploadVars === false ) {
			return false;
		}
		$flexFormUploaderAddedFields = [
			'FFUploadedFile-UploadName',
			'FFUploadedFile-UploadBase',
			'FFUploadedFile-NewName'
		];
		foreach ( $flexFormUploaderAddedFields as $uField ) {
			if ( strpos( $field, $uField ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check to see if a variable is a FlexForm variable
	 *
	 * @param string $field field to check
	 * @param bool $checkFileUploadVars field to check
	 * @return bool true or false
	 */
	public static function isFlexFormSystemField( string $field, bool $checkFileUploadVars = true ) : bool {
		$FlexFormSystemFields = [
			"mwaction",
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
			"showonselect",
			"mwcreateuser",
			"mwnooverwrite",
			"mwslot",
			"mwformat",
			"ff_upload_actions",
			'ff_separator',
			'mwformpermissions',
			'ff-message'
		];
		if ( in_array(
			strtolower( $field ),
			$FlexFormSystemFields
		) ) {
			return true;
		} elseif ( self::isFlexFormUploaderVariables( $field, $checkFileUploadVars ) !== false ) {
			return true;
		} else {
			return false;
		}
	}
}