<?php
//
// XML Library, by Keith Devens, version 1.2b
// http://keithdevens.com/software/phpxml
// 
// This code is Open Source, released under terms similar to the Artistic License.
// Read the license at http://keithdevens.com/software/license
//

//
// XML_unserialize: takes raw XML as a parameter (a string)
// and returns an equivalent PHP data structure
//
function & XML_unserialize($xml)
{
	$xml_parser = new XML();
	$data = $xml_parser->parse($xml);
	$xml_parser->destruct();
	return $data;
}

//
// XML_serialize: serializes any PHP data structure into XML
// Takes one parameter: the data to serialize. Must be an array.
//
function & XML_serialize(&$data, $level = 0, $prior_key = NULL)
{
	if (!$level)
	{
		ob_start();
		echo '<?xml version="1.0" ?>',"\n";
	}
	
	foreach ($data as $key => $value)
	{
		if (!strpos($key, ' attr')) { } #if it's not an attribute
		#we don't treat attributes by themselves, so for an empty element
		# that has attributes you still need to set the element to NULL
		
		if (is_array($value) && array_key_exists(0, $value))
		{
			XML_serialize($value, $level, $key);
		}
		else
		{
			$tag = $prior_key ? $prior_key : $key;
			echo str_repeat("\t", $level) . '<' . $tag;
			
			if (array_key_exists("$key attr", $data)) // If there's an attribute for this element
			{
				foreach ($data[$key . ' attr'] as $attr_name => $attr_value)
				{
					echo ' ' . $attr_name . '="' . htmlspecialchars($attr_value) . '"';
				}
				
				if (is_null($value))
				{
					echo " />\n";
				}
				else if (!is_array($value))
				{
					echo '>' . htmlspecialchars($value) . "</$tag>\n";
				}
				else
				{
					echo ">\n" . XML_serialize($value, $level + 1) . str_repeat("\t", $level) . "</$tag>\n";
				}
			}
		}
		
		if (!$level)
		{
			$str = &ob_get_contents();
			ob_end_clean();
			return $str;
		}
	}
}

//
// XML class: utility class to be used with PHP's XML handling functions
//
class XML
{
	var $parser;   				#a reference to the XML parser
	var $document; 				#the entire XML structure built up so far
	var $parent;   				#a pointer to the current parent - the parent will be an array
	var $stack;    				#a stack of the most recent parent at each nesting level
	var $last_opened_tag; #keeps track of the last tag opened.

	function XML()
	{
 		$this->parser = xml_parser_create();
		xml_parser_set_option(&$this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_parser_set_option(&$this->parser, XML_OPTION_TARGET_ENCODING, 'ISO-8859-1');
		xml_set_object(&$this->parser, &$this);
		xml_set_element_handler(&$this->parser, 'open','close');
		xml_set_character_data_handler(&$this->parser, 'data');
	}
	
	function destruct()
	{
		xml_parser_free(&$this->parser);
	}
	
	function parse($data)
	{
		$this->document = array();
		$this->stack = array();
		$this->parent = &$this->document;
		return xml_parse($this->parser, $data, true) ? $this->document : NULL;
	}
	
	function open(&$parser, $tag, $attributes)
	{
		$this->data = ''; #stores temporary cdata
		$this->last_opened_tag = $tag;
		
		// If you've seen this tag before
		if (is_array($this->parent) && array_key_exists($tag,$this->parent))
		{
			// If the keys are numeric
			if (is_array($this->parent[$tag]) && array_key_exists(0, $this->parent[$tag]))
			{
				// This is the third or later instance of $tag we've come across
				$key = $this->numeric_items($this->parent[$tag]);
			}
			else
			{
				// This is the second instance of $tag that we've seen. shift around
				if (array_key_exists("$tag attr",$this->parent))
				{
					$arr = array('0 attr'=>&$this->parent["$tag attr"], &$this->parent[$tag]);
					unset($this->parent["$tag attr"]);
				}
				else
				{
					$arr = array(&$this->parent[$tag]);
				}
				
				$this->parent[$tag] = &$arr;
				$key = 1;
			}
			
			$this->parent = &$this->parent[$tag];
		}
		else
		{
			$key = $tag;
		}
		
		if ($attributes)
		{
			$this->parent["$key attr"] = $attributes;
		}
		$this->parent  = &$this->parent[$key];
		$this->stack[] = &$this->parent;
	}
	
	function data(&$parser, $data)
	{
		// You don't need to store whitespace in between tags
		if ($this->last_opened_tag != NULL)
		{
			$this->data .= $data;
		}
	}
	
	function close(&$parser, $tag)
	{
		if ($this->last_opened_tag == $tag)
		{
			$this->parent = $this->data;
			$this->last_opened_tag = NULL;
		}
		
		array_pop($this->stack);
		if ($this->stack)
		{
			$this->parent = &$this->stack[count($this->stack) - 1];
		}
	}
	
	function numeric_items(&$array)
	{
		return is_array($array) ? count(array_filter(array_keys($array), 'is_numeric')) : 0;
	}
}

?>