Zanata PHP Toolkit
==================

Several PHP scripts that allow to interact with [Zanata's](https://github.com/zanata/zanata) API.

Installation
------------

Simply include the main file in your PHP code:
```php
require_once('ZanataPHPToolkit.php');
```

Getting started
---------------

In order to start interacting with Zanata's API, simply create a ZanataPHPToolkit object:
```php
$zanataPhpToolkit = new ZanataPHPToolkit($user, $apiKey, $projectSlug, $iterationSlug, $zanataUrl);
```

For now, the ```ZanataPHPToolkit``` class exposes the following methods:

```php
/**
 * Push the source POT entries to Zanata, 
 * creating the project/project version if necessary
 * 
 * @param string $resourceFilePath Full path to the POT file
 * @param string $sourceLocale The source locale
 * @return	boolean	  False if the push has succeeded, True otherwise
 *			(hook exit code)
 */
public function pushPotEntries($resourceFilePath, $sourceLocale)
```

```php
/**
 * Push a set of translations from a PO file to the Zanata platform
 * @param string $resourceFilePath Absolute path to the PO file
 * @param string $sourceDocName Name of the source document on Zanata 
 * @param string $destLocale Name of the target locale
 * @return boolean False if the push has succeeded, True otherwise
 *			(hook exit code)
 */
public function pushTranslations($resourceFilePath, $sourceDocName, $destLocale)
```
