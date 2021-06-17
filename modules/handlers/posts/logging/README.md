
### note

---
* To use this extension for WSForm you will have to have the WSForm user and password configured. Logs are written through the MediaWiki API and we need user rights for this.
* This extension uses a customized version of CustomLogs extensions (available from wikibase)
* Works with WSForm version 0.8.0.9.6.6 and above

---

### note 2

---
The allowed logtypes written and their accompanied logtags are defined in wslogger.class.php.

Key is logtype, value is logtag
```php
private $allowedTags = [
'log-publiceren'   => 'gepubliceerd',
'log-depubliceren' => 'gedepubliceerd'
];```


