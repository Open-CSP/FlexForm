# WSForm

WSForm is an enhanced HTML5 rendering engine

## Features

 * Renders all HTML5 form elements
 * By default it allows all default additional element attributes (class, id, required, etc..)
 * By default it will always use a method of post to a form handler script
 * Form elements can be filled by url parameters
 * A form can use a template to add or edit a page in the wiki
 * A form can send an email
 * Most options can be combined (create a new page, edit a page and send an email with one form)
 * On a closed wiki it is possible to let anonymous persons also add a page to the wiki
 * Acknowledges $wgEmailConfirmToEdit setting (if set to true, only users with verified email addresses can add a page to a wiki)
 * Supports file upload to Wiki
 * Supports signatures file upload to Wiki



## Installation

**This information can also be found index.php/Special:WSForm/Docs. Look for wsform installation and wsform config**

Grab in instance from the https://bitbucket.org/wikibasesolutions/mw-wsform/. Create a "WSForm" folder in your Wiki extensions folder and extract the files there.

---

## Setup
In order to add pages to your wiki, you will need to setup a BOT password for WSForm.
More information on setting up a bot-password can be found [https://www.mediawiki.org/wiki/Manual:Bot_passwords](here).

OR

You will need to create an user account with all appropriate rights.

First go to Special:CreateAccount and create an account e.g. formsubmitter.

Set the appropriate rights in Special:UserRights.

---

METHOD 1:

Open ``` /WSForm/config/config_default.php  ``` fill the username and password you just created.

If you on a FARM add the path to the FARM (read the description in the config file). 

Save the file as config.php

METHOD 2:

Go to index.php/Special:WSForm/Setup and follow the instructions

---

If you are on FARM you can add a WSFormSettings.php in the wiki/name folder with the following content :
```php
<?php
$this->app['username']='...';
$this->app['password']='...';
``` 
For uploading files the user created will need to have the rights for :

* upload

* uploadfile

* upload_by_url

General settings that need to be enabled :
```php
$wgAllowCopyUploads = true;
$wgCopyUploadsFromSpecialUpload = true;
````

Finally add the following line at the end of your LocalSettings.php to enable the extension :
```php
require_once( "$IP/extensions/WSForm/WSForm.php" );
```
This is the "old-fashioned" way for backwards compatibility. You could also do :
```php
wfLoadExtension( 'WSForm' );
```

WSForm has a notification system build in. This is used to show possible errors or success / custom  messages.

To enable this.. add to your header page :
```html
<wsform showmessages />
```

## Docs
Visit : Special:WSForm/Docs

## Example
```HTML5
<wsform class="ws-test-form" action="addToWiki" mwreturn="/thankyou.html">
<wsfield type="hidden" name="mwtemplate" value="Testpage" /> <!-- name of a template to you use for creating new page -->
<wsfield type="hidden" name="mwwrite" value="Wiki:Formcreate_" /><!-- name of new page -->
<wsfield type="hidden" name="mwoption" value="add_random" /><!-- add unique identifier after name of new page -->
<wsfield type="hidden" name="Username" value="{{CURRENTLOGGEDUSER}}" />
<wsfield type="textarea" name="comment" placeholder="Write your comment here" />
<wsfield type="submit" class="btn btn-default" value="Submit" />
</wsform>
```


### Changelog
* 0.8.0.9.8.5 : Multiple instances and show on select Final tests
* 0.8.0.9.8.4 : Actual bug find by Dennis! After slot support, multiple edits were broken. Fixed!
* 0.8.0.9.8.3 : Changed mwslot argument to be consistent throughout. Updated docs.
* 0.8.0.9.8.2 : Multiple instances and show-on-selected. Rdy for 2nd test stage! Added error message documentation.
* 0.8.0.9.8.1 : Show on select and multiple instance still not supported (testing). Added Docs Search function
* 0.8.0.9.8.0 : Extra config option api-url-option. See docs
* 0.8.0.9.7.9 : Multiple instances and show on select added. RDY FOR TESTNG!!
* 0.8.0.9.7.8 : Version bump
* 0.8.0.9.7.7 : Added editing content slots [NEEDS TO BE TESTED!!]
* 0.8.0.9.7.6 : Saving to a slot now done through WSSlots
* 0.8.0.9.7.5 : changed behaviour for new version of select2. Removed deprecated disableCache function. wsLogger extension removed. Refresh SMW fixed. Force trailing slash on document root.
* 0.8.0.9.7.4 : Added extension wslogger and docs on wslogger
* 0.8.0.9.7.3 : Updated external requests where url was encoded by server
* 0.8.0.9.7.2 : Updated to work when wiki is installed in a subfolder. Updated Formbuilder. Changed Docs behaviour
* 0.8.0.9.7.1 : Updates select2 to version 4.0.13
* 0.8.0.9.7.0 : wstokens multiple="multiple" and only the wsform tag will load needed JavaScript. Meaning fields that need JavaScript need to be inside wsform tag
* 0.8.0.9.6.9 : Added placeholder to wsselect. Changed docs.
* 0.8.0.9.6.8 : wgScript used to set correct url to folders
* 0.8.0.9.6.7 : Handling of Form post with multiple WSForms on one page and VE included
* 0.8.0.9.6.6 : Better handling for extensions
* 0.8.0.9.6.5 : Added preSaveTransform and force smw properties update in maintenance script
* 0.8.0.9.6.4 : Added isBot to config
* 0.8.0.9.6.3 : Added default value for checkboxes. Updated Docs
* 0.8.0.9.6.2 : Added loadscript options to config. Updated Docs
* 0.8.0.9.6.1 : Added global variable to loadscript feature
* 0.8.0.9.6 : Rewrote css and JavaScript includes. !!NEEDS TESTING BEFORE DEPLOY ON PRODUCTION SERVERS!!
* 0.8.0.9.5.3 : Changed recursiveTagParse to recursiveTagParseFully
* 0.8.0.9.5.2 : Addendum
* 0.8.0.9.5.1 : Fix for Zero-width assertion that ensures a pattern is preceded by another pattern in a JavaScript regular expression that is not supported by Safari, IOS Safari and Opera mini
* 0.8.0.9.5 : Awaiting MWF answer for carriage return, stripped in this version (NEEDS TESTING IF IT NOT BREAKS ANYTHING ELSE). Changed form handling priorities
* 0.8.0.9.4 : Added config for form session timeout. This changed the security code and needs a good WSForm general test! {T6962}
* 0.8.0.9.3.1 : Two addendums and two terminal notices removed.
* 0.8.0.9.3 : New version of trumbowyg, updated docs for new features, updated setup page
* 0.8.0.9.2 : Fixed saving of api-url-overrule from Special setup page
* 0.8.0.9.1 : Added autosave functionality. Needs testing! TODO: docs
* 0.8.0.9.0 : Added Security key in config for multiple instances on e.g. AWS
* 0.8.0.8.9 : Added Install Wizard
* 0.8.0.8.8 : Added VE4ALL support
* 0.8.0.8.7 : Fixed reCaptcha
* 0.8.0.8.6 : More secured form rendering
* 0.8.0.8.5 : Marijn's script was broken for not 1.35 wiki's. Fixed
* 0.8.0.8.4 : 1.35 Maintenance script update > Thanks to Marijn!
* 0.8.0.8.3 : Allow " and ' in form
* 0.8.0.8.2 : {T6636} link for verifying email
* 0.8.0.8.1 : {T6602}, {T6603}, {T6600}
* 0.8.0.8.0 : All Post fields are now cleaned if secure version is on.
* 0.8.0.7.9 : apostroph no longer filtered when html=default for field
* 0.8.0.7.8 : Missing reCaptcha code from 0.9.0.0 (added .js and valid params)
* 0.8.0.7.7 : When sec and not posting as API, userID crypted
* 0.8.0.7.6 : MW.135 renamed parser function in outputpage (not documented!!) Also removed mw.user functions {T6399} {T6390}
* 0.8.0.7.5 : Added handling secure multiple forms. Rewrote next available and range to be indirect API calls (should be a lot faster) (TEST!!)
* 0.8.0.7.4 : When sec-true, html option for fields is applied (rdy for internal testing)!
* 0.8.0.7.3 : Returning Get url filtered. action="get" allows for wsedit and wscreate
* 0.8.0.7.2.2 : If config sec=true, all input fields are filtered and new field "secure" comes available
* 0.8.0.7.2.1 : First steps for heavy security setup
* 0.8.0.7.2 : Added config variable wgScript. Added config documentation
* 0.8.0.7.1.1 : Cosmetics
* 0.8.0.7.1 : Added new version information and changelog
* 0.8.0.7.0 : Updated email docs and fixed bug when editing doc (lost creator and creator date)
* 0.8.0.6.9 : Added repo version check for Doc pages
* 0.8.0.6.8 : Removed cleaning of return url
* 0.8.0.6.7 : Removing PHP notices
* 0.8.0.6.6 : Added classes for ajax submit to form {T5851}
* 0.8.0.6.5 : Added markup cleaning for wstoken {T5575}. Added change for tokens when page is loaded.
* 0.8.0.6.45: Fixed form time out (form can be shown for 2 hrs, then time-out).  {T4989}
* 0.8.0.6.4 : Removed warnings when run from CLI
* 0.8.0.6.3 : added minlength option for input fields
* 0.8.0.6.2 : Added do not verify peer for WSForm (issue rvs)
* 0.8.0.6.1 : Added a message on missing config information ( .. )
* 0.8.0.6.0 : change to range options to fix page names that are similar
* 0.8.0.5.9 : wsfollow lost in multiple wscreate
* 0.8.0.5.8 : Added support for posting as user using Ajax
* 0.8.0.5.7 : Cookie path wrong code fixed
* 0.8.0.5.6 : Added SMTP support for mailing
* 0.8.0.5.5 : Added installation manual in Docs
* 0.8.0.5.4 : Added cookie path to config settings [still needs to be tested]
* 0.8.0.5.3 : Fixed an issue checking for logged-in user
* 0.8.0.5.2 : Changed WSToken rendering
* 0.8.0.5.1 : Fixed reCaptcha bug and added missing file from 0.8.0.5.0
* 0.8.0.5.0 : [MS] Added config option "use-api-user-only" to facilitate functionality add in 0.8.0.4.9. New version of formbuilder.
* 0.8.0.4.9 : Added upload, edit and create as logged in user.
* 0.8.0.4.8 : Minor tweaks. Fixed Verbose ID not working correctly. Changed file upload docs. Added Formbuilder
* 0.8.0.4.7 : Restrictions parameter is now parsed first if needed
* 0.8.0.4.6 : Removed PHP error notices. Added {T5041} (edit end brackets on new line). Changed upload JavaScript.
* 0.8.0.4.5 : Changed wsfollow
* 0.8.0.4.4 : Added check if Config file exists
* 0.8.0.4.3 : Enhancement Issue #7 {T5018}
* 0.8.0.4.2 : Made changes to file-upload regarding to jQuery not loaded yet
* 0.8.0.4.1 : Fixed two PHP notices : nameSpace and canonical being undefined
* 0.8.0.4.0 : Added multi wscreates with next available and ranges {T4952}. Fixed enhancement Issue #2
* 0.8.0.3.9 : Forced email to UTF-8 encoding
* 0.8.0.3.8 : Added message on success for emails
* 0.8.0.3.7 : Added support for replyto. Also multiple to, cc and bcc support in templates
* 0.8.0.3.6 : Added support for multiple to, cc and bcc in email
* 0.8.0.3.5 : Added parselast for email and add attachment to email
* 0.8.0.3.4 : Implemented reCaptcha from 0.9.0.0
* 0.8.0.3.3 : Added docs for issue #3
* 0.8.0.3.2 : Made all config files separate added form tokens
* 0.8.0.3.1 : Small doc contribution and ajax callback check
* 0.8.0.3.0 : Initial release community version
* 0.8.0.2.6 : Option to create a page without a template parameter
* 0.8.0.2.5 : Added remove select option
* 0.8.0.2.4 : Forgot one namespace migration
* 0.8.0.2.3 : Added parsepost as WSForm system field
* 0.8.0.2.2 : Added parsepost option to wsfield
* 0.8.0.2.1 : Missing Semantic javaScript
* 0.8.0.2.0 : Semantic relax lookup
* 0.8.0.1.9 : More option for fileupload content page
* 0.8.0.1.8 : Added support using formfields in fileupload content page
* 0.8.0.1.7 : Added support for ranges and next available when using namespaces. Added leading zeros when using range
* 0.8.0.1.6 : Added formbuilder
* 0.8.0.1.5 : Added some security steps for external request
* 0.8.0.1.4 : Various small fixes ( file upload, path calculation and more )
* 0.8.0.1.3 : Changed API url lookup from wgserver to wgscript
* 0.8.0.1.2 : Finalizing classes from hooks
* 0.8.0.1.1 : Created all other classes
* 0.8.0.1.0 : Started to create render, validate and wsform classes for better clarity
* 0.8.0.0.3 : Added default settings for SMW queries
* 0.8.0.0.2 : Add custom tokens support
* 0.8.0.0.1 : Added new examples in Documentation
* 0.8.0.0.0 : Larger version bump due to function documentation virtually complete. Added email template options.
* 0.7.0.3.5 : Fixed: Fixed Docs menu not showing when no jQuery
* 0.7.0.3.4 : Started implementing i18n. Fixed Docs menu not showing when no jQuery
* 0.7.0.3.3 : Fixed end-result Ajax post
* 0.7.0.3.2 : Cleanup and documentation
* 0.7.0.3.1 : Cleanup
* 0.7.0.3.0 : Added ip address for anonymous poster and adjusted api call for ranged pages to new MW api standard
* 0.7.0.2.9 : Minor fixes introduced in previous version
* 0.7.0.2.8 : Added no parse function for wsfield inputs
* 0.7.0.2.7 : Check if JavaScript and CSS is only loaded once. Also for custom added js.
* 0.7.0.2.6 : Changed code for v0.7.0.2.0 implemented features
* 0.7.0.2.5 : Added Phabricator module (for creating tasks)
* 0.7.0.2.4 : Start of translations
* 0.7.0.2.3 : Code cleanup
* 0.7.0.2.2 : Docs updated
* 0.7.0.2.0 : Added next available and range to creating a new page and a class for reading i18n folder for messages
* 0.7.0.1.9 : Added Form follow option
* 0.7.0.1.8 : User lookup through cookies (needs work still)
* 0.7.0.1.7 : Updated Docs
* 0.7.0.1.6 : Extensions support for pre-render and after render
* 0.7.0.1.5 : Added extensions and changed user info for summaries
* 0.7.0.1.4 : Added user info on edit/save summaries
* 0.7.0.1.3 : Success message linked to save btn instead of form. Added new docs layout
* 0.7.0.1.2 : Fixed handling form field names with spaces (wscreate)
* 0.7.0.1.1 : Added first steps for live mobile screenshots. Added general JavaScript for handling stuff
* 0.7.0.1.0 : Added Signature support, updated docs and made it url aware
* 0.7.0.0.0 : Initial stand-alone version
