<?php
require_once('CurlWrapper.php');
require_once('ZanataApiUrl.php');
define('CRITICAL_SIZE', 7000);

/**
 * This class represents a cURL request that can be sent to
 * the Zanata API
 *
 * @author nghia
 */
class ZanataApiCurlRequest {
  private $ZanataApiUrl;
  private $defaultCurlOptions;
	private $isVerbose;
	private $isInDebug;

  /**
   * Constructor
   *
   * @param string $user The user
   * @param string $apiKey The API Key
   * @param string $baseUrl The base URL of the Zanata instance
	 * @param boolean $verbose Verbosity
   */
  function __construct($user, $apiKey, $baseUrl, $verbose = false, $debug = false) {
    $this->ZanataApiUrl = new ZanataApiUrl($baseUrl);

		$this->isVerbose = $verbose;
		$this->isInDebug = $debug;

    // Define a generic options array for GET cURL calls
    $this->defaultCurlOptions = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_TIMEOUT => 0,
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
          $this->getZanataApiUrl()->projectService($projectSlug),
          $this->getDefaultCurlOptions(),
          $this->isVerbose,
          $this->isInDebug,
          "Looking for project $projectSlug");

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
    if ($projectSlug !== '' && $iterationSlug !== '')
    {
      $checkIterationCall = new CurlWrapper(
        $this->getZanataApiUrl()->projectIterationService($projectSlug, $iterationSlug),
        $this->getDefaultCurlOptions(),
        $this->isVerbose,
        $this->isInDebug,
        "Looking for iteration $iterationSlug of project $projectSlug");

      return ($checkIterationCall->fetch());
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
        $this->getZanataApiUrl()->projectService($projectSlug),
        $this->getPutOptions($projectCreationJson),
        $this->isVerbose,
        $this->isInDebug,
        "Creating project $projectSlug");

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
        $this->getZanataApiUrl()->projectIterationService($projectSlug, $iterationSlug),
        $this->getPutOptions($iterationCreationJson),
        $this->isVerbose,
        $this->isInDebug,
        "Creating iteration $iterationSlug for project $projectSlug");

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
		// This array keeps track of the entries hashes.
		// That way we don't include duplicate strings in the request
		// which will trigger an internal server error
		$hashArray = array();

		if (count($entries) > CRITICAL_SIZE)
		{
			// Slice the entries into blocks of CRITICAL_SIZE
			$chunks = array_chunk($entries, CRITICAL_SIZE);
		}
		else
		{
			$chunks = array($entries);
		}

		$accChunk = array();
		$results = array();

		foreach($chunks as $id => $chunk)
		{
			if ($this->isInDebug)
				echo "Processing chunk #$id...\n";

			// Accumulate pot entries
			$accChunk = array_merge($accChunk, $chunk);

			// Contains the textFlows that will be sent to Zanata's API
			$textFlows = array();

			// Gather textFlows from the entries array
			foreach($accChunk as $entry)
			{
				$entryHash = $entry->getHash();

				// If the entry's msgid is not empty...
				if(!in_array($entryHash, $hashArray) && $entry->getSource() !== '')
				{
					// Push to the textFlows array
					array_push($textFlows, array(
						'id' => $entry->getHash(),
						'lang' => $sourceLocale,
						'content' => $entry->getSource(),
						'extensions' => array(array(
							'object-type' => 'pot-entry-header',
							'context' => $entry->getContext()))));
					// Update the hash array
					array_push($hashArray, $entryHash);
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
					$this->getZanataApiUrl()->sourceDocResourceService($projectSlug, $iterationSlug, $sourceDocName, true),
					$this->getPutOptions($putSourceDocJson),
					$this->isVerbose,
					$this->isInDebug,
					"Uploading source document $sourceDocName to project $projectSlug($iterationSlug)");

			// Execute it
			array_push($results, $putSourceDocCall->fetch());
		}

		foreach($results as $result)
		{
			if ($result === false)
				return $result;
		}

		return $result[count($result) - 1];
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
		// Initialize necessary variables
		$transState = '';
		$textFlowTargets = array();
		$textFlowTargetIds = array();

		// Retrieve the existing translations
		$retrieve = new CurlWrapper(
				$this->getZanataApiUrl()->translatedDocResourceService($projectSlug, $iterationSlug, $sourceDocName, $locale),
        $this->getDefaultCurlOptions(),
        $this->isVerbose,
        $this->isInDebug,
        "Retrieving translations for source document $sourceDocName in project $projectSlug($iterationSlug)");

		echo "Retrieving and adapting existing translations...";

    $retrieveResult = $retrieve->fetch();

		// If the retrieval was successful
		if ($retrieveResult != false)
		{
			// Convert the JSON response to an associative array
			$existingTranslations = json_decode($retrieve->fetch(), true);

			if (!empty($existingTranslations))
			{
				// Push the existing translations onto the text flow targets array
				foreach($existingTranslations['textFlowTargets'] as $tfTarget)
				{
					// So we don't lose any old translations
					array_push($textFlowTargets, array(
						'resId' => $tfTarget['resId'],
						'state' => $tfTarget['state'],
						'content' => $tfTarget['content'],
						'extensions' => array(),
						'revision' => $tfTarget['revision'],
						'textFlowRevision' => $tfTarget['textFlowRevision']
						));

					// Update the second array, that will help to quickly target
					// a specific entry in the first array using only its resId
					$textFlowTargetIds[$tfTarget['resId']] = key($textFlowTargets);
				}
			}
		}

		echo "Done\n";

		echo "Preparing all entries...";

		// Loop over all the PO entries obtained by parsing the file
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

				if (array_key_exists($stringId, $textFlowTargetIds))
				{
					// There is an existing translation for the current source entry
					// This means that we want to update this translation, i.e. replace
					// it with the new translation
					// If the new translation is, for some reasons, fuzzy, then we keep
					// the original translation
					if ($target !== $textFlowTargets[$textFlowTargetIds[$stringId]]['content']
							&& !$entry->isFuzzy())
					{
						$textFlowTargets[$textFlowTargetIds[$stringId]]['content'] = $target;
					}
				}
				else
				{
					// No translation exists for the current source entry
					// In this case, we simply push the new translations to the text flow
					// targets array
					array_push($textFlowTargets, array(
						"resId" => $stringId,
						"state" => $transState,
						"content" => $target,
						"extensions" => array()));
				}
			}
		}

		echo "Done\n";

		// Prepare the JSON content to send via cURL
		$putTranslationsJson = json_encode(array(
			"extensions" => array(),
			"textFlowTargets" => $textFlowTargets
    ));


    // Initialize a cURL call with the right options
    $putTranslationsCurl = new CurlWrapper(
        $this->getZanataApiUrl()->translatedDocResourceService($projectSlug, $iterationSlug, $sourceDocName, $locale, 'import'),
        $this->getPutOptions($putTranslationsJson),
        $this->isVerbose,
        $this->isInDebug,
        "Uploading translations for $sourceDocName to project $projectSlug($iterationSlug)");

    // Execute it
    return $putTranslationsCurl->fetch();
	}

	/**
	 *
	 * @param string $projectSlug
	 * @param string $iterationSlug
	 * @return mixed The cURL response
	 */
	public function getTranslationStats(
			$projectSlug,
			$iterationSlug)
	{
		$getTranslationStats = new CurlWrapper(
				$this->getZanataApiUrl()->statisticsResource($projectSlug, $iterationSlug),
				$this->getDefaultCurlOptions(),
				$this->isVerbose);

		return $getTranslationStats->fetch();
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
