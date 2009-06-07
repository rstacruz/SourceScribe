<?php
/*
 * Class: ScProject
 * The project.
 * 
 * Description:
 *   This is a sub-singleton of class [[Scribe]], and is initialized on it's
 *   constructor. It may be accessed through the main singleton (the `$Sc`
 *   global variable) using something like the example below.
 * 
 *      global $Sc;
 *      echo "The project's name is:" . $Sc->Project->getName();
 */

class ScProject
{
    /*
     * Group: Properties
     */
     
    var $Sc;
    
    var $cwd;
    var $config_file;
    
    /*
     * Group: Configurable properties
     */
     
    var $src_path = NULL;
    var $src_path_options = array();
    var $output;
    
    /*
     * Property: $name
     * The name of the project.
     */
     
    var $name;
    
    /*
     * Group: Temporary properties
     */
     
    /*
     * Property: $_ancestry
     * Temporary property.
     * 
     * Used by [[register()]].
     */
     
    var $__ancestry = array();
    
    /*
     * Property: $data
     * Storage for all blocks associated with the project.
     *
     * Sample data:
     *   
     *     $data = array
     *     (
     *       'blocks' => array( ScBlock, ScBlock, ScBlock, ... ),
     *       'tree'   => array( ScBlock, ScBlock, ScBlock, ... ),
     *       'home'   => ScBlock
     *     );
     * 
     * Description:
     *   This is an associative array with the following keys below.
     *
     *   blocks   - Array of [[ScBlock]]s. The list of blocks.
     *   home     - [[ScBlock]] instance. the main root block (i.e., home page)
     *   tree     - Array of [[ScBlock]]s. The list of "root" blocks
     * 
     * Usage:
     *   - To add blocks, use [[register()]].
     */
    
    var $data = array
    (
        'blocks' => array(),
        'tree'   => array(),
        'home'   => NULL
    );
    
    /*
     * Function: ScProject()
     * The constructor.
     * 
     * Usage:
     *     Don't
     * 
     * Description:
     *   This ctor is responsible for checking project configuration and
     *   inspecting the source paths.
     * 
     *   This will not call [[build()]]. It is issued separately by the
     *   bootstrapper.
     * 
     * References:
     *   This is called on startup via [[Scribe::Scribe()]].
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
     * 
     * Description:
     *   This is the main function that builds the documentation. It does
     *   the following things below.
     * 
     *   1. The source path is scanned for files recursively, and it'll
     *      delegate each file to it's respective `reader` to be read.
     * 
     *   2. These readers will parse out the files and call [[register()]] when
     *      it finds a block.
     *   
     *   3. It checks the outputs to be made (defined in the configuration
     *      file) and delegates to each output driver the task of producing
     *      the documentation files.
     * 
     *   This is called when the user types `ss build` in the command line
     *   (or just plain `ss` as it's the default action).
     * 
     * References:
     *   This is called by the default action of [[class Scribe]],
     *   namely [[Scribe::do_build()]]. Being the default action, this fires
     *   up when the user types `ss` in the command line.
     */
     
    function build()
    {
        // Scan the files
        $this->Sc->status('Scanning files...');
        $options = array('recursive' => 1, 'mask' => '/./', 'fullpath' => 1);
        $options = array_merge($this->src_path_options, $options);
        
        // For each source path...
        foreach ((array) $this->src_path as $path)
        {
            // Parse each of the files
            $files = aeScandir($path, $options);
            foreach ($files as $file)
            {
                // Find out which reader it's assigned to
                foreach ($this->Sc->Options['file_specs']
                         as $spec => $reader_name)
                {
                    if (preg_match("~$spec~", $file) == 0) { continue; }
                
                    // Show status that we're reading
                    $file_min = substr(realpath($file),
                        1 + strlen(realpath($this->cwd)));
                    $this->Sc->status("* [$reader_name] $file_min");
                
                    // And read
                    $this->registerStart();
                    $reader = $this->Sc->Readers[$reader_name];
                    $blocks = $reader->parse($file, $this);
                    break;
                }
            }
        }
        
        // Spit out the outputs.
        // Do this for every output defined...
        foreach ($this->output as $id => $output_options)
        {
            // Make sure we have an output driver
            if (!isset($output_options['driver']))
                { return $this->Sc->error("No driver defined for output $id"); }
                
            // Make sure the driver exists
            $driver = $output_options['driver'];
            if (!isset($this->Sc->Outputs[$driver])) {
                $this->Sc->notice('No output driver for ' . $driver . '.');
                continue;
            }
            
            // Initialize
            $this->Sc->status('Writing ' . $driver . ' output' .
                (($driver!=$id)?" ($id)":'') . '...');
            $path   = $output_options['path'];
            $output = $this->Sc->Outputs[$driver];

            // Mkdir the path
            $path = ($this->cwd . DS . $path);
            $result = @mkdir($path, 0744, true);
            if (!is_dir($path))
                { return $this->Sc->error("Can't create folder for $driver output."); }
            
            // Run
            $output->run($this, $path, $output_options);
        }
        
        $this->Sc->status('Build complete.');

    }
    
    /*
     * Function: register()
     * Registers a block.
     * 
     * Description:
     *   This is called by readers.
     * 
     *   Sample input would be something like below.
     *
     *     "Function: test()\nDescription.\n\nEtc etc"
     */
     
    function register($block)
    {
        global $Sc;
        
        if (is_string($block))
            { $block = ScBlock::factory($block); }
        
        // Die if not valid
        if (!$block->valid) { return; }
        
        // Register to where?
        $parent = NULL;
        
        // Find the ancestor.
        for ($i=0; $i < count($this->__ancestry); ++$i)
        {
            $ancestor =& $this->__ancestry[$i];
            $childtypes = $ancestor->getTypeData('starts_group_for');
            if (in_array($block->type, (array) $childtypes))
            {
                $ancestor->registerChild($block);
                break;
            }
        }
        
        // Remove the vestegial ancestors
        // [e,d,c,b,a], 0, 2 => [c,b,a]
        array_splice($this->__ancestry, 0, $i);

        // Add us
        // [b,c,d], a => [a,b,c,d]
        array_unshift($this->__ancestry, &$block);
        
        // Is it alone?
        if (count($this->__ancestry) <= 1)
            { $this->data['tree'][] =& $block; }
            
        // Register to all blocks
        $this->data['blocks'][$block->getID()] = &$block;
    }
    
    function registerStart()
    {
        $this->__ancestry = array();
    }
    
    /*
     * Function: getName()
     * Returns the name of the project.
     *
     * Usage:
     *      $this->getName()
     *
     * Returns:
     *   The name of the project as defined in the configuration file.
     */

    function getName()
    {
        return $this->name;
    }
}