<?php
$path = __DIR__ . "/../files/";
$diropen = opendir($path);

if ( isset($_POST['filename']) && $_POST['filename'] != '' ) {
    $file_name = $_POST['filename'];
} else die();

$fh = fopen("../files/$file_name", "r");
fclose($fh);
$myfile = unlink("../files/$file_name");
echo $myfile;
?>

