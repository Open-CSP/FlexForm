<?php

/*
 * You have a $IP and $serverName (similar to $wgWiki) available here
 *
 * api-username is the username you have create for WSForm in your Wiki to handle posts
 * make sure this user has right to create and edit pages as well as upload files.
 * api-password is the username password
 *
 * api-url-overrule can be used if WSForm incorrectly detects the MediaWiki api url
 * This needs to be the full url to the folder where api.php is.
 * api-url-overrule should be left empty unless WSForm cannot find the MediaWiki API
 * Example api-url-overrule : "https://mywebsite"
 *
 * wgAbsoluteWikiPath is used when in a farm. Needs to be the path to the current wiki
 * There it will look for WSFormSettings.php to read the username and password for that wiki user
 * Example wgAbsoluteWikiPath :  $IP . '/wikis/' . $serverName
 */

$config = array(
    "api-username"       => '',
    "api-password"       => '',
    "api-url-overrule"   => '',
    "wgAbsoluteWikiPath" => '',
);