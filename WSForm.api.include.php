<?php

function makeMessage($msg, $type="danger") {
	setcookie("wsform[type]", $type, 0, '/' );
	setcookie("wsform[txt]", 'WSForm :: '.$msg, 0, '/' );
}

function MakeTitle() {
	//$date      = new DateTime( '2000-01-01' );
	//$dt        = date( 'd/m/Y H:i:s' );
	$pageTitle = time();

	return $pageTitle;
}

function is_cli()
{
	if( defined('STDIN') )
	{
		return true;
	}

	if( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0)
	{
		return true;
	}

	return false;
}

function checkDefaultInformation() {
	global $api;
    $ret = array();
	$ret['mwhost'] = false;
    $ret['mwtoken'] = false;
    $ret['mwsession'] = false;
    if( isset($_POST['mwtoken']) && $_POST['mwtoken'] !== '') {
	    $token = base64_decode($_POST['mwtoken']);
	    $explo = explode('_', $token );
	    if($explo === false || !is_array( $explo ) ) {
	    	return $ret;
	    }
	    $ret['mwtoken'] = true;
        $ttime = $explo[2];
        $host = $explo[1];
        if( $_SERVER['HTTP_HOST'] === $host ) {
	        $ret['mwhost'] = true;
        }
        $ctime = time();
        $difference = $ctime - (int)$ttime;
        //echo "<p>$ctime - $ttime = $difference</p>";
	    //die($token);
	    // Get config timeout
		$timeOut = $api->app['form-timeout-limit'];
		if( $timeOut === '' ) {
			$timeOut = 7200;
		}
        if ($difference < (int) $timeOut) { // 2 hrs session time
            $ret['mwsession'] = true;
        }
    }
    return $ret;
}

/**
 * @brief Return the current version number for WSForm taken from extension.json
 *
 * @return string Version number
 */
function getVersion() {
	$extension = json_decode(file_get_contents('extension.json'), true);
	return $extension['version'];
}

function get_all_string_between($string, $start, $end)
{
    $result = array();
    $string = " ".$string;
    $offset = 0;
    while(true)
    {
        $ini = strpos($string,$start,$offset);
        if ($ini == 0)
            break;
        $ini += strlen($start);
        $len = strpos($string,$end,$ini) - $ini;
        $result[] = substr($string,$ini,$len);
        $offset = $ini+$len;
    }
    return $result;
}

function parseTitle( $title ) {
	$tmp = get_all_string_between( $title, '[', ']' );
	foreach ( $tmp as $fieldname ) {
        if( isset( $_POST[makeUnderscoreFromSpace($fieldname)] ) ) {
            $fn = $_POST[makeUnderscoreFromSpace($fieldname)];
            if( is_array( $fn ) ) {
                $imp = implode( ', ', $fn );
                $title = str_replace('[' . $fieldname . ']', $imp, $title);
            } elseif ( $fn !== '' ) {
                $title = str_replace('[' . $fieldname . ']', $fn, $title);
            } else {
                $title = str_replace('[' . $fieldname . ']', '', $title);
            }
		} else {
            $title = str_replace('[' . $fieldname . ']', '', $title);
        }
		if( $fieldname == 'mwrandom' ) {
			$title = str_replace( '['.$fieldname.']', MakeTitle(), $title );
		}
	}
	return $title;
}

function makeSpaceFromUnderscore( $txt ) {
	return str_replace( "_", " ", $txt );
}

function makeUnderscoreFromSpace( $txt ) {
	return str_replace( " ", "_", $txt );
}

/**
 * Check to see if a variable is a WSForm variable
 *
 * @param $field string field to check
 * @return bool true or false
 */
function isWSFormSystemField ($field) {
	$WSFormSystemFields = array (
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
		"mwslot"
	);
	if(in_array(strtolower($field),$WSFormSystemFields)) {
		return true;
	} else return false;
}

/**
 * Check if it is a single file and check if error check is set
 *
 * @param $file string
 *
 * @return bool
 */
function checkFileUploadForError($file) {
	if ( !isset($file['error']) || is_array($file['error'] ) ) {
		return "No file found or we received multiple files.";
	} else return false;
}

/**
 * Check for error messages in uploaded file
 *
 * @param $file string
 *
 * @return bool|string
 */
function checkFileForErrors($file) {
	switch ($file['error']) {
		case UPLOAD_ERR_OK:
			return false;
			break;
		case UPLOAD_ERR_NO_FILE :
			return "no file received";
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			return "file exceeds filesize limit";
			break;
		default:
			return "unkown file error";
	}
}

function remove_extension_from_image($image){
		$extension = getFileExtension($image); //get extension
		$only_name = basename($image, '.'.$extension); // remove extension
		return $only_name;
	}

function getFileExtension( $file ) {
	$path_parts = pathinfo($file);
	$extension = $path_parts['extension'];
	return strtolower( $extension );
}

function setNewFileName( $filename ) {
	$identifier = date("Ymdhis");
	$name = sprintf('%s.%s', sha1_file($identifier . "-". $filename), getFileExtension($filename['name']));
	return $name;
}

/**
 * Converting image types
 * @param  string  $convert_type  [where to convert to. png, jpg or gif]
 * @param  string  $target_dir    [description]
 * @param  string  $target_name   [name of the target file]
 * @param  string  $image         [path to image]
 * @param  integer $image_quality [0-100]
 * @return string                 [return path of new file or false if nothing can be worked out]
 */
function convert_image($convert_type, $target_dir, $target_name, $image, $image_quality=100){

		//remove extension from image;
		$img_name = remove_extension_from_image($target_name);
		//to png
		if($convert_type == 'png'){
			$binary = imagecreatefromstring(file_get_contents($image));
			//third parameter for ImagePng is limited to 0 to 9
			//0 is uncompressed, 9 is compressed
			//so convert 100 to 2 digit number by dividing it by 10 and minus with 10
			$image_quality = floor(10 - ($image_quality / 10));
			ImagePNG($binary, $target_dir.$img_name.'.'.$convert_type, $image_quality);
			return $img_name.'.'.$convert_type;
		}

		//to jpg
		if($convert_type == 'jpg'){
			$binary = imagecreatefromstring(file_get_contents($image));
			imageJpeg($binary, $target_dir.$img_name.'.'.$convert_type, $image_quality);
			return $img_name.'.'.$convert_type;
		}
		//to gif
		if($convert_type == 'gif'){
			$binary = imagecreatefromstring(file_get_contents($image));
			imageGif($binary, $target_dir.$img_name.'.'.$convert_type, $image_quality);
			return $img_name.'.'.$convert_type;
		}
		return false;
	}

/**
 * Function called to create return parameters in a consistent way.
 *
 * @param string|array $msg Message to pass
 * @param string $status Defaults to error. "ok" to pass
 * @param bool|url $mwreturn url to return page
 * @param bool|string $type type of visial notice to show (error, warning, success, etc)
 * @return array
 */
function createMsg($msg,$status="error", $mwreturn=false, $type=false) {
		$tmp = array();
		$tmp['status']=$status;
        $tmp['type']=$type;
        $tmp['mwreturn']=$mwreturn;
        if(is_array($msg)) {
        	$combined = implode('<BR>',$msg);
            $tmp['msg']=$combined;

		} else $tmp['msg']=$msg;
		return $tmp;
	}


/**
 * Function to create thumbnail from an ixisting image
 *
 * @param $src - a valid file location
 * @param $dest - a valid file target
 * @param $targetWidth - desired output width
 * @param $targetHeight - desired output height or null
 *
 * @return
 */
