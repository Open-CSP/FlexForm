<?php
$path = __DIR__."/../files/";
$diropen = opendir($path);

$json = array();
$count = 0;
foreach ( glob($path."*") as $file ) {
    $pathinfo = pathinfo($file);
    $filejson = array();
    $filejson['pathinfo']['dirname'] = $pathinfo['dirname'];
    $filejson['pathinfo']['basename'] = $pathinfo['basename'];
    $filejson['pathinfo']['extension'] = $pathinfo['extension'];
    $filejson['pathinfo']['filename'] = $pathinfo['filename'];
    $filejson['pathinfo']['path'] = $path;
    $filejson['filename'] = $pathinfo['filename'];
    $filejson['pathinfo']['file'] = $file;
    $filejson['pathinfo']['realfile'] = realpath($file);
    $filejson['server'] = $_SERVER['SERVER_NAME'];
    $myfile = file_get_contents($path.$pathinfo['basename']);
    $readMyfile = json_decode($myfile, true);

    if ( isset($readMyfile['lastModifiedBy']) ) {
        $filejson['lastModifiedBy'] = $readMyfile['lastModifiedBy'];
        $filejson['lastModifiedDate'] = $readMyfile['lastModifiedDate'];
    } else {
        $filejson['lastModifiedBy'] = $readMyfile['submittedBy'];
        $filejson['lastModifiedDate'] = $readMyfile['submitDate'];
    }

    fclose($myfile);

    $json[$count] = $filejson;
    $count++;
}

echo json_encode($json, JSON_PRETTY_PRINT);

?>