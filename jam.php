<?php
// Check that all required arguments were passed
if (count($argv) < 3)
{
	echo 'Syntax is "php jam.php repoName sourceDocName poFilePath locale"' . "\n";
	echo 'e.g. php jam.php elms.trunk elms.trunk /path/to/fr-FR.po fr-FR' . "\n";
	exit("Missing parameters");
}

require_once('Toaster.php');

// Parse the ini file 
$configs = parse_ini_file('config.ini', true);

$zanataUrl = $configs['Zanata']['url'];
$user = $configs['Zanata']['user'];
$apiKey = $configs['Zanata']['api_key'];
$projectSlug = '';
$iterationSlug = '';

// Extract the repo name and POT file path from the parameters
$repoName = $argv[1];
$sourceDocName = $argv[2];
$poFilePath = $argv[3];
$locale = $argv[4];

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
$toast = new Toaster($user, $apiKey, $projectSlug, $iterationSlug, $zanataUrl);

// exit with the appropriate code
exit($toast->pushTranslations($poFilePath, $sourceDocName, $locale));

?>