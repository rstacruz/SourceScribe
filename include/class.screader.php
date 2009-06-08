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
}