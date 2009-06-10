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
    /* ======================================================================
     * Properties
     * ====================================================================== */
     
    /*
     * Property: $Sc
     * Reference to the [[Scribe]] singleton.
     */
     
    var $Sc;
    
    /*
     * Property: $cwd
     * The current working directory of the project (i.e., where the config is).
     */
     
    var $cwd;
    
    /* ======================================================================
     * Data properties
     * ====================================================================== */
    
    /* Property: $data['blocks']
     * All the blocks.
     * 
     * Description:
     *   This is an array of [[ScBlock]]s. It lists all the blocks in
     *   the project.
     * 
     *   This is a read-only property. To add blocks, use [[register()]].
     * 
     * Sample data:
     *     array( ScBlock, ScBlock, ScBlock, ... )
     * 
     * [Read-only, grouped under "Data properties"]
     */
     
    /* Property: $data['home']
     * The home page.
     * 
     * Description:
     *   This is an [[ScBlock]] instance reference to the main root node,
     *   the home page.
     * 
     *   This is a read-only property. To add blocks, use [[register()]].
     * 
     * [Read-only, grouped under "Data properties"]
     */
    
    /* Property: $data['tree']
     * The list of second-level blocks.
     * 
     * Description:
     *   This lists all blocks that are children of the home page. Synonymous
     *   to `$data['home']->getChildren()`.
     * 
     *   This is a read-only property. To add blocks, use [[register()]].
     * 
     * Sample data:
     *     array( ScBlock, ScBlock, ScBlock, ... )
     * 
     * [Read-only, grouped under "Data properties"]
     */
     
    var $data = array
    (
        'blocks' => array(),
        'tree'   => array(),
        'home'   => NULL
    );
     
    /* ======================================================================
     * Options properties
     * ====================================================================== */
     
    /* Property: $options['type_keywords']
     * Yay
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['block_types']
     * Yay
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['file_specs']
     * Yay
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['tags']
     * Tags
     * 
     * [Grouped under "Options"]
     */
     
     
    /* Property: $options['name']
     * Yay
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['output']
     * Yay
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['src_path']
     * Yay
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['exclude']
     * Exclusion list
     * 
     * [Grouped under "Options"]
     */
    var $options = array
    (
        'name' => 'My manual',
        'src_path' => '.',
    );
    
    /* ======================================================================
     * Misc properties
     * ====================================================================== */
     
    /*
     * Property: $__ancestry
     * Temporary property.
     * 
     * Description:
     *   This is a temporary property used by [[register()]].
     * 
     * [Grouped under "Private properties"]
     */
     
    var $__ancestry = array();
    
    /* ======================================================================
     * Constructor
     * ====================================================================== */

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
     * 
     * [Grouped under "Constructor"]
     */
     
    function ScProject(&$Sc)
    {
        $this->Sc =& $Sc;
        $this->cwd = $Sc->cwd;

        $this->_loadConfig($Sc);
        $this->_verifyConfig($Sc);
        $this->_fillOptionalConfig($Sc);
    }
    
    function _loadConfig(&$Sc)
    {
        // Verify each required field
        foreach (array('name', 'output') as $required_field) {
            if (!isset($Sc->_config[$required_field]))
            {
                $Sc->error(
                    'Configuration is missing the ' .
                    'required field "' . $required_field . '".');
                return;
            }
        }
        
        // Migrate defaults
        foreach (array('type_keywords', 'block_types', 'file_specs', 'tags') as $k)
        {
            $this->options[$k] = $Sc->defaults[$k];
        }
        
        // Load configuration variables
        foreach (array('name','output','src_path','exclude') as $k)
        {
            if (!isset($Sc->_config[$k])) { continue; }
            $this->options[$k] = $Sc->_config[$k];
        }
    }
    
    function _verifyConfig(&$Sc)
    {
        // Check output
        if ((!is_array($this->options['output'])) ||
            (count($this->options['output']) == 0))
        {
            return $Sc->error("You must define at least one output.");
        }
        
        // Check output
        foreach ($this->options['output'] as $id => $output)
        {
            if (!is_array($output))
                { return $Sc->error("Output #$id is invalid."); }
            if (!isset($output['driver'])) { return $Sc->error("Output #$id is missing a driver."); }
            if (!isset($output['path']))   { return $Sc->error("Output #$id ({$output['driver']}) is missing it's output path."); }
        }
        
        // Check if all paths are valid
        $this->options['src_path'] = (array) $this->options['src_path'];
        foreach ($this->options['src_path'] as $k => $path)
        {
            // Try as a relative path
            if (!is_dir($this->options['src_path'][$k]))
                { $this->options['src_path'][$k] = ($this->cwd . DS . $path); } 
            
            // If invalid, die
            if (!is_dir($this->options['src_path'][$k]))
                { return $Sc->error('Source path is invalid: "' . $path . '"'); }
        }
    }
    function _fillOptionalConfig(&$Sc)
    {
        // Add Default output to spit out the .sourcescribe_index file
        $this->options['output']['serial'] = array('driver' => 'serial'); 
    }
    /*
     * Function: build()
     * Builds the project.
     * 
     * Description:
     *   This is the main function that builds the documentation. This is
     *   called when the user types `ss build` in the command line (or just
     *   plain `ss` as it's the default action). It does the things below:
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

        // Take the exclusions list into consideration
        if (isset($this->options['exclude']))
            { $options['exclude'] = (array) $this->options['exclude']; }
        
        // For each source path...
        foreach ((array) $this->options['src_path'] as $path)
        {
            // Parse each of the files
            $files = aeScandir($path, $options);
            foreach ($files as $file)
            {
                // Find out which reader it's assigned to
                foreach ($this->options['file_specs']
                         as $spec => $reader_name)
                {
                    if (preg_match("~$spec~", $file) == 0) { continue; }
                
                    // Show status of what file we're reading
                    $file_min = substr(realpath($file),
                        1 + strlen(realpath($this->cwd)));
                    $this->Sc->status("* [$reader_name] $file_min");
                
                    // And read it I
                    $this->registerStart();
                    $reader = $this->Sc->Readers[$reader_name];
                    $blocks = $reader->parse($file, $this);
                    break;
                }
            }
        }
        
        $this->_doPostBuild();
        
        // Spit out the outputs.
        // Do this for every output defined...
        foreach ($this->options['output'] as $id => $output_options)
        {
            
            // Make sure we have an output driver
            if (!isset($output_options['driver']))
                { return $this->Sc->error("No driver defined for output $id"); }
                
            // Load it and make sure it exists
            $driver = $this->Sc->loadOutputDriver($output_options['driver'], $this,
                      $options);
            if (!$driver)
            {
                $this->Sc->notice('No output driver for ' . $driver . '.');
                continue;
            }
            
            // Initialize
            $this->Sc->status('Writing ' . $output_options['driver'] . ' output...');
            $path   = $output_options['path'];
            
            // Mkdir the path
            $path = ($this->cwd . DS . $path);
            $result = @mkdir($path, 0744, true);
            if (!is_dir($path))
                { return $this->Sc->error("Can't create folder for $driver output."); }
            
            // Run
            $driver->run($path);
        }
        
        $this->Sc->status('Build complete.');
        $output = serialize($this->Sc);
    }
    
    /*
     * Function: _doPostBuild()
     * Does post-build actions like modifying the homepage.
     *
     * Usage:
     *     $this->_doPostBuild
     *
     * Returns:
     *   Unspecified.
     */

    function _doPostBuild()
    {
        
        // If there's no homepage,
        // Make one!
        if (is_null($this->data['home']))
        {
            $this->register("Page: " . $this->getName());
        }
        
        // [1] If there's a home, [2] each of the tree firstlevels
        // [3] that isn't the homepage [4] will be the child of the homepage.
        if (!is_null($this->data['home']))
            foreach ($this->data['tree'] as $i => $block)
                if ($block->getID() != 'index')
                    { $this->data['home']->registerChild($this->data['tree'][$i]); }
    }
    
    /*
     * Function: lookup()
     * Looks up a keyword and returns the matching blocks.
     *
     * Usage:
     *     $this->lookup($keyword, $reference)
     *
     * Returns:
     *   Unspecified.
     */

    function& lookup($keyword, $reference = NULL)
    {
        $keyword = $this->_distill($keyword);
        $return = array();
        
        foreach ((array) $this->data['blocks'] as $block)
        {
            $title = $this->_distill($block->getKeyword());
            if ($title == $keyword)
                { $return[] = $block; }
        }
        
        return $return;
    }
    
    function _distill($str)
    {
        preg_match_all('~[a-zA-Z0-9]~', $str, $m);
        return strtolower(implode('', (array) $m[0]));
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
     
    function register($blockData)
    {
        global $Sc;
        
        $block =& ScBlock::factory($blockData, $this);
        
        // Die if not valid
        if (!$block->valid) { return; }
        
        // Register to where?
        $parent = NULL;
        
        $id = count($this->data['blocks']);
        
        // Register to all blocks
        $this->data['blocks'][$id] = &$block;
        
        // Register as home page if needed
        if ($block->isHomePage())
        {
            $this->data['home'] =& $this->data['blocks'][$id];
        }
        
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
        array_unshift($this->__ancestry, &$this->data['blocks'][$id]);
        
        // Is it alone?
        if (count($this->__ancestry) <= 1)
            { $this->data['tree'][] =& $this->data['blocks'][$id]; }
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
        // This should never have to be done; 'name' is a required field
        return isset($this->options['name']) ? $this->options['name'] : 'Manual';
    }
}