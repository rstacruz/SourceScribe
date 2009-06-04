<?php

/*
 * Class: Screader
 * Yeah!
 */
class Screader
{
    var $Sc;
    
    /*
     * Function: Screader()
     * Constructor. Called by Scribe::Scribe()
     */ 
    function Screader(&$Sc)
    {
        $this->Sc =& $Sc;
    }
}