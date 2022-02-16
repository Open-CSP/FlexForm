<?php

namespace FlexForm\Processors\Files;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\FlexFormException;
use FlexForm\Processors\Utilities\General;
use FlexForm\Processors\Files;
use Symfony\Component\Security\Acl\Exception\Exception;

class FilesCore {

	 public const FILENAME = 'wsformfile';

	/**
	 * @return void
	 * @throws FlexFormException
	 */
	public function handleFileUploads(): void {
		$wsSignature = General::getPostString(
			'wsform_signature',
			false
		);
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'File upload class',
				['Looking for ' . self::FILENAME, $_FILES]
			);
		}
		/*
		if ( $wsSignature !== false ) {
			$res = signature::upload(
				$wsuid,
				$api,
				$messages
			);
			if ( $res['status'] === 'error' ) {
				$messages->doDie( ' signature : ' . $res['msg'] );
			}
			$ret = $res; // v0.7.0.3.3 added
		}
		*/

		if ( isset( $_FILES[self::FILENAME] ) ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Checking for files to upload',
					$_FILES[self::FILENAME]
				);
			}
			if ( file_exists( $_FILES[self::FILENAME]['tmp_name'] ) || is_uploaded_file(
					$_FILES[self::FILENAME]['tmp_name']
				) ) {
				$fileUpload = new Upload();
				try {
					$res = $fileUpload->fileUpload();
				} catch ( FlexFormException $e ) {
					throw new FlexFormException( $e->getMessage(), 0 );
				}

			}
		}

		/*
		if ( isset( $_POST['wsformfile_slim'] ) ) {
			$ret = upload::fileUploadSlim( $api );
			if ( isset( $ret['status'] ) && $ret['status'] === 'error' ) {
				$messages->doDie( ' slim : ' . $ret['msg'] );
			}
		}
		*/
	}

	/**
	 * Check if it is a single file and check if error check is set
	 *
	 * @param $file
	 *
	 * @return bool|string
	 */
	public function checkFileUploadForError( $file ) {
		if ( ! isset( $file['error'] ) || is_array( $file['error'] ) ) {
			return "No file found or we received multiple files.";
		} else {
			return false;
		}
	}

	/**
	 * Check for error messages in uploaded file
	 *
	 * @param $file
	 *
	 * @return bool|string
	 */
	public function checkFileForErrors( $file ) {
		switch ( $file['error'] ) {
			case UPLOAD_ERR_OK:
				return false;
			case UPLOAD_ERR_NO_FILE :
				return "no file received";
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return "file exceeds filesize limit";
			default:
				return "unknown file error";
		}
	}

	/**
	 * @param string $image
	 *
	 * @return string
	 */
	public function remove_extension_from_image( string $image ) : string {
		$extension = $this->getFileExtension( $image );
		$only_name = basename(
			$image,
			'.' . $extension
		); // remove extension

		return $only_name;
	}

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	public function getFileExtension( string $file ) : string {
		$path_parts = pathinfo( $file );
		$extension  = $path_parts['extension'];

		return strtolower( $extension );
	}

	/**
	 * Converting image types
	 *
	 * @param string $convert_type [where to convert to. png, jpg or gif]
	 * @param string $target_dir [description]
	 * @param string $target_name [name of the target file]
	 * @param string $image [path to image]
	 * @param integer $image_quality [0-100]
	 *
	 * @return string|bool                 [return path of new file or false if nothing can be worked out]
	 */
	public function convert_image(
		string $convert_type,
		string $target_dir,
		string $target_name,
		string $image,
		int $image_quality = 100
	) {
		//remove extension from image;
		$img_name = $this->remove_extension_from_image( $target_name );
		//to png
		if ( $convert_type == 'png' ) {
			$binary = imagecreatefromstring( file_get_contents( $image ) );
			//third parameter for ImagePng is limited to 0 to 9
			//0 is uncompressed, 9 is compressed
			//so convert 100 to 2 digit number by dividing it by 10 and minus with 10
			$image_quality = floor( 10 - ( $image_quality / 10 ) );
			ImagePNG(
				$binary,
				$target_dir . $img_name . '.' . $convert_type,
				$image_quality
			);

			return $img_name . '.' . $convert_type;
		}

		//to jpg
		if ( $convert_type == 'jpg' ) {
			$binary = imagecreatefromstring( file_get_contents( $image ) );
			imageJpeg(
				$binary,
				$target_dir . $img_name . '.' . $convert_type,
				$image_quality
			);

			return $img_name . '.' . $convert_type;
		}
		//to gif
		if ( $convert_type == 'gif' ) {
			$binary = imagecreatefromstring( file_get_contents( $image ) );
			imageGif(
				$binary,
				$target_dir . $img_name . '.' . $convert_type,
				$image_quality
			);

			return $img_name . '.' . $convert_type;
		}

		return false;
	}

	/**
	 * @param string $target
	 * @param string $file
	 *
	 * @return string
	 */
	public function parseTarget( string $target, string $file ): string {
		return str_replace( '[filename]', $file, $target );
	}

	/**
	 * http://stackoverflow.com/a/2021729
	 * Remove anything which isn't a word, whitespace, number
	 * or any of the following characters -_~,;[]().
	 * If you don't need to handle multi-byte characters
	 * you can use preg_replace rather than mb_ereg_replace
	 *
	 * @param $str
	 *
	 * @return string
	 */
	public function sanitizeFileName( $str ) {
		// Basic clean up
		$str = preg_replace(
			'([^\w\s\d\-_~,;\[\]\(\).])',
			'',
			$str
		);
		// Remove any runs of periods
		$str = preg_replace(
			'([\.]{2,})',
			'',
			$str
		);

		return $str;
	}

	/**
	 * Strips the "data:image..." part of the base64 data string so PHP can save the string as a file
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public function getBase64Data( $data ) {
		return base64_decode(
			preg_replace(
				'#^data:image/\w+;base64,#i',
				'',
				$data
			)
		);
	}


}