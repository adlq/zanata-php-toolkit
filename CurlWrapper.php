<?php
/**
 * cURL Wrapper
 */
class CurlWrapper
{
  // cURL URL
  private $url;
  // cURL options
	private $options;
  // cURL handle
	private $handle;
  // cURL response code and their associated messages
	private $curlResponseMessages = array(
		'0' => 'Could not connect to server',
		'201' => 'Document created',
		'200' => 'Success',
		'404' => 'Project or project iteration not found',
		'403' => 'Update forbidden',
		'401' => 'Unauthorized',
		'500' => 'Internal server error');
  // Boolean indicating verbose mode or not
  private $verbose;
  // Boolean indicating debug mode or not
	private $debug;
  // String describing the purpose of the cURL call
  private $description;

	/**
	 * Constructor
	 * @param string	$url The cURL URL
	 * @param array		$options The cURL options
   * @param boolean $verbose True to enable verbose mode
   * @param boolean $debug True to enable debug mode
   * @param string 	$description Description of the cURL request (needed in verbose mode)
	 */
	public function __construct($url, $options,
      $verbose = false,
      $debug = false,
      $description = '')
	{
		if ($url !== '')
		{
      $this->verbose = $verbose;
      $this->debug= $debug;
      $this->description = $description;
			// Initialize the cURL handle with the crafted API URL
			$this->handle = curl_init($url);

			// Set cURL options
			$this->options = $options;
			curl_setopt_array($this->getHandle(), $this->getOptions());

			// Debug mode
			if ($debug) {
				curl_setopt($this->getHandle(), CURLOPT_NOPROGRESS, false);
				curl_setopt($this->getHandle(), CURLOPT_PROGRESSFUNCTION, array('CurlWrapper', 'progressCallback'));
			}

		}
	}

	/**
	 * Callback function for CURLOPT_PROGRESSFUNCTION
	 *
	 * @param int $download_size Total download size
	 * @param int $downloaded_size Current download size
	 * @param int $upload_size Total updoad size
	 * @param int $uploaded_size Current upload size
	 */
	function progressCallback($download_size, $downloaded_size, $upload_size, $uploaded_size)
	{

    if ($download_size == 0 && $upload_size == 0)
        $progress = 0;
    else if ($download_size != 0)
        $progress = round($downloaded_size * 100 / $download_size);
    else
        $progress = round($uploaded_size * 100 / $upload_size);

    $timestamp = time();
    echo "Progress at $timestamp: $progress %\n";
	}

	/**
	 * Close the cURL if the handle is of valid format
	 */
	public function __destruct()
	{
    if(gettype($this->getHandle()) == 'resource')
			curl_close($this->getHandle());
	}

	/**
	 * Execute the cURL call
	 * @return mixed	cURL response if the cURL call was successful, False otherwise
	 */
	public function fetch()
	{
		// Execute the cURL call
		$response = curl_exec($this->getHandle());
    $finalResponse = $response === '' ? true : $response;

		// Report the outcome to the user
		$result = $this->reportOutcome();

		if ($result)
		{
			return $finalResponse;
		}
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

		if ($this->debug)
		{
			$data = curl_getinfo($this->getHandle());
			print_r($data);
		}

    // If verbose mode is enabled, notify appropriately
    // with respect to the cURL response code
    if ($this->verbose)
    {
			// Default message
			$msg = 'Unknown HTTP response';

			if (isset($this->curlResponseMessages[strval($curlResponse)]))
				$msg = $this->curlResponseMessages[strval($curlResponse)];

      echo $this->getDescription()
          . ": $curlResponse-"
          . $msg
          . "\n";
    }

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

  /**
   * Return the cURL description text
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }


}
?>
