# <img alt="FlexForm" width="200" src="FlexForm-logo.png">

FlexForm is an enhanced HTML5 rendering engine.

It renders HTML5 form elements and allows to edit or create a page or multiple pages with one form.

This version 1 is a complete rewrite and stripped down version from the previous FlexForm.

Compared to the previous version File upload and E-mail are not supported and will be added in a later version.
Rendering of a form has been rewritten to support themes.

Documentation will be added soon.

## Installation

Grab in instance from https://github.com/WikibaseSolutions/FlexForm. Create a "FlexForm" folder in your Wiki extensions
folder and extract the files there.

---

## Setup

You can tweak FlexForm to an extent in your Localsettings.php

```php
$wgFlexFormConfig['secure']                                 = true; //( default is true ). Will render form that make no sense when inspected in the browser
$wgFlexFormConfig['sec_key']                                = ""; // A salt key for encryption. Used together with "secure" option. Must be set when using multiple instances of a wiki
$wgFlexFormConfig['auto_save_interval']                     = 30000; // defaults to 3 minutes.
$wgFlexFormConfig['auto_save_after_change']                 = 3000; // defaults to 3 seconds after last change
$wgFlexFormConfig['FlexFormDefaultTheme']                   = "Plain"; // Currently the only form
$wgFlexFormConfig['rc_site_key']                            = ""; // reCaptcha site key
$wgFlexFormConfig['rc_secret_key']                          = ""; // reCaptcha secret key
$wgFlexFormConfig['file_temp_path']                         = ""; // When using image upload conversion, we need a place to temporarily store images.
$wgFlexFormConfig['can_create_user']                        = false; // If FlexForm is allowed to create new users
$wgFlexFormConfig['filter_input_tags']                      = false; // Defaults to false. Will filter all parser arguments to plain text, except value parameters. Will also disallow onClick and onFocus parameter. This feature will most likely be removed in future updates.
$wgFlexFormConfig['CreateAndEditForms']['allowedGroups']    = ["sysop","moderator"]; // Defaults to sysop. Only a user in the allowedGroups is able to edit pages with a FlexForm in the source.
$wgFlexFormConfig['CreateAndEditForms']['hideEdit']         = true; // Defaults to true. If a user is not in the allowedGroups then hide edit and editsource menu items for any page containing a FlexForm form.
$wgFlexFormConfig['auto_save_btn_on']                       = "Autosave On";
$wgFlexFormConfig['auto_save_btn_off']                      = "Autosave Off";
$wgFlexFormConfig['use_smtp']                               = false; // when sending email, should we use separate smtp ?
$wgFlexFormConfig['smtp_host']                              = "";
$wgFlexFormConfig['smtp_authentication']                    = true;
$wgFlexFormConfig['smtp_username']                          = "";
$wgFlexFormConfig['smtp_password']                          = "";
$wgFlexFormConfig['smtp_secure']                            = "TLS";
$wgFlexFormConfig['smtp_port']                              = 587;
```

---

Finally add the following line at the end of your LocalSettings.php to enable the extension :

```php
wfLoadExtension( 'FlexForm' );
```

FlexForm has a notification system build in. This is used to show possible errors or success / custom messages.

To enable this.. add to your header page :

```html

<_form showmessages/>
```

## Docs

Visit : https://www.open-csp.org/DevOps:Doc/FlexForm

### Changelog

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
