<?php
// Check that all required arguments were passed
if (count($argv) < 3)
{
	echo 'Syntax is "php pushTranslations.php repoName sourceDocName poFilePath locale"' . "\n";
	echo 'e.g. php pushTranslations.php elms.trunk elms.trunk /path/to/fr-FR.po fr-FR' . "\n";
	exit("Missing parameters");
}

require_once('conf.php');
require_once('ZanataPHPToolkit.php');

$zanataUrl = $GLOBALS['conf']['zanata']['url'];
$user = $GLOBALS['conf']['zanata']['user'];
$apiKey = $GLOBALS['conf']['zanata']['apiKey'];
$projectSlug = '';
$iterationSlug = '';

// Extract the repo name and POT file path from the parameters
$repoName = $argv[1];
$sourceDocName = $argv[2];
$poFilePath = $argv[3];
$locale = $argv[4];

// Attempt to find the repo name in the config.ini file
if (isset($GLOBALS['conf']['repos'][$repoName]))
{
	$projectSlug = $GLOBALS['conf']['repos'][$repoName]['projectSlug'];
	$iterationSlug = $GLOBALS['conf']['repos'][$repoName]['iterationSlug'];
}
else
{
	exit("Unknown project, no section $repoName in conf.php file");
}

// Update the source entries on Zanata!
$zanataToolkit = new ZanataPHPToolkit($user, $apiKey, $projectSlug, $iterationSlug, $zanataUrl, true);

// exit with the appropriate code
exit($zanataToolkit->pushTranslations($poFilePath, $sourceDocName, $locale));

?>