function createThumbnail($src, $dest, $targetWidth, $targetHeight = null) {

	global $imageHandler;
    // 1. Load the image from the given $src
    // - see if the file actually exists
    // - check if it's of a valid image type
    // - load the image resource

    // get the type of the image
    // we need the type to determine the correct loader
    $type = exif_imagetype($src);

    // if no valid type or no handler found -> exit
    if (!$type || !$imageHandler[$type]) {
        return false;
    }

    // load the image with the correct loader
    $image = call_user_func($imageHandler[$type]['load'], $src);

    // no image found at supplied location -> exit
    if (!$image) {
        return false;
    }


    // 2. Create a thumbnail and resize the loaded $image
    // - get the image dimensions
    // - define the output size appropriately
    // - create a thumbnail based on that size
    // - set alpha transparency for GIFs and PNGs
    // - draw the final thumbnail

    // get original image width and height
    $width = imagesx($image);
    $height = imagesy($image);

    // maintain aspect ratio when no height set
    if ($targetHeight == null) {

        // get width to height ratio
        $ratio = $width / $height;

        // if is portrait
        // use ratio to scale height to fit in square
        if ($width > $height) {
            $targetHeight = floor($targetWidth / $ratio);
        }
        // if is landscape
        // use ratio to scale width to fit in square
        else {
            $targetHeight = $targetWidth;
            $targetWidth = floor($targetWidth * $ratio);
        }
    }

    // create duplicate image based on calculated target size
    $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

    // set transparency options for GIFs and PNGs
    if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {

        // make image transparent
        imagecolortransparent(
            $thumbnail,
            imagecolorallocate($thumbnail, 0, 0, 0)
        );

        // additional settings for PNGs
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
    }

    // copy entire source image to duplicate image and resize
    imagecopyresampled(
        $thumbnail,
        $image,
        0, 0, 0, 0,
        $targetWidth, $targetHeight,
        $width, $height
    );


    // 3. Save the $thumbnail to disk
    // - call the correct save method
    // - set the correct quality level

    // save the duplicate version of the image to disk
    return call_user_func(
        $imageHandler[$type]['save'],
        $thumbnail,
        $dest,
        $imageHandler[$type]['quality']
    );
}


/**
 * Takes care of uploading a signature file
 *
 * @return array|bool Either true on success or a createMsg() error
 */
function signatureUpload() {
	global $wsuid;
    $allowedTypes = array(
        'png',
        'jpg',
        'svg'
    );

    $wname = getPostString('wsform_signature_filename');
    $data = getPostString('wsform_signature');
    $fileType = getPostString('wsform_signature_type');
    $pcontent = getPostString('wsform_signature_page_content');

    if (!$wname) return createMsg( 'No target file for signature.' );
    if (!$pcontent) return createMsg( 'No page content found. Required.' );
    if (!$data) return createMsg( 'No signature file found.' );
    if (!$fileType || !in_array( $fileType, $allowedTypes ) ) return createMsg( 'No signature file type found or a not allowed filetype' );

    if( !class_exists( 'wbApi') ) {
        require_once( 'WSForm.api.class.php' );
    }
    $api = new wbApi();
	if( $api->getStatus() === false ){
		return createMsg( $api->getStatus( true) );
	}
    $upload_dir = getcwd()."/uploads/";
    if ($fileType !== 'svg') {
        $data = $api->getBase64Data( $data );
    }

    // Test if directory already exists
    if(!is_dir($upload_dir)){
        mkdir($upload_dir, 0755, true);
    }
    $fname =  $api->sanitizeFileName($wname) . ".".$fileType;
    echo $fname;
    if (!file_put_contents($upload_dir . $fname, $data)) {
        return createMsg( 'Could not save file.' );
    }
    $api->logMeIn();
    $url = $api->app['baseURL'] . 'extensions/WSForm/uploads/'.$fname;
    $pname = trim($wname);
    $comment = "Uploaded using WSForm.";
    $result = $api->uploadFileToWiki($pname, $url, $pcontent, $comment, $upload_dir . $fname);
    unlink($upload_dir . $fname);
    return true;
}


/**
 * When a file is uploaded using the Slim extension, this function will take care of the saving
 *
 * @return array|bool Either true on success or a createMsg() error
 */
function fileUploadSlim() {

	if ( !isset( $_POST['wsform_file_target'] ) || $_POST['wsform_file_target']=="" ) {
		return createMsg( 'No target file.' );
	}

	if ( !isset($_POST['wsform_page_content'] ) || $_POST['wsform_page_content']=="" ) {
		return createMsg( 'No wiki content for this file.' );
	}

    if ( isset($_POST['wsform_file_thumb_width'] ) && $_POST['wsform_file_thumb_width'] !== "" ) {
        $thumbWidth = $_POST['wsform_file_thumb_width'];
    } else $thumbWidth = false;

    if ( isset($_POST['wsform_file_thumb_height'] ) && $_POST['wsform_file_thumb_height'] !== "" && $thumbWidth !== false) {
        $thumbHeight = $_POST['wsform_file_thumb_height'];
    } else $thumbHeight = null;


	$upload_dir = getcwd()."/uploads/";

	include_once('modules/slim/server/slim.php');
	// Get posted data
	$images = Slim::getImages('wsformfile_slim');


	// No image found under the supplied input name
	if ($images == false) {

	    // inject your own auto crop or fallback script here
	    return createMsg( 'Presentor Slim was not used to upload these images.',"ok" );

	}
	if (isset($images[0]['output']['data'])) {
		// Save the file
		$name = $images[0]['output']['name'];
		$name = $targetFile = makeUnderscoreFromSpace($name);

		// We'll use the output crop data
		$data = $images[0]['output']['data'];

		$output = Slim::saveFile($data, $name,$upload_dir,false);
		require_once( 'WSForm.api.class.php' );
		$api = new wbApi();
		if( $api->getStatus() === false ) {
			return createMsg( $api->getStatus( true ) );
		}
		$url = $api->app['baseURL'] . 'extensions/WSForm/uploads/'.$output['name'];
		$api->logMeIn();
		$pname = trim($_POST['wsform_file_target']);
		$details = trim($_POST['wsform_page_content']);
		$comment = "Uploaded using WSForm.";
		$result = $api->uploadFileToWiki($pname, $url, $details, $comment, $upload_dir . $output['name']);
		if($thumbWidth !== false) {
			$thumbName = 'sm_'.$name;
            $turl = $api->app['baseURL'] . 'extensions/WSForm/uploads/'.$thumbName;
            if( createThumbnail($upload_dir.$name, $upload_dir.$thumbName, $thumbWidth, $thumbHeight) ) {
                $result = $api->uploadFileToWiki('sm_'.$pname, $turl, $details, $comment, $upload_dir . $thumbName);
                unlink($upload_dir . $thumbName);
			}

		}
		unlink($upload_dir . $output['name']);
		return true;


	} else return createMsg( "Presentor Slim said : No image output." );

}

/**
 * Normal HTML5 file upload handling
 *
 * TODO: Add upload of multiple file, by passing file to be uploaded, instead of using $_FILES
 *
 * @return array|bool Either true on success or a createMsg() error
 */
