<?php 

class POEntry 
{
	private $comments;
	private $context;
	private $source;
	private $target;
	
	/**
	 * Constructor method
	 * @param	$source		Source string (msgid)
	 *			$target		Target string (msgstr)
	 *			$context	(Optional) The entry's context (msgctxt)
	 *			$comments	(Optional) An array containing the entry's comments (#...)
	 */
	public function __construct($source, $target, $context = "", $comments = array())
	{
		$this->comments = $comments;
		$this->context = $context;
		$this->source = $source;
		$this->target = $target;
	}

	/**
	 * Retrieve the translated state of an entry
	 *
	 * @return	True is the entry is translated, False otherwise
	 */
	public function isTranslated()
	{
		return ($this->getTarget() !== '');
	}
	
	/**
	 * Retrieve the fuzzy state of an entry
	 *
	 * @return True if the entry is fuzzy, False otherwise
	 */
	public function isFuzzy()
	{
		// Extract the comments from each entry
		$comments = $this->getComments();
		if (!empty($comments))
		{
			// The fuzzy status is specified amongst the flags
			if (array_key_exists('flag', $comments))
			{
				// Examine each flag
				foreach ($comments['flag'] as $flag)
				{
					if (trim($flag) == 'fuzzy')
						return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Retrieve the comments associated to an entry
	 *
	 * @return	An array containing the entry's comments
	 */
    public function getComments()
    {
        return $this->comments;
    }

	/**
	 * Retrieve an entry's context
	 *
	 * @return	The entry's context, in string format
	 */
    public function getContext()
    {
        return $this->context;
    }

	/**
	 * Retrieve the source string (msgid) of an entry
	 *
	 * @return	The msgid, in string format
	 */
    public function getSource()
    {
        return $this->source;
    }

	/**
	 * Retrieve the target string (msgstr) of an entry
	 *
	 * @return	The msgstr, in string format
	 */
    public function getTarget()
    {
        return $this->target;
    }
	
	/**
	 * Display the entry, in standard gettext format
	 */
	public function display() 
	{
		// Display comments first
		$comments = $this->getComments();            
		foreach ($comments as $type => $comment) 
		{
			// Display comments
			switch ($type)
			{
				case "translator":
					foreach ($comment as $translatorComment)
					{
						echo "# $translatorComment\n";
					}
					break;
				case "extracted":
					foreach ($comment as $extracted)
					{
						echo "#. $extracted\n";
					}
					break;
				case "reference":
					foreach ($comment as $ref)
					{
						echo "#: " . str_replace('/', '\\', $ref) . "\n";
					}
					break;
				case "flag":
					echo "#, ";
					foreach ($comments['flag'] as $id => $flag)
					{
						if ($id === 0)
						{
							echo "$flag"; 
						} 
						else 
						{
							echo ", $flag";
						}
					}
					echo "\n";
					break;
				default:
					foreach ($comment as $old)
					{
						echo "#| $old\n";
					}
					break;
			}
		}

		// Context
		$context = $this->getContext();
		if ($context != "") 
			echo "msgctxt \"" . $context . "\"\n";

		// msgid
		$source = $this->getSource();
		echo 'msgid ';
		$this->displayWithLineBreak($source);

		// msgstr 
		$target = $this->getTarget();
		echo 'msgstr ';
		if ($this->isTranslated())
		{
			$this->displayWithLineBreak($target);
		} 
		else 
		{
			echo "\"\"\n";
		}
		echo "\n";
	}
	
	
    private function displayWithLineBreak($str)
    {
		// Only perform this if the string is not empty
		if ($str !== '')
		{
			// Offset to be used with strpos
			$offset = 0;
			
			// Find first occurence of line break in the string
			$break = strpos($str, '\n', $offset) !== false;
			
			// If there is no line break, simply print out the string
			if ($break == false)
			{
				echo "\"$str\"\n";
			}
			else 
			{
				// Otherwise, we break lines till there are no more to break
				while ($break !== false)
				{
					$break = strpos($str, '\n', $offset);
					if ($break !== false)
					{
						echo "\"" . substr($str, $offset, $break - $offset) . '\n' . "\"\n";
						$padding = strlen('\n');
						$offset = $break + $padding > strlen($str) ? strlen($target) - 1 : $break + $padding; 
					}
				}
				if ($offset !== strlen($str))
					echo "\"" . substr($str, $offset) . "\"\n";
			}
		}
    } 
}

?>