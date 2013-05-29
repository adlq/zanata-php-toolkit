<?php
/**
 * Craft the required Zanata API URL
 */
class ZanataApiUrl
{
	// Base URL of the Zanata instance
	private $baseUrl;
	
	/**
	 * Constructor
	 * @param string $baseUrl URL where the Zanata instance is hosted (ends with 'zanata')
	 */
	public function __construct($baseUrl)
	{
		// Adjust the base URL
		$this->baseUrl = $baseUrl . "/seam/resource/restv1";
	}
	
	/**
	 * Return the base URL
	 * @return string The base URL
	 */
	public function getBaseUrl()
	{
		return $this->baseUrl;
	}
	
	/**
	 * Craft the URL corresponding to the ProjectService endpoint
	 * @param string $projectSlug The project slug (short name)
	 * @return string The API URL
	 */
	public function projectService($projectSlug)
	{
		return $this->getBaseUrl() . "/projects/p/$projectSlug";
	}
	
	/**
	 * Craft the URL corresponding to the ProjectIterationService endpoint
	 * @param string  $projectSlug	The project slug (short name)
	 * @param string  $iterationSlug	The project version
	 * @return string The API URL
	 */
	public function projectIterationService(
      $projectSlug, 
      $iterationSlug)
	{
		return $this->projectService($projectSlug) 
        . "/iterations/i/$iterationSlug";
	}
	
	/**
	 * Craft the URL corresponding to the SourceDocResouceService endpoint
	 * @param string  $projectSlug	  The project slug (short name)
	 * @param string  $iterationSlug  The project version
	 * @param string	  $docName		  The document name
	 * @param boolean $ext Whether to specify the 'gettext' extension in the URL
	 * @return string The API URL
	 */
	public function sourceDocResourceService(
      $projectSlug, 
      $iterationSlug, 
      $docName,
			$ext = false)
	{
		$result = $this->projectIterationService($projectSlug, $iterationSlug) 
        . "/r/$docName";
		if ($ext)
		{
			$result .= '?ext=gettext';
		}
		return $result;
	}
	
	/**
	 * Craft the URL corresponding to the TranslatedDocResourceService endpoint
	 * @param string $projectSlug The project slug
	 * @param string $iterationSlug The project version
	 * @param string $docName The document name
	 * @param string $locale The locale
	 * @return string The API URL
	 */
	public function translatedDocResourceService(
			$projectSlug,
			$iterationSlug,
			$docName,
			$locale,
			$merge = '')
	{
		$result = $this->sourceDocResourceService(
				$projectSlug,
				$iterationSlug, 
				$docName) . "/translations/$locale";
		if ($merge !== '')
			$result .= "?merge=" . $merge;
		
		return $result;
	}
	
	/**
	 * Craft the URL corresponding to the StatisticsResource endpoint
	 * @param string $projectSlug The project name
	 * @param string $iterationSlug The project version
	 * @param string $docName The document name (optional)
	 * @return string The API URL
	 */
	public function statisticsResource(
			$projectSlug,
			$iterationSlug,
			$docName = '')
	{
		$result = $this->getBaseUrl() . "/stats/proj/$projectSlug/iter/$iterationSlug";
		if ($docName !== '')
			$result .= "/doc/$docName";
		
		return $result;
	}

}
?>
