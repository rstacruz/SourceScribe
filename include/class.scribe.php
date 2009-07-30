<?php

/*
 * Class: Scribe
 * The main singleton and front controller.
 * 
 * Description:
 *   This is instanciated as the global variable `$Sc`. This class is
 *   responsible for reading the configuration file. It also holds the 
 *   [[ScProject]] sub-singleton.
 * 
 * [Filed under "API reference"]
 */

class Scribe
{
    /* ======================================================================
     * Properties
     * ====================================================================== */
     
    /*
     * Property: $Project
     * The [[ScProject]] sub-singleton.
     */
     
    var $Project;
    
    /*
     * Property: $Readers
     * Key/value pairs of file reader drivers.
     * 
     * Description:
     *   Uhm
     * 
     * [Read-only]
     */
    var $Readers = array();
    
    /*
     * Property: $_config
     * Raw data from the scribe.conf file (after being YAML-parsed).
     * [Read-only]
     */
    var $_config;
    
    /* ======================================================================
     * Constructor
     * ====================================================================== */
     
    function Scribe($variant = NULL)
    {
        /* Function: Scribe()
         * Constructor.
         * 
         * [In group "Constructor"]
         */
         
        // Load config and stuff
        $this->Config     = new ScConfig($this, $variant);
        
        // Finally, initialize
        $this->cwd        = $this->Config->cwd;
        $this->Project    = new ScProject($this);
        $this->Readers['default'] = new DefaultReader($this);
    }
    
    /* ======================================================================
     * Methods
     * ====================================================================== */
     

    function& loadOutputDriver($driver, &$project, $options = array())
    {
        /* Function: loadOutputDriver()
         * Loads an output driver.
         *
         * Usage:
         *     $this->loadOutputDriver($driver[, $options])
         *
         * Returns:
         *   Driver on success, FALSE on failure.
         */
     
        // TODO: Proofing: This should make sure $driver is sanitized
        require_once SCRIBE_PATH . DS . 'include' . DS . "output.$driver.php";
        $classname = "{$driver}Output";

        if (!class_exists($classname))
            { return FALSE; }
            
        $output = new $classname($project, $options);
        return $output;
    }
    
    function findConfigFile()
    {
        /* Function: findConfigFile()
         * Tries to find the configuration file.
         *
         * Usage:
         *     $this->findConfigFile()
         *
         * Returns:
         *   The configuration file as a string if found, otherwise FALSE on failure.
         * 
         * References:
         *   Used by [[Scribe::Scribe()]].
         */

        $names = array('sourcescribe.conf', 'scribe.conf', 'ss.conf');
        $path = explode(DS, realpath(getcwd()));
        for ($i = count($path); $i >= 1; --$i)
        {
            $current_path = implode(DS, array_slice($path, 0, $i));
            foreach ($names as $name)
            {
                if (is_file($current_path . DS . $name))
                    { return realpath($current_path . DS . $name); }
            }
        }
        
        return FALSE;
    }
    /*
     * Function: loadState()
     * Stupid.
     */
     
    function loadState()
    {
        $path = $this->Project->cwd . DS . '.sourcescribe_index';
        if (is_file($path)) { return unserialize(file_get_contents($path)); }
        return;
    }
    
    /*
     * Function: _getDefaultOutput()
     * Returns the default output driver.
     * 
     * [Private]
     */
     
    function& _getDefaultOutput()
    {
        $output_key = array_keys($this->Project->options['output']);
        $output_key = $output_key[0];
        $output = $this->Project->options['output'][$output_key];
        return $output;
    }
    
    /* ======================================================================
     * End class
     * ====================================================================== */
}
