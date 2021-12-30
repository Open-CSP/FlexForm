<?php
/**
 * Created by  : Designburo.nl
 * Project     : wsformWikiBaseNL
 * Filename    : upload.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 17:54
 */

namespace WSForm\Processors\Files;

use wsform\processors\utilities\wsUtilities;
use wsform\processors\wbHandleResponses;
use wsform\processors\Wsi18n;

class Upload {
	/**
	 * Normal HTML5 file upload handling
	 * TODO: Add upload of multiple files, by passing file to be uploaded, instead of using $_FILES
	 * TODO: Make i18n messages
	 *
	 * @param $api
	 *
	 * @return array|bool
	 */
	public function fileUpload( $api, $responses ) {
		$i18n = new wsi18n();
		if ( ! isset( $_FILES['wsformfile'] ) ) {
			$responses->setReturnData( 'no wsformfile file found' );
			$responses->setReturnStatus( 'error' );

			return false;
		}
		if ( $fileChk = FilesCore::checkFileUploadForError( $_FILES['wsformfile'] ) ) {
			$responses->setReturnData( $fileChk );
			$responses->setReturnStatus( 'error' );

			return false;
		}
		if ( $res = FilesCore::checkFileForErrors( $_FILES['wsformfile'] ) ) {
			$responses->setReturnData( $res );
			$responses->setReturnStatus( 'error' );

			return false;
		}
		if ( ! isset( $_POST['wsform_file_target'] ) || $_POST['wsform_file_target'] == "" ) {
			$responses->setReturnData( 'No target file.' );
			$responses->setReturnStatus( 'error' );

			return false;
		}
		if ( ! isset( $_POST['wsform_page_content'] ) || $_POST['wsform_page_content'] == "" ) {
			$responses->setReturnData( 'No wiki content for this file.' );
			$responses->setReturnStatus( 'error' );

			return false;
		}
		if ( ! isset( $_POST['wsform_image_force'] ) || $_POST['wsform_image_force'] == "" ) {
			$convert = false;
		} else {
			if ( FilesCore::getFileExtension( $_FILES['wsformfile']['name'] ) == $_POST['wsform_image_force'] ) {
				$convert = false;
			} else {
				$convert = $_POST['wsform_image_force'];
			}
		}

		$upload_dir = $api->getTempPath();
		$targetFile = wsUtilities::makeUnderscoreFromSpace( $_FILES['wsformfile']['name'] );
		if ( $convert ) {
			$newFile = FilesCore::convert_image(
				$convert,
				$upload_dir,
				$targetFile,
				$_FILES['wsformfile']['tmp_name'],
				$image_quality = 100
			);
			if ( $newFile === false ) {
				$responses->setReturnData(
					"Error while converting image from " . FilesCore::getFileExtension(
						$_FILES['wsformfile']['name']
					) . " to " . $convert . "."
				);
				$responses->setReturnStatus( 'error' );

				return false;
			}
		} else {
			if ( move_uploaded_file(
				$_FILES['wsformfile']['tmp_name'],
				$upload_dir . $targetFile
			) ) {
				$newFile = $targetFile;
			} else {
				$responses->setReturnData( "Error uploading file to destination (file-handling)" );
				$responses->setReturnStatus( 'error' );

				return false;
			}
		}

		// file upload is done.. Now getting it into the wiki

		$url = $api->getCanonicalUrl() . 'extensions/WSForm/uploads/' . $newFile;

		//TODO: This is not right yet!
		$name    = trim( $_POST['wsform_file_target'] );
		$details = trim( $_POST['wsform_page_content'] );
		if ( isset( $_POST['wsform_parse_content'] ) ) {
			$details = FilesCore::parseTitle( $details );
		}
		$comment = "Uploaded using WSForm.";
		$result  = $api->uploadFileToWiki(
			$name,
			$url,
			$details,
			$comment,
			$upload_dir . $targetFile
		);
		unlink( $upload_dir . $targetFile );

		return true;
	}

	/**
	 * When a file is uploaded using the Slim extension, this function will take care of the saving
	 *
	 * @param $api
	 *
	 * @return array|bool Either true on success or a createMsg() error
	 */
	public function fileUploadSlim( $api ) {
		global $IP;
		$messages = new wbHandleResponses( false );
		if ( ! isset( $_POST['wsform_file_target'] ) || $_POST['wsform_file_target'] == "" ) {
			return $messages->createMsg( 'No target file.' );
		}

		if ( ! isset( $_POST['wsform_page_content'] ) || $_POST['wsform_page_content'] == "" ) {
			return $messages->createMsg( 'No wiki content for this file.' );
		}

		if ( isset( $_POST['wsform_file_thumb_width'] ) && $_POST['wsform_file_thumb_width'] !== "" ) {
			$thumbWidth = $_POST['wsform_file_thumb_width'];
		} else {
			$thumbWidth = false;
		}

		if ( isset( $_POST['wsform_file_thumb_height'] ) && $_POST['wsform_file_thumb_height'] !== "" && $thumbWidth !== false ) {
			$thumbHeight = $_POST['wsform_file_thumb_height'];
		} else {
			$thumbHeight = null;
		}

		$upload_dir = $IP . "/extensions/WSForm/uploads/";

		include_once( $IP . '/extensions/WSForm/modules/slim/server/slim.php' );
		// Get posted data
		$images = Slim::getImages( 'wsformfile_slim' );

		// No image found under the supplied input name
		if ( $images == false ) {
			// inject your own auto crop or fallback script here
			return $messages->createMsg(
				'Presentor Slim was not used to upload these images.',
				"ok"
			);
		}
		if ( isset( $images[0]['output']['data'] ) ) {
			// Save the file
			$name = $images[0]['output']['name'];
			$name = $targetFile = wsUtilities::makeUnderscoreFromSpace( $name );

			// We'll use the output crop data
			$data = $images[0]['output']['data'];

			$output = Slim::saveFile(
				$data,
				$name,
				$upload_dir,
				false
			);
			if ( $api->getStatus() === false ) {
				return $messages->createMsg( $api->getStatus( true ) );
			}
			$url = $api->app['baseURL'] . 'extensions/WSForm/uploads/' . $output['name'];
			$api->logMeIn();
			$pname   = trim( $_POST['wsform_file_target'] );
			$details = trim( $_POST['wsform_page_content'] );
			$comment = "Uploaded using WSForm.";
			$result  = $api->uploadFileToWiki(
				$pname,
				$url,
				$details,
				$comment,
				$upload_dir . $output['name']
			);
			if ( $thumbWidth !== false ) {
				$thumbName = 'sm_' . $name;
				$turl      = $api->app['baseURL'] . 'extensions/WSForm/uploads/' . $thumbName;
				if ( createThumbnail(
					$upload_dir . $name,
					$upload_dir . $thumbName,
					$thumbWidth,
					$thumbHeight
				) ) {
					$result = $api->uploadFileToWiki(
						'sm_' . $pname,
						$turl,
						$details,
						$comment,
						$upload_dir . $thumbName
					);
					unlink( $upload_dir . $thumbName );
				}
			}
			unlink( $upload_dir . $output['name'] );

			return true;
		} else {
			return $messages->createMsg( "Presentor Slim said : No image output." );
		}
	}
}