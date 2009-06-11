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
     *     ScStatus::error("Printer on fire!");
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
     
    function status($msg, $newline = TRUE)
    {
        $ScS =& ScStatus::getInstance();
        fwrite($ScS->stderr, $msg . (($newline) ? "\n" : ""));
    }
    
    function updateStart($msg1)
    {
        $ScS =& ScStatus::getInstance();
        $ScS->msg1 = $msg1;
        $msg = ' * ' . $msg1 . '...';
        $msg .= str_repeat(' ',79-strlen($msg));
        fwrite($ScS->stderr, $msg . "\r");
    }
    
    function update($msg2)
    {
        $ScS =& ScStatus::getInstance();
        if (!isset($ScS->msg1)) { return; }
        $msg = ' * ' . $ScS->msg1 . '...' . str_repeat(' ',25-(strlen($ScS->msg1)+2)) . $msg2;
        $msg .= str_repeat(' ',79-strlen($msg));
        fwrite($ScS->stderr, $msg . "\r");
    }
    
    function updateDone($msg2)
    {
        $ScS =& ScStatus::getInstance();
        if (!isset($ScS->msg1)) { return; }
        $msg = ' * ' . $ScS->msg1 . ':' . str_repeat(' ',25-strlen($ScS->msg1)) . $msg2;
        $msg .= str_repeat(' ',79-strlen($msg));
        fwrite($ScS->stderr, $msg . "\n");
        unset($ScS->msg1);
    }
}
