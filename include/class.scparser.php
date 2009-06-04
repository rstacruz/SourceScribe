<?php

/*
 * Class: ScParser
 * Yeah!
 */
class ScParser
{
    var $Sc;
    
    /*
     * Function: ScParser()
     * Constructor. Called by Scribe::Scribe()
     */ 
    function ScParser(&$Sc)
    {
        $this->Sc =& $Sc;
    }
}