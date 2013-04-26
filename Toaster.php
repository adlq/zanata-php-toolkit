<?php
require_once('POFile.php');
require_once('CurlWrapper.php');
require_once('ZanataApiUrlCrafter.php');

/**
 * Push source entries to a specified Zanata installation
 * given a POT file
 */
class Toaster
{
	// The API Key
	private $apiKey;
	// The project slug (ex: lms)
	private $projectSlug;
	// The iteration slug (ex: 1.0)
	private $iterationSlug;
	// Abosulte path to the resource (POT) file
	private $resourceFilePath;
	// The parsed version of the resource (POT) file 
	private $potFile;
	// This array contains the textFlows to be sent to Zanata
	// It will be converted to JSON for the cURL call
	private $requestArray;
	// This JSON object is obtained by converting the previous array 
	private $jsonRequest;
	// The source locale
	private $sourceLocale;
	// The source document name (for Zanata)
	private $sourceDocName;
	// API resquest URL crafter
	private $apiUrlCrafter;
	
	/**
	 * Constructor
	 * @param string $apiKey			The API key
	 * @param string $projectSlug		The project slug (short name)
	 * @param string $iterationSlug		The project version
	 * @param string $resourceFilePath	The absolute path to the POT file
	 * @param string $zanataHost		URL where the Zanata instance is hosted
	 *									(e.g. 'http;//localhost:8080/zanata/'), ends with a '/'
	 */
	public function __construct($apiKey, $projectSlug, $iterationSlug, $resourceFilePath, $zanataHost)
	{
		$this->apiKey = $apiKey;
		$this->projectSlug = $projectSlug;
		$this->iterationSlug = $iterationSlug;
		$this->resourceFilePath = $resourceFilePath;
		$this->zanataHost = $zanataHost;
		$this->sourceLocale = 'en-GB';
		
		// Extract the filename from the absolute path
		$basename = basename($resourceFilePath);
		$this->sourceDocName = substr($basename, 0, strpos($basename, '.'));
		
		$this->apiUrlCrafter = new ZanataApiUrlCrafter($zanataHost);
		
		$this->requestArray = array();
		$this->jsonRequest = array();
	}
	
	/**
	 * Push the source entries to Zanata, creating the project/project version if necessary
	 * 
	 * @return	boolean	  False if the push has succeeded, True otherwise
	 *			(hook exit code)
	 */
	public function launch() 
	{
		// Define a generic options array for GET cURL calls
		$getOptions = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array('Content-type: application/json', 'Accept: application/json', 'X-Auth-User: admin', 'X-Auth-Token: ' . $this->getApiKey()));
		
		// Check whether the project exists 
		$checkProjectCall = new CurlWrapper($this->getApiUrlCrafter()->projectService($this->getProjectSlug()), $getOptions);
		if($checkProjectCall->fetch(true))
		{
			// The project exists
		
			// Check whether the iteration exists
			$checkIterationCall = new CurlWrapper($this->getApiUrlCrafter()->projectIterationService(
					$this->getProjectSlug(), 
					$this->getIterationSlug()), $getOptions);
			if($checkIterationCall->fetch(true))
			{
				$valid = true;
			}
			else
			{
				// Create the iteration
			}
		}
		else
		{
			// Create the project
		}
		
		if ($valid)
		{
			// Build the JSON request
			$this->buildJsonRequest();

			// Write out JSON data to a temporary file that will be used with the PUT method
			$tempFile = fopen('php://temp', 'w');
			if (!$tempFile)
			{
				die('Could not open temp memory data');
			}
			fwrite($tempFile, $this->getjsonRequest());
			fseek($tempFile, 0);

			// Initialize the cURL call
			$options = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => array('Content-type: application/json', 'Accept: application/json', 'X-Auth-User: admin', 'X-Auth-Token: ' . $this->getApiKey()),
				CURLOPT_PUT => true,
				CURLOPT_INFILE => $tempFile,
				CURLOPT_INFILESIZE => strlen($this->getjsonRequest())
			);