function fileUpload() {

	if( !isset($_FILES['wsformfile']) ) {
		return createMsg('no wsformfile file found');
	}
	if( $filechk = checkFileUploadForError($_FILES['wsformfile']) ) {
		return createMsg($filechk);
	}
	if ( $res = checkFileForErrors( $_FILES['wsformfile'] ) ) {
		if ( $res !== false ) {
			return createMsg($res);
		} else return $res;
	}
	if ( !isset($_POST['wsform_file_target']) || $_POST['wsform_file_target']=="") {
		return createMsg('No target file.');
	}
	if ( !isset($_POST['wsform_page_content']) || $_POST['wsform_page_content']=="" ) {
		return createMsg('No wiki content for this file.');
	}
	if ( !isset($_POST['wsform_image_force']) || $_POST['wsform_image_force']=="") {
		$convert = false;
	} else {
		if (getFileExtension($_FILES['wsformfile']['name']) == $_POST['wsform_image_force']) {
			$convert = false;
		} else $convert = $_POST['wsform_image_force'];
	}

	$upload_dir = getcwd()."/uploads/";
	$targetFile = makeUnderscoreFromSpace($_FILES['wsformfile']['name']);
	if ($convert) {
			$newFile = convert_image($convert, $upload_dir, $targetFile, $_FILES['wsformfile']['tmp_name'], $image_quality=100);
			if($newFile === false) {
				return createMsg("Error while converting image from ".getFileExtension($_FILES['wsformfile']['name'])." to ".$convert.".");
			}
	} else {
		if( move_uploaded_file( $_FILES['wsformfile']['tmp_name'], $upload_dir . $targetFile ) ) {
			$newFile = $targetFile;
		} else return createMsg("Error uploading file to destination (file-handling)");
	}

	// file upload is done.. Now getting it into the wiki
	require_once( 'WSForm.api.class.php' );
	$api = new wbApi();
	if( $api->getStatus() === false ) {
		return createMsg( $api->getStatus( true ) );
	}
	$url = $api->app['baseURL'] . 'extensions/WSForm/uploads/'.$newFile;
	$api->logMeIn();
	$name = trim($_POST['wsform_file_target']);
	$details = trim($_POST['wsform_page_content']);
	if( isset( $_POST['wsform_parse_content'] ) ) {
	    $details = parseTitle( $details );
    }
	$comment = "Uploaded using WSForm.";
	$result = $api->uploadFileToWiki($name, $url, $details, $comment, $upload_dir . $targetFile);
	unlink($upload_dir . $targetFile);
	return true;
}

function createGet() {
	global $removeList;

	if ( isset( $_POST['mwreturn'] ) && $_POST['mwreturn'] !== "" ) {
		$returnto = $_POST['mwreturn'];
	} else {
		$returnto = false;
	}
	if ( $returnto ) {
		require_once( 'WSForm.api.class.php' );
		$api = new wbApi();
		$secure = $api->isSecure();

		$ret = $returnto;
		foreach ( $_POST as $k => $v ) {
			if ( strpos( $ret, "?" ) ) {
				$delimiter = '&';
			} else {
				$delimiter = '?';
			}
			if ( is_array( $v ) ) {
				if( !isWSFormSystemField( $k ) ) {
					$ret .= $delimiter . makeSpaceFromUnderscore( $k ) . "=";
					foreach ( $v as $multiple ) {
						$ret .= cleanHTML( cleanBraces( $multiple ) ) . ',';
					}
					$ret = rtrim( $ret,
						',' );
				}
			} else {
				$resultDelete = in_array( $k, $removeList );
				if ( $k !== "mwreturn" &&
					 $v != "" &&
					 $k !== 'mwdb' &&
					 (isWSFormSystemField( $k ) === false ) &&
					 !$resultDelete
				) {

					$html = getHTMLType( $k );
					if( $html !== "all" ) {
						if( $html === "nohtml" ) {
							$apo = true;
						} else $apo = false;
						$ret .= $delimiter . makeSpaceFromUnderscore( $k ) . '=' . cleanUrl( getPostString( $k ), $apo );
					} else {
						$ret .= $delimiter . makeSpaceFromUnderscore( $k ) . '=' . getPostString( $k );
					}
				}
			}
		}
		//exit;
		return $ret;
	}
}

/**
 * Creates a mail job
 * @param $data array Received data from form
 * @param $api object instance of the WSFrom api class
 * @return array
 */
function createJob($data, $api) {
	if( $data['to'] == "" ) return array( "status"=>"error", "message"=>"No to" );
	if( $data['from'] == "" ) return array( "status"=>"error", "message"=>"No from" );
	if( $data['html'] ===  false || $data['html'] == 'yes' ) {
		$data['html'] = true;
	} else $data['html'] = false;
	if( $data['subject'] == "" ) return array( "status"=>"error", "message"=>"No subject" );
	if( $data['template'] == "" ) {
		return array( "status"=>"error", "message"=>"No template" );
	} else {
		$tpl = $api->parseWikiPageByTitle($data['template']);
		if ($tpl == "" ) {
			return array( "status"=>"error", "message"=>"Template not found in wiki" );
		} else $data['template']=$tpl;
	}
	if( $data['cc'] == "" ) $data['cc'] = false;
	if( $data['bcc'] == "" ) $data['bcc'] = false;
	if( $data['header'] == "" ) {
		$data['header'] = false;
	} else {
		$header = $api->parseWikiPageByTitle($data['header']);
		if ($header == "" ) {
			return array( "status"=>"error", "message"=>"Header not found in wiki" );
		} else $data['header']=$header;
	}
	if( $data['footer'] == "" ) {
		$data['footer'] = false;
	} else {
		$footer = $api->parseWikiPageByTitle($data['footer']);
		if ($footer == "" ) {
			return array( "status"=>"error", "message"=>"Footer not found in wiki" );
		} else $data['footer']=$footer;
	}
	if( $data['variableKey'] == "" ) {
		$data['variableKey'] = false;
	} else {
		$keys = explode(',', $data['variableKey'] );
	}
	if( $data['variableValue'] == "" ) {
		$data['variableValue'] = false;
	} else {
		$vals = explode(',', $data['variableValue'] );

	}

	if( $data['variableValue'] !== false && $data['variableKey'] !== false ) {
		if ( count( $keys ) != count( $vals ) ) {
			return array( "status"=>"error", "message"=>"Number of variableKeys and variableValues do not match" );
		}
		foreach($keys as $k=>$v) {
			$data['variables'][$v]=$vals[$k];
		}
	} else return array( "status"=>"error", "message"=>"Mismatch in variableKey and variableValue" );

	$body = "";
	if ( $data['header'] !== false ) {
		$body .= $data['header'];
		unset( $data['header'] );
	}

	foreach ($data['variables'] as $k=>$v) {
		$data['template'] = str_replace('$'.$k, $v, $data['template']);
	}
	$body .= $data['template'];
	unset( $data['variables'] );
	unset( $data['template'] );
	unset( $data['variableValue'] );
	unset( $data['variableKey'] );

	if ( $data['footer'] !== false ) {
		$body .= $data['footer'];
		unset( $data['footer'] );
	}
	$data['body']=$body;

	return array ("status"=>"ok", "data"=>$data);

}


/**
 * Check and get a $_POST value
 *
 * @param $var $_POST value to check
 * @param bool $clean to clean input
 * @return bool Returns false if not set or empty.
 * @return string value of the $_POST key
 */
function getPostString( $var, $clean = true ) {
	if ( isset( $_POST[$var] ) && $_POST[$var] !== "" ) {
		$template = $_POST[$var];
	} else {
		$template = false;
	}
	if( $clean === true && $template !== false ) {
		$clean_html = cleanHTML( $template, $var );
		return cleanBraces( $clean_html );
	} else return $template;
}

function getHTMLType( $name ) {
	global $checksum, $formId;
	if(is_null( $checksum)) return "default";
	if( isset( $checksum[$formId][$name] ) ) {
		return $checksum[$formId][$name]['html'];
	} else return "default";
}

