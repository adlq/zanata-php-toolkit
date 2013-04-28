<?php
/**
 * cURL Wrapper
 */
class CurlWrapper
{
  private $url;
	private $options;
	private $handle;
	private $curlResponseMessages = array(
		'201' => 'Document created',
		'200' => 'Success',
		'404' => 'Project or project iteration not found',
		'403' => 'Update forbidden',
		'401' => 'Unauthorized',
		'500' => 'Internal server error');
	
	/**
	 * Constructor
	 * @param string	$url
	 * @param array		$options
	 */
	public function __construct($url, $options)
	{
		if ($url !== '')
		{
			// Initialize the cURL handle with the crafted API URL
			$this->handle = curl_init($url);

			// Set cURL options
			$this->options = $options;
			curl_setopt_array($this->getHandle(), $this->getOptions());
		}
	}
	
	/**
	 * Execute the cURL call
	 * @return mixed	cURL response if the cURL call was successful, False otherwise
	 */
	public function fetch()
	{
		// Execute the cURL call
		$response = curl_exec($this->getHandle());
    $response = $response === '' ? true : $response;
    
		// Report the outcome to the user
		$result = $this->reportOutcome();
		
		// Close the cURL handle
		curl_close($this->getHandle());
    
		if ($result)
      return $response;
		return $result;
	}
	
  /**
   * Given the HTTP response code,
   * Notify the user of the cURL outcome with the appropriate message
   * 
	 * @return boolean	True if the cURL call was successful, False otherwise
	 */
	public function reportOutcome()
	{
		// Retrieve the cURL response
		$curlResponse = curl_getinfo($this->getHandle(), CURLINFO_HTTP_CODE);
		
		// Notify appropriately with respect to the cURL response code
		echo "$curlResponse-" . $this->curlResponseMessages[strval($curlResponse)] . "\n";
		
		return ($curlResponse === 200 || $curlResponse === 201);
	}
	
	/**
	 * Return the url
	 * @return string	
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * Return the options array
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * Return the cURL handle
	 * @return resource
	 */
	public function getHandle()
	{
		return $this->handle;
	}
}
?>
