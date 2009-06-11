<?php

/*
 * Class: Scribe
 * The main singleton and front controller.
 * 
 * Description:
 *   This is instanciated as the global variable `$Sc`. This class is
 *   responsible for reading the configuration file. It also holds the 
 *   [[ScProject]] sub-singleton.
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
     * Property: $Controller
     * The [[ScController]] sub-singleton.
     */
     
    var $Controller;
    
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
     * Property: $defaults
     * An array containing defaults for [[ScProject::$options]].
     */
     
    var $defaults = array
    (   
        'block_types' => array
        (
            'page' => array(
                'title_plural' => 'Pages',
                'short' => 'p',
                'has_brief' => FALSE,
                'synonyms' => array('page', 'section')
            ),
            'function' => array(
                'title_plural' => 'Functions',
                'has_brief' => TRUE,
                'parent_in_id' => array('class'),
                'short' => 'fn',
                'synonyms' => array('constructor', 'ctor', 'destructor', 'dtor', 'method'),
                'tags' => array('static', 'private', 'public', 'protected')
            ),
            'var' => array(
                'title_plural' => 'Properties',
                'has_brief' => TRUE,
                'parent_in_id' => array('class'),
                'short' => 'v',
                'synonyms' => array('property'),
                'tags' => array('read-only', 'private', 'public', 'protected')
            ),
            'class' => array(
                'title_plural' => 'Classes',
                'has_brief' => TRUE,
                'starts_group_for' => array('var', 'function'),
                'block_class' => 'ScClassBlock',
                'short' => 'cl',
            ),
            'module' => array(
                'title_plural' => 'Sections',
                'has_brief' => TRUE,
                'starts_group_for' => array('page', 'class', 'function', 'var'),
                'short' => 's',
                'synonyms' => array('file')
            ),
        ),
        
        'file_specs' => array(
            '\.php$' => 'default',
            '\.inc$' => 'default',
            '\.doc.txt$' => 'default'
        ),
        
        'tags' => array('deprecated', 'unimplemented'),
        
        'tag_synonyms' => array(
            'read-only' => array('readonly'),
            'deprecated' => array('deprec'),
        ),
    );
    
    /*
     * Property: $_config
     * Raw data from the scribe.conf file (after being YAML-parsed).
     * [Read-only]
     */
    var $_config;
    
    /*
     * Property: $stderr
     * Standard error.
     * 
     * Description:
     *   Used by [[error()]], [[status()]] and [[notice()]].
     */
    var $stderr; 
     
    /* ======================================================================
     * Constructor
     * ====================================================================== */
     
    /*
     * Function: Scribe()
     * Constructor.
     * 
     * [In group "Constructor"]
     */
    function Scribe()
    {   
        $this->stderr = fopen('php://stderr', 'w');

        // Find config file
        $config_file = $this->findConfigFile();
        if ($config_file === FALSE)
            { return $this->error('No config file found.'); }
        
        // Load config file and validate
        $this->_config = yaml($config_file);
        if (!is_array($this->_config))
            { return $this->error('Configuration file is invalid.'); }
        
        // Finally, initialize
        $this->cwd        = dirname($config_file);
        $this->Project    = new ScProject($this);
        $this->Controller = new ScController($this);
        $this->Readers['default'] = new DefaultReader($this);
    }
    
    /* ======================================================================
     * Methods
     * ====================================================================== */
     
    /*
     * Function: loadOutputDriver()
     * Loads an output driver.
     *
     * Usage:
     *     $this->loadOutputDriver($driver[, $options])
     *
     * Returns:
     *   Driver on success, FALSE on failure.
     */

    function& loadOutputDriver($driver, &$project, $options = array())
    {
        // TODO: Proofing: This should make sure $driver is sanitized
        require_once SCRIBE_PATH . DS . 'include' . DS . "output.$driver.php";
        $classname = "{$driver}Output";

        if (!class_exists($classname))
            { return FALSE; }
            
        $output = new $classname($project, $options);
        return $output;
    }
    
    /*
     * Function: findConfigFile()
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

    function findConfigFile()
    {
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
    
    /*
     * Function: error()
     * Spits out an error and dies.
     * 
     * ## Description
     *    This is called by any function that needs to generate an error.
     * 
     * ## Example
     * 
     *     OH yeah
     *     $Sc->error("Printer on fire!");
     */
    function error($error)
    {
        fwrite($this->stderr, "Scribe error: " . $error. "\n");
        exit();
    }
    
    // Function: notice()
    // Test
    function notice($message)
    {
        fwrite($this->stderr, "* " . $message. "\n");
    }
    
    function status($msg)
    {
        fwrite($this->stderr, $msg . "\n");
    }
    
    /* ======================================================================
     * End class
     * ====================================================================== */
}
