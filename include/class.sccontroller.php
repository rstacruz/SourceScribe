<?php

/*
 * Class: ScController
 * The front controller.
 */
 
class ScController
{
    var $Sc;
    
    function ScController(&$Sc)
        { $this->Sc =& $Sc; }
        
    /*
     * Function: go()
     * Starts the controller.
     * 
     * ## Description
     *    This function is called by the bootstrapper.
     */
    function go()
    {
        $args = array_slice($_SERVER['argv'], 1);
        if (count($args) == 0) { $args = array('build'); }
        
        if (!is_callable(array($this, 'do_'.$args[0])))
            { $this->error("Unknown command: " . $args[0]); return; }
            
        $this->{'do_'.$args[0]}(array_slice($args, 1));
    }
    
    /* ======================================================================
     * Command-line actions
     * ====================================================================== */
    
    
    /*
     * Function: do_build
     * Does a build.
     * 
     * [Grouped under "Command-line actions"]
     */
     
    function do_build($args = array())
    {
        $this->Sc->Project->build();
    }
    
    /*
     * Function: version
     * Shows the version.
     * 
     * [Grouped under "Command-line actions"]
     */
     
    function do_version($args = array())
    {
        echo "SourceScribe\n";
    }
    
    /*
     * Function: url
     * Shows the file location of the default documentation.
     * 
     * [Grouped under "Command-line actions"]
     */

    function do_url($args = array())
    {
        $Sc =& $this->Sc;
        $str = trim(implode(' ', $args));
        
        $output = $Sc->_getDefaultOutput();
        $path = $Sc->Project->cwd . DS . $output['path']; // Output path
        $return = realpath($path . DS . 'index.html');
        
        // Check if documentation has already been written
        if (!is_dir($path)) {
            $Sc->error("Can't find the output documentation. Try building it first.");
            return;
        }

        // TODO: do_url() is very rudimentary
        if ($str != '') 
        {
            $ScX = $Sc->loadState();
            $results = $ScX->Project->lookup($str);
            if (count($results) > 0)
            {
                $return = realpath($path . DS . $results[0]->getID() . '.html');
                if (!is_file($return)) {
                    $Sc->error("Can't find the output documentation. Try building it first.");
                    return;
                }
            }
            else {
                return $Sc->error("Can't find your keyword.");
                $return = '';
            }
        }
        
        echo $return . "\n";
    }
    
    /*
     * Function: open
     * Opens the default documentation in the browser.
     * 
     * [Grouped under "Command-line actions"]
     */
     
    function do_open($args = array())
    {
        ob_start();
        $this->do_url($args);
        $output = trim((string) ob_get_clean());
        if ($output == '') return;
        $path = $output;
        
        // For Mac OSX
        exec('open ' . escapeshellarg($path), $o, $return);
        if ($return == 0) { return; }
        
        // Retry for Linux
        exec('xdg-open ' . escapeshellarg($path), $o, $return);
        if ($return == 0) { return; }

        // Retry for Windows
        exec('start ' . escapeshellarg($path), $o, $return);
        if ($return == 0) { return; }

        // Give up
    }
    
    /*
     * Function: help
     * Shows help.
     * 
     * [Grouped under "Command-line actions"]
     */
     
    function do_help($args = array())
    {
        echo "SourceScribe\n";
        echo "Usage: ss [command] [options]\n";
        echo "Commands:\n";
        echo "  build        Builds documentation\n";
        echo "  open         Opens the documentation in the browser\n";
        echo "  url          Shows the documentation's URL\n";
        echo "  help         Shows this help screen\n";
    }
    
}