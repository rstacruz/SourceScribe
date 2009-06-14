<?php

/*
 * Class: ScReader
 * Yeah!
 * 
 * 
 * [Filed under "API reference"]
 */
 
class ScReader
{
    var $Sc;
    
    /*
     * Constructor: ScReader()
     * Constructor. Called by Scribe::Scribe()
     * Go [[parse]] something.
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