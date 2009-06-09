<?php

/*
 * Class: ScReader
 * Yeah!
 */
 
class ScReader
{
    var $Sc;
    
    /*
     * Constructor: ScReader()
     * Constructor. Called by Scribe::Scribe()
     */
    function ScReader(&$Sc)
    {
        $this->Sc =& $Sc;
    }
    
    /*
     * Function: parse()
     * Parses a file.
     * 
     * References:
     *   Called by [[ScProject::build()]] on every file in the project.
     */
    function parse($path, $project)
    {
        return FALSE;
    }
}