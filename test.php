<?php
require_once('Toaster.php');

$configs = parse_ini_file('config.ini', true);

$zanataUrl = $configs['Zanata']['url'];
$user = $configs['Zanata']['user'];
$apiKey = $configs['Zanata']['api_key'];
$projectSlug = $configs['Zanata']['project_slug'];
$iterationSlug = $configs['Zanata']['iteration_slug'];

$toast = new Toaster($user, $apiKey, $projectSlug, $iterationSlug, 'lang.pot', $zanataUrl);

exit($toast->launch());
?>