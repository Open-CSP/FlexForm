
# <img alt="FlexForm" width="200" src="FlexForm-logo.png">


FlexForm is an enhanced HTML5 rendering engine.

It renders HTML5 form elements and allows to edit or create a page or multiple pages with one form.

This version 1 is a complete rewrite and stripped down version from the previous FlexForm.

Compared to the previous version File upload and E-mail are not supported and will be added in a later version. Rendering of a form has been rewritten to support themes.

Documentation will be added soon.



## Installation

Grab in instance from https://gitlab.wikibase.nl/community/mw-wsform.
Create a "FlexForm" folder in your Wiki extensions folder and extract the files there.

---

## Setup

You can tweak FlexForm to an extent in your Localsettings.php
```php
$wgFlexFormConfig['secure'] = true; //( default is true ). Will render form that make no sense when inspected in the browser
$wgFlexFormConfig['sec_key'] = ""; // A salt key for encryption. Used together with "secure" option. Must be set when using multiple instances of a wiki
$wgFlexFormConfig['auto_save_interval'] = 30000; // defaults to 3 minutes.
$wgFlexFormConfig['auto_save_after_change'] = 3000; // defaults to 3 seconds after last change
$wgFlexFormConfig['FlexFormDefaultTheme'] = "Plain"; // Currently the only form
$wgFlexFormConfig['rc_site_key'] = ""; // reCaptcha site key
$wgFlexFormConfig['rc_secret_key'] = ""; // reCaptcha secret key
$wgFlexFormConfig['file_temp_path'] = ""; // When using image upload conversion, we need a place to temporarily store images.
$wgFlexFormConfig['form_timeout_limit'] = 7200; // 7200 seconds is the default
```
---

Finally add the following line at the end of your LocalSettings.php to enable the extension :
```php
wfLoadExtension( 'FlexForm' );
```

FlexForm has a notification system build in. This is used to show possible errors or success / custom  messages.

To enable this.. add to your header page :
```html
<_form showmessages />
```

## Docs
Visit : Special:FlexForm/Docs


### Changelog
* 1.0.0 Beta 10: dropped all resourceloader support
* 1.0.0 Beta 9: Sortable loaded in PHP, not js
* 1.0.0 Beta 8: Various adjustments. Work-around Chameleon bug.
* 1.0.0 Beta 3: More clean-up render Select fixed and show-on-select fix.
* 1.0.0 Beta 2: More clean-up and added GET action. Added hidden css class.
* 1.0.0       : Initial first public release
