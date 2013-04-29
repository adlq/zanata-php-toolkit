<?php
// Check that all required arguments were passed
if (count($argv) < 3)
{
	echo 'Syntax is "php pushPotEntries.php repoName potfile.pot"' . "\n";
	echo 'e.g. php pushPotEntries.php trunk.elms trunk.elms.pot' . "\n";
	exit("Missing parameters");
}

require_once('Toaster.php');

$configs = parse_ini_file('config.ini', true);

$zanataUrl = $configs['Zanata']['url'];
$user = $configs['Zanata']['user'];
$apiKey = $configs['Zanata']['api_key'];
$projectSlug = '';
$iterationSlug = '';

$repoName = $argv[1];
$potFilePath = $argv[2];

switch($repoName)
{
	case 'trunk.elms':
		$projectSlug = 'lms';
		$iterationSlug = '13.1';
		
}

$toast = new Toaster($user, $apiKey, $projectSlug, $iterationSlug, 'lang.pot', $zanataUrl);

exit($toast->launch());
?>