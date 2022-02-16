<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : FlexForm
 * Filename    : upload.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 17:54
 */

namespace FlexForm\Processors\Files;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\ContentCore;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Utilities\General;
use FlexForm\Processors\Utilities\Utilities;
use FlexForm\Processors\wbHandleResponses;
use MediaHandler;
use MWFileProps;
use Title;
use Wikimedia\AtEase\AtEase;
use wsform\processors\Wsi18n;
use MediaWiki\MediaWikiServices;

class Upload {
	/**
	 * @return bool
	 * @throws FlexFormException
	 */
	public function fileUpload(): bool {
		/**
		 * 	return [
		'files'        => $files,
		'pagecontent'  => General::getPostString( 'wsform_page_content' ),
		'parsecontent' => General::getPostString( 'wsform_parse_content' ),
		'comment'      => General::getPostString( 'wsform-upload-comment' ),
		'returnto'     => General::getPostString(
		'mwreturn',
		false
		),
		'target'       => General::getPostString( 'wsform_file_target' ),
		'force'        => General::getPostArray( 'wsform_image_force' )
		];
		 */
		global $wgUser;
		$fields = Definitions::fileUploadFields();
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'File upload start',
				$fields
			);
		}
		$fileToProcess = $fields['files'];
		$nrOfFiles = count( $fileToProcess['name'] );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Number of files to process',
				$nrOfFiles
			);
		}
		$errors = [];
		$filesCore = new FilesCore();
		if ( $fields['target'] === false || $fields['target'] === '' ) {
			throw new FlexFormException( 'No target filepage.', 0 );
		}
		if ( $fields['pagecontent'] === false ) {
			$fields['pagecontent'] = '';
		}
		if ( $fields['comment'] === false ) {
			$fields['comment'] = "Uploaded using FlexForm.";
		}
		if ( $fields['force'] === false || $fields['force'] === '' ) {
			$convert = false;
		} else {
			/*
			if ( $filesCore->getFileExtension( $_FILES['wsformfile']['name'] ) == $_POST['wsform_image_force'] ) {
				$convert = false;
			} else {
				$convert = $_POST['wsform_image_force'];
			}
			*/
			$convert = $fields['force'];
		}
		$upload_dir = rtrim( Config::getConfigVariable( 'file_temp_path' ), '/' ) . '/';
		for ( $i = 0; $i < $nrOfFiles; $i++ ) {
			if ( ! file_exists( $fileToProcess['tmp_name'][$i] ) || ! is_uploaded_file(
					$fileToProcess['tmp_name'][$i]
				) ) {
				throw new FlexFormException( 'Cannot find file ' . $fileToProcess['name'][$i], 0 );
			}
			$filename = $fileToProcess['name'][$i];
			$status = $filesCore->checkFileForErrors( $fileToProcess['error'][$i] );
			$tmpName = $fileToProcess['tmp_name'][$i];

			if ( $status !== false ) {
				throw new FlexFormException( $fileToProcess['name'][$i] . ': ' . $status, 0 );
			}

			$targetFile = General::makeUnderscoreFromSpace( $filename );
			if ( $convert !== false && $filesCore->getFileExtension( $filename ) !== $convert ) {
				$newFile = $filesCore->convert_image(
					$convert,
					$upload_dir,
					$targetFile,
					$tmpName,
					100
				);
				if ( $newFile === false ) {
					throw new FlexFormException(
						"Error while converting image from " . $filesCore->getFileExtension(
							$filename
						) . " to " . $convert . ".",
						0
					);
				}
			} else {
				if ( move_uploaded_file(
					$tmpName,
					$upload_dir . $targetFile
				) ) {
					$newFile = $targetFile;
				} else {
					throw new FlexFormException( "Error uploading file to destination (file-handling)", 0 );
				}
			}
			$name    = $filesCore->parseTarget( trim( $fields['target'] ), $filename );
			$details = trim( $fields['pagecontent'] );
			if ( $fields['parsecontent'] !== false ) {
				$details = ContentCore::parseTitle( $details );
			}
			$name = ContentCore::parseTitle( $name );

			if ( Config::isDebug() ) {
				Debug::addToDebug( 'Preparing to upload file',
								   [
									   'original file name' => $filename,
									   'new file name'      => $name
								   ] );
			}

			$resultFileUpload = $this->uploadFileToWiki(
				$upload_dir . $newFile,
				$name,
				$wgUser,
				$details,
				$fields['comment'],
				wfTimestampNow()
			);
			if ( $resultFileUpload !== true ) {
				throw new FlexFormException( $resultFileUpload, 0 );
			}
			unlink( $upload_dir . $newFile );
		}
		return true;
	}

	/**
	 * @param string $filePath
	 * @param string $filename
	 * @param mixed $user
	 * @param string $content
	 * @param string $summary
	 * @param mixed $timestamp
	 *
	 * @return bool|string
	 */
	public function uploadFileToWiki(
		string $filePath,
		string $filename,
		$user,
		string $content,
		string $summary,
		$timestamp
	) {
		global $wgUser;
		if ( ! file_exists( $filePath ) ) {
			return 'Cannot find file';
		}

		if ( $user === false ) {
			return 'Cannot find user';
		}
		$wgUser = $user;
		$base   = \UtfNormal\Validator::cleanUp( wfBaseName( $filename ) );
		# Validate a title
		$title = Title::makeTitleSafe(
			NS_FILE,
			$base
		);
		if ( ! is_object( $title ) ) {
			return "{$base} could not be imported; a valid title cannot be produced";
		}

		$fileRepo       = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
		$image          = $fileRepo->newFile( $title );
		$mwProps        = new MWFileProps( MediaWikiServices::getInstance()->getMimeAnalyzer() );
		$props          = $mwProps->getPropsFromPath(
			$filePath,
			true
		);
		$flags          = 0;
		$publishOptions = [];
		$handler        = MediaHandler::getHandler( $props['mime'] );
		if ( $handler ) {
			$metadata = AtEase::quietCall(
				'unserialize',
				$props['metadata']
			);

			$publishOptions['headers'] = $handler->getContentHeaders( $metadata );
		} else {
			$publishOptions['headers'] = [];
		}
		$archive = $image->publish(
			$filePath,
			$flags,
			$publishOptions
		);

		if ( ! $archive->isGood() ) {
			return $archive->getWikiText(
				false,
				false,
				'en'
			);
		}

		$image->recordUpload3(
			$archive->value,
			$summary,
			$content,
			$user,
			$props,
			$timestamp
		);

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

		$upload_dir = $IP . "/extensions/FlexForm/uploads/";

		include_once( $IP . '/extensions/FlexForm/<odules/slim/server/slim.php' );
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
			$url = $api->app['baseURL'] . 'extensions/FlexForm/uploads/' . $output['name'];
			$api->logMeIn();
			$pname   = trim( $_POST['wsform_file_target'] );
			$details = trim( $_POST['wsform_page_content'] );
			$comment = "Uploaded using FlexForm.";
			$result  = $api->uploadFileToWiki(
				$pname,
				$url,
				$details,
				$comment,
				$upload_dir . $output['name']
			);
			if ( $thumbWidth !== false ) {
				$thumbName = 'sm_' . $name;
				$turl      = $api->app['baseURL'] . 'extensions/FlexForm/uploads/' . $thumbName;
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