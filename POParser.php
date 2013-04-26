<?php
class POParser
{
	private $state;
	private $buffers;
	private $poEntries;
	const STATE_OBSOLETE = 0;
	const STATE_MSGCTXT = 1;
	const STATE_MSGID = 2;
	const STATE_MSGSTR = 3;
	
	/**
	 * Constructor method
	 */
	public function __construct()
	{
		$this->init();
		$this->poEntries = array();
	}
	
	/**
	 * Initialize the parser
	 */
	public function init()
	{
		$this->setState('');
		$this->buffers = array('source' => '', 'target' => '', 'context' => '', 'comments' => array());
	}
	
	/**
	 * Retrieve the parser's state
	 *
	 * @return	The parser's state
	 */
	public function getState()
	{
		return $this->state;
	}
	
	/**
	 * Update the parser's state
	 *
	 * @param	$state	The new state
	 */
	public function setState($state)
	{
		$this->state = $state;
	}
	
	/**
	 * Update the comment buffer
	 * 
	 * @param	$commentType	The comment type
	 *			$commentContent The comment body
	 */
	private function feedCommentBuffer($commentType, $commentContent)
	{
		$this->buffers['comments'][$commentType][] = $commentContent;
	}
	
	/**
	 * Update the context buffer
	 * 
	 * @param	$context	The context
	 */
	private function feedContextBuffer($context)
	{
		$this->buffers['context'] .= $context;
	}
	
	/**
	 * Update the msgid (source string) buffer
	 * 
	 * @param	$string	The msgid string
	 */
	private function feedSourceStringBuffer($string)
	{
		$this->buffers['source'] .= $string;
	}
	
	/**
	 * Update the msgstr (target string) buffer
	 * 
	 * @param	$string	The msgstr string
	 */
	private function feedTargetStringBuffer($string)
	{
		$this->buffers['target'] .= $string;
	}
	
	/**
	 * Update the parsed PO/POT entries array
	 */
	private function updatePOEntries()
	{
		if ($this->buffers['source'] !== '' || $this->buffers['target'] !== '')
		{
			array_push($this->poEntries, 
				new POEntry(
					$this->buffers['source'], 
					$this->buffers['target'], 
					$this->buffers['context'], 
					$this->buffers['comments']));
		}
	}
	
	/**
	 * Main parser function
	 * 
	 * @param	$file	The PO/POT file to be parsed
	 * @return	An array containing all the parsed entries
	 */
	public function parse($file) 
	{	
		$regexes = array(
		"comment" 	=> 	"/^#(.+)?\n/",
		"quote"		=>	"/^\"(.+)?\"/",
		"msgctxt" 	=>	"/^msgctxt \"(.*)?\"/",
		"msgid" 	=>	"/^msgid \"(.*)?\"/",
		"msgstr" 	=>	"/^msgstr \"(.*)?\"/");
	
		$lines = file($file);
		
		// Iterate through all the lines
		foreach ($lines as $line) 
		{
			// Preg matches
			$match = array();
			
			if (preg_match($regexes["comment"], $line, $match) && $this->getState() !== self::STATE_OBSOLETE && isset($match[1][0])) 
			{
				/**
				 * Comment parsing
				 */
				 
				// Switch on the character following the #, which defines the comment type
				switch ($match[1][0])
				{
					case ".":
						// Extracted comment
						$this->feedCommentBuffer('extracted', trim(substr($match[1], 2)));
						break;
					case ":":
						// Reference
						$this->feedCommentBuffer('reference', trim(str_replace('\\', '/', substr($match[1], 2))));
						break;
					case ",":
						// Flags
						$flags = explode(", ", substr($match[1], 2));
						foreach ($flags as $flag)
						{
							$this->feedCommentBuffer('flag', trim($flag));
						}
						break;
					case "|":
						// Previous comments
						$attr = array();
						preg_match("/(\w+)/", $match[1], $attr);
						$pos = strpos($match[1], "\"");
						$this->feedCommentBuffer('old' . ucfirst($attr[0]), trim(substr($match[1], $pos)));
						break;
					case "~":
						// Obsolete comments
						$this->setState(self::STATE_OBSOLETE);
						break;
					default:
						// Translator comments
						$this->feedCommentBuffer('translator', trim(substr($match[1], 1)));
						break;
				}
			}
			else if (preg_match($regexes["msgctxt"], $line, $match) && $this->getState() !== self::STATE_OBSOLETE)
			{
				/**
				 * Context parsing (msgctxt)
				 */
				$this->setState(self::STATE_MSGCTXT);
				if (isset($match[1]))
				{
					$this->feedContextBuffer($match[1]);
				}
			} 
			else if (preg_match($regexes["msgid"], $line, $match) && $this->getState() !== self::STATE_OBSOLETE)
			{
				/**
				 * Source strings parsing (msgid)
				 */
				$this->setState(self::STATE_MSGID);
				if (isset($match[1]))
				{
					$this->feedSourceStringBuffer($match[1]);
				}
			} 
			else if (preg_match($regexes["msgstr"], $line, $match) && $this->getState() !== self::STATE_OBSOLETE)
			{
				/**
				 * Target string parsing (msgstr)
				 */
				$this->setState(self::STATE_MSGSTR);
				if (isset($match[1]))
				{
					$this->feedTargetStringBuffer($match[1]);
				}
			} 
			else if (preg_match($regexes["quote"], $line, $match) && $this->getState() !== self::STATE_OBSOLETE)
			{
				/**
				 * This happens when 
				 */
				if ($this->getState() === self::STATE_MSGID)
				{
					$this->feedSourceStringBuffer($match[1]);
				}
				else if ($this->getState() === self::STATE_MSGSTR)
				{
					$this->feedTargetStringBuffer($match[1]);
				}
			}
			else
			{
				/**
				 * A blank line is found, we have to update the parsed
				 * entries array with the buffers' contents
				 */
				if ($this->getState() === self::STATE_MSGSTR)
				{
					$this->updatePOEntries();
					$this->init();
					$comments = array();
				}
			}
		}
		
		// Add the last entry
		$this->updatePOEntries();
		
        return $this->poEntries;
	}
}
?>
