<?php

/*
 * You have a $IP and $serverName (similar to $wgWiki) available here
 *
 * user-api-user-only is a new feature as off 0.8.0.5.0. Default is set to yes (old WSForm behaviour)
 * If set to "no", WSForm will create/edit/upload under the current logged in user, instead of the WSForm user
 * described below. If set to yes (leaving it empty also means yes) or no,
 * WSForm still need an API user for other tasks, so the next step is mandatory.
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
 * api-url-option. We have a website and based on an url, cookie or language choice it changes database.
 * All these three options set a variable. It can also be set with a parameter in the url.
 * For the API to know what database to use, we use that url parameter. You can add this parameter and its value
 * to this config option : api-url-action.
 * Example : ?WSLanguage=' . $wikiID ( where $wikiID is a set variable we get from the cookie set ).
 *
 * api-cookie-path will default to the tmp folder of the server. With some hosting providers
 * you might need to change that location. Here's where you can change the path. When empty
 * it defaults to /tmp/CURLCOOKIE
 *
 * wgAbsoluteWikiPath is used when in a farm. Needs to be the path to the current wiki
 * There it will look for WSFormSettings.php to read the username and password for that wiki user
 * Example wgAbsoluteWikiPath :  $IP . '/wikis/' . $serverName
 *
 * By default use-smtp is set to no, meaning it will use PHP Mail() functions. When set to yes, make sure
 * you fill in all the other fields needed for SMTP. If you are in a Farm, please use WSFormSettings.php to setup
 * SMTP on a farm.
 *
 * wgScript default to '/index.php' and is used for following a new created page. If your main wiki instalment is
 * somewhere else, then set it here
 *
 * If you want to use the Google Recaptcha, set rc_site_key and rc_secret_key. You receive this from Google when
 * you sign-up
 *
 * sec is work in progress and means security heavy. Will also filter javascript and such.
 * sec-key is a general seed key for securing forms. Needs to be set if run on multiple instances
 * environment like AWS
 *
 * autosave-interval
 * When using autosave and autosave is set to auto or oninterval. This will be the interval time in milliseconds (30000 is 30 seconds) the form will be saved.
 *
 * autosave-after-change
 * When using autosave and autosave is set to auto or onchange. This will be the time in milliseconds (3000 is 3 seconds) the form will be saved after the last change.
 *
 * autosave-btn-on
 * When using autosave and autosave is set to auto or oninterval. This will be the text on the button above the form when interval is on.
 *
 * autosave-btn-off
 * When using autosave and autosave is set to auto or oninterval. This will be the text on the button above the form when interval is off.
 *
 * use-formbuilder
 * Defaults to true and shows the link to Formbuilder in the menus. Hide the link by changing the setting to false;
 *
 * allow-edit-docs
 * If people with access to the documentation are allowed to edit them, defaults to false. Set to true to enable edit. Warning: When WSForm is updated by the administrator, all documentation that comes with WSForm will overwrite local made edits. Only custom new documentation will remain.
 *
 * allow-special-page-setup
 * When set to true, the index.php/Special:WSForm/Setup allows for editing the config file from the Special page. Set to false to disable.
 *
 *
 *
 */

$config = array(
	"use-api-user-only"        => 'yes',
	"is-bot"                   => false,
	"api-username"             => '',
	"api-password"             => '',
	"api-url-overrule"         => '',
	"api-url-option"           => '',	// or false
	"api-cookie-path"          => '',
	"wgAbsoluteWikiPath"       => '',
	"wgScript"                 => '/index.php',
	"rc_site_key"              => '',
	"rc_secret_key"            => '',
	"use-smtp"                 => 'no',
	"sec"                      => false,
	"sec-key"                  => '',
	'autosave-interval'        => 30000,
	'autosave-after-change'    => 3000,
	'autosave-btn-on'          => 'Autosave is on',
	'autosave-btn-off'         => 'Autosave is off',
	"smtp-host"                => '',
	"smtp-authentication"      => true,
	"smtp-username"            => '',
	"smtp-password"            => '',
	"smtp-secure"              => 'TLS',
	"smtp-port"                => '587',
	'use-formbuilder'          => true,
	'allow-edit-docs'          => true,
	'form-timeout-limit'       => 7200,
	'allow-special-page-setup' => true
);