<?php

/*
 * Class: DefaultParser
 * The default parser.
 */
class DefaultParser extends ScParser
{
    /*
     * Function: parse()
     * Called by ScProject::build().
     */
    function parse($path, $project)
    {
        $file = file_get_contents($path);
        $single_char = "(?://|#)";
        $r_singles = "(?:[\\r\\n^][ \\t]*{$single_char}[ ]*(.+)){2,}";
        preg_match_all("~$r_singles~", $file, $m1);
        $this->_cleanSingle($m1[0]);
        
        $r_blocks = '(?:/\\*((?:.|[\\r\\n])*?)\\*/)';
        preg_match_all("~$r_blocks~", $file, $m2);
        $this->_cleanBlock($m2[0]);
        
        if ($m1 != array(array(),array())) {
            print_r($m1[0]);
        }
        if ($m2 != array(array(),array())) {
            print_r($m2[0]);
        }
    }
    
    function _cleanSingle($string)
    {
        return $string;
    }
    
    function _cleanBlock($string)
    {
        return $string;
    }
}