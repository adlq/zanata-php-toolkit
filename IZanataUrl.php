<?php
interface IZanataUrl
{
	/**
	 * Construct a base URL for the specific service (API, Webtrans)
	 * from the webserver host URL
	 *
	 * @param string $baseUrl URL where the Zanata instance is hosted (ends with 'zanata')
	 */
	public function __construct($baseUrl);
}
