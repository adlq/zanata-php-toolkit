<?php
// Check that all required arguments were passed
if (count($argv) < 3)
{
	echo 'Syntax is "php jam.php doc pofile locale"' . "\n";
	echo 'e.g. php jam.php lang fr-FR.po fr-FR' . "\n";
	exit("Missing parameters");
}

require_once("POFile.php");

$configs = parse_ini_file('config.ini', true);

$apiKey = $configs['Zanata']['api_key'];
$projectSlug = $configs['Zanata']['project_slug'];
$iterationSlug = $configs['Zanata']['iteration_slug'];

$locale = $argv[3];
$stringId = '';

$docName = $argv[1];
$textFlowTargets = array();
$textFlowIds = array();

$transUrl = "http://localhost:8080/zanata/seam/resource/restv1/projects/p/$projectSlug/iterations/i/$iterationSlug/r/$docName/translations/$locale";

/**
 * Retrieve existing translations
 */
$transCurl = curl_init($transUrl);

curl_setopt($transCurl, CURLOPT_RETURNTRANSFER, true);

$existingTrans = curl_exec($transCurl);

$curlResult = curl_getinfo($transCurl, CURLINFO_HTTP_CODE);

if ($curlResult === 200)
{
	$existingTrans = json_decode($existingTrans, true);
	// Retrieve the ids of the strings already translated
	foreach ($existingTrans['textFlowTargets'] as $entry) {
		$textFlowIds[$entry['resId']] = $entry['content'];
	}
}

/**
 * Parse the po file and update the translations
 */

$pofile = new POFile($argv[2]);

$entries = $pofile->getEntries();

$transState = 'New';

foreach($entries as $entry) 
{
	$needToPush = true;
	
	// Retrieve entry's data
	$source = $entry->getSource();
	$target = $entry->getTarget();
	$context = $entry->getContext();
	
	// Only process if the entry has a non-empty msgid and msgstr
	if($source !== '' && $target !== '')
	{
		// Hash the msgid and msgctxt
		$stringId = hash('sha256', $context . $source);
		
		if (array_key_exists($stringId, $textFlowIds) && $textFlowIds[$stringId] === $target)
		{
			// The new translation is the same as the existing one
			$needToPush = false;
		}
		else 
		{
			// The new translation differs from the existing one
			$transState = $entry->isFuzzy() ? 'NeedReview' : 'Approved';
		}
		
		if ($needToPush) 
		{
			array_push($textFlowTargets, array(
				"resId" => $stringId,
				"state" => $transState,
				"content" => $target,
				"extensions" => array(),
				"revision" => 2,
				"textFlowRevision" => 20));
				
			$textFlowIds[$stringId] = $target;
		}
	}
}

$jsonContents = json_encode(array(   
    "extensions" => array(),
    "textFlowTargets" => $textFlowTargets
    ));

// Write out JSON data to a temporary file that will be used with the PUT method
$tempFile = fopen('php://temp', 'w');
if (!$tempFile)
{
	die('Could not open temp memory data');
}
fwrite($tempFile, $jsonContents);
fseek($tempFile, 0);

$options = array(   
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array('Content-type: application/json', 'Accept: application/json', 'X-Auth-User: admin', "X-Auth-Token: $apiKey"),
    CURLOPT_PUT => true,
    CURLOPT_INFILE => $tempFile,
	CURLOPT_INFILESIZE => strlen($jsonContents)
);

$c = curl_init($transUrl);

curl_setopt_array($c, $options);

$response = curl_exec($c);

$curlResult = curl_getinfo($transCurl, CURLINFO_HTTP_CODE);

echo $curlResult;

echo $response;

curl_close($c);

?>