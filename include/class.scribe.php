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
    
    /*
     * Property: $Outputs
     * Key/value pairs of output drivers.
     * 
     * Description:
     *   - To register an output driver to use,
     *     use [[loadOutputDriver()]].
     * 
     * [Read-only]
     */
    var $Outputs = array();
    
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
     
    /* Property: $Options['valid_tags']
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
        
        // TODO: Proofing: Must ensure this is valid
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
        
        'valid_tags' => array(
            'private', 'public', 'static', 'read-only'),
            
        'tag_thesaurus' => array(
            'readonly' => 'read-only'),
    );
    
    var $config_file;
    
    // Property: $_config
    // Raw data from the scribe.conf file (after being YAML-parsed).
    var $_config;
    
    /*
     * Function: Scribe()
     * Constructor.
     */
    function Scribe()
    {
        $this->cwd = getcwd();
        $this->config_file = $this->findConfigFile();
        
        // Die if no config
        if (!is_file($this->config_file)) {
            $this->error('No config file found');
            return;
        }
        
        $this->cwd = dirname($this->config_file);
        $this->_config = yaml($this->config_file);
        
        if ( (!is_array($this->_config)) ||
             (!isset($this->_config['project'])) ||
             (!is_array($this->_config['project']))
           ) {
            $this->error('Configuration file is invalid.');
        }
        
        $this->Project = new ScProject($this);
        $this->Readers['default'] = new DefaultReader($this);
        $this->loadOutputDriver('html');
        
        // Initialize $Options['tags']
        $this->Options['tags'] = array();
        foreach ($this->Options['valid_tags'] as $v)
            { $this->Options['tags'][strtolower($v)] = strtolower($v); }
        foreach ($this->Options['tag_thesaurus'] as $k => $v)
            { $this->Options['tags'][strtolower($k)] = strtolower($v); }
    }
    
    /*
     * Function: loadOutputDriver()
     * Loads an output driver.
     *
     * Usage:
     *     $this->loadOutput($driver[, $use])
     *
     * Returns:
     *   TRUE on success, FALSE on failure.
     */

    function loadOutputDriver($driver, $use = TRUE)
    {
        // TODO: BP: This should make sure $driver is sanitized
        require_once SCRIBE_PATH . DS . 'include' . DS . "output.$driver.php";
        $classname = "{$driver}Output";

        if (!class_exists($classname))
            { return FALSE; }
            
        if ($use)
            { $this->Outputs[$driver] = new $classname($this); }
        
        return TRUE;
    }
    
    /*
     * Function: findConfigFile()
     * Tries to find the configuration file.
     *
     * Usage:
     *     $this->findConfigFile()
     *
     * Returns:
     *   The configuration file as a string if found, otherwise an empty string.
     * 
     * References:
     *   Used by [[Scribe::Scribe()]].
     */

    function findConfigFile()
    {
        $names = array('sourcescribe.conf', 'scribe.conf', 'ss.conf');
        $path = explode(DS, realpath($this->cwd));
        for ($i = count($path); $i >= 1; --$i)
        {
            $current_path = implode(DS, array_slice($path, 0, $i));
            foreach ($names as $name)
            {
                if (is_file($current_path . DS . $name))
                    { return realpath($current_path . DS . $name); }
            }
        }
        
        return '';
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
        // TODO: Lookup for a keyword
        $str = implode(' ', $args);
        
        $output = $this->_getDefaultOutput();
        $path = $this->Project->cwd . DS .
                $output['path'] . DS .
                'index.html';
        $path = realpath($path);
        echo $path;
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
        $path = realpath(ob_get_clean());
        
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

class ScOutput
{
    var $Sc;
    
    function HtmlOutput(&$Sc)
    {
        $this->Sc = &$Sc;
    }   
}