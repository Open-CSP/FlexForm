<?php

namespace FlexForm\Processors\Convert;

use Exception;
use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\Save;
use FlexForm\Processors\Files\FilesCore;
use FlexForm\Processors\Files\Upload;
use MediaWiki\MediaWikiServices;
use MWContentSerializationException;
use User;

class PandocUploadHandler {

	/**
	 * @var Upload
	 */
	private Upload $uploadProcessor;

	/**
	 * @var FilesCore
	 */
	private FilesCore $filesCore;

	/**
	 * @param Upload $uploadProcessor
	 * @param FilesCore $filesCore
	 */
	public function __construct( Upload $uploadProcessor, FilesCore $filesCore ) {
		$this->uploadProcessor = $uploadProcessor;
		$this->filesCore       = $filesCore;
	}

	/**
	 * Executes the Pandoc conversion and handles wiki uploads.
	 *
	 * @param array $convertDetails
	 * @param string $storedFile
	 * @param string $targetFile
	 * @param string $titleName
	 * @param string $fileNameExtension
	 * @param string $pageContentPrefix
	 * @param string $pageContentSuffix
	 * @param string $fileSlot
	 * @param string $details
	 * @param string $imageComment
	 * @param User $user
	 * @param string $uploadDir
	 *
	 * @throws FlexFormException
	 * @throws MWContentSerializationException
	 */
	public function process(
		array $convertDetails,
		string $storedFile,
		string $targetFile,
		string $titleName,
		string $fileNameExtension,
		string $pageContentPrefix,
		string $pageContentSuffix,
		string $fileSlot,
		string $details,
		string $imageComment,
		User $user,
		string $uploadDir
	): void {
		$convert = new PandocConverter();
		$convert->setConvertFrom( $convertDetails['convertfrom'] );
		$convert->setConvertTo( $convertDetails['convertto'] );
		$convert->setAdditionalArguments( $convertDetails['additional-arguments'] );
		$convert->setFileName(
			$this->filesCore->parseTarget( $storedFile, $convertDetails['uploadoriginalas'] )
		);

		$newContent = $convert->convertFile();

		Debug::addToDebug(
			'File converted with Pandoc: ' . $titleName,
			[
				'$pageContentPrefix' => $pageContentPrefix,
				'$newContent'        => $newContent,
				'$pageContentSuffix' => $pageContentSuffix
			]
		);

		if ( !$convert->isBinaryTarget() ) {
			$newContent = $pageContentPrefix . $newContent . $pageContentSuffix;
			$this->processExtractedImages( $convert, $titleName, $user, $imageComment, $details, $newContent );

			// Save the resulting text page
			$save = new Save();
			try {
				$save->saveToWiki(
					$titleName,
					[ $fileSlot => $newContent ],
					$imageComment
				);
			} catch ( FlexFormException $e ) {
				throw new FlexFormException( $e->getMessage(), 0, $e );
			}
		} else {
			// Binary target upload
			$titleName .= "." . $fileNameExtension;
			$resultFileUpload = $this->uploadProcessor->uploadFileToWiki(
				$uploadDir . $storedFile,
				$titleName,
				$user,
				$details,
				$imageComment,
				wfTimestampNow()
			);

			if ( $resultFileUpload !== true ) {
				throw new FlexFormException( (string)$resultFileUpload, 0 );
			}
		}

		// Handle uploadoriginalas logic
		if ( $convertDetails['uploadoriginalas'] !== "false" ) {
			$this->processOriginalUpload(
				$convertDetails, $targetFile, $titleName, $storedFile,
				$newContent, $details, $imageComment, $user, $uploadDir
			);
		}
	}

