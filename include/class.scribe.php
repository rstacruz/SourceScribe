<?php

// Class: Scribe
// Yeah.
class Scribe
{
    var $Project;
    var $Readers = array();
    var $Outputs = array();
    
    /* Property: $Options['type_keywords']
     * Yay
     */
    /* Property: $Options['block_types']
     * Yay
     */
    /* Property: $Options['file_specs']
     * Yay
     */
    /* Property: $Options['valid_tags']
     * Yay
     */
    /* Property: $Options['tags']
     * Auto-set
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
        $this->Outputs['html']    = new HtmlOutput($this);
        
        // Initialize $Options['tags']
        $this->Options['tags'] = array();
        foreach ($this->Options['valid_tags'] as $v)
            { $this->Options['tags'][strtolower($v)] = strtolower($v); }
        foreach ($this->Options['tag_thesaurus'] as $k => $v)
            { $this->Options['tags'][strtolower($k)] = strtolower($v); }
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
     * Function: do_build()
     * [Grouped under "Controller actions"]
     * 
     * Does a build.
     */
     
    function do_build($args = array())
    {
        $this->Project->build();
    }
    
    function do_version($args = array())
    {
        echo "SourceScribe\n";
    }
    
    function do_open($args = array())
    {
        $output_key = array_keys($this->Project->output);
        $output_key = $output_key[0];
        $output = $this->Project->output[$output_key];
        $path = $this->Project->cwd . DS .
                $output['path'] . DS .
                'index.html';
        system("open $path");
    }
    
    function do_help($args = array())
    {
        echo "SourceScribe\n";
        echo "Usage: ss [command] [options]\n";
        echo "Commands:\n";
        echo "  build        Builds documentation\n";
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