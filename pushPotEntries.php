<?php
// Check that all required arguments were passed
if (count($argv) < 3)
{
	echo 'Syntax is "php pushPotEntries.php repoName potfile.pot"' . "\n";
	echo 'e.g. php pushPotEntries.php trunk.elms trunk.elms.pot' . "\n";
	exit("Missing parameters");
}

require_once('conf.php');
require_once('ZanataPHPToolkit.php');

$zanataUrl = $ZANATA['conf']['zanata']['url'];
$user = $ZANATA['conf']['zanata']['user'];
$apiKey = $ZANATA['conf']['zanata']['apiKey'];
$projectSlug = '';
$iterationSlug = '';

// Extract the repo name and POT file path from the parameters
$repoName = $argv[1];
$potFilePath = $argv[2];

// Attempt to find the repo name in the config.ini file
if (isset($ZANATA['conf']['repos'][$repoName]))
{
	$projectSlug = $ZANATA['conf']['repos'][$repoName]['projectSlug'];
	$iterationSlug = $ZANATA['conf']['repos'][$repoName]['iterationSlug'];
}
else
{
	exit('Unknown project, no section $repoName in conf.php file');
}

// Update the source entries on Zanata!
$zanataToolkit = new ZanataPHPToolkit($user, $apiKey, $projectSlug, $iterationSlug, $zanataUrl, true);

exit($zanataToolkit->pushPotEntries($potFilePath, 'en-GB'));
?>