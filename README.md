Zanata PHP Toolkit
==================

Several PHP scripts that allow to interact with [Zanata's](https://github.com/zanata/zanata) API.

Installation
------------

Simply include the main file in your PHP code:
'''php
require_once('ZanataPHPToolkit.php');
'''

Getting started
---------------

In order to start interacting with Zanata's API, simply create a ZanataPHPToolkit object:
'''php
$zanataPhpToolkit = new ZanataPHPToolkit($user, $apiKey, $projectSlug, $iterationSlug, $zanataUrl);
'''

