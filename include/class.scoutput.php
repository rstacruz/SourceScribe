<?php

class ScOutput
{
    var $Project;
    var $options = array();
    
    function ScOutput(&$Project, $options = array())
    {
        $this->Project = &$Project;
        $this->options = $options;
    }   
    
    /*
     * Function: link()
     * Returns the link to a certain block.
     */
     
    function link(&$block)
    {
        return '';
    }
}