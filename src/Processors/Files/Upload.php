<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : upload.class.php
 * Description :
 * Date        : 08/02/2021
 * Time        : 17:54
 */

namespace FlexForm\Processors\Files;

use Exception;
use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\ContentCore;
use FlexForm\Processors\Content\Save;
use FlexForm\Processors\Convert\PandocUploadHandler;
use FlexForm\Processors\Convert\SpreadsheetUploadHandler;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Utilities\General;
use MediaHandler;
use MWContentSerializationException;
use MWFileProps;
use Title;
use User;
use MediaWiki\MediaWikiServices;

class Upload {

	/**
	 * @var string
	 */
	private string $fileName;

	/**
	 * @var array
	 */
	private array $fileDetails;

	/**
	 * @return string
	 */
	private function getSummary(): string {
		$summary = General::getPostString( 'mwwikicomment' );
		if ( $summary === false ) {
			return "Uploaded using FlexForm.";
		} else {
			return ContentCore::parseTitle( $summary );
		}
	}

	/**
	 * @param string $fileName
	 * @param array $fileDetails
	 */
	public function __construct( string $fileName, array $fileDetails ) {
		$this->fileName = $fileName;
		$this->fileDetails = $fileDetails;
	}

	/**
	 * @return string
	 */
	private function getFileName(): string {
		return $this->fileName;
	}

	/**
	 * @return array
	 */
	private function getFileDetails(): array {
		return $this->fileDetails;
	}


	/**
	 * Validates the file action and extracts Pandoc convert details if applicable.
	 *
	 * @param mixed $fileAction
	 *
	 * @return array{0: string|bool, 1: array|null} Returns the finalized action and convert details.
	 * @throws FlexFormException
	 */
	private function parseFileAction( mixed $fileAction ): array {
		$fileActionConvertDetails = null;

		if ( $fileAction === false ) {
			return [
				$fileAction,
				$fileActionConvertDetails
			];
		}

		$actionLower = strtolower( $fileAction );

		if ( $actionLower !== 'upload' && !str_contains( $actionLower, 'convertfrom:' ) ) {
			throw new FlexFormException( 'Unknown upload action', 0 );
		}

		if ( str_contains( $actionLower, 'convertfrom:' ) ) {
			$fileActionConvertDetails = $this->decodeAction( $fileAction );

			if ( $fileActionConvertDetails !== null ) {
				$fileAction = 'convert';
			} else {
				Debug::addToDebug(
					'Pandoc convertfrom error',
					[
						'fileaction' => $fileAction,
						'fileActionConvertDetails' => null
					]
				);
				throw new FlexFormException( 'Pandoc convertfrom error. No convert-from found', 0 );
			}
		}

		return [
			$fileAction,
			$fileActionConvertDetails
		];
	}

