<?php
require_once("POParser.php");
require_once("POEntry.php");

class POFile 
{
	private $entries;
	
	/**
	 * Constructor method
	 * 
	 * @param	$file	the PO/POT file to construct from
	 */
	
	public function __construct($file)
	{
		$parser = new POParser();
		$this->entries = $parser->parse($file);
	}

	/**
	 * Retrieve the file's entries
	 * 
	 * @param	$fromFiles	(Optional) Parameter to filter the entries 
	 *			with respect to the files they come from
	 * @return	An array containing the PO/POT file's entries
	 */
    public function getEntries($fromFiles = array())
    {
		$result = array();

		if (!empty($fromFiles)) 
		{
			foreach ($this->entries as $entry) 
			{
				// Extract the comments from each entry
				$comments = $entry->getComments();
				// If there's reference information 
				if (array_key_exists("reference", $comments))
				{
					// Loop over all the references
					foreach ($comments["reference"] as $reference)
					{
						// Retrieve the referenced file path
						if (preg_match("/(.+):/", $reference, $match))
						{
							$referencePath = $match[1];
							// If the file is included in the filter, we keep the string
							if (in_array($referencePath, $fromFiles))
							{
								array_push($result, $entry);
								// Break out of foreach
								break;
							}
						}
					}
				}
			}
			return $result;
		}
		
		// If there's no filter, return all the entries
        return $this->entries;
    }

	/**
	 * Retrieve the file's source strings
	 * 
	 * @param	$fromFiles	(Optional) Parameter to filter the entries 
	 *			with respect to the files they come from
	 * @return 	the file's source strings
	 */
	public function getSourceStrings($fromFiles = array())
	{
		$entries = $this->getEntries($fromFiles);
		$sourceStrings = array();
		foreach ($entries as $entry) 
		{
			if ($entry->getSource() !== '')
                array_push($sourceStrings, $entry->getSource());
		}
		return $sourceStrings;
	}

	/**
	 * Retrieve the file's fuzzy strings
	 * 
	 * @return	An array containing the file's fuzzy strings
	 */
	public function getFuzzyStrings()
	{
		$entries = $this->getEntries();
		$fuzzyStrings = array();
		
		foreach ($entries as $entry)
		{
			// Extract the comments from each entry
			if ($entry->isFuzzy())
				array_push($fuzzyStrings, $entry->getSource());
		}
		return $fuzzyStrings;
	}
	
	/**
	 * Retrieve the file's untranslated strings
	 * 
	 * @return	An array containing the file's untranslated strings
	 */
	public function getUntranslatedStrings()
	{
		$entries = $this->getEntries();
		$untranslatedStrings = array();
		
		foreach ($entries as $entry)
		{
			if (!$entry->isTranslated())
			{
				array_push($untranslatedStrings, $entry->getSource());
			}
		}
		
		return $untranslatedStrings;
	}
	
	/**
	 * Retrieve the file's translated strings
	 * 
	 * @return	An array containing the file's translated strings
	 */
	public function getTranslatedEntries()
	{
		$entries = $this->getEntries();
		$translatedStrings = array();
		
		foreach ($entries as $entry)
		{
			if ($entry->isTranslated())
			{
				array_push($translatedStrings, $entry);
			}
		}
		
		return $translatedStrings;
	}

	/**
	 * Retrieve the translation for a specified source string (or msgid)
	 * 
	 * @param	$str 	The msgid string
	 * @return	null if the string is not translated, 
	 *			its translation otherwise
	 */	
	public function getTranslation($str)
	{
		$entries = $this->getTranslatedEntries();
		
		foreach($entries as $entry)
		{
			if ($entry->getSource() === $str)
			{
				return $entry->getTarget();
			}
		}
		
		return null;
	}
	
	
	/**
	 * Output a raw representation of the PO/POT file or 
	 * the specified entries
	 * 
	 * @param	$entries	(Optional) The entries to output
	 */
    public function display($entries = array())
    {
		// If no entries are specified as parameter, display all of them
		$entries = empty($entries) ? $this->entries : $entries;
        foreach ($this->entries as $entry)
        {
			// Call the display() method of each entry
			$entry->display();
        }
    }
	
}

?>