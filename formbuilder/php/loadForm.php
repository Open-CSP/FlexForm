<?php
$path = __DIR__."/../files/";
$diropen = opendir($path);
if ( isset($_POST['filename']) ) {
    $filename = $_POST['filename'];
    foreach ( glob($path."*") as $file ) {
        $pathinfo = pathinfo($file);
        if ( $filename === $pathinfo['basename'] ) {
            $myfile = fopen($path.$filename, "r");
            $readMyfile = fread($myfile, filesize($path.$filename));
            $json = json_decode($readMyfile, true);

            echo $readMyfile ;

            fclose($myfile);
        }
    }
}
?>