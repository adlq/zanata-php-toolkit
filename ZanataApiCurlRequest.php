<?php
require_once('CurlWrapper.php');
require_once('ZanataApiUrl.php');

/**
 * This class represents a cURL request that can be sent to 
 * the Zanata API
 *
 * @author nghia
 */
class ZanataApiCurlRequest {
  private $ZanataApiUrl;
  private $defaultCurlOptions;
      
  /**
   * Constructor
   * 
   * @param string $user The user
   * @param string $apiKey The API Key
   * @param string $baseUrl The base URL of the Zanata instance
   */
  function __construct($user, $apiKey, $baseUrl) {
    $this->ZanataApiUrl = new ZanataApiUrl($baseUrl);
    
    // Define a generic options array for GET cURL calls
    $this->defaultCurlOptions = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array(
        'Content-type: application/json',
        'Accept: application/json', 
        'X-Auth-User: ' . $user, 
        'X-Auth-Token: ' . $apiKey));
  
  }
  
  /**
   * Retrieve information about a project
   * This is mainly used to check whether a project already exists
   * 
   * @param string $projectSlug
   * @return mixed JSON element containing the project information
   * if successful, False otherwise
   */
  public function getProject($projectSlug)
  {
    if ($projectSlug !== '')
    {  
      // Build the cURL call
      $checkProjectCall = new CurlWrapper(
          $this->getZanataApiUrl()->projectService(
              $projectSlug), 
          $this->getDefaultCurlOptions());

      // Execute it
      return ($checkProjectCall->fetch());
    }
    return false;
  }
  
  /**
   * Retrieve information about the given project iteration
   * This is mainly used to check whether the iteration already exists 
   * 
   * @param string $projectSlug The project slug
   * @param string $iterationSlug The project version
   * @return mixed JSON element containing the information if the 
   * iteration exists, False otherwise
   */
  public function getProjectIteration($projectSlug, $iterationSlug)
  {
    if ($this->getProject($projectSlug))
    {
      if ($iterationSlug !== '')
      {
        $checkIterationCall = new CurlWrapper(
          $this->getZanataApiUrl()->projectIterationService(
            $projectSlug, 
            $iterationSlug), 
          $this->getDefaultCurlOptions());
      
        return ($checkIterationCall->fetch());
      }
    }
    return false;
  }
  
  /**
   * Create or modify a project on Zanata
   * 
   * @param type $projectSlug
   * @return boolean True if the operation was successful, False
   * otherwise
   */
  public function putProject($projectSlug)
  {
    // Initialize the appropriate API fields
    $projectCreationArray = array(
        'id' => $projectSlug,
        'name' => $projectSlug,
        'defaultType' => 'Gettext',
        'status' => 'ACTIVE'
      );

    // Convert the previous array to JSON
    $projectCreationJson = json_encode($projectCreationArray);

    // Initialize the cURL handle with the right options
    $projectCreationCurl = new CurlWrapper(
    $this->getZanataApiUrl()->projectService(
      $projectSlug), 
        $this->getPutOptions($projectCreationJson));

    // Execute the cURL
    return $projectCreationCurl->fetch();
  }
  
  /**
   * Create or modify a project iteration
   * 
   * @param string $projectSlug
   * @param string $iterationSlug
   * @return boolean True if the operation was sucessful, False
   * otherwise
   */
  public function putProjectIteration($projectSlug, $iterationSlug)
  {
    // Initialize the appropriate API fields
    $iterationCreationArray = array(
        'id' => $iterationSlug,
        'status' => 'ACTIVE',
        'projectType' => 'Gettext'
      );
          
    // Convert the previous array to JSON
    $iterationCreationJson = json_encode($iterationCreationArray);
    
    // Initialize the cURL handle with the right options
    $iterationCreationCurl = new CurlWrapper(
    $this->getZanataApiUrl()->projectIterationService(
      $projectSlug, $iterationSlug),
        $this->getPutOptions($iterationCreationJson));

    // Execute the cURL
    return $iterationCreationCurl->fetch();
  }
  
  /**
   * Create or modify a source document for the given iteration of 
   * the given project
   * 
   * @param string $projectSlug The project slug
   * @param string $iterationSlug The project version
   * @param string $sourceDocName The name of the source document
   * @param string $sourceLocale The source locale
   * @param array<POEntry> $entries An array containing the parsed POT
   * entries
   * @return boolean True if the operation was successful, False 
   * otherwise
   */
  public function putSourceDoc(
      $projectSlug,
      $iterationSlug,
      $sourceDocName,
      $sourceLocale,
      $entries)
  {
    // Contains the textFlows that will be sent to Zanata's API
    $textFlows = array();
    // Unique ID for each entry
    $stringId = '';
    
    // Gather textFlows from the entries array
		foreach($entries as $entry) 
		{
			// If the entry's msgid is not empty...
			if($entry->getSource() !== '')
			{
				// Hash the context and the msgid, 
        // to make sure that the id is unique
				$stringId = hash(
            'sha256', 
            $entry->getContext() . $entry->getSource());

				// Push to the textFlows array
        array_push($textFlows, array(
          'id' => $stringId,
          'lang' => $sourceLocale,
          'content' => $entry->getSource(),
          'extensions' => array(array(
            'object-type' => 'pot-entry-header', 
            'context' => $entry->getContext()))));
			}
		}
		
		// Build a JSON object containing all the entries
		$putSourceDocJson = json_encode(array(   
			'name' => $sourceDocName,
			'lang' => $sourceLocale,
			'contentType' => 'application/x-gettext',
			'textFlows' => $textFlows
			));    
    
    // Initialize a cURL call with the right options
    $putSourceDocCall = new CurlWrapper(
        $this->getZanataApiUrl()->sourceDocResourceService(
            $projectSlug, $iterationSlug, $sourceDocName, true), 
        $this->getPutOptions($putSourceDocJson));
        
    // Execute it
    return $putSourceDocCall->fetch();
  }
  
  /**
   * Create or modify the translations in the given locale 
	 * for the given source document in the given iteration 
	 * of the given project.
   * 
   * @param string $projectSlug The project slug
   * @param string $iterationSlug The project version
   * @param string $sourceDocName The name of the source document
   * @param string $locale The locale
   * @param array<POEntry> $entries An array containing the parsed POT
   * entries
   * @return boolean True if the operation was successful, False 
   * otherwise
   */
	public function putTranslations(
			$projectSlug,
			$iterationSlug,
			$sourceDocName,
			$locale,
			$entries)
	{
		$transState = '';
		$textFlowTargets = array();

		foreach($entries as $entry) 
		{

			// Retrieve entry's data
			$source = $entry->getSource();
			$target = $entry->getTarget();
			$context = $entry->getContext();

			// Only process if the entry has a non-empty msgid and msgstr
			if($source !== '' && $target !== '')
			{
				// Hash the msgid and msgctxt
				$stringId = hash('sha256', $context . $source);
				
				$transState = $entry->isFuzzy() ? 'NeedReview' : 'Approved';
				
				array_push($textFlowTargets, array(
					"resId" => $stringId,
					"state" => $transState,
					"content" => $target,
					"extensions" => array()));
			}
		}
		

		$putTranslationsJson = json_encode(array(   
			"extensions" => array(),
			"textFlowTargets" => $textFlowTargets
    ));
    
    // Initialize a cURL call with the right options
    $putTranslationsCurl = new CurlWrapper(
        $this->getZanataApiUrl()->translatedDocResourceService(
            $projectSlug, 
						$iterationSlug, 
						$sourceDocName,
						$locale, 'import'), 
        $this->getPutOptions($putTranslationsJson));
        
    // Execute it
    return $putTranslationsCurl->fetch();
	}
	
  /**
   * Retrieve options for a cURL PUT request
   * 
   * @param array $data The (JSON) data to be sent via PUT
   * @return array Options for the cURL call
   */
  public function getPutOptions($data)
  {
    // Write out JSON data to a temporary file that will be 
    // used with the PUT method
    $tempFile = fopen('php://temp', 'w');
    if (!$tempFile)
      die('Could not open temp memory data');
    fwrite($tempFile, $data);
    fseek($tempFile, 0);

    // Initialize the cURL call
    $putOptions = $this->getDefaultCurlOptions() + array(
      CURLOPT_PUT => true,
      CURLOPT_INFILE => $tempFile,
      CURLOPT_INFILESIZE => strlen($data)
    );
    
    return $putOptions;
  }

  /**
   * Return the ZanataApiUrl builder object
   * @return ZanataApiUrl The API
   */
  public function getZanataApiUrl() {
    return $this->ZanataApiUrl;
  }

  /**
   * 
   * @return array Options 
   */
  public function getDefaultCurlOptions() {
    return $this->defaultCurlOptions;
  }
}

?>
