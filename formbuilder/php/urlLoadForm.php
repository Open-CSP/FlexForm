<?php

if ( isset($_POST['url']) ) {
    $myfile = file_get_contents($_POST['url']);
    $readMyfile = json_decode($myfile, true);

    echo $myfile;

    fclose($myfile);
}