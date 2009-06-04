<?php

// Class: Scribe
// Yeah.
class Scribe
{
    var $Project;
    var $Readers = array();
    var $Outputs = array();
    
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
            'property'    => 'property',
            'var'         => 'property',
            'class'       => 'class',
            'page'        => 'page',
            'section'     => 'page',
            'module'      => 'module',
            'file'        => 'module',
        ),
        
        'block_types' => array
        (
            'function' => array(
                'page' => TRUE,
                'has_brief' => TRUE
            ),
            'property' => array(
                'page' => FALSE,
                'has_brief' => TRUE
            ),
            'class' => array(
                'page' => TRUE,
                'has_brief' => TRUE,
                'starts_group_for' => array('property', 'function'),
            ),
            'module' => array(
                'page' => TRUE,
                'has_brief' => TRUE,
                'starts_group_for' => array('page', 'class', 'function'),
            ),
            'page' => array(
                'page' => TRUE,
            ),
            /*'group' => array(
                'page' => FALSE,
                'has_brief' => TRUE,
                'starts_group' => TRUE,
                'ends_group' => array('group')
            ),*/
        ),
        
        'file_specs' => array(
            '\.php$' => 'default',
            '\.inc$' => 'default',
            '\.doc.txt$' => 'default'
        )
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
        $this->config_file = $this->cwd . DS . 'sourcescribe.conf';
        
        // Die if no config
        if (!is_file($this->config_file)) {
            $this->error('No config file found');
            return;
        }
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
    
    function do_build($args = array())
    {
        $this->Project->build();
    }
    
    function do_version($args = array())
    {
        echo "SourceScribe\n";
    }
    
    function do_open()
    {
        $path = $this->Project->cwd . DS .
                $this->Project->output['html']['path'] . DS .
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