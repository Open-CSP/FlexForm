<?php

namespace FlexForm\Processors\Files;

use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Core\HandleResponse;
use FlexForm\FlexFormException;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Utilities\General;
use FlexForm\Processors\Files;
use Symfony\Component\Security\Acl\Exception\Exception;

class FilesCore {

	 public const FILENAME = 'wsformfile';
	 public const SIGNATURE_FILENAME = 'wsform_signature';
	 public const CANVAS_FILENAME = 'ff_canvas_file';

	/**
	 * @return void
	 * @throws FlexFormException
	 */
	public function handleFileUploads(): void {

		$fields = Definitions::fileUploadFields();
		if ( $fields['actions'] === null ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug( 'No file uploads',
								   [
									   'fields' => $fields,
									   'post'   => $_POST
								   ] );
			}
			return;
		}

		foreach ( $fields['actions'] as $entry ) {
			foreach ( $entry as $fileName => $fileDetails ) {
				$fileName = General::makeUnderscoreFromSpace( $fileName );
				switch ( General::getJsonValue(
					'type',
					$fileDetails
				) ) {
					case "signature":
						$wsSignature = General::getPostString(
							$fileName,
							false
						);
						$res = Signature::upload( $wsSignature, $fileDetails );
						break;
					case "canvas":
						$wsCanvas = General::getPostString(
							$fileName,
							false
						);
						if ( $wsCanvas !== false ) {
							try {
								$res = Canvas::upload( $wsCanvas, $fileDetails );
							} catch ( FlexFormException $e ) {
								throw new FlexFormException(
									$e->getMessage(),
									0
								);
							} catch ( \MWContentSerializationException | \MWException $e ) {
							}
						}
						break;
					case "file":
						if ( Config::isDebug() ) {
							Debug::addToDebug(
								'File upload class',
								[
									'Looking for ' . $fileName,
									$_FILES
								]
							);
						}
						if ( isset( $_FILES[$fileName] ) ) {
							if ( Config::isDebug() ) {
								Debug::addToDebug(
									'Checking for files to upload',
									$_FILES
								);
							}
							if ( ( is_array( $_FILES[$fileName]['tmp_name'] ) && file_exists(
										$_FILES[$fileName]['tmp_name'][0]
									) ) || ( file_exists( $_FILES[$fileName]['tmp_name'] ) ) ) {
								$fileUpload = new Upload( $fileName, $fileDetails );
								try {
									$res = $fileUpload->fileUpload();
								} catch ( FlexFormException $e ) {
									throw new FlexFormException(
										$e->getMessage(),
										0
									);
								}
								if ( Config::isDebug() ) {
									Debug::addToDebug(
										'New _POST',
										$_POST
									);
								}
							}
						}
						break;
				}
			}
		}
	}

	/**
	 * Check if it is a single file and check if error check is set
	 *
	 * @param array $file
	 *
	 * @return bool|string
	 */
	public function checkFileUploadForError( array $file ) {
		if ( !isset( $file['error'] ) || is_array( $file['error'] ) ) {
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
		switch ( $file ) {
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
	 * @param int $image_quality [0-100]
	 *
	 * @return string|bool return path of new file or false if nothing can be worked out]
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
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Converting ' . $target_name,
				[ 'image' => $image,
				  'target_name' => $target_name,
				  'target_dir' => $target_dir,
				  'convert_type' => $convert_type,
					'img_name' => $img_name ]
			);
		}
		//to png
		if ( $convert_type == 'png' ) {
			$binary = imagecreatefromstring( file_get_contents( $image ) );
			//third parameter for ImagePng is limited to 0 to 9
			//0 is uncompressed, 9 is compressed
			//so convert 100 to 2 digit number by dividing it by 10 and minus with 10
			$image_quality = floor( 10 - ( $image_quality / 10 ) );
			$result = ImagePNG(
				$binary,
				$target_dir . $img_name . '.' . $convert_type,
				$image_quality
			);
			if ( Config::isDebug() ) {
				if ( $result === false ) {
					$res = 'png conversion failed';
				} else {
					$res = 'png conversion success';
				}
				Debug::addToDebug(
					'png convert ' . $target_name,
					$res
				);
			}

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

	/**
	 * @return string
	 */
	public function getUploadDir(): string {
		return rtrim(
						  Config::getConfigVariable( 'file_temp_path' ),
						  '/'
					  ) . '/';
	}


}