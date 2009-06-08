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
}