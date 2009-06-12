<?php

/*
 * Class: ScController
 * The front controller.
 * 
 * [Filed under "API reference"]
 */
 
class ScController
{
    var $Sc = NULL;
    
    function ScController() {} //&$Sc)
        //{ $this->Sc =& $Sc; }
        
    function& getSc()
    {
        if (!is_null($this->Sc)) { return $this->Sc; }
        $this->Sc = new Scribe();
        return $this->Sc;
    }
    
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
            { ScStatus::error("Unknown command: " . $args[0]); return; }
            
        $this->{'do_'.$args[0]}(array_slice($args, 1));
    }
    
    /* ======================================================================
     * Command-line actions
     * ====================================================================== */
    
    
    /*
     * Function: do_build()
     * Does a build.
     * 
     * [Grouped under "Command-line actions"]
     */
     
    function do_build($args = array())
    {
        $Sc =& $this->getSc();
        $Sc->Project->build();
    }
    
    /*
     * Function: do_version()
     * Shows the version.
     * 
     * [Grouped under "Command-line actions"]
     */
     
    function do_version($args = array())
    {
        echo "SourceScribe " . SCRIBE_VERSION . "\n";
    }
    
    /*
     * Function: do_url()
     * Shows the file location of the default documentation.
     * 
     * [Grouped under "Command-line actions"]
     */

    function do_url($args = array())
    {
        $Sc =& $this->getSc();
        
        // Initialize options
        $show_all = FALSE; $show_info = FALSE; $show_html = FALSE;
        while (TRUE)
        {
            if     ($args[0] == '-all')   { $show_all = TRUE; }
            elseif ($args[0] == '-info')  { $show_info = TRUE; }
            elseif ($args[0] == '-html')  { $show_html = TRUE; }
            else { break; }
            array_shift($args); 
        }
        
        $str = trim(implode(' ', $args));
        
        $output = $Sc->_getDefaultOutput();
        $path = $Sc->Project->cwd . DS . $output['path']; // Output path
        $return = '';
        
        // Check if documentation has already been written
        if (!is_dir($path)) {
            ScStatus::error("Can't find the output documentation. Try building it first.");
            return;
        }

        // Load output drivers, die if it fails    
        $Sc->Project->_loadOutputDrivers();
        if (!$Sc->Project->outputs[0])
            { return ScStatus::error("No default output. Boo"); }

        // Do we need to do a lookup?
        if ($str != '') 
        {
            // Load the statefile and look it up using it; die if it fails
            $ScX = $Sc->loadState();
            $results = $ScX->Project->lookup($str); // returns an array of ScBlock
            if (count($results) == 0)
                { return ScStatus::error("Sorry, no matches for \"$str\"."); }

            if ($show_html) { $this->_showHtmlResults($str, $path, $results); return; }
            
            // If only one is requested, discard the other results
            if (!$show_all) { $results = array($results[0]); }

            foreach ($results as $result)
            {
                if ($show_info)
                    { echo $result->getLongTitle() . "\n"; }
                echo "file://" . realpath($path) . "/" .
                     $Sc->Project->outputs[0]->link($result) . "\n";
            }
            
            return;
        }
        else
        {
            // Optimization: don't load statefile if HTML
            $return = realpath($path . DS . 'index.html');
            if (!is_file($return))
                { return ScStatus::error("Can't find the output documentation. Try building it first."); }
            echo "file://" . $return . "\n";
            return;
        }
    }
    
    /*
     * Function: do_open()
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
     * Function: do_help()
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
        echo "  html         Shows an HTML snippet of a specific keyword\n";
        echo "  url          Shows the documentation's URL\n";
        echo "  makeconfig   Create default configuration\n";
        echo "  configinfo   Shows the options as loaded in the configuration\n";
        echo "  help         Shows this help screen\n";
    }
    
    /*
     * Function: do_makeconfig()
     * Outputs a default configuration file.
     */

    function do_makeconfig()
    {
        ob_start(); @include SCRIBE_PATH . '/include/misc.defaultconfig.php';
        $contents = ob_get_clean();
        $output_fname = getcwd() . DS . 'sourcescribe.conf';
        if (is_file($output_fname))
            { return ScStatus::error("A configuration file already exists!"); }
            
        file_put_contents($output_fname, $contents);
    }
    
    /*
     * Function: do_html()
     * Shows an HTML snippet of a specific keyword.
     * [Grouped under "Command-line actions"]
     */

    function do_html($args = array())
    {
        return $this->do_url(array_merge(array('-html'), $args));
    }
    
    /*
     * Function: do_configinfo()
     * Checks configuration info
     */

    function do_configinfo()
    {
        $Sc =& $this->getSc();
        print_r(Spyc::YAMLDump($Sc->Project->options));
    }
    
    /*
     * Function: _showHtmlResults()
     * Shows results in HTML format. Delegate of [[do_url()]].
     * [Grouped under "Private functions"]
     */
     
    // Scribe
    function _showHtmlResults($keyword, $path, $results)
    {
        if (count($results) == 0) { return; }
        elseif (count($results) == 1)
        {
            $url = "file://" . realpath($path) . "/" .
                 $this->Sc->Project->outputs[0]->link($results[0]);
            echo "<meta http-equiv='Refresh' content='0;URL=$url'>";
            return;
        }
        
        echo "<html><head>\n";
        echo '<link rel="stylesheet" href="file://' . $path . '/assets/style.css" media="all" />' . "\n";
        echo "</head><body>\n";
        echo "<div id=\"disambiguation\">\n";
        echo "<h1><code>$keyword</code> may refer to:</h1>\n";
        echo "<ul>\n";
        
        foreach ($results as $result)
        {
            $url = "file://" . realpath($path) . "/" .
                 $this->Sc->Project->outputs[0]->link($result);
            $title = htmlentities($result->getTitle());
            $desc = "";
            if ($result->hasParent()) {
                $parent =& $result->getParent();
                $desc = " a " . strtolower($result->getTypeName()) . " of " . $parent->getTitle();
            }
            echo "<li><a href=\"$url\"><strong>$title</strong>$desc</a></li>\n";
        }
        
        echo "</ul>\n";
        echo "</div>\n";
        echo "</body></html>\n";
    }
}