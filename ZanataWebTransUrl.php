<?php
require_once('IZanataUrl.php');
/**
 * Craft the required Zanata API URL
 */
class ZanataWebTransUrl implements IZanataUrl
{
	// Base URL of the Zanata instance
	private $baseUrl;

	/**
	 * Construct the webtrans-specific base URL out of the
	 * webserver URL.
	 *
	 * @param string $baseUrl URL where the Zanata instance is hosted (ends with 'zanata')
	 */
	public function __construct($baseUrl)
	{
		// Adjust the base URL
		$this->baseUrl = $baseUrl . "/webtrans/translate?";
	}

	/**
	 * Craft the URL for the dashboard of the specified
	 * project iteration in the given locale
	 *
	 * @param string $project The project slug
	 * @param string $iteration The iteration slug
	 * @param string $localeId The locale id
	 * @param string $locale The locale shortname
	 *
	 * @return string
	 */
	public function goToProjectIterationLocale($project, $iteration, $localeId, $locale)
	{
		return $this->baseUrl . "project=$project&iteration=$iteration&localeId=$localeId&locale=$locale";
	}

	/**
	 * Craft the URL leading to the WebTrans panel
	 * for the specified source document in the given locale
	 *
	 * @param string $project The project slug
	 * @param string $iteration The iteration slug
	 * @param string $localeId The locale id
	 * @param string $locale The locale shortname
	 * @param string $doc The source document name
	 *
	 * @return string
	 */
	public function goToDoc($project, $iteration, $localeId, $locale, $doc)
	{
		return $this->goToProjectIterationLocale($project, $iteration, $localeId, $locale) . "#view:doc;doc:$doc;";
	}

	/**
	 * Craft the URL leading to the WebTrans panel
	 * for the specified source document in the given locale.
	 * The cursor will automatically be focusing the given textflow
	 *
	 * @param string $project The project slug
	 * @param string $iteration The iteration slug
	 * @param string $localeId The locale id
	 * @param string $locale The locale shortname
	 * @param string $doc The source document name
	 * @param int $textflowId The textflow's id
	 *
	 * @return string
	 */
	public function goToTextflow($project, $iteration, $localeId, $locale, $doc, $textflowId)
	{
		return $this->goToDoc($project, $iteration, $localeId, $locale, $doc) . "textflow:$textflowId";
	}
}
?>