function cleanHTML( $var, $name = false ) {
	global $securedVersion;
	if( $securedVersion === false ) {
		return $var;
	}
	$html = '';
	if( $name !== false ) {
		$html = getHTMLType( $name );
	}
	require_once( 'classes/protect.class.php' );
	$pure = new wsform\protect\protect();
	return $pure::purify( $var, $html, $securedVersion );
	/*
	$config = HTMLPurifier_Config::createDefault();
	$purifier = new HTMLPurifier($config);
	return $purifier->purify( $var );
	*/
}

function purify( $value, $clean = "default", $custom = false ) {
	if( $clean === "all" ) return $value;
	$api = new wbApi();
	$IP = $api->app['IP'];
	require_once( $IP . '/extensions/WSForm/modules/htmlpurifier/library/HTMLPurifier.auto.php' );
	$config = \HTMLPurifier_Config::createDefault();
	switch( $clean ) {
		case "nohtml":
			$config->set('HTML.Allowed', '');
			break;
		case "custom":
			if( $custom !== false ){
				$config->set('HTML.Allowed', $custom); // e.g. 'p,ul[style],ol,li'
			}
			break;
		case "default" :

		default:
			break;
	}
	$purifier = new \HTMLPurifier($config);
	return $purifier->purify( $value );

}


function cleanUrl( $var, $clearApo = true ) {
	return urlencode( $var ); // temp for allowing "" and ''
	/*
	$var = str_replace('"', "", $var);
	if( $clearApo ) {
		$var = str_replace( "'", "", $var );
	}
	return $var;
	*/
}

/**
 * Helper function for WSForm extension to check available fields
 * @param $var string Key for field to check
 * @return bool when false
 * @return string value of key
 */
function getFormValues( $var)  {
    global $wsPostFields;
    if ( isset( $wsPostFields[$var] ) && $wsPostFields[$var] !== "" ) {
        $template = $wsPostFields[$var];
    } else {
        $template = false;
    }
    return $template;
}

/**
 * Function to check and get a $_GET value
 *
 * @param $var $_GET value to check
 * @param bool $check if true it will also check if not empty. If false it only checks to if key exists
 * @return bool when false
 * @return string value of key
 */
function getGetString( $var, $check = true, $clean = true ) {
	if( $check ) {
		if ( isset( $_GET[$var] ) && $_GET[$var] !== "" ) {
			$template = $_GET[$var];
		} else {
			$template = false;
		}
	} else {
		if ( isset( $_GET[ $var ] ) ) {
			$template = $_GET[ $var ];
		} else {
			$template = false;
		}
	}
	if( $clean === true && $template !== false ) {
		$config = HTMLPurifier_Config::createDefault();
		$purifier = new HTMLPurifier($config);
		$clean_html = $purifier->purify( $template );
		$template = cleanBraces( $clean_html );
	}

    return $template;
}

/**
 * Check if Post has variable as array. yes, then return array, else return false
 *
 * @param  string $var Name of key to check if array
 * @return array The array
 * @return bool false if not an array
 */
function getPostArray($var) {
	if ( isset( $_POST[$var] ) && is_array( $_POST[$var] ) ) {
		$template = $_POST[$var];
	} else {
		$template = false;
	}
	return $template;
}



function pregExplode($str) {
    $result = preg_split('~\|(?![^{{}}]*\}\})~', $str);
    return $result;
}

// void
function createMail() {

}

/**
 * Experimental function to get a username from session
 *
 * @param bool $onlyName
 * @return string
 */
function setSummary($onlyName=false) {
	//TODO: Still needs work as mwdb is not always a default
	$dbn = getPostString('mwdb');
	if( $dbn !== false && isset( $_COOKIE[$dbn.'UserName'] ) ) {
		if($onlyName === true) {
			return ($_COOKIE[$dbn.'UserName'] );
		} else {
			return ('[[User:' . $_COOKIE[$dbn . 'UserName'] . ']]');
		}
	} else {
	    $ip = $_SERVER['REMOTE_ADDR'];
        return ('Anon user: ' . $ip);
    }
}

/**
 * Remove all curly braces
 * @param string $value
 *
 * @return string cleaned text
 */
function cleanBraces( $value ) {
	global $wsuid;
	if( $wsuid !== false && !is_null( $wsuid ) ) {
		return $value;
	}
	//return $value;
	return preg_replace("/\{{[^)]+\}}/","",$value);
}

/**
 * Main function for editing or creating new pages in wiki
 *
 * @param bool $email
 * @return array
 */
