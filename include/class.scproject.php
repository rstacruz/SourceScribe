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
    
    /*
     * Property: $data
     * The data.
     *
     * Description:
     *   This is an associative array with the following keys below.
     *
     *   blocks   - Array. The list of blocks.
     *   index    - Array. The index
     *
     * Blocks:
     *   The `blocks` list is an associative array of `AeBlock` instances.
     *   It is populated by the readers.
     *
     * Index:
     *   LOL, I don't know yet.
     * 
     * Example data:
     *   Here's a possible data:
     * 
     *     $data = array
     *     (
     *       'blocks' => array(
     *         'id1' => ScBlock,
     *         'id2' => ScBlock,
     *         ...
     *       ),
     *       'index' => array(
     *       ),
     *     );
     */
    
    var $data = array(
        'blocks' => array(),
        'index' => array()
    );
    
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
        
        // Check if all paths are valid
        $this->src_path = (array) $this->src_path;
        foreach ($this->src_path as $k => $path)
        {
            // Try as a relative path
            if (!is_dir($this->src_path[$k]))
                { $this->src_path = realpath($this->cwd . DS . $this->src_path); } 
                
            // If invalid, die
            if (!is_dir($this->src_path[$k]))
                { return $Sc->error('src_path is invalid: ' . $path); }
        }
    }
    
    /*
     * Function: build()
     * Builds the project.
     */
     
    function build()
    {
        // Scan the files
        $this->Sc->status('Scanning files...');

        $options = array('recursive' => 1, 'mask' => '/./', 'fullpath' => 1);
        $options = array_merge($this->src_path_options, $options);
        foreach ((array) $this->src_path as $path)
        {
            $files = aeScandir($path, $options);
            
            // Each of the files, parse them
            foreach ($files as $file)
            {
                foreach ($this->Sc->Options['file_specs']
                         as $spec => $reader_name)
                {
                    if (preg_match("~$spec~", $file) == 0) { continue; }
                
                    // Show status
                    $file_min = substr(realpath($file),
                        1 + strlen(realpath($this->cwd)));
                    $this->Sc->status("* [$reader_name] $file_min");
                
                    $reader = $this->Sc->Readers[$reader_name];
                    $blocks = $reader->parse($file, $this);
                    break;
                }
            }
        }
        
        // Spit out the outputs
        foreach ($this->output as $driver => $output_options)
        {
            $path = $output_options['path'];
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
            
            $output->run($this, $path, $output_options);
        }
        
        $this->Sc->status('Build complete.');

    }
    
    function register($block)
    {
        if (is_string($block))
            { $block = new ScBlock($block); }
        
        // Die if not valid
        if (!$block->valid) { return; }
        
        // Register to blocks
        $this->data['blocks'][$block->id] = $block;
        
        $this->data['index'][$block->id] = 
            array('block' => &$block);
    }
}