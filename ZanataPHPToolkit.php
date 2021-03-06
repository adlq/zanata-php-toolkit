<?php
require_once('conf.php');
require_once($GLOBALS['paths']['pophp'] . 'POFile.php');
require_once('ZanataApiCurlRequest.php');
require_once('ZanataWebTransUrl.php');

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
	// WebTrans URL crafter
	private $zanataWebTransUrl;
	// Db Handle
	private $dbHandle;

	/**
	 * Constructor
   * @param string $user        The user
	 * @param string $apiKey			The API key
	 * @param string $projectSlug		The project slug (short name)
	 * @param string $iterationSlug		The project version
	 * @param string $baseUrl		URL where the Zanata instance
   * is hosted, must not end with '/'
	 * @param boolean $verbose Print out debug info
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
	 * @param string $sourceDocName Name of the source document to push to
	 * @param string $sourceLocale The source locale
	 * @return	boolean	  True if the push has succeeded, False otherwise
	 *
	 */
	public function pushPotEntries($resourceFilePath, $sourceDocName, $sourceLocale)
	{
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
	 * @return boolean True if the push has succeeded, False otherwise
	 *
	 */
	public function pushTranslations(
			$resourceFilePath,
			$sourceDocName,
			$destLocale)
	{
    // Parse the resource (PO) file
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

	/**
	 * Return a DB handle
	 *
	 * @return PDO
	 */
	public function createDbHandle()
	{
		$host = $GLOBALS['conf']['zanata']['db']['host'];
		$user = $GLOBALS['conf']['zanata']['db']['user'];
		$password = $GLOBALS['conf']['zanata']['db']['password'];
		$dbname = $GLOBALS['conf']['zanata']['db']['dbname'];

		$this->dbHandle = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
	}

	/**
	 * Return the textflow id for a given source string
	 * in a given source document
	 *
	 * @param string $str The string
	 * @param string $doc The source document name
	 *
	 * @return bool The id (int) if it exists, false otherwise
	 */
	public function getTextflowId($str, $doc)
	{
		if (!isset($this->dbHandle))
			$this->createDbHandle();

		$sql = 'SELECT tf.potEntryData_id, tf.content0
									FROM HTextFlow as tf
									INNER JOIN HDocument AS doc
										ON tf.document_id = doc.id
									INNER JOIN HProjectIteration AS it
										ON it.id = doc.project_iteration_id
									INNER JOIN HProject AS project
										ON project.id = it.project_id
									WHERE doc.name LIKE :doc
									AND tf.obsolete = 0
									AND project.slug LIKE :project
									AND it.slug LIKE :iteration
									AND tf.content0 LIKE :string
									LIMIT 0,1';

		$query = $this->dbHandle->prepare($sql);
		$query->bindParam(':project', $this->projectSlug);
		$query->bindParam(':iteration', $this->iterationSlug);
		$query->bindParam(':doc', $doc);
		$query->bindParam(':string', $str);

		$query->execute();

		$row = $query->fetch(PDO::FETCH_ASSOC);

		if (count($row) < 1)
			return false;
		else
			return $row['potEntryData_id'];
	}

	/**
	 *
	 * @param $string
	 * @param $localeId
	 * @param $locale
	 * @param $doc
	 *
	 * @return string
	 */
	public function getTextflowWebTransUrl($string, $localeId, $locale, $doc)
	{
		// Generate Zanata URL for each added string
		$resId = $this->getTextFlowId($string, $doc);

		if ($resId === false)
			return '';

		if (!isset($this->zanataWebTransUrl))
			$this->zanataWebTransUrl = new ZanataWebTransUrl($GLOBALS['conf']['zanata']['url']);

		return $this->zanataWebTransUrl->goToTextflow($this->projectSlug, $this->iterationSlug, $localeId, $locale, $doc, $resId);
	}
}


?>