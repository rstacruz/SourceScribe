<?php

/*
 * Class: ScStatus
 * Status.
 */
 
class ScStatus
{
    function ScStatus()
    {
        $this->stderr = fopen('php://stderr', 'w');
    }
    
    /*
     * Function: getInstance()
     * Returns the instance for ScStatus.
     * [Static]
     */

    function& getInstance()
    {
        global $ScStatus;
        if (!$ScStatus) { $ScStatus = new ScStatus(); }
        return $ScStatus;
    }
    
    /*
     * Function: error()
     * Spits out an error and dies.
     * 
     * ## Description
     *    This is called by any function that needs to generate an error.
     * 
     * ## Example
     * 
     *     $Sc->error("Printer on fire!");
     * 
     * [Static, grouped under "Status update functions"]
     */
     
    function error($error)
    {
        $ScS =& ScStatus::getInstance();
        fwrite($ScS->stderr, "Scribe error: " . $error. "\n");
        exit();
    }
    
    /*
     * Function: notice()
     * Shows a warning message in stderr.
     * 
     * [Static, grouped under "Status update functions"]
     */
     
    function notice($message)
    {
        $ScS =& ScStatus::getInstance();
        fwrite($ScS->stderr, "* " . $message. "\n");
    }
    
    /*
     * Function: status()
     * Shows a status message in stderr.
     * 
     * [Static, grouped under "Status update functions"]
     */
     
    function status($msg)
    {
        $ScS =& ScStatus::getInstance();
        fwrite($ScS->stderr, $msg . "\n");
    }
}