	/**
	 * Helper to process images extracted from the Pandoc conversion.
	 *
	 * @param PandocConverter $convert
	 * @param string $titleName
	 * @param User $user
	 * @param string $imageComment
	 * @param string $details
	 * @param string $newContent
	 *
	 * @return void
	 * @throws FlexFormException
	 * @throws MWContentSerializationException
	 * @throws Exception
	 */
	private function processExtractedImages(
		PandocConverter $convert, string $titleName, User $user,
		string $imageComment, string $details, string &$newContent
	): void {
		$possibleImages = $convert->getPossibleImagesFromConversion();
		if ( $possibleImages === false ) {
			return;
		}

		$fCount = 1;
		foreach ( $possibleImages as $singleImage ) {
			$newFname = $titleName . '-' . basename( $singleImage );

			if ( Config::isDebug() ) {
				Debug::addToDebug(
					$fCount . ' - Preparing to upload image file from document',
					[
						'$newFname'    => $newFname,
						'$singleImage' => $singleImage,
						'details'      => $details,
						'comment'      => $imageComment
					]
				);
			} else {
				$resultFileUpload = $this->uploadProcessor->uploadFileToWiki(
					$singleImage, $newFname, $user, $details, $imageComment, wfTimestampNow()
				);

				if ( $resultFileUpload !== true ) {
					throw new FlexFormException( (string)$resultFileUpload, 0 );
				}
			}

			$search     = $convert->pandocGetSearchFor() . basename( $singleImage );
			$replace    = $convert->pandocGetReplaceWith( $newFname );
			$newContent = str_replace( $search, $replace, $newContent );

			unlink( $singleImage );
			$fCount++;
		}
	}

	/**
	 *
	 * Helper to process the original file upload if requested.
	 *
	 * @param array $convertDetails
	 * @param string $targetFile
	 * @param string $titleName
	 * @param string $storedFile
	 * @param string $newContent
	 * @param string $details
	 * @param string $imageComment
	 * @param User $user
	 * @param string $uploadDir
	 *
	 * @return void
	 * @throws FlexFormException
	 * @throws MWContentSerializationException
	 * @throws \MWException
	 */
	private function processOriginalUpload(
		array $convertDetails, string $targetFile, string $titleName, string $storedFile,
		string $newContent, string $details, string $imageComment, User $user, string $uploadDir
	): void {
		$uploadOriginalAs = $this->filesCore->parseTarget(
			$convertDetails['uploadoriginalas'],
			$targetFile
		);

		Debug::addToDebug( 'Pandoc Upload Original - parse [filename]', [
			'targetfile'                           => $targetFile,
			'uploadoriginalas original'            => $convertDetails['uploadoriginalas'],
			'after [filename] in targetfile parse' => $uploadOriginalAs
		] );

		$isOriginalTargetAFile = $this->isFileNameSpace( $titleName );
		$pTitleName            = $this->checkTitleForTarget( $titleName, $uploadOriginalAs );

		Debug::addToDebug( 'Pandoc Upload Original - parse [target]', [
			'titleName'                         => $titleName,
			'after [filename] in targetfile parse' => $uploadOriginalAs,
			'after [target] in titlename parse'   => $pTitleName,
		] );

		if ( $convertDetails['original-file-content'] !== "false" ) {
			$details = $convertDetails['original-file-content'];
		}

		if ( $isOriginalTargetAFile && empty( $details ) ) {
			Debug::addToDebug(
				'Pandoc Upload Original - same title',
				'Converted title same as uploadoriginal title, content is for main slot, so adding to file-upload'
			);
			$details = $newContent;
		}

		$resultFileUpload = $this->uploadProcessor->uploadFileToWiki(
			$uploadDir . $storedFile,
			$pTitleName,
			$user,
			$details,
			$imageComment,
			wfTimestampNow()
		);

		if ( $resultFileUpload !== true ) {
			throw new FlexFormException( (string)$resultFileUpload, 0 );
		}
	}

	/**
	 * @param string $title
	 * @return bool
	 */
	private function isFileNameSpace( string $title ): bool {
		$titleObject = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $title );
		return $titleObject !== null && $titleObject->getNamespace() === NS_FILE;
	}

	/**
	 * @param string $pageTargetName
	 * @param string $target
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
}