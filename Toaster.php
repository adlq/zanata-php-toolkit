<?php
require_once('pophp/POFile.php');
require_once('ZanataApiCurlRequest.php');

/**
 * Push source entries to a specified Zanata installation
 * given a POT file
 */
class Toaster
{
	// The project slug (ex: lms)
	private $projectSlug;
	// The iteration slug (ex: 1.0)
	private $iterationSlug;
	// Abosulte path to the resource (POT) file
	private $resourceFilePath;
	// The source locale
	private $sourceLocale;
	// The source document name (for Zanata)
	private $sourceDocName;
  // cURL request crafter
  private $zanataCurlRequest;
	
	/**
	 * Constructor
   * @param string $user        The user
	 * @param string $apiKey			The API key
	 * @param string $projectSlug		The project slug (short name)
	 * @param string $iterationSlug		The project version
	 * @param string $resourceFilePath	The absolute path to the POT file
	 * @param string $zanataHost		URL where the Zanata instance 
   *                              is hosted, must not end with '/'
	 */
	public function __construct(
      $user, 
      $apiKey, 
      $projectSlug, 
      $iterationSlug, 
      $resourceFilePath, 
      $baseUrl)
	{
		$this->projectSlug = $projectSlug;
		$this->iterationSlug = $iterationSlug;
		$this->resourceFilePath = $resourceFilePath;
		$this->sourceLocale = 'en-GB';
		
		// Extract the source document name from the absolute path
		$basename = basename($resourceFilePath);
		$this->sourceDocName = pathinfo($basename, PATHINFO_FILENAME);
		
		$this->zanataCurlRequest = new ZanataApiCurlRequest(
        $user, $apiKey, $baseUrl);
	}
	
	/**
	 * Push the source entries to Zanata, 
   * creating the project/project version if necessary
	 * 
	 * @return	boolean	  False if the push has succeeded, True otherwise
	 *			(hook exit code)
	 */
	public function launch() 
	{
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

    // Parse the resource (POT) file
    $potFile = new POFile($this->getResourceFilePath());

    // Retrieve its entries
    $potEntries = $potFile->getEntries();

    // Send the entries to Zanata
    return !($this->getZanataCurlRequest()->putSourceDoc(
        $this->getProjectSlug(), 
        $this->getIterationSlug(),
        $this->getSourceDocName(), 
        $this->getSourceLocale(), $potEntries));
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