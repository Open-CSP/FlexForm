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
use FlexForm\Processors\Content\Save;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Utilities\General;
use MediaHandler;
use MWFileProps;
use Title;
use User;
use Wikimedia\AtEase\AtEase;
use MediaWiki\MediaWikiServices;

class Upload {
	/**
	 * @return bool
	 * @throws FlexFormException
	 */
	public function fileUpload() : bool {
		/**
		 *    return [
		 * 'files'        => $files,
		 * 'pagecontent'  => General::getPostString( 'wsform_page_content' ),
		 * 'parsecontent' => General::getPostString( 'wsform_parse_content' ),
		 * 'comment'      => General::getPostString( 'wsform-upload-comment' ),
		 * 'returnto'     => General::getPostString(
		 * 'mwreturn',
		 * false
		 * ),
		 * 'target'       => General::getPostString( 'wsform_file_target' ),
		 * 'force'        => General::getPostArray( 'wsform_image_force' )
		 * ];
		 */ global $wgUser;
		$fields = Definitions::fileUploadFields();
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'File upload start',
							   [
								   'fields' => $fields,
								   'post'   => $_POST
							   ] );
		}
		$fileToProcess = $fields['files'];
		$nrOfFiles     = count( $fileToProcess['name'] );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Number of files to process',
				$nrOfFiles
			);
		}
		$errors    = [];
		$filesCore = new FilesCore();
		if ( $fields['target'] === false || $fields['target'] === '' ) {
			throw new FlexFormException(
				wfMessage( 'flexform-fileupload-no-target' )->text(),
				0
			);
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
		$upload_dir = rtrim(
						  Config::getConfigVariable( 'file_temp_path' ),
						  '/'
					  ) . '/';
		for ( $i = 0; $i < $nrOfFiles; $i++ ) {
			if ( Config::isDebug() ) {
				if ( is_uploaded_file( $fileToProcess['tmp_name'][$i] ) ) {
					$uploaded = "yes";
				} else {
					$uploaded = "no";
				}
				if ( file_exists( $fileToProcess['tmp_name'][$i] ) ) {
					$exists = "yes";
				} else {
					$exists = "no";
				}

				Debug::addToDebug( 'File #' . $i,
								   [
									   'tmp_name'    => $fileToProcess['tmp_name'][$i],
									   'name'        => $fileToProcess['name'][$i],
									   'is_uploaded' => $uploaded,
									   'exists'      => $exists,
									   'error'       => $fileToProcess['error'][$i]
								   ] );
			}
			if ( ! file_exists( $fileToProcess['tmp_name'][$i] ) || ! is_uploaded_file(
					$fileToProcess['tmp_name'][$i]
				) ) {
				throw new FlexFormException(
					wfMessage(
						'flexform-fileupload-file-not-found',
						$fileToProcess['name'][$i]
					)->text(),
					0
				);
			}
			$filename = $fileToProcess['name'][$i];
			$status   = $filesCore->checkFileForErrors( $fileToProcess['error'][$i] );
			$tmpName  = $fileToProcess['tmp_name'][$i];

			if ( $status !== false ) {
				throw new FlexFormException(
					wfMessage(
						'flexform-fileupload-file-errors',
						$status
					)->text(),
					0
				);
			}

			$targetFile = General::makeUnderscoreFromSpace( $filename );

			$fileNameExtension = $filesCore->getFileExtension( $filename );
			$originalFileNameExtension = $fileNameExtension;
			$fileNameBase = $filesCore->remove_extension_from_image( $targetFile );

			$fileNameBase = ContentCore::urlToSEO( $fileNameBase );

			if ( Config::isDebug() ) {
				Debug::addToDebug( 'File #' . $i . ' passed error checks',
								   [
									   'targetFile'             => $targetFile,
									   'upload dir'             => $upload_dir,
									   'convert'                => $convert,
									   'current file extension' => $fileNameExtension,
									   'current file basename'  => $fileNameBase
								   ] );
			}
			$filesSupported = Definitions::getImageHandler();
			$fileType = exif_imagetype( $tmpName );
			if ( $convert !== false &&
				 $filesCore->getFileExtension( $filename ) !== $convert &&
				 isset( $filesSupported[$fileType] ) ) {
				if ( Config::isDebug() ) {
					Debug::addToDebug( 'Converting File #' . $i . ' to ' . $convert,
									   [] );
				}

				$newFile = $filesCore->convert_image(
					$convert,
					$upload_dir,
					$targetFile,
					$tmpName,
					100
				);
				if ( $newFile === false ) {
					throw new FlexFormException(
						wfMessage(
							'flexform-fileupload-file-convert-error',
							$filesCore->getFileExtension(
								$filename
							),
							$convert
						)->text(),
						0
					);
				}
				$fileNameExtension = $filesCore->getFileExtension( $newFile );
			} else {
				if ( move_uploaded_file(
					$tmpName,
					$upload_dir . $targetFile
				) ) {
					$newFile = $targetFile;
				} else {
					throw new FlexFormException(
						wfMessage( 'flexform-fileupload-filemove-error' )->text(),
						0
					);
				}
			}

			// filename of stored file in temp FF folder
			$storedFile = $newFile;
			// Filename without extension
			$titleName = $filesCore->remove_extension_from_image( $newFile );
			if ( Config::getConfigVariable( 'create-seo-titles' ) === true ) {
				$titleName = ContentCore::urlToSEO( $titleName );
			}
			// find [filename] and replace
			$titleName = $filesCore->parseTarget(
				trim( $fields['target'] ),
				$titleName
			);

			$details = trim( $fields['pagecontent'] );
			if ( $fields['parsecontent'] !== false ) {
				$details = ContentCore::parseTitle( $details );
			}

			// find any other form fields and put them into the title
			if ( Config::isDebug() ) {
				Debug::addToDebug( 'Title before parsetitle', $titleName );
			}
			$titleName = ContentCore::parseTitle( $titleName );
			if ( Config::isDebug() ) {
				Debug::addToDebug( 'Title after parsetitle', $titleName );
			}

			$titleName = $this->finalNameCleanUp( $titleName, [ $fileNameExtension, $originalFileNameExtension ] );
			$titleName .= "." . $fileNameExtension;

			if ( Config::isDebug() ) {
				Debug::addToDebug( 'Preparing to upload file',
								   [
									   'original file name' => $filename,
									   'new file name'      => $titleName,
									   'stored file'        => $storedFile,
									   'details'            => $details,
									   'comment'            => $fields['comment']
								   ] );
			}

			$resultFileUpload = $this->uploadFileToWiki(
				$upload_dir . $storedFile,
				$titleName,
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
			unlink( $upload_dir . $storedFile );
		}

		return true;
	}

	/**
	 * @param string $name
	 * @param array $extensions
	 *
	 * @return string
	 */
	private function finalNameCleanUp( string $name, array $extensions ): string {
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'finalNameCleanup',
				[
					'name' => $name,
					'extensions to remove'      => $extensions
				] );
		}
		foreach ( $extensions as $extension ) {
			if ( strpos( $name, '.' . $extension ) !== false ) {
				$name = str_replace( '.' . $extension, '', $name );
			}
		}
		return $name;
	}

	/**
	 * @param string $filePath
	 * @param string $filename
	 * @param User $user
	 * @param string $content
	 * @param string $summary
	 * @param $timestamp
	 *
	 * @return bool|string
	 * @throws FlexFormException
	 * @throws \MWContentSerializationException
	 * @throws \MWException
	 */
	public function uploadFileToWiki(
		string $filePath,
		string $filename,
		User $user,
		string $content,
		string $summary,
		$timestamp
	) {
		if ( ! file_exists( $filePath ) ) {
			throw new FlexFormException(
				wfMessage(
					'flexform-fileupload-file-not-found',
					$filePath
				)->text(),
				0
			);
		}

		if ( $user === false ) {
			throw new FlexFormException(
				wfMessage( 'flexform-fileupload-user-unknown' )->text(),
				0
			);
		}

		$base = \UtfNormal\Validator::cleanUp( wfBaseName( $filename ) );
		# Validate a title
		$title = Title::makeTitleSafe(
			NS_FILE,
			$base
		);
		if ( ! is_object( $title ) ) {
			throw new FlexFormException(
				wfMessage(
					'flexform-fileupload-user-unknown',
					$base
				)->text(),
				0
			);
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
			throw new FlexFormException(
				$archive->getWikiText(
					false,
					false,
					'en'
				),
				0
			);
		}
		$commentText = $content;
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Uploading ' . $filename,
							   [
								   'archive value' => $archive->value,
								   'summary'       => $summary,
								   'content'       => $content,
								   'props'         => $props,
								   'base'          => $base,
								   'commentText'   => $commentText
							   ] );
		}

		$state = $image->recordUpload3(
			$archive->value,
			$summary,
			$commentText,
			$user,
			$props,
			$timestamp
		);

		$content = [ "main" => $content ];
		/**
		 *
		 */
		if ( $state->isOK() ) {
			$save = new Save();
			$save->saveToWiki(
				"File:" . $filename,
				$content,
				$summary
			);
			if ( Config::isDebug() ) {
				Debug::addToDebug( 'Uploading state for ' . $filename,
								   [ 'state' => $state ] );
			}
		}

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