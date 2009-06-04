<?php

/*
 * Class: ScBlock
 * A block
 */

class ScBlock
{
    var $_data;
    var $valid = FALSE;
    
    var $type;
    var $title;
    var $content;
    var $brief;
    
    function ScBlock($str)
    {
        global $Sc;
        
        // Get the lines
        $this->_data = $str;
        $lines = str_replace(array("\r\n","\r"), array("\n","\n"), $str);
        $this->_lines = explode("\n", $str);
        
        // Check: the first line has to have a type
        $title_line = $this->_lines[0];
        if (strpos($title_line, ':') === FALSE) { return; }
        
        // Get the type keyword.
        // For instance, in ("Class: MyClass"), it's 'class'
        // Then check if it exists in the defined type_keywords
        $type_str    = trim(substr($title_line, 0, strpos($title_line, ':')));
        $this->title = trim(substr($title_line, strpos($title_line, ':')+1, 99999)); 
        $type_str = trim(strtolower($type_str));
        $this->_lines = array_slice($this->_lines, 1);
        
        // Check: the first line has to have a *valid* type
        if (!in_array($type_str, array_keys($Sc->Options['type_keywords'])))
            { return; }
        
        $this->type = $Sc->Options['type_keywords'][$type_str];
        
        // If it can have a brief
        if ((isset($this->type['has_brief'])) && ($this->type['has_brief']))
        {
            // Look for a blank line
            $offset = array_search('', $this->_lines);
            if ($offset !== FALSE)
            {
                // Break at the first blank line
                $this->brief = array_slice($this->_lines, 0, $offset);
                $this->brief = $this->mkdn($this->brief);
                $this->_lines = array_slice($this->_lines, $offset+1);
            } else {
                // Everything is a brief description
                $this->brief = $this->mkdn($this->_lines);
                $this->_lines = array();
            }
        }
        
        $this->content = $this->mkdn($this->_lines);
        $this->valid = TRUE;
    }
    
    function getContent()
    {
        return $this->content;
    }
    
    function getTitle()
    {
        return $this->title;
    }
    
    function getBrief()
    {
        return $this->brief;
    }
    
    function mkdn($lines)
    {
        if (is_array($lines)) { $str = implode("\n", $lines); }
        else { $str = (string) $lines; }
        
        // Convert "Usage:" to H2's
        $str = preg_replace('~[\\r\\n]([A-Za-z0-9\- ]+):[\\r\\n]~s',
            "\n## \\1\n\n", $str);
        return markdown($str);
    }
}