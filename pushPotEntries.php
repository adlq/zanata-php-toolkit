<?php
// Check that all required arguments were passed
if (count($argv) < 3)
{
	echo 'Syntax is "php pushPotEntries.php repoName potfile.pot"' . "\n";
	echo 'e.g. php pushPotEntries.php trunk.elms trunk.elms.pot' . "\n";
	exit("Missing parameters");
}

require_once('ZanataPHPToolkit.php');

// Parse the ini file 
$configs = parse_ini_file('config.ini', true);

$zanataUrl = $configs['Zanata']['url'];
$user = $configs['Zanata']['user'];
$apiKey = $configs['Zanata']['api_key'];
$projectSlug = '';
$iterationSlug = '';

// Extract the repo name and POT file path from the parameters
$repoName = $argv[1];
$potFilePath = $argv[2];

// Attempt to find the repo name in the config.ini file
if (isset($configs[$repoName]))
{
	$projectSlug = $configs[$repoName]['project_slug'];
	$iterationSlug = $configs[$repoName]['iteration_slug'];
}
else
{
	exit('Unknown project, no section $repoName in config.ini file');
}

// Update the source entries on Zanata!
$zanataToolkit = new ZanataToolkit($user, $apiKey, $projectSlug, $iterationSlug, $zanataUrl);

exit($zanataToolkit->pushPotEntries($potFilePath, 'en-GB'));
?>