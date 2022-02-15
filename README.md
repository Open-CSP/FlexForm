
# <img alt="WSForm" width="50" src="https://gitlab.wikibase.nl/uploads/-/system/project/avatar/137/WSForm-logo.png"> WSForm

---

WSForm is an enhanced HTML5 rendering engine.

It renders HTML5 form elements and allows to edit or create a page or multiple pages with one form.

This version 1 is a complete rewrite and stripped down version from the previous WSForm.

Compared to the previous version File upload and E-mail are not supported and will be added in a later version. Rendering of a form has been rewritten to support themes.

Documentation will be added soon.



## Installation

Grab in instance from https://gitlab.wikibase.nl/community/mw-wsform.
Create a "WSForm" folder in your Wiki extensions folder and extract the files there.

---

## Setup

You can tweak WSForm to an extent in your Localsettings.php
```php
$wgWSFormConfig['secure'] = true; //( default is true ). Will render form that make no sense when inspected in the browser
$wgWSFormConfig['sec_key'] = ""; // A salt key for encryption. Used together with "secure" option. Must be set when using multiple instances of a wiki
$wgWSFormConfig['auto_save_interval'] = 30000; // defaults to 3 minutes.
$wgWSFormConfig['auto_save_after_change'] = 3000; // defaults to 3 seconds after last change
$wgWSFormConfig['WSFormDefaultTheme'] = "wsform"; // Currently the only form
$wgWSFormConfig['rc_site_key'] = ""; // reCaptcha site key
$wgWSFormConfig['rc_secret_key'] = ""; // reCaptcha secret key
$wgWSFormConfig['file_temp_path'] = ""; // Currently not is use.
$wgWSFormConfig['form_timeout_limit'] = 7200; // 7200 seconds is the default
```
---

Finally add the following line at the end of your LocalSettings.php to enable the extension :
```php
wfLoadExtension( 'FlexForm' );
```

WSForm has a notification system build in. This is used to show possible errors or success / custom  messages.

To enable this.. add to your header page :
```html
<wsform showmessages />
```

## Docs
Visit : Special:WSForm/Docs


### Changelog
* 1.0.0 Beta 3: More clean-up render Select fixed and show-on-select fix.
* 1.0.0 Beta 2: More clean-up and added GET action. Added hidden css class.
* 1.0.0       : Initial first public release
