<?php

class Scribe
{
    var $Project;
    var $Readers = array();
    var $Outputs = array();
    
    var $Options = array(
        'type_keywords' => array(
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
            'module'      => 'page',
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
        $this->Project->build();
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