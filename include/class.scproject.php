<?php

/*
 * Class: ScProject
 * The project.
 */

class ScProject
{
    var $Sc;
    
    var $cwd;
    var $config_file;
    
    // From config
    var $src_path = NULL;
    var $src_path_options = array();
    var $output;
    
    // The data!
    var $data;
    
    /*
     * Function: ScProject()
     * The constructor.
     */
    function ScProject(&$Sc)
    {
        $this->Sc =& $Sc;
        // Get the CWD.
        $this->cwd = getcwd();
        
        // Load config
        foreach ($Sc->_config['project'] as $k => $v)
            { $this->{$k} = $v; }
        
        // Check source
        if (is_null($this->src_path))
            { $this->src_path = $this->cwd; }
            
        // Try it as a relative URL
        if (!is_dir($this->src_path))
            { $this->src_path = realpath($this->cwd . DS . $this->src_path); }
        
        // Else, uh oh
        if (!is_dir($this->src_path))
            { return $Sc->error('src_path is invalid.'); }
    }
    
    /*
     * Function: build()
     * Builds the project.
     */
     
    function build()
    {
        // Scan the files
        $this->Sc->status('Scanning files...');

        $options = array('recursive' => 1, 'fullpath' => 1);
        $files = aeScandir($this->src_path,
            array_merge($this->src_path_options, $options));
            
        // Each of the files, parse them
        foreach ($files as $file) {
            // TODO: Check for output formats instead of passing it on to all
            foreach ($this->Sc->Parsers as $k => $parser)
            {
                $this->Sc->status("Parsing $file with $k");
                $parser->parse($file, $this);
            }
        }
        
        // Spit out the outputs
        foreach ($this->output as $driver => $path)
        {
            // Make sure we have an output driver
            if (!isset($this->Sc->Outputs[$driver])) {
                $this->Sc->notice('No output driver for ' . $driver . '.');
                continue;
            }
            $output = $this->Sc->Outputs[$driver];

            $this->Sc->status('Writing ' . $driver . ' output...');
            // Make the path
            $path = ($this->cwd . DS . $path);
            $result = @mkdir($path, 0744, true);
            if (!is_dir($path))
                { return $this->Sc->error("Can't create folder for $driver output."); }
            
        }
        
        $this->Sc->status('Build complete.');

    }
}