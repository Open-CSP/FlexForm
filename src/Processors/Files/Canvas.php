<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : FlexForm
 * Filename    : canvas.class.php
 * Description :
 * Date        : 12/05/2022
 * Time        : 15:05
 */

namespace FlexForm\Processors\Files;

use FlexForm\Core\Debug;
use flexform\processors\api\mediawiki\render;
use flexform\processors\api\mwApi;
use FlexForm\Processors\Content\ContentCore;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Utilities\General;
use FlexForm\FlexFormException;
use FlexForm\Core\Config;
use FlexForm\Processors\Files\FilesCore;

/**
 * Class canvas
 * @package FlexForm\Processors\Files
 */
class Canvas {


	/**
	 * @return string
	 */
	private static function getSummary(): string {
		$summary = General::getPostString( 'mwwikicomment' );
		if ( $summary === false ) {
			return "Uploaded using FlexForm.";
		} else {
			return ContentCore::parseTitle( $summary );
		}
	}

	/**
	 * @param string $data
	 *
	 * @return false|string
	 */
	private static function cleanBase64( string $data ) {
		$base64img = str_replace( 'data:image/jpeg;base64,', '', $data );
		return base64_decode( $base64img );
	}

	/**
	 * @param string $canvasfile
	 *
	 * @return bool
	 * @throws FlexFormException
	 * @throws \MWContentSerializationException
	 * @throws \MWException
	 */
	public static function upload( string $canvasFile ) {
		global $wgUser;
		$fields = Definitions::fileUploadFields();
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'File upload start',
							   [
								   'fields' => $fields,
								   'post'   => $_POST
							   ] );
		}

		if ( $fields['target'] === false || $fields['target'] === '' ) {
			throw new FlexFormException(
				wfMessage( 'flexform-fileupload-no-target' )->text(),
				0
			);
		}

		if ( $fields['pagecontent'] === false ) {
			$fields['pagecontent'] = '';
		}

		$fields['comment'] = self::getSummary();

		$filesCore = new FilesCore();
		$uploadPath = $filesCore->getUploadDir();
		$canvasFile = $filesCore->getBase64Data( $canvasFile );
		$pageName = $filesCore->remove_extension_from_image(
				General::makeUnderscoreFromSpace( $fields['target'] )
			) . ".jpg";

		$tmpFileName = uniqid() . '.jpg';

		// Test if directory already exists
		if ( !is_dir( $uploadPath ) ) {
			mkdir( $uploadPath, 0755, true );
		}

		if ( !file_put_contents( $uploadPath . $tmpFileName, $canvasFile ) ) {
			throw new FlexFormException(
				wfMessage( 'flexform-fileupload-filemove-error' )->text(),
				0
			);
		}

		$details = trim( $fields['pagecontent'] );

		if ( $fields['pagetemplate'] && $fields['parsecontent'] !== false ) {
			$filePageTemplate = trim( $fields['pagetemplate'] );
			$details = ContentCore::setFileTemplate( $filePageTemplate, $details );
		}

		if ( $fields['parsecontent'] !== false ) {
			$details = ContentCore::parseTitle( $details );
		}

		// find any other form fields and put them into the title
		$pageName = ContentCore::parseTitle( $pageName );
		$uploadFile = new Upload();
		$resultFileUpload = $uploadFile->uploadFileToWiki(
			$uploadPath . $tmpFileName,
			$pageName,
			$wgUser,
			$details,
			$fields['comment'],
			wfTimestampNow()
		);
		if ( $resultFileUpload !== true ) {
			throw new FlexFormException(
				$resultFileUpload,
				0
			);
		}

		unlink( $uploadPath . $tmpFileName );

		return true;
	}
}