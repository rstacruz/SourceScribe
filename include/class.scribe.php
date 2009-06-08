<?php

// Class: Scribe
// Yeah.
class Scribe
{
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
    
    /* Property: $Options['type_keywords']
     * Yay
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $Options['block_types']
     * Yay
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $Options['file_specs']
     * Yay
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $Options['tags']
     * Auto-set
     * 
     * [Grouped under "Options"]
     */
    var $Options = array
    (
        'type_keywords' => array
        (
            'function'    => 'function',
            'constructor' => 'function',
            'ctor'        => 'function',
            'destructor'  => 'function',
            'dtor'        => 'function',
            'method'      => 'function',
            'property'    => 'var',
            'var'         => 'var',
            'class'       => 'class',
            'page'        => 'page',
            'section'     => 'page',
            'module'      => 'module',
            'file'        => 'module',
        ),
        
        // TODO: Proofing: Must ensure configuration is valid
        
        'block_types' => array
        (
            'function' => array(
                'title_plural' => 'Functions',
                'page' => TRUE,
                'has_brief' => TRUE,
                'parent_in_id' => array('class'),
                'short' => 'fn',
            ),
            'var' => array(
                'title_plural' => 'Properties',
                'page' => FALSE,
                'has_brief' => TRUE,
                'parent_in_id' => array('class'),
                'short' => 'v',
            ),
            'class' => array(
                'title_plural' => 'Classes',
                'page' => TRUE,
                'has_brief' => TRUE,
                'starts_group_for' => array('var', 'function'),
                'block_class' => 'ScClassBlock',
                'short' => 'cl',
            ),
            'module' => array(
                'title_plural' => 'Modules',
                'page' => TRUE,
                'has_brief' => TRUE,
                'starts_group_for' => array('page', 'class', 'function', 'var'),
                'short' => 'm',
            ),
            'page' => array(
                'page' => TRUE,
                'short' => '',
            ),
        ),
        
        'file_specs' => array(
            '\.php$' => 'default',
            '\.inc$' => 'default',
            '\.doc.txt$' => 'default'
        ),
        
        'tags' => array(
            'private'    => 'private',
            'public'     => 'public',
            'static'     => 'static',
            'read-only'  => 'read-only',
            'readonly'   => 'read-only',
            'deprecated' => 'deprecated',
            'deprec'     => 'deprecated',
        ),
    );
    
    // Property: $_config
    // Raw data from the scribe.conf file (after being YAML-parsed).
    // [Read-only]
    var $_config;
    
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
            { return $this->error('No config file found.'); }
        
        // Load config file and validate
        $this->_config = yaml($config_file);
        if (!is_array($this->_config))
            { return $this->error('Configuration file is invalid.'); }
        
        // Finally, initialize
        $this->cwd     = dirname($config_file);
        $this->Project = new ScProject($this);
        $this->Readers['default'] = new DefaultReader($this);
    }
    
    /*
     * Function: loadOutputDriver()
     * Loads an output driver.
     *
     * Usage:
     *     $this->loadOutputDriver($driver[, $options[, $use]])
     *
     * Returns:
     *   Driver on success, FALSE on failure.
     */

    function& loadOutputDriver($driver, &$project, $options = array(), $use = TRUE)
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
     * Function: go()
     * Starts the build process.
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
    
    /*
     * Function: do_build
     * Does a build.
     * 
     * [Grouped under "Command-line actions"]
     */
     
    function do_build($args = array())
    {
        $this->Project->build();
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
        print_r($args);
    }
    
    /*
     * Function: url
     * Shows the file location of the default documentation.
     * 
     * [Grouped under "Command-line actions"]
     */

    function do_url($args = array())
    {
        $str = trim(implode(' ', $args));
        
        $output = $this->_getDefaultOutput();
        $path = $this->Project->cwd . DS . $output['path']; // Output path
        $return = realpath($path . DS . 'index.html');

        // TODO: do_url() is very rudimentary
        if ($str != '') 
        {
            $ScX = $this->loadState();
            $results = $ScX->Project->lookup($str);
            if (count($results) > 0)
                { $return = realpath($path . DS . $results[0]->getID() . '.html'); }
            else { $return = ''; }
        }
        
        echo $return;
    }
    
    /*
     * Function: loadState()
     * Stupid.
     */
     
    function loadState()
    {
        foreach ($this->Project->output as $o)
        {
            if ((!isset($o['driver'])) ||
                ($o['driver'] != 'serial')) { continue; }
            
            $path = $this->Project->cwd . ''
                . DS . (((isset($o['path'])) && ($o['path'])) ?
                         ($o['path']) : ('.'))
                . DS . (((isset($o['filename'])) && ($o['filename'])) ?
                         ($o['filename']) : ('.sourcescribe_index'));

            global $ScX;
            $ScX = unserialize(file_get_contents($path));
            return $ScX;
            break;
        }
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
        $output = ob_get_clean();
        if (trim((string) $output) == '') return;
        $path = realpath($output);
        
        // For Mac OSX
        system('open ' . escapeshellarg($path), $return);
        if ($return == 0) { return; }
        
        // Retry for Linux
        system('xdg-open ' . escapeshellarg($path), $return);
        if ($return == 0) { return; }

        // Retry for Windows
        system('start ' . escapeshellarg($path), $return);
        if ($return == 0) { return; }

        // Give up
    }
    
    
    /*
     * Function: _getDefaultOutput()
     * Returns the default output driver.
     * 
     * [Private]
     */
     
    function& _getDefaultOutput()
    {
        $output_key = array_keys($this->Project->output);
        $output_key = $output_key[0];
        $output = $this->Project->output[$output_key];
        return $output;
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
        echo "Scribe error: " . $error. "\n";
        exit();
    }
    // Function: notice()
    // Test
    function notice($message)
    {
        echo "* " . $message. "\n";
    }
    
    function status($msg)
    {
        echo $msg . "\n";
    }
}
