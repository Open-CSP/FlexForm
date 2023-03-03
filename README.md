# <img alt="FlexForm" width="200" src="FlexForm-logo.png">

FlexForm is an enhanced HTML5 rendering engine.

It renders HTML5 form elements and allows to edit or create a page or multiple pages with one form.

Version 2 has changes in its config. So upgrading to version 2 means adjusting your local settings.

Version 1 was a complete rewrite and stripped down version from the previous WSForm.

## Installation

Grab in instance from https://github.com/WikibaseSolutions/FlexForm. Create a "FlexForm" folder in your Wiki extensions
folder and extract the files there.

Or install using Composer. Read more here: https://www.mediawiki.org/wiki/Composer/For_extensions

The Composer required name is: wikibase-solutions/flex-form

```
composer require wikibase-solutions/flex-form
```
---

## Setup

You can tweak FlexForm to an extent in your Localsettings.php

```php
$wgFlexFormConfig['secure']                                        = true; //( default is true ). Will render form that make no sense when inspected in the browser
$wgFlexFormConfig['sec_key']                                       = ""; // A salt key for encryption. Used together with "secure" option. Must be set when using multiple instances of a wiki
$wgFlexFormConfig['auto_save_interval']                            = 30000; // defaults to 3 minutes.
$wgFlexFormConfig['auto_save_after_change']                        = 3000; // defaults to 3 seconds after last change
$wgFlexFormConfig['FlexFormDefaultTheme']                          = "Plain"; // Currently the only form
$wgFlexFormConfig['rc_site_key']                                   = ""; // reCaptcha site key
$wgFlexFormConfig['rc_secret_key']                                 = ""; // reCaptcha secret key
$wgFlexFormConfig['file_temp_path']                                = ""; // When using image upload conversion, we need a place to temporarily store images.
$wgFlexFormConfig['can_create_user']                               = false; // If FlexForm is allowed to create new users
$wgFlexFormConfig['filter_input_tags']                             = false; // Defaults to false. Will filter all parser arguments to plain text, except value parameters. Will also disallow onClick and onFocus parameter. This feature will most likely be removed in future updates.
$wgFlexFormConfig['allowedGroups']                                 = ["sysop","moderator"]; // Defaults to sysop. Only a user in the allowedGroups is able to edit pages with a FlexForm in the source.
$wgFlexFormConfig['renderonlyapprovedforms']                       = true; // Defaults to true. When a user in the allowedGroups creates a form it will become valid and will be rendered. Someone not in the allowedGroups can create a form and save it, but it will never be rendered until a user from the allowedGroups will edit and re-save the page. Only then will a form become valid. The message "FORM CANNOT BE RENDERED, NOT VALIDATED" will be shown instead of the form when it is invalid.
$wgFlexFormConfig['renderi18nErrorInsteadofImageForApprovedForms'] = false; // When a form is invalid, an invalid image will be rendered instead of the form. Set to true to render i18n invalid message.
$wgFlexFormConfig['userscaneditallpages']                          = false; // Defaults to false. This differs from FlexForm before 2.0. FlexForm will now honor the UserCan functions in MediaWiki. If a form edits or creates a page a user has no rights to, the form will fail.
$wgFlexFormConfig['hideEdit']                                      = true; // Defaults to true. If a user is not in the allowedGroups then hide edit and editsource menu items for any page containing a FlexForm form.
$wgFlexFormConfig['create-seo-titles']                             = true; // Defaults to false. Will filter any user input on creating a new page to be SEO friendly.
$wgFlexFormConfig['auto_save_btn_on']                              = "Autosave On";
$wgFlexFormConfig['auto_save_btn_off']                             = "Autosave Off";
$wgFlexFormConfig['use_smtp']                                      = false; // when sending email, should we use separate smtp ?
$wgFlexFormConfig['smtp_host']                                     = "";
$wgFlexFormConfig['smtp_authentication']                           = true;
$wgFlexFormConfig['smtp_username']                                 = "";
$wgFlexFormConfig['smtp_password']                                 = "";
$wgFlexFormConfig['smtp_secure']                                   = "TLS";
$wgFlexFormConfig['smtp_port']                                     = 587;
```

---

Add the following line at the end of your LocalSettings.php to enable the extension :

```php
wfLoadExtension( 'FlexForm' );
```

---

