<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : FlexForm
 * Filename    : signature.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 15:05
 */

namespace FlexForm\Processors\Files;

use flexform\processors\api\mediawiki\render;
use flexform\processors\api\mwApi;
use FlexForm\Processors\Utilities\General;
use FlexForm\FlexFormException;
use FlexForm\Core\Config;
use FlexForm\Processors\Files\FilesCore;

/**
 * Class signature
 * @package wsform\processors\files
 */
class Signature {

	/**
	 * Takes care of uploading a signature file
	 * @param $wsuid
	 *
	 * @return string|bool Either true on success or false
	 */
	public static function upload( $wsuid ) {
		global $IP;
		$allowedTypes = array(
			'png',
			'jpg',
			'svg'
		);

		$wname    = General::getPostString( 'wsform_signature_filename' );
		$data     = General::getPostString( 'wsform_signature' );
		$fileType = General::getPostString( 'wsform_signature_type' );
		$pcontent = General::getPostString( 'wsform_signature_page_content' );

		if ( ! $wname ) {
			throw new FlexFormException( 'No target file for signature.', 0 );
		}
		if ( ! $pcontent ) {
			throw new FlexFormException( 'No page content found. Required.', 0 );
		}
		if ( ! $data ) {
			throw new FlexFormException( 'No signature file found.', 0 );
		}
		if ( ! $fileType || ! in_array( $fileType, $allowedTypes ) ) {
			throw new FlexFormException( 'No signature file type found or a not allowed filetype', 0 );
		}

		$upload_dir = Config::getConfigVariable( 'file_temp_path' );
		$fileCore = new FilesCore();
		if ( $fileType !== 'svg' ) {
			$data = $fileCore->getBase64Data( $data );
		}

		// Test if directory already exists
		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir, 0755, true );
		}
		$fname = $fileCore->sanitizeFileName( $wname ) . "." . $fileType;
		//echo $fname;
		if ( ! file_put_contents( $upload_dir . $fname, $data ) ) {
			throw new FlexFormException( 'Could not save file.', 0 );
		}
		$fileCore = new FilesCore();
		$url     = Config::getConfigVariable( 'wgCanonicalServer' ) . 'extensions/FlexForm/uploads/' . $fname;
		$pname   = trim( $wname );
		$comment = "Uploaded using FlexForm.";
		$result  = $api->uploadFileToWiki( $pname, $url, $pcontent, $comment, $upload_dir . $fname );
		unlink( $upload_dir . $fname );

		return $result;
	}
}