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
     * Property: $defaults
     * An array containing defaults for [[ScProject::$options]].
     */
     
    var $defaults = array
    (   
        'block_types' => array
        (
            'page' => array(
                'title_plural' => 'Pages',
                'short' => '',
                'has_brief' => FALSE,
                'synonyms' => array('page', 'section'),
                'default_order' => 10,
            ),
            'function' => array(
                'title_plural' => 'Functions',
                'has_brief' => TRUE,
                'parent_in_id' => array('class'),
                'short' => 'fn',
                'title_suffix' => '()',
                'synonyms' => array('constructor', 'ctor', 'destructor', 'dtor', 'method'),
                'tags' => array('static', 'private', 'public', 'protected', 'virtual'),
                'default_order' => 0,
            ),
            'var' => array(
                'title_plural' => 'Properties',
                'has_brief' => TRUE,
                'parent_in_id' => array('class'),
                'short' => 'var',
                'synonyms' => array('property'),
                'title_prefix' => '$',
                'tags' => array('read-only', 'private', 'public', 'protected', 'constant'),
                'default_order' => -5,
            ),
            'class' => array(
                'title_plural' => 'Classes',
                'has_brief' => TRUE,
                'starts_group_for' => array('var', 'function'),
                'priority' => 8,
                'title_prefix' => 'Class ',
                // 'block_class' => 'ScClassBlock',
                'tags' => array('interface', 'abstract'),
                'short' => 'class',
                'default_order' => 0,
            ),
            'module' => array(
                'title_plural' => 'Sections',
                'has_brief' => TRUE,
                'priority' => 4,
                'starts_group_for' => array('page', 'class', 'function', 'var'),
                'short' => 'mod',
                'synonyms' => array('file'),
                'default_order' => 20,
            ),
        ),
        
        'include' => array
        (
            '\.inc$'  => 'default',
            '\.rb$'   => 'default',
            '\.py$'   => 'default',
            '\.js$'   => 'default',
            '\.as$'   => 'default',
            '\.c$'    => 'default',
            '\.d$'    => 'default',
            '\.sql$'  => 'default',
            '\.nse$'  => 'default',
            '\.cpp$'  => 'default',
            '\.java$' => 'default',
            '\.m$'    => 'default',
            '\.sh$'   => 'default',
            '\.cs$'   => 'default',
            '\.h$'    => 'default',
            '\.pl$'   => 'default',
            '\.perl$' => 'default',
            '\.php[3-5]?$' => 'default',
            '\.doc.txt$' => 'default'
        ),
        
        'tags' => array('deprecated', 'unimplemented'),
        
        'tag_synonyms' => array(
            'read-only' => array('readonly'),
            'deprecated' => array('deprec'),
            'constant' => array('const'),
        ),
    );
    
    /*
     * Property: $_config
     * Raw data from the scribe.conf file (after being YAML-parsed).
     * [Read-only]
     */
    var $_config;
    
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
        // Find config file
        $config_file = $this->findConfigFile();
        if ($config_file === FALSE)
            { return ScStatus::error('No config file found. You may generate one using `ss makeconfig`.'); }
        
        // Load config file and validate
        $this->_config = yaml($config_file);
        if (!is_array($this->_config))
            { return ScStatus::error('Configuration file is invalid.'); }
        
        // Finally, initialize
        $this->cwd        = dirname($config_file);
        $this->Project    = new ScProject($this);
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
    
    /* ======================================================================
     * End class
     * ====================================================================== */
}
