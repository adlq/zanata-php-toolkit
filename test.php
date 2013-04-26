<?php
require_once('Toaster.php');

$configs = parse_ini_file('config.ini', true);

$apiKey = $configs['Zanata']['api_key'];
$projectSlug = $configs['Zanata']['project_slug'];
$iterationSlug = $configs['Zanata']['iteration_slug'];

$toast = new Toaster($apiKey, $projectSlug, $iterationSlug, 'lang.pot', 'http://localhost:8080/zanata');

exit($toast->launch());
?>