			// Initialize the cURL handle with the crafted API URL
			$curlWrapper = new CurlWrapper($this->getApiUrlCrafter()->sourceDocResourceService(
				$this->getProjectSlug(), 
				$this->getIterationSlug(), 
				$this->getSourceDocName()), 
					$options);

			// Execute the cURL
			return !($curlWrapper->fetch());
		}
	}
	
	/**
	 * Craft the JSON request
	 */
	private function buildJsonRequest()
	{	
		// Build the request array from the resource file first
		$this->buildRequestArray();
		
		// Build a JSON object containing all the entries
		// from the request array built in buildRequestArray()
		$this->jsonRequest = json_encode(array(   
			'name' => $this->getSourceDocName(),
			'lang' => $this->getSourceLocale(),
			'contentType' => 'application/x-gettext',
			'textFlows' => $this->getRequestArray()
			));
	}
	
	/**
	 * Craft an array representing the request, to be converted to JSON
	 */
	private function buildRequestArray()
	{
		// Parse the resource (POT) file
		$this->potFile = new POFile($this->getResourceFilePath());

		// Retrieve its entries
		$potEntries = $this->potFile->getEntries();
		
		// Initialize the resource id that will be sent 
		// with each entry to Zanata
		$stringId = '';

		// Incrementally build the request array (the textFlows part at least)
		foreach($potEntries as $entry) 
		{
			// If the entry's msgid is not empty...
			if($entry->getSource() !== '')
			{
				// Hash the context and the msgid, to make sure that the id is unique
				$stringId = hash('sha256', $entry->getContext() . $entry->getSource());

				// Push to the array that will be converted into JSON
				$this->addSourceEntry($stringId, $entry->getSource(), $entry->getContext());
			}
		}

	}

	/**
	 * Add a source entry to the request array that is sbeing built
	 *
	 * @param string $id		The entry's id for Zanata
	 * @param string $content	The entry's content
	 * @param string $context	The entry's context
	 */
	private function addSourceEntry($id, $content, $context)
	{
		array_push($this->requestArray, array(
			'id' => $id,
			'lang' => $this->sourceLocale,
			'content' => $content,
			'extensions' => array(array('object-type' => 'pot-entry-header', 'context' => $context))));
	}

	/**
	 * Retrieve the absolute path to the resource (POT) file
	 * @return string
	 */
	public function getResourceFilePath()
	{
		return $this->resourceFilePath;
	}
	
	/**
	 * Retrieve the POT file's name
	 * @return string
	 */
	public function getSourceDocName()
	{
		return $this->sourceDocName;
	}

	/**
	 * Retrieve the source locale
	 * @return string
	 */
	public function getSourceLocale()
	{
		return $this->sourceLocale;
	}
	
	/**
	 * Retrieve the current request array
	 * @return array
	 */
	public function getRequestArray()
	{
		return $this->requestArray;
	}
	
	/**
	 * Retrieve the API URL
	 * @return string
	 */
	public function getApiUrl()
	{
		return $this->apiUrl;
	}
	
	/**
	 * Retrieve the JSON request
	 * @return string
	 */
	public function getjsonRequest()
	{
		return $this->jsonRequest;
	}
	
	/**
	 * Retrieve the API key
	 * @return string
	 */
	public function getApiKey()
	{
		return $this->apiKey;
	}
	
	/**
	 * Return the project slug
	 * @return string 
	 */
	public function getProjectSlug()
	{
		return $this->projectSlug;
	}

	/**
	 * Return the project version
	 * @return string
	 */
	public function getIterationSlug()
	{
		return $this->iterationSlug;
	}

	/**
	 * Return the Zanata API URL Crafter
	 * @return ZanataApiUrlCrafter
	 */
	public function getApiUrlCrafter()
	{
	  return $this->apiUrlCrafter;
	}
}


?>