	/**
	 * @param string $action
	 *
	 * @return string[]|null
	 * @throws FlexFormException
	 */
	private function decodeAction( string $action ): ?array {
		$fileActions = [
			'convertfrom' => null,
			'convertto' => 'mediawiki',
			'uploadoriginalas' => "false",
			'original-file-content' => "false",
			'additional-arguments' => []
		];

		if ( str_contains( $action, '|' ) ) {
			$explodedAction = explode( '|', $action );
		} else {
			$explodedAction = [ $action ];
		}
		foreach ( $explodedAction as $singleAction ) {
			if ( str_contains( $singleAction, ':' ) ) {
				$explodedSingeAction = explode( ':', $singleAction );
				if ( array_key_exists( $explodedSingeAction[0], $fileActions ) ) {
					$fileActions[$explodedSingeAction[0]] = $explodedSingeAction[1];
				}
			}
		}

		if ( !empty( $fileActions['additional-arguments'] ) ) {
			$fileActions['additional-arguments'] = explode( ',', $fileActions['additional-arguments'] );
			$newArgs = [];
			foreach ( $fileActions['additional-arguments'] as $singleAdditionalArgument ) {
				if ( str_contains( $singleAdditionalArgument, '=' ) ) {
					$explodedArgs = explode( '=', $singleAdditionalArgument );
					$newArgs[$explodedArgs[0]] = $explodedArgs[1];
				} else {
					$newArgs[$singleAdditionalArgument] = '';
				}
			}
			$fileActions['additional-arguments'] = $newArgs;
		}
		Debug::addToDebug( 'Pandoc conversion options', $fileActions );
		$checkConversions = $this->checkAllowedConversions(
			$fileActions['convertfrom'],
			$fileActions['convertto'],
			$fileActions['additional-arguments']
		);
		if ( $checkConversions !== true ) {
			throw new FlexFormException(
				'Pandoc Error: ' . $checkConversions, 0
			);
		}
		if ( $fileActions['convertfrom'] === null ) {
			return null;
		}

		return $fileActions;
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param array $additional
	 *
	 * @return bool|string
	 */
	private function checkAllowedConversions( string $from, string $to, array $additional = [] ): bool|string {
		if ( !in_array( $from, Config::getConfigVariable( 'pandoc-convert-from' ) ) ) {
			return "Convert from '$from' is not allowed.";
		}
		if ( !in_array( $to, Config::getConfigVariable( 'pandoc-convert-to' ) ) ) {
			return "Convert to '$to' is not allowed.";
		}
		if ( !empty( $additional ) ) {
			if ( Config::getConfigVariable( 'pandoc-allow-additional-arguments' ) === false ) {
				return "pandoc-allow-additional-arguments are not allowed.";
			}
		}

		return true;
	}

	/**
	 * @return bool
	 * @throws FlexFormException
	 * @throws MWContentSerializationException
	 * @throws \MWException
	 */
	public function fileUpload(): bool {
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
		 * 'force'        => General::getPostArray( 'wsform_image_force' ),
		 * 'convertFrom'        => General::getPostArray( 'wsform_action' ),
		 * ];
		 */

		$thisUser = \RequestContext::getMain()->getUser();
		$processedFiles = [];

		$fileName = $this->getFileName();
		$fileDetails = $this->getFileDetails();

		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'File upload start',
				[
					'field' => $fileName,
					'fileDetails' => $fileDetails,
					'post' => $_POST
				]
			);
		}

		$fileToProcess = $_FILES[$fileName];

		$options = new FileUploadOptions( $fileDetails );

		$target = $options->target;
		$pageContent = $options->pageContent;
		$pageContentPrefix = $options->pageContentPrefix;
		$pageContentSuffix = $options->pageContentSuffix;
		$pageTemplate = $options->pageTemplate;
		$parseContent = $options->parseContent;
		$imageForce = $options->imageForce;
		$imageComment = $options->imageComment;

		[
			$fileAction,
			$fileActionConvertDetails
		] = $this->parseFileAction( $options->fileAction );

		$nrOfFiles = count( $fileToProcess['name'] );
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Number of files to process', $nrOfFiles );
		}

		$errors = [];
		$filesCore = new FilesCore();

		if ( $target === false || $target === '' ) {
			throw new FlexFormException( wfMessage( 'flexform-fileupload-no-target' )->text(), 0 );
		}

		if ( $pageContent === false ) {
			$pageContent = '';
		}

		$pageContentPrefix = ( $pageContentPrefix === false )
			? ''
			: ContentCore::parseTitle(
				$pageContentPrefix,
				true
			);

		$pageContentSuffix = ( $pageContentSuffix === false )
			? ''
			: ContentCore::parseTitle(
				$pageContentSuffix,
				true
			);

		if ( $imageComment === false ) {
			$imageComment = $this->getSummary();
		}

		if ( $imageForce === false || $imageForce === '' ) {
			$convert = false;
		} else {
			$convert = $imageForce;
		}

		$upload_dir = rtrim( Config::getConfigVariable( 'file_temp_path' ), '/' ) . '/';

		$filesCore = new FilesCore();

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

				Debug::addToDebug(
					'File #' . $i,
					[
						"fileaction" => $fileAction,
						"fileActionConvertDetails" => $fileActionConvertDetails,
						'tmp_name' => $fileToProcess['tmp_name'][$i],
						'name' => $fileToProcess['name'][$i],
						'is_uploaded' => $uploaded,
						'exists' => $exists,
						'error' => $fileToProcess['error'][$i]
					]
				);
			}
			if (
				!file_exists( $fileToProcess['tmp_name'][$i] ) || !is_uploaded_file(
					$fileToProcess['tmp_name'][$i]
				)
			) {
				throw new FlexFormException(
					wfMessage(
						'flexform-fileupload-file-not-found',
						$fileToProcess['name'][$i]
					)->text(), 0
				);
			}
			$filename = $fileToProcess['name'][$i];
			$status = $filesCore->checkFileForErrors( $fileToProcess['error'][$i] );
			$tmpName = $fileToProcess['tmp_name'][$i];

			if ( $status !== false ) {
				throw new FlexFormException(
					wfMessage(
						'flexform-fileupload-file-errors',
						$status
					)->text(), 0
				);
			}

			$targetFile = General::makeUnderscoreFromSpace( $filename );

			$fileNameExtension = $filesCore->getFileExtension( $filename );
			$originalFileNameExtension = $fileNameExtension;
			$fileNameBase = $filesCore->remove_extension_from_image( $targetFile );

			$fileNameBase = ContentCore::urlToSEO( $fileNameBase );

			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'File #' . $i . ' passed error checks',
					[
						'targetFile' => $targetFile,
						'upload dir' => $upload_dir,
						'convert' => $convert,
						'current file extension' => $fileNameExtension,
						'current file basename' => $fileNameBase
					]
				);
			}

			$filesSupported = Definitions::getImageHandler();
			$fileType = exif_imagetype( $tmpName );
			if (
				$convert !== false &&
				$filesCore->getFileExtension( $filename ) !== $convert &&
				isset( $filesSupported[$fileType] )
			) {
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'Converting File #' . $i . ' to ' . $convert,
						[]
					);
				}

				$newFile = $filesCore->convert_image(
					$convert,
					$upload_dir,
					$targetFile,
					$tmpName,
					100
				);
				if ( Config::isDebug() ) {
					Debug::addToDebug( 'NewFile for File #' . $i, [ 'newFile' => $newFile ] );
				}
				if ( $newFile === false ) {
					throw new FlexFormException(
						wfMessage(
							'flexform-fileupload-file-convert-error',
							$filesCore->getFileExtension(
								$filename
							),
							$convert
						)->text(), 0
					);
				}
				$fileNameExtension = $filesCore->getFileExtension( $newFile );
			} else {
				if ( move_uploaded_file( $tmpName, $upload_dir . $targetFile ) ) {
					$newFile = $targetFile;
				} else {
					throw new FlexFormException(
						wfMessage( 'flexform-fileupload-file-move-error' )->text(), 0
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
			$titleName = $filesCore->parseTarget( trim( $target ), $titleName );

			if ( $parseContent !== false ) {
				$details = trim( $pageContent );
			} else {
				$details = "";
			}

			if ( $pageTemplate && $parseContent !== false ) {
				$filePageTemplate = trim( $pageTemplate );
				$details = ContentCore::setFileTemplate(
					$filePageTemplate,
					$details
				);
			}

			if ( $parseContent !== false ) {
				// find [filename] and replace
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'details before parseTarget file #' . $i,
						$details
					);
				}
				$details = $filesCore->parseTarget(
					$details,
					$titleName

				);
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'details after parseTarget for file #' . $i,
						$details
					);
				}
				$details = ContentCore::parseTitle(
					$details,
					true
				);
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'details after parseTitle file #' . $i,
						$details
					);
				}
			}

			// find any other form fields and put them into the title
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Title before parsetitle file #' . $i,
					$titleName
				);
			}
			$titleName = ContentCore::parseTitle( $titleName );
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Title after parsetitle file #' . $i,
					$titleName
				);
			}

			$titleName = $this->finalNameCleanUp(
				$titleName,
				[
					$fileNameExtension,
					$originalFileNameExtension
				]
			);

			// Not converting file, then add filename extension back
			if ( $fileAction === false ) {
				$titleName .= "." . $fileNameExtension;
			}

			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Preparing to upload file #' . $i,
					[
						'original file name' => $filename,
						'new file name' => $titleName,
						'stored file' => $storedFile,
						'details' => $details,
						'comment' => $imageComment
					]
				);
			}

			$processedFiles[$fileName]['upload-name'][] = $filename;
			$processedFiles[$fileName]['upload-base'][] = $fileNameBase;
			$processedFiles[$fileName]['new-name'][] = $titleName;

			if ( $fileAction !== false ) {
				$fileSlot = General::getJsonValue( 'wsform_slot', $fileDetails );
				if ( $fileSlot === false ) {
					$fileSlot = 'main';
				}
				switch ( $fileAction ) {
					case "xls":
					case "xlsx":
						$spreadsheetHandler = new SpreadsheetUploadHandler();
						$spreadsheetHandler->process(
							$fileAction,
							$storedFile,
							$titleName,
							$fileDetails,
							$imageComment
						);
						break;
					case "convert":
						$pandocHandler = new PandocUploadHandler( $this, $filesCore );

						$pandocHandler->process(
							$fileActionConvertDetails,
							$storedFile,
							$targetFile,
							$titleName,
							$fileNameExtension,
							$pageContentPrefix,
							$pageContentSuffix,
							$fileSlot,
							$details,
							$imageComment,
							$thisUser,
							$upload_dir
						);
						break;
				}
			} else {
				$resultFileUpload = $this->uploadFileToWiki(
					$upload_dir . $storedFile,
					$titleName,
					$thisUser,
					$details,
					$imageComment,
					wfTimestampNow()
				);
				if ( $resultFileUpload !== true ) {
					throw new FlexFormException(
						$resultFileUpload, 0
					);
				}
			}
			unlink( $upload_dir . $storedFile );
		}

		$separator = ',';

		$ffUploadedFile = General::makeUnderscoreFromSpace( 'FFUploadedFile-UploadName-' . $fileName );
		$ffUploadedFileBase = General::makeUnderscoreFromSpace( 'FFUploadedFile-UploadBase-' . $fileName );
		$ffUploadedFileNew = General::makeUnderscoreFromSpace( 'FFUploadedFile-NewName-' . $fileName );
		$_POST[$ffUploadedFile] = implode(
			$separator,
			$processedFiles[$fileName]['upload-name']
		);
		$_POST[$ffUploadedFileBase] = implode(
			$separator,
			$processedFiles[$fileName]['upload-base']
		);
		$_POST[$ffUploadedFileNew] = implode(
			$separator,
			$processedFiles[$fileName]['new-name']
		);

		return true;
	}

	/**
	 * @param string $title
	 *
	 * @return bool
	 */
	private function isFileNameSpace( string $title ): bool {
		$titleObject = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $title );
		if ( $titleObject !== null && $titleObject->getNamespace() === NS_FILE ) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $pageTargetName
	 * @param string $target
	 *
	 * @return string
	 */
	private function checkTitleForTarget( string $pageTargetName, string $target ): string {
		$target = str_replace( '[target]', $pageTargetName, $target );
		if ( $this->isFileNameSpace( $target ) ) {
			$titleObject = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $target );
			if ( $titleObject !== null ) {
				$target = $titleObject->getBaseText();
			}
		}

		return $target;
	}

	/**
	 * @param string $name
	 * @param array $extensions
	 *
	 * @return string
	 */
	private function finalNameCleanUp( string $name, array $extensions ): string {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $name );
		if ( $title !== null && $title->getNamespace() === NS_FILE ) {
			return $name;
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'finalNameCleanup',
				[
					'name' => $name,
					'extensions to remove' => $extensions
				]
			);
		}
		foreach ( $extensions as $extension ) {
			if ( str_contains( $name, '.' . $extension ) ) {
				$name = str_replace(
					'.' . $extension,
					'',
					$name
				);
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
	 * @throws MWContentSerializationException
	 * @throws Exception
	 */
	public function uploadFileToWiki(
		string $filePath,
		string $filename,
		User $user,
		string $content,
		string $summary,
		$timestamp
	) {
		if ( !file_exists( $filePath ) ) {
			throw new FlexFormException(
				wfMessage(
					'flexform-fileupload-file-not-found',
					$filePath
				)->text(), 0
			);
		}

		if ( $user === false ) {
			throw new FlexFormException(
				wfMessage( 'flexform-fileupload-user-unknown' )->text(), 0
			);
		}

		$base = \UtfNormal\Validator::cleanUp( wfBaseName( $filename ) );
		# Validate a title
		$title = Title::makeTitleSafe(
			NS_FILE,
			$base
		);
		if ( !is_object( $title ) ) {
			throw new FlexFormException(
				wfMessage(
					'flexform-fileupload-user-unknown',
					$base
				)->text(), 0
			);
		}

		$fileRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
		$image = $fileRepo->newFile( $title );
		$mwProps = new MWFileProps( MediaWikiServices::getInstance()->getMimeAnalyzer() );
		$props = $mwProps->getPropsFromPath(
			$filePath,
			true
		);
		$flags = 0;
		$publishOptions = [];
		$handler = MediaHandler::getHandler( $props['mime'] );
		if ( $handler ) {
			$publishOptions['headers'] = $handler->getContentHeaders( $props['metadata'] );
		} else {
			$publishOptions['headers'] = [];
		}
		$archive = $image->publish(
			$filePath,
			$flags,
			$publishOptions
		);

		if ( !$archive->isGood() ) {
			throw new FlexFormException(
				$archive->getWikiText(
					false,
					false,
					'en'
				), 0
			);
		}
		$commentText = $content;
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Uploading ' . $filename,
				[
					'archive value' => $archive->value,
					'summary' => $summary,
					'content' => $content,
					'props' => $props,
					'base' => $base,
					'commentText' => $commentText
				]
			);
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
				Debug::addToDebug(
					'Uploading state for ' . $filename,
					[ 'state' => $state ]
				);
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
		if ( !isset( $_POST['wsform_file_target'] ) || $_POST['wsform_file_target'] == "" ) {
			return $messages->createMsg( 'No target file.' );
		}

		if ( !isset( $_POST['wsform_page_content'] ) || $_POST['wsform_page_content'] == "" ) {
			return $messages->createMsg( 'No wiki content for this file.' );
		}

		if ( isset( $_POST['wsform_file_thumb_width'] ) && $_POST['wsform_file_thumb_width'] !== "" ) {
			$thumbWidth = $_POST['wsform_file_thumb_width'];
		} else {
			$thumbWidth = false;
		}

		if (
			isset( $_POST['wsform_file_thumb_height'] ) &&
			$_POST['wsform_file_thumb_height'] !== "" &&
			$thumbWidth !== false
		) {
			$thumbHeight = $_POST['wsform_file_thumb_height'];
		} else {
			$thumbHeight = null;
		}

		$upload_dir = $IP . "/extensions/FlexForm/uploads/";

		include_once( $IP . '/extensions/FlexForm/modules/slim/server/slim.php' );
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
			$pname = trim( $_POST['wsform_file_target'] );
			$details = trim( $_POST['wsform_page_content'] );
			$comment = "Uploaded using FlexForm.";
			$result = $api->uploadFileToWiki(
				$pname,
				$url,
				$details,
				$comment,
				$upload_dir . $output['name']
			);
			if ( $thumbWidth !== false ) {
				$thumbName = 'sm_' . $name;
				$turl = $api->app['baseURL'] . 'extensions/FlexForm/uploads/' . $thumbName;
				if (
					createThumbnail(
						$upload_dir . $name,
						$upload_dir . $thumbName,
						$thumbWidth,
						$thumbHeight
					)
				) {
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