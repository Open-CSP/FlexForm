<?php
if ( isset($_POST['content']) ) {
    $content = $_POST['content'];
}
if ( isset($_POST['formName']) ) {
    $formName = $_POST['formName'];
}
if ( isset($_POST['formElement']) ) {
    $formElement = $_POST['formElement'];
}

$json = array();
$json['content'] = $content;
$json['filename'] = $formName;
$json['formElement'] = $formElement;

chdir("../");
$path = __DIR__."/../files/";

/*$json = array();
$formName = $_POST['formName'];
$content = $_POST['content'];

$json['content']['html'] = $content;
$json['content']['filename'] = $formName;*/

$submitted = false;
foreach( glob($path.'*.*') as $filename ){
    $elements = pathinfo($filename);
    if ( $formName === $elements['basename'] ) {
        $submitted = true;
        $myfile = file_get_contents($path.$formName);
        $readMyfile = json_decode($myfile, true);

        fclose($myfile);
    }
}
chdir('../../../');
include ('includes/WebStart.php');
if($wgUser->isLoggedIn()) {
    //echo $wgUser->getName();
    if ( $submitted === false ) {
        $json['submittedBy'] = $wgUser->getName();
        $json['submitDate'] = date("Y-m-d");
    }
    else {
        $json['lastModifiedBy'] = $wgUser->getName();
        $json['submittedBy'] = $readMyfile['submittedBy'];
        $json['submitDate'] = $readMyfile['submitDate'];
        $json['lastModifiedDate'] = date('Y-m-d');
    }
} else {

}

$file = fopen($path.$formName, "w");
$data = json_encode($json, JSON_PRETTY_PRINT);
//die($data);

fwrite($file, $data);
//echo $file;
//fwrite($file, json_encode($json, PRETTY_PRINT));
fclose($file);

?>