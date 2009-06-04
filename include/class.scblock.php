<?php

class ScBlock
{
    var $_data;
    var $valid = FALSE;
    
    var $type;
    var $title;
    var $content;
    
    function ScBlock($str)
    {
        global $Sc;
        
        // Get the lines
        $this->_data = $str;
        $lines = str_replace(array("\r\n","\r"), array("\n","\n"), $str);
        $this->_lines = explode("\n", $str);
        
        $title_line = $this->_lines[0];
        if (strpos($title_line, ':') === FALSE) { return; }
        $this->type  = trim(substr($title_line, 0, strpos($title_line, ':')+1));
        $this->title = trim(substr($title_line, strpos($title_line, ':')+1, 99999)); 
        
        $this->content = $this->mkdn(array_slice($this->_lines, 1));
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
    
    function mkdn($lines)
    {
        if (is_array($lines)) { $str = implode("\n", $lines); }
        else { $str = (string) $lines; }
        return markdown($str);
    }
}