function saveToWiki( $email=false ) {

	global $title, $i18n;
	$weHaveApi = false;



    $parsePost = getPostString('wsparsepost' );
    $parseLast = getPostString('mwparselast');
	$etoken = getPostString('wsedittoken' );
	$template = getPostString('mwtemplate');
	$writepage = getPostString('mwwrite');
	$option = getPostString('mwoption');
	$returnto = getPostString('mwreturn', false );
	$returnfalse = getPostString('mwreturnfalse');
	$mwedit = getPostArray('mwedit');
	$writepages = getPostArray('mwcreatemultiple');
    $msgOnSuccess = getPostString('mwonsuccess');
	$mwfollow = getPostString('mwfollow');
    $leadByZero = false;
	$summary = getPostString('mwwikicomment');
	$slot = getPostString( 'mwslot' );



	if( $summary === false ) {
		$summary = setSummary();
	}

	if( isset( $_POST['mwleadingzero'] ) ) {
        $leadByZero = true;
    }
	if( $returnto === false && $returnfalse === false ) {
        return createMsg('no return url defined','error', $returnto );
	}

    if( $parsePost !== false && is_array( $parsePost ) ) {
        foreach ( $parsePost as $pp ) {
            if( isset( $_POST[makeUnderscoreFromSpace($pp)] ) ) {
                $_POST[makeUnderscoreFromSpace($pp)] = parseTitle( $_POST[makeUnderscoreFromSpace($pp)] );
            }
        }
    }


	$noTemplate = false;
	if ( $template !== false && $writepage !== false ) {

		if( $template === strtolower( 'wsnone' ) ) {
			$noTemplate = true;
		}
		if( !$noTemplate ) {
			$ret = "{{" . $template . "\n";
		}
		foreach ( $_POST as $k => $v ) {

			if ( is_array( $v ) && !isWSFormSystemField($k) ) {

				$ret .= "|" . makeSpaceFromUnderscore( $k ) . "=";
				foreach ( $v as $multiple ) {
					$ret .= cleanBraces( $multiple ) . ',';
				}
				$ret = rtrim( $ret, ',' ) . PHP_EOL;
			} else {
				if ( !isWSFormSystemField($k) && $v != "" ) {
					if( !$noTemplate ) {
						$ret .= '|' . makeSpaceFromUnderscore( $k ) . '=' . cleanBraces( $v ) . "\n";
					} else {
						$ret = $v . PHP_EOL;
					}
				}
			}
		}
		if( !$noTemplate ) {
			$ret .= "}}";
		}

		require_once( 'WSForm.api.class.php' );
		$api = new wbApi();
		if( $api->getStatus() === false ){
			return createMsg( $api->getStatus( true ), 'error', $returnto);
		}

		if (strpos($writepage,'[') !== false) {
			$writepage = parseTitle($writepage);
		}


		if ( $writepage !== false ) {
			$title = $writepage;
		}
		if( $option == 'next_available' && $writepage !== false ) {
			// get highest number
			$hnr = $api->getNextAvailable( $title );
			if( $hnr['status'] !== 'error') {
				$title = $writepage . $hnr['result'];
			} else {
				return createMsg( $hnr['message'], 'error', $returnto);
			}
			//$title = $writepage . $api->getNextAvailable( $title );
			//die( $title );
			//$title = $writepage . $api->getWikiListNumber($title);
			if( $title === false ) {
                return createMsg($i18n->wsMessage( 'wsform-mwcreate-wrong-title2' ), 'error', $returnto);
            }
		}
		if ( substr( strtolower( $option ) ,0,6 ) === 'range:' ) {
			$range = substr( $option,6 );
			$rangeCheck = explode('-', $range);

			if( !ctype_digit( $rangeCheck[0] ) || !ctype_digit( $rangeCheck[1] ) ) {
				return createMsg($i18n->wsMessage( 'wsform-mwoption-bad-range' ), 'error', $returnto);
			}

            //$startRange = (int)$range[0];
            //$endRange = (int)$range[1];


			//$tmp  = $api->getWikiListNumber($title, array('start' => $startRange, 'end' => $endRange) );
			$tmp  = $api->getFromRange( $title, $range );
			if( $tmp['status'] === 'error') {
				//echo $tmp['message'];
				return createMsg( $tmp['message'], 'error', $returnto);
			}
			$tmp = $tmp['result'];
			/*
			if($tmp === false) {
				return createMsg($i18n->wsMessage('wsform-mwoption-out-of-range'), 'error', $returnto);
			}
			*/
            if( $leadByZero === true ) {
                $endrangeLength = strlen($range[1]);
                $tmp = str_pad($tmp, $endrangeLength, '0', STR_PAD_LEFT);
            }
			$title = $writepage . $tmp;
		}

		if ( $option == 'add_random' && $writepage !== false ) {
			$title = $writepage . MakeTitle();
		}


		if ( ! $writepage ) {
            return createMsg( $i18n->wsMessage( 'wsform-mwcreate-wrong-title') );

		}
		// Now add the page to the wiki


		//$api->usr = $etoken;
		$api->logMeIn();
		//die($wsuid);

		if( $api->app['create-seo-titles'] === true ) {
			$title = $api->urlToSEO( $title );
		}
		$result = $api->savePageToWiki( $title, $ret, $summary, $slot );

		if(isset($result['received']['error'])) {
			return createMsg($result['received']['error'],'error',$returnto);
		}
		if( $mwfollow !== false ) {
			if( $mwfollow === 'true' ) {

				$returnto = $api->app['wgScript'] . '/' . $title;
			} else {
				if( strpos( $returnto, '?' ) ) {
					$returnto = $returnto . '&' . $mwfollow . '=' . $title;
				} else {
					$returnto = $returnto . '?' . $mwfollow . '=' . $title;
				}
			}
		}
		$weHaveApi = true;


	}

	// Do we have other edits to make ?
	if ( ! $mwedit && ! $email && ! $writepages ) {
        if($msgOnSuccess) {
            return( createMsg($msgOnSuccess,'ok',$returnto, 'success') );
        } else return( createMsg('ok','ok',$returnto) );
	}

if($writepages !== false) {

	foreach ($writepages as $singlePage) {
        $noTemplate = false;
        $writePageSlot = false;
		$pageData = explode( '-^^-', $singlePage );
		if ( $pageData[0] == '' || $pageData[1] == '') {
			continue;
		}
		$pageTemplate = $pageData[0];
		if( $pageTemplate === strtolower( 'wsnone' ) ) {
		    $noTemplate = true;
        }
		$pageTitle = $pageData[1];
		if ($pageData[2] == "") {
			$pageOption = false;
		} else $pageOption = $pageData[2];
		if ($pageData[3] == "") {
			$formFields = false;
		} else $formFields = $pageData[3];
		if ($pageData[4] == "") {
			$writePageSlot = false;
		} else $writePageSlot = $pageData[4];
		if ( !$noTemplate ) {
            $ret = "{{" . $pageTemplate . "\n";
        }
		if($formFields !== false) {
			$formFields = explode(',', $formFields);
		}
		$formFields = array_map('trim', $formFields);
		foreach ( $_POST as $k => $v ) {
			if ( is_array( $formFields ) ) {
				if ( !in_array( makeSpaceFromUnderscore( $k ), $formFields ) && !in_array( $k, $formFields ) ) {
					continue;
				}
			}
			if ( is_array( $v ) ) {
				$ret .= "|" . makeSpaceFromUnderscore( $k ) . "=";
				foreach ( $v as $multiple ) {
					$ret .= cleanBraces( $multiple ) . ',';
				}
				$ret = rtrim( $ret, ',' ) . PHP_EOL;
			} else {
				if ( !isWSFormSystemField($k) && $v != "" ) {
				//if ( $k !== "mwtemplate" && $k !== "mwoption" && $k !== "mwwrite" && $k !== "mwreturn" && $k !== "mwedit" && $v != "" ) {
                    if( !$noTemplate ) {
                        $ret .= '|' . makeSpaceFromUnderscore($k) . '=' . cleanBraces( $v ) . PHP_EOL;
                    } else {
                        $ret = $v;
                    }
				}
			}
		}
		if( !$noTemplate ) {
            $ret .= "}}";
        }
		if (strpos($pageTitle,'[') !== false) {
			$pageTitle = parseTitle($pageTitle);
		}

		if( $pageOption == 'next_available' && $pageTitle !== false ) {
			// get highest number
			if($weHaveApi) {
				$hnr = $api->getNextAvailable( $pageTitle );
				if( $hnr['status'] !== 'error') {
					$pageTitle = $writepage . $hnr['result'];
				} else {
					return createMsg( $hnr['message'], 'error', $returnto);
				}
				//$pageTitle = $pageTitle . $api->getWikiListNumber( $pageTitle );
			} else {
				require_once( 'WSForm.api.class.php' );
				$api = new wbApi();
				if( $api->getStatus() === false ){
					return createMsg( $api->getStatus( true ), 'error', $returnto);
				}
				$res = $api->logMeIn();

				//$pageTitle = $pageTitle . $api->getWikiListNumber( $pageTitle );
				$hnr = $api->getNextAvailable( $pageTitle );
				if( $hnr['status'] !== 'error') {
					$pageTitle = $writepage . $hnr['result'];
				} else {
					return createMsg( $hnr['message'], 'error', $returnto);
				}
				//die( "New title : ". $pageTitle );
			}
			if( $pageTitle === false ) {
				return createMsg($i18n->wsMessage( 'wsform-mwcreate-wrong-title2' ), 'error', $returnto);
			}
		}

		// ranges begin
		if ( substr( strtolower( $pageOption ) ,0,6 ) === 'range:' ) {
			$range = substr( $pageOption,6 );
			$rangeCheck = explode('-', $range);

			if( !$weHaveApi) {
				require_once( 'WSForm.api.class.php' );
				$api = new wbApi();
				if( $api->getStatus() === false ){
					return createMsg( $api->getStatus( true ), 'error', $returnto);
				}
				$res = $api->logMeIn();
			}
			//echo "<pre>";
			//print_r($range);
			//die();
			if( !ctype_digit( $rangeCheck[0] ) || !ctype_digit( $rangeCheck[1] ) ) {
				return createMsg($i18n->wsMessage( 'wsform-mwoption-bad-range' ), 'error', $returnto);
			}


			//$startRange = (int)$range[0];
			//$endRange = (int)$range[1];
			$tmp  = $api->getFromRange( $title, $range );
			if( $tmp['status'] === 'error') {
				//echo $tmp['message'];
				return createMsg( $tmp['message'], 'error', $returnto);
			}
			$tmp = $tmp['result'];

			/*
			$tmp  = $api->getWikiListNumber( $pageTitle, array('start' => $startRange, 'end' => $endRange ) );
			if($tmp === false) {
				return createMsg($i18n->wsMessage('wsform-mwoption-out-of-range'), 'error', $returnto);
			}
			*/
			if( $leadByZero === true ) {
				$endrangeLength = strlen($range[1]);
				$tmp = str_pad($tmp, $endrangeLength, '0', STR_PAD_LEFT);
			}


			$pageTitle = $pageTitle . $tmp;

		}
		// ranges end


		if ( $pageOption == 'add_random' && $pageTitle !== false ) {
			$ptitle = $pageTitle . MakeTitle();
		}



		if ( $pageTitle !== false && $pageOption != "add_random" ) {
			$ptitle = $pageTitle;
		}
		if ( ! $pageTitle ) {
            return( createMsg('no title could be former [parser error]','error',$returnto) );
		}

		if( !$weHaveApi) {
			require_once( 'WSForm.api.class.php' );
			$api = new wbApi();
		}

		if( $api->app['create-seo-titles'] === true ) {

			$ptitle = $api->urlToSEO( $ptitle );
		}

		if( $mwfollow !== false ) {
			if( $mwfollow === 'true' ) {
				$returnto = $api->app['wgScript'] . '/' . $ptitle;
			} else {
				if( strpos( $returnto, '?' ) ) {
					$returnto = $returnto . '&' . $mwfollow . '=' . $title;
				} else {
					$returnto = $returnto . '?' . $mwfollow . '=' . $title;
				}
			}
		}

		if($weHaveApi) {

			$result = $api->savePageToWiki( $ptitle, $ret, $summary, $writePageSlot );
			if(isset($result['received']['error'])) {
				return createMsg($result['received']['error'],'error',$returnto);
			}
		} else {
			require_once( 'WSForm.api.class.php' );
			$api = new wbApi();

			$res=$api->logMeIn();
			if($res === false) {
				return createMsg($res);
			}
			if( $api->app['create-seo-titles'] === true ) {
				$ptitle = $api->urlToSEO( $ptitle );
			}
			$result = $api->savePageToWiki($ptitle, $ret, $summary, $writePageSlot );
			if(isset($result['received']['error'])) {
                return createMsg($result['received']['error'],'error',$returnto);
			}
			$weHaveApi = true;
		}
	}
}
if ( ! $mwedit && ! $email ) {
	if($msgOnSuccess !== false) {
        return( createMsg($msgOnSuccess,'ok',$returnto, 'success') );
	} else return( createMsg('ok','ok',$returnto) );
}
 if( $mwedit !== false ) {
	// We have edits to make to existing pages!
	$data = array();
	$t=0;
	//edit = [0]pid [1]template [2]Form field [3]Use field [4]Value [5]Slot
	foreach ( $mwedit as $edits ) {
		$edit = explode( '-^^-', $edits );
		if ( $edit[0] == '' || $edit[1] == '' || $edit[2] == '' ) {
			continue;
		}
		$pid = $edit[0];
		$data[$pid][$t]['template'] = makeSpaceFromUnderscore($edit[1]);
		if( ( strpos($edit[1],'|') !== false ) && ( strpos($edit[1],'=') !== false ) ) {
			// We need to find the template with a specific argument and value
			$line = explode('|',$data[$pid][$t]['template']);
			$info = explode('=',$line[1]);
			$data[$pid][$t]['find'] = $info[0];
			$data[$pid][$t]['val'] = $info[1];
			$data[$pid][$t]['template'] = $line[0];
		} else {
			$data[$pid][$t]['find'] = false;
			$data[$pid][$t]['val'] = false;
		}

		if ( $edit[3] != '' ) {
			$data[$pid][$t]['variable'] = $edit[3];
		} else {
			$data[$pid][$t]['variable'] = $edit[2];
		}

		if ( $edit[4] != '' ) {
			$data[$pid][$t]['value'] = $edit[4];
		} else {
			$ff = makeUnderscoreFromSpace($edit[2]);
			// Does this field exist in the current form so we can use ?
			if ( ! isset( $_POST[ $ff ] ) ) {
				$data[$pid][$t]['value'] = '';
			} else {
				// The value will be grabbed from the form
				// But first check if this is an array
				if ( is_array( $_POST[$ff] ) ) {
					$data[$pid][$t]['value'] = "";
					foreach ( $_POST[$ff] as $multiple ) {
						$data[$pid][$t]['value'] .= $multiple . ',';
					}
					$data[$pid][$t]['value'] = rtrim(
						$data[$pid][$t]['value'],
						','
					);
				} else { // it is not an array.
					$data[$pid][$t]['value'] = $_POST[$ff];
				}
			}
		}
		if ( $edit[5] != '' ) {
			$data[$pid][$t]['slot'] = $edit[5];
		} else {
			$data[$pid][$t]['slot'] = false;
		}

		$t++;
	}
	// We have all the info in the data Array
	// Now we need to grab the page and replace what needs to be replaced.

	if(!$weHaveApi) {
		//echo "logging in";
		require_once( 'WSForm.api.class.php' );
		$api = new wbApi();
		if( $api->getStatus() === false ){
			return createMsg( $api->getStatus( true ), 'error', $returnto);
		}
		$api->logMeIn();


		$weHaveAPI = true;
	}
	$pageContents = array();
	foreach ($data as $pid => $edits) {
		//setup slots if needed
		$wehaveslots = false;
		foreach( $edits as $edit ) {
			if( $edit['slot'] !== false ) {
				$wehaveslots = true;
				$pageTitle = $edit['title'];

				$content = $api->getWikiPage( $pid, $edit['slot'] );
				if( $content['content'] == '' ) {
					$pageContents[ $edit['slot']['content'] ] = false;
				} else {
					$pageContents[ $edit['slot'] ] = $content['content'];
				}

				$pageTitle = $content['title'];
			}
		}
		//print_r( "We have " . count( $pageContents ) . " pagecontents and we have " .count( $edits ). " edits" );
		if( !$wehaveslots ) {
			$pageContents[ 'main' ] = $api->getWikiPage( $pid );
			$pageTitle = $pageContents['main']['title'];
			$pageContents['main'] = $pageContents['main']['content'];

		}
		//die();
		//$editSlot = $edits[0]['slot'];
		//$pageContent = $api->getWikiPage( $pid, $editSlot );
		$usedVariables = array();
		foreach ( $edits as $edit ) {
			$slotToEdit = $edit['slot'];
			if( $slotToEdit === false ){
				$slotToEdit = 'main';
			}

			if($edit['find'] !== false) {
				$templateContent = $api->getTemplate( $pageContents[$slotToEdit], $edit['template'], $edit['find'], $edit['val'] );
				if($templateContent===false) {
					$result['received']['error'][] = 'Template: '.$edit['template'].' where variable:'.$edit['find'] . '='.$edit['val'].' not found';
				}
			} else {
				$templateContent = $api->getTemplate( $pageContents[$slotToEdit], $edit['template'] );
			}
			if ($templateContent === false) {
				//echo 'skipping ' . $edit['template'] ;
				continue;
			}

			$expl = pregExplode($templateContent);
			foreach ($expl as $k=>$line) {
				$tmp = explode('=',$line);
				if( trim( $tmp[0]) == $edit['variable'] ) {
					$expl[$k] = $edit['variable'].'='.$edit['value'];
					$usedVariables[]=$edit['variable'];
				}
			}
			if(!in_array($edit['variable'],$usedVariables)) {
				$ttemp = $edit['variable'];
				$expl[]=$edit['variable'].'='.$edit['value'];
			}


			$newTemplateContent = '';
			$cnt = count( $expl );
			$t = 0;
			foreach ($expl as $line) {

				if(strlen($line) > 1) {
					$newTemplateContent .= "\n" . '|' . trim($line) ;
				}
				// Is it the last one. Then {5041} put end template }} on a new line
				if( $t === ($cnt-1) ){
					$newTemplateContent .= "\n";
				}
				$t++;

			}
			$pageContents[$slotToEdit] = str_replace($templateContent,$newTemplateContent, $pageContents[$slotToEdit] );

		}
		//echo "<pre>";
		//print_r( $pageContents );
		//echo "</pre>";
		foreach( $pageContents as $slotName => $slotContents ) {
			if( $slotName === 'main' ) $slotname = false;
			$result = $api->savePageToWiki($pageTitle, $slotContents, $summary, $slotName );
			if(isset($result['received']['error'])) {
				return createMsg($result['received']['error'],'error',$returnto);
			}
		}

		//$result = $api->savePageToWiki($pageTitle, $pageContents[$slotToEdit], $summary, $slotToEdit );
		//if(isset($result['received']['error'])) {
        //    return createMsg($result['received']['error'],'error',$returnto);
		//}
	}

	}  // end mwedit
	if($email === "yes" ) {
		$to = getPostString('mwmailto');
		$from = getPostString('mwmailfrom');
		$subject = getPostString('mwmailsubject');
		$content = getPostString('mwmailcontent');
		$cc = getPostString('mwmailcc');
		$bcc = getPostString('mwmailbcc');
		$replyto = getPostString('mwmailreplyto');
		$header = getPostString('mwmailheader');
		$footer = getPostString('mwmailfooter');
		$mtemplate = getPostString('mwmailtemplate');
		$mjob = getPostString('mwmailjob');
		$html = getPostString('mwmailhtml');
        $attachment = getPostString('mwmailattachment');


        if(!$weHaveApi) {
            require_once( 'WSForm.api.class.php' );
            $api = new wbApi();
	        if( $api->getStatus() === false ){
		        return createMsg( $api->getStatus( true ), 'error', $returnto);
	        }
            $res = $api->logMeIn();
            if($res === false) {
                return createMsg($res);
            }
            $weHaveAPI = true;
        }

        if($mtemplate) {

            if( $parseLast === false ) {
                $tpl = $api->parseWikiPageByTitle( $mtemplate );
            } else {
                $tpl = $api->getWikiPageByTitle( $mtemplate );
            }
            if( $tpl == "" ) {
                return createMsg('WSFORM :: Can not find template','error',$returnto);
            }
            //Get all form elements and replace in Template
            foreach ($_POST as $k=>$v) {
                if ( !isWSFormSystemField($k) ) {
                    if( is_array( $v ) ) {
                        $tmpArray = implode(", ", cleanBraces( $v ) );
                        $tpl = str_replace('$' . $k, $tmpArray, $tpl);
                    } else {
                        $tpl = str_replace('$' . $k, cleanBraces( $v ), $tpl);
                    }
                }
            }
            $tpl = preg_replace('/\$([\S]+)/', '', $tpl);
            if( $parseLast !== false ) {
                $tpl = $api->parseWikiText( $tpl );
            }
            $tmp = $api->getTemplateValueAndDelete('to', $tpl );
            if( $to === false ) {
                $to = $tmp['val'];
                $tpl = $tmp['tpl'];
            }
            $tmp = $api->getTemplateValueAndDelete('from', $tpl );
            if( $from === false ) {
                $from = $tmp['val'];
                $tpl = $tmp['tpl'];
            }
            $tmp = $api->getTemplateValueAndDelete( 'subject', $tpl );
            if( $subject === false ) {
                $subject = $tmp['val'];
                $tpl = $tmp['tpl'];
            }
            $tmp = $api->getTemplateValueAndDelete('cc', $tpl );
            if( $cc === false ) {
                $cc = $tmp['val'];
                $tpl = $tmp['tpl'];
            }
            $tmp = $api->getTemplateValueAndDelete('bcc', $tpl );
            if( $bcc === false ) {
                $bcc = $tmp['val'];
                $tpl = $tmp['tpl'];
            }
	        $tmp = $api->getTemplateValueAndDelete('replyto', $tpl );
	        if( $replyto === false ) {
		        $replyto = $tmp['val'];
		        $tpl = $tmp['tpl'];
	        }
            $tmp = $api->getTemplateValueAndDelete('header', $tpl );
            if( $header === false ) {
                $header = $tmp['val'];
                $tpl = $tmp['tpl'];
            }
            $tmp = $api->getTemplateValueAndDelete('footer', $tpl );
            if( $footer === false ) {
                $footer = $tmp['val'];
                $tpl = $tmp['tpl'];
            }

            $content = base64_encode( $tpl );
        }

        if( $html ===  false || $html === 'yes' ) {
            $html = true;
        } else $html = false;


        if( strpos( $to, 'user:') ) {
            $to  = str_replace( 'user:', '', $to );
        }


		// No Job and no TO
		if($mjob === false && $to === false ) {
            return createMsg('Cannot send message. No TO.','error',$returnto);
		}
		// No Job and No From
		if($mjob === false && $from === false) {
	        return createMsg('Cannot send message. No FROM.','error',$returnto);
		}
		// No Job and no Subject
		if($mjob === false && $subject === false) {
            return createMsg('Cannot send message. No SUBJECT.','error',$returnto);
		}
		// No Job, no Content AND no Template
		if($mjob === false && $content === false && $mtemplate === false) {
			return createMsg('Cannot send message. No CONTENT.','error',$returnto);
		}
		// We have content
		if($content !== false) {
			$content = '<div class="wsform-mail-content">'.base64_decode( $content ).'</div>';
		}



		// Get the header
		if($header !== false) {
			$header_content = $api->parseWikiPageByTitle($header);

			$content = $header_content.$content;
		}
		// Get the Footer
		if($footer !== false) {
			$footer_content = $api->parseWikiPageByTitle($footer);
			$content = $content.$footer_content;
		}
        //error_reporting( -1 );
        //ini_set( 'display_errors', 1 );
		// no Job and no Template, but we do have what we need from previous IF statements
		if( $mjob === false ) {
            //echo $content;
            //die();
			$result = sendmail($from, $to, $cc, $bcc, $replyto, $subject, $content, $html, $attachment) ;
			if( $result === true ) {
			    if( $msgOnSuccess === false ) {
                    return createMsg('Mail sent successfully', 'ok', $returnto, 'success');
                } else return( createMsg($msgOnSuccess,'ok',$returnto, 'success') );
			} else {
                return createMsg($result,'error',$returnto);
			}
		}
		// Job handling
		if( $mjob !== false ) {
			$job = $api->getWikiPageByTitle($mjob);
			//echo $job;
			if( $job == "" ) {
                return createMsg('WSFORM :: Can not find job','error',$returnto);
			}
		}
		$job = json_decode($job,true);
		if(!$job) {
            return createMsg('WSFORM :: Job is invalid json','error',$returnto);
		}
		$errors = "";
		foreach ( $job['jobs'] as $task ) {
			$data = createJob($task, $api);
			if ($data['status'] != 'ok') {
				$errors .= '<p>WSForm Job error : '.$data['message'].'</p>';
			} else {
				$result = sendMail(
					$data['data']['from'],
					$data['data']['to'],
					$data['data']['cc'],
					$data['data']['bcc'],
					$data['data']['replyto'],
					$data['data']['subject'],
					$data['data']['body'],
					$data['data']['html']
				);

				if(!$result) {
					$errors .= '<p>WSForm Job Mail error :: '.$result.'</p>';
				}
			}
		}
		if($errors != "") {
            return createMsg($errors,'error',$returnto);
		} elseif( $msgOnSuccess === false ) {
            return createMsg('Mailjob sent successfully','ok',$returnto,'success');
		}
	} elseif( $email === "get" ) {
		return createGet();
	}
    if($msgOnSuccess !== false) {
        return( createMsg($msgOnSuccess,'ok',$returnto, 'success') );
    } else return( createMsg('ok','ok',$returnto) );


}

