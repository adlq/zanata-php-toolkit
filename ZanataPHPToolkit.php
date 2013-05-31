<?php
require_once('conf.php');
require_once($ZANATA['paths']['pophp'] . 'POFile.php');
require_once('ZanataApiCurlRequest.php');

/**
 * Zanata Toolkit
 */
class ZanataPHPToolkit
{
	// The project slug (ex: lms)
	private $projectSlug;
	// The iteration slug (ex: 1.0)
	private $iterationSlug;
  // cURL request crafter
  private $zanataCurlRequest;
	
	/**
	 * Constructor
   * @param string $user        The user
	 * @param string $apiKey			The API key
	 * @param string $projectSlug		The project slug (short name)
	 * @param string $iterationSlug		The project version
	 * @param string $zanataHost		URL where the Zanata instance 
   *                              is hosted, must not end with '/'
	 */
	public function __construct(
      $user, 
      $apiKey, 
      $projectSlug, 
      $iterationSlug, 
      $baseUrl,
			$verbose = false)
	{
		$this->projectSlug = $projectSlug;
		$this->iterationSlug = $iterationSlug;
		
		$this->zanataCurlRequest = new ZanataApiCurlRequest(
        $user, $apiKey, $baseUrl, $verbose);
		
		// Check to see whether the given project exists
		$projectExists = $this->getZanataCurlRequest()->getProject(
        $this->getProjectSlug());
    
		if(!$projectExists)
		{
			// The project does not exist, we have to create it first
      $this->getZanataCurlRequest()->putProject($this->getProjectSlug());
    }
		
    // Now the project exists
    // Check whether the iteration exists
    $iterationExists = 
        $this->getZanataCurlRequest()->getProjectIteration(
            $this->projectSlug, $this->getIterationSlug());
    
    if (!$projectExists || !$iterationExists) 
    {
      // If the project didn't exist in the first place, we have
      // to create the iteration anyway
      $this->getZanataCurlRequest()->putProjectIteration(
          $this->getProjectSlug(), $this->getIterationSlug());
    }

	}
	
	/**
	 * Push the source POT entries to Zanata, 
   * creating the project/project version if necessary
	 * 
	 * @param string $resourceFilePath Full path to the POT file
	 * @param string $sourceLocale The source locale
	 * @return	boolean	  False if the push has succeeded, True otherwise
	 *			(hook exit code)
	 */
	public function pushPotEntries($resourceFilePath, $sourceLocale) 
	{
		// Extract the source document name from the absolute path
		$basename = basename($resourceFilePath);
		$sourceDocName = pathinfo($basename, PATHINFO_FILENAME);
		
		
    // Parse the resource (POT) file
    $potFile = new POFile($resourceFilePath);

    // Retrieve its entries
    $potEntries = $potFile->getEntries();

    // Send the entries to Zanata
    return !($this->getZanataCurlRequest()->putSourceDoc(
        $this->getProjectSlug(), 
        $this->getIterationSlug(),
        $sourceDocName, 
        $sourceLocale, $potEntries));
	}
	
	/**
	 * Push a set of translations from a PO file to the Zanata platform
	 * @param string $resourceFilePath Absolute path to the PO file
	 * @param string $sourceDocName Name of the source document on Zanata 
	 * @param string $destLocale Name of the target locale
	 * @return boolean False if the push has succeeded, True otherwise
	 *			(hook exit code)
	 */
	public function pushTranslations(
			$resourceFilePath, 
			$sourceDocName,
			$destLocale)
	{
    // Parse the resource (POT) file
    $poFile = new POFile($resourceFilePath);

    // Retrieve its entries
    $poEntries = $poFile->getEntries();

    // Send the entries to Zanata
    return !($this->getZanataCurlRequest()->putTranslations(
        $this->getProjectSlug(), 
        $this->getIterationSlug(),
        $sourceDocName, 
        $destLocale, $poEntries));
	}
	
	/**
	 * Retrieve translation stats for a specific locale
	 * 
	 * @param string $destLocale The target locale
	 * @return array Empty array 
	 */
	public function getTranslationStats($destLocale = '')
	{
		$rawStats = $this->getZanataCurlRequest()->getTranslationStats(
				$this->projectSlug, $this->iterationSlug);
		
		$stats = json_decode($rawStats);
		
		$result = array();
		
		// Return empty array if nothing is found
		if (empty($stats))
			return $result;
		
		foreach ($stats->stats as $stat)
		{
			$statsForLocale = array(
				'total' => $stat->total,
				'untranslated' => $stat->untranslated,
				'needReview' => $stat->needReview,
				'translated' => $stat->translated,
				'lastTranslated' => $stat->lastTranslated
			);
			
			$result[$stat->locale] = $statsForLocale;
			if ($destLocale !== '' && $stat->locale === $destLocale)
				return array($stat->locale => $statsForLocale);
		}
		
		return $result;
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
   * Return the curl crafter
   * @return ZanataApiCurlRequest
   */
  public function getZanataCurlRequest() {
    return $this->zanataCurlRequest;
  }
}


?>