Run the [update script](https://www.mediawiki.org/wiki/Manual:Update.php) which will automatically create the necessary database tables that this extension needs.

Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

---
FlexForm has a notification system build in. This is used to show possible errors or success / custom messages.

To enable this.. add to your header page :

```html

<form showmessages />
```

==== Migrate from version 1.x to 2.0 ====
Please notice the changes in the config settings.
Also, by default, the setting renderonlyapprovedforms will be true. Meaning that once you install FlexForm v2.0 all your 
existing FlexForm forms in your wiki will be shown as unvalidated. 
Visit this documentation page https://www.open-csp.org/DevOps:Doc/FlexForm/2.0/Validated_Forms to read how to solve this easily.

## Docs

Visit : https://www.open-csp.org/DevOps:Doc/FlexForm

### Changelog

* 2.0.12 : Use wfExpandUrl to accommodate for non-null ArticlePath settings
* 2.0.11 : Added loadscript config setting
* 2.0.10 : TinyMCE selector change.
* 2.0.9 : Added parsing of options and selected to Select and Token
* 2.0.8 : new way of rendering select and tokens without options
* 2.0.7 : fixed HTML argument custom
* 2.0.6 : autosave || to &&, Added autosave="none", see docs.
* 2.0.4 : Missing sortable on tokens
* 2.0.3 : Edit on page id 0 fix
* 2.0.2 : Minor tweaks to autosave buttons and the placing
* 2.0.0 : Added approved forms, -usercan- options and code optimization, wgCapitalLinks and many more
* 1.1.45 : Split wiki edit and create
* 1.1.44 : JSON Support for instances. Fixed nooverwrite on create page option.
* 1.1.43 : JSON Edit support. Dropped jQuery.UI dependency
* 1.1.42 : Instances and multiple _create json support
* 1.1.41 : Add copy and paste support form formats. _create json support finished. Tempex and Calc secure and with instances. reCaptcha changes
* 1.1.40 : _create json support
* 1.1.39 : Localhost redirect fix
* 1.1.38 : API next available warning removed
* 1.1.37 : Version bumb
* 1.1.36 : Secure calc added and resolve template fields
* 1.1.35 : Calc options added. Added Fix for wikis with different paths and urls
* 1.1.34 : file upload dropzone verbose fixes
* 1.1.33 : Added template support for file pages
* 1.1.32 : Seperated Git from Special page
* 1.1.31 : Version bump to git update
* 1.1.28 : autosave onintervalafterchange. Added admin git update feature.
* 1.1.27 : Survey module added. More on this later. Filter option for SMQ Queries added. noseo option for _create
* 1.1.26 : Rendering instances with default content will do a SMW Ask to get the Display property for a token using its value and Query.
* 1.1.25 : Instance default-content 2 token fix
* 1.1.24 : Fixed an issue where select2 tokens callbacks were initiated multiple times. Removed 1.12 J-UI dependency.
* 1.1.23 : Another instance update for tokens. Recent changes are now initiated.
* 1.1.22 : Instances and textarea fix
* 1.1.21 : Fixed Paragraph tag appearing in fieldset
* 1.1.20 : Fixed file upload preview and dropzone issues
* 1.1.19 : Added wscreate usefield like options. Fixed SMW query results being escaped
* 1.1.17 : Fixed autosave with Instances
* 1.1.16 : VE error message fix when nog VE loaded.
* 1.1.15 : Extended the hook to contain extension name.
* 1.1.14 : Create user email is now a system message. Added FFAfterFormHandling Hook.
* 1.1.13 : Added extension support
* 1.1.12 : Added entity decoding to mwreturn to support &-sign. Fixed multiple instances issue.
* 1.1.11 : SEO url's to file upload
* 1.1.10 : Added sortable tokens
* 1.1.9 : Addendum and SEO setting
* 1.1.8 : FileUpload fix
* 1.1.7 : Add HTML screenshot upload (canvas)
* 1.1.6 : reCaptcha fixed. Rdy to test. https://github.com/WikibaseSolutions/FlexForm/issues/8
* 1.1.5 : Fixed slot creation bug
* 1.1.4 : Instances changes
* 1.1.3 : Added frame parsing for tokens. Form validation was set to input field validations.. Fixed!
* 1.1.2 : Fixed no submit on enter per form. Security checksum changes.
* 1.1.1 : Added support for anonymous users
* 1.1.0 : Email bot api support, create user support, various fixes after refactoring. Added security options.
* 1.0.0 : Release
* 1.0.0 Release Candidate 6 : CreateUser, extensions support
* 1.0.0 Release Candidate 5 : leadingZero With Multiple Creates
* 1.0.0 Release Candidate 4 : id regex validation changed to allow just HTML5
* 1.0.0 Release Candidate 3 : File upload naming convention changed
* 1.0.0 Release Candidate 2 : smwquery result fix
* 1.0.0 Release Candidate 1 : id fixes on create
* 1.0.0 Beta 10: instances and security changes
* 1.0.0 Beta 8: resource loading changed in regards to working with slots.
* 1.0.0 Beta 3: More clean-up render Select fixed and show-on-select fix.
* 1.0.0 Beta 2: More clean-up and added GET action. Added hidden css class.
* 1.0.0 Beta 1: Initial first public release
