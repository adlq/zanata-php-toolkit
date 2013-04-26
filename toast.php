<?php
// Check that all required arguments were passed
if (count($argv) < 2)
{
	echo 'Syntax is "php toast.php potfile.pot"' . "\n";
	echo 'e.g. php toast.php lang.pot' . "\n";
	exit("Missing parameters");
}

require_once("POFile.php");

$configs = parse_ini_file('config.ini', true);

$apiKey = $configs['Zanata']['api_key'];
$projectSlug = $configs['Zanata']['project_slug'];
$iterationSlug = $configs['Zanata']['iteration_slug'];

$curlResponseMessage = array(
						'201' => 'Document created',
						'200' => 'Document successfully updated',
						'404' => 'Project or project iteration not found',
						'403' => 'Update forbidden',
						'401' => 'Unauthorized',
						'500' => 'Internal server error');
 
$basename = basename($argv[1]);
$docName = substr($basename, 0, strpos($basename, '.'));

$docUrl = "http://localhost:8080/zanata/seam/resource/restv1/projects/p/$projectSlug/iterations/i/$iterationSlug/r/$docName?ext=gettext";
$textFlows = array();
$stringIds = array();

/**
 * Parse the template file and build the JSON content to PUT
 */

// POT Parsing
$pofile = new POFile($argv[1]);

$potEntries = $pofile->getEntries();

// Initialize 
$stringId = '';

// Incrementally build the JSON content
foreach($potEntries as $entry) 
{
	// If the entry's msgid is not empty...
	if($entry->getSource() !== '')
	{
		// Hash the context and the msgid, that way we're sure the id is unique 
		$stringId = hash('sha256', $entry->getContext() . $entry->getSource());

		// Push to the array that will be converted into JSON
		if (!in_array($stringId, $stringIds, true))
		{	
			array_push($textFlows, array(
				'id' => $stringId,
				'lang' => 'en-GB',
				'content' => $entry->getSource(),
				'extensions' => array(array('object-type' => 'pot-entry-header', 'context' => $entry->getContext()))));
				
			array_push($stringIds, $stringId);
		}
		
	}
}

// Build a JSON object containing all the entries above
$jsonContents = array();

$jsonContents = json_encode(array(   
	"name" => $docName,
	"lang" => "en-GB",
	"contentType" => "application/x-gettext",
	"textFlows" => $textFlows
	));

/**
 * Set up the cURL calls
 */

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

$c = curl_init($docUrl);

curl_setopt_array($c, $options);

curl_exec($c);

$curlResponse = curl_getinfo($c, CURLINFO_HTTP_CODE);

echo "$curlResponse-" . $curlResponseMessage[strval($curlResponse)] . "\n";

if ($curlResponse !== 200 && $curlResponse !== 201)
	exit(1);
exit(0);

curl_close($c);
?>