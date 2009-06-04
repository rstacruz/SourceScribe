<?php

/*
 * Class: DefaultReader
 * The default reader.
 */
class DefaultReader extends ScReader
{
    /*
     * Function: parse()
     * Parses a file.
     * 
     * Description:
     *   Called by ScProject::build().
     */
    function parse($path, $project)
    {
        $blocks = array();
        
        // Get contents, and find comment blocks.
        $file = file_get_contents($path);
        $single_char = "(?://[/!]?|#)";
        $r_singles = "(?:[\\r\\n^][ \\t]*{$single_char}[ ]*.*){2,}";
        $r_blocks = '(?:/\\*(?:.|[\\r\\n])+?\\*/)';
        
        preg_match_all("~($r_singles)|($r_blocks)~", $file, $m3);
        foreach ($m3[0] as $k => $block_text)
        {
            if ($m3[0][$k] == $m3[1][$k]) // Single
                { $block_text = $this->_cleanSingle($block_text); }
            else // Multiline
                { $block_text = $this->_cleanBlock($block_text); }
            
            // Make it (let ScBlock parse it)
            $block = new ScBlock($block_text);
            if ($block->valid) { $blocks[$block->id] = $block; }
        }
        
        return $blocks;
    }
    
    // Input are arrays
    function _cleanSingle($string)
    {
        $string = preg_replace('~^[ \\t]*(?://|#) ?~sm', '', $string);
        $string = trim($string);
        return $string;
    }
    
    // Input are arrays
    function _cleanBlock($string)
    {
        $string = preg_replace('~^[ \\t]*/?\\*\\**!*(?: |/$)?~sm', '', $string);
        $string = trim($string);
        return $string;
    }
}