/**
 * Function to create submitted postfields to pass on to WSForm extensions
 *
 * @return mixed
 */
function setWsPostFields() {
	foreach($_POST as $k=>$v) {
		if ( isWSFormSystemField( $k ) ) {
			unset( $_POST[$k] );
		}
	}
	$wsPostFields = $_POST;
	unset($_POST);
	return $wsPostFields;
}

/*
function checkEmailForName( $email ) {
    preg_match('#\[(.*?)\]#', $email, $match);
    $ret = array();
    $ret['name'] = false;
    if( !empty( $match ) ) {
        $ret['name'] = trim( str_replace( $match[0], '', $email ) );
        $ret['email'] = $match[1];
    } else {
        $ret['name'] = false;
        $ret['email'] = $email;
    }
    return $ret;
}
*/

function createEmailArray( $email, $mail ) {
	$tmp =  str_replace( array('[',']'),array('<','>'), $email );
	return $mail->parseAddresses( $tmp );
}


/**
 * Actual email send function
 *
 * @param $from string
 * @param $to string
 * @param $cc string
 * @param $bcc string
 * @param $subject
 * @param $body string
 * @param bool $html Send as html true or false
 * @return bool true when succeeded
 * @return string error message
 */
function sendMail($from, $to, $cc, $bcc, $replyto, $subject, $body, $html=true, $attachment=false ) {
    if(file_exists('modules/pm/src/Exception.php')) {
        require_once ('modules/pm/src/Exception.php');
    } else die('NO PM');
    if(file_exists('modules/pm/src/PHPMailer.php')) {
        require_once ('modules/pm/src/PHPMailer.php');
    } else die('NO PM');
	$api = new wbApi();
	$useSMTP = false;
	if( strtolower( $api->app['use-smtp'] ) === "yes" ) {
		$useSMTP = true;
	}



	$mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $to = createEmailArray( $to, $mail );
    $from = createEmailArray( $from, $mail );
	$replyto = createEmailArray( $replyto, $mail );
    if( $cc ) {
        $cc = createEmailArray($cc, $mail);
    }
    if( $bcc ) {
        $bcc = createEmailArray($bcc, $mail);
    }
	if( $useSMTP ) {
		if(file_exists('modules/pm/src/SMTP.php')) {
			require_once( 'modules/pm/src/SMTP.php' ); // Needed when doing SMTP
		}
	}
	//require_once ('modules/pm/src/SMTP.php');  Needed when doing SMTP
	//$mail = new PHPMailer\PHPMailer\PHPMailer(true);
	try {
		//$to = $mail->parseAddresses(str_replace( array('[',']'),array('<','>'), $to ) );
		//print_r($to);
		//die();
		//Server settings
		if( $useSMTP ) {
			$mail->isSMTP();
			$mail->Host = $api->app['smtp-host'];                 // Specify main and backup SMTP servers
			$mail->SMTPAuth = $api->app['smtp-authentication'];   // Enable SMTP authentication
			$mail->Username = $api->app['smtp-username'];         // SMTP username
			$mail->Password = $api->app['smtp-password'];         // SMTP password
			$mail->SMTPSecure = $api->app['smtp-secure'];         // Enable TLS encryption, `ssl` also accepted
			$mail->Port = $api->app['smtp-port'];
		} else {
			$mail->isMail();
		}
        $mail->CharSet = 'UTF-8';
        //$mail->SMTPDebug = 2;                                 // Enable verbose debug output

		/*
        if( $from['name'] === false ) {
            $mail->setFrom( $from['email'] );
        } else {
            $mail->setFrom( $from['email'], $from['name'] );
        }
		*/
		foreach( $from as $single ) {
			$mail->setFrom( $single['address'], $single['name'] );     // Add a recipient
		}
        /*
        if( $to['name'] === false ) {
            $mail->addAddress( $to['email'] );     // Add a recipient
        } else {
            $mail->addAddress( $to['email'], $to['name'] );     // Add a recipient
        }
        */

        foreach( $to as $single ) {
	        $mail->addAddress( $single['address'], $single['name'] );     // Add a recipient
        }
        if( $cc !== false ) {
	        foreach ( $cc as $single ) {
		        $mail->addCC( $single['address'], $single['name'] );     // Add a cc
	        }
        }
		if( $bcc !== false ) {
			foreach ( $bcc as $single ) {
				$mail->addBCC( $single['address'], $single['name'] );     // Add a bcc
			}
		}
		if( $replyto !== false ) {
			foreach( $replyto as $single ) {
				$mail->addReplyTo( $single['address'], $single['name'] );     // Add a reply to
			}
		}
		/*
        if( $cc ) {
            if( $cc['name'] === false ) {
                $mail->addCC( $cc['email'] );
            } else {
                $mail->addCC( $cc['email'], $cc['name'] );
            }
        }

        if($bcc) {
            if( $bcc['name'] === false ) {
                $mail->addBCC( $bcc['email'] );
            } else {
                $mail->addBCC( $bcc['email'], $bcc['name'] );
            }
        }
		*/

        $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https:' : 'http:';
        if( $attachment !== false ) {

	        if( strpos( $attachment, 'http' ) === false ) {
		        $fileAttachedContent = file_get_contents( $protocol . $attachment );
	        } else {
		        $fileAttachedContent = file_get_contents( $attachment );
	        }

        } else $fileAttachedContent = false;
        if( $fileAttachedContent !== false ) {
            $pInfo = pathinfo( $attachment );
            $fileAttachedName = $pInfo['basename'];
        }
		$mail->isHTML($html);
		$mail->Subject=$subject;
		$mail->Body=$body;
        if( $fileAttachedContent !== false ) {
            $mail->addStringAttachment($fileAttachedContent, $fileAttachedName );
        }
		$mail->send();
		return true;


	} catch (Exception $e) {
		return 'Email could not be sent, Mailer error : '. $mail->ErrorInfo ;
	}

}

/**
 * Function that calls the MediaWiki API to parse Wikitext
 *
 * @return array Parsed wiki text with status
 */
function renderWiki() {
    if( isset( $_POST['wikitxt'] ) && $_POST['wikitxt'] != '' ) {
        require_once( 'WSForm.api.class.php' );
        $api = new wbApi();
	    if( $api->getStatus() === false ){
		    return createMsg( $api->getStatus( true ) );
	    }
        $api->logMeIn();
        $ret = array();
        $result= $api->parseWikiText($_POST['wikitxt']);
        if( $result !== "" ) {
            $ret['status'] = 'ok';
            $ret['result'] = $result;
        } else {
            $ret['status'] = 'error';
            $ret['error'] = 'No Wikitext to parse or something went wrong parsing';
        }
        return $ret;
    }
}

