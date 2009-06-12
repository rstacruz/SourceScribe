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
 * 
 * [Filed under "API reference"]
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
    
    /*
     * Property: $outputs
     * Array of output drivers ([[ScOutput]] instances).
     */
     
    var $outputs = array();
    
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
     * To be documented. Auto-generated and not taken from config
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['block_types']
     * To be documented.
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['file_specs']
     * To be documented.
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['tags']
     * Project-wide tags.
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['tag_synonyms']
     * Associative array tag thesaurus.
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['name']
     * To be documented.
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['output']
     * The output drivers.
     * 
     * Description:
     *   These are not the [[ScOutput]] instances, but rather this is the
     *   information needed to load those. If you're looking for the actual
     *   output drivers, see [[$outputs]].
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['src_path']
     * To be documented.
     * 
     * [Grouped under "Options"]
     */
     
    /* Property: $options['exclude']
     * File exclusion list.
     * 
     * Description:
     *   This is an array of regex strings that will be excluded from the
     *   file search.
     * 
     * Example:
     *   This example will exclude all `.php3` files, and everything in the
     *   `.git` folder.
     * 
     *     array('#\.php3$#', '#\.git/#')
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
        $this->_verifyBlockTypes();
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
        ScStatus::status('Starting build process:');
        // Scan the files
        ScStatus::updateStart("Scanning files");
        $file_count = 0;
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
                    ScStatus::update("[$reader_name] $file_min");
                    $file_count++;
                
                    // And read it I
                    $this->registerStart();
                    $reader = $this->Sc->Readers[$reader_name];
                    $blocks = $reader->parse($file, $this);
                    break;
                }
            }
        }
        ScStatus::updateDone("$file_count files scanned.");
        
        $this->_doPostBuild();
        $this->_loadOutputDrivers();
        
        // Spit out the outputs.
        // Do this for every output defined...
        foreach ($this->options['output'] as $id => $output_options)
        {
            
            // Make sure we have an output driver
            if (!isset($output_options['driver']))
                { return ScStatus::error("No driver defined for output $id"); }
                
            // Load it and make sure it exists
            $driver =& $this->Sc->loadOutputDriver($output_options['driver'],
                       $this, $options);
                      
            $driver =& $this->outputs[$id];
            if (!$driver) { continue; }
            
            // Initialize
            ScStatus::updateStart('Writing ' . $output_options['driver'] . ' output');
            $path   = $output_options['path'];
            
            // Mkdir the path
            $path = ($this->cwd . DS . $path);
            $result = @mkdir($path, 0744, true);
            if (!is_dir($path))
                { return $this->Sc->error("Can't create folder for $driver output."); }
            
            // Run
            $driver->run($path);
            ScStatus::updateDone('Done.');
        }
        
        ScStatus::status('Build complete.');
        $output = serialize($this->Sc);
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

    function& lookup($raw_keyword, $reference = NULL)
    {
        // Split keywords (e.g., Scribe::lookup() will be [scribe,lookup])
        preg_match_all('~([A-Za-z0-9-_\. ]+)~s', $raw_keyword, $m);
        $keyword_array = $m[1];
        
        // No keywords? No result
        if (count($keyword_array) == 0)
            { return array(); }
        
        // Lookup the last keyword (e.g., 'lookup' in ['scribe','lookup'])
        $keyword = $this->_distill($keyword_array[count($keyword_array)-1]);
        $parent_keyword = (count($keyword_array) >= 2) ? ($keyword_array[count($keyword_array)-2]) : NULL;
        $parents = $this->lookup($parent_keyword, $reference);
        
        // Construct a list of results with priorities.
        // $results = [ {priority: 0, block: ScBlock()}, ... ]
        $results = array();
        foreach ($this->data['blocks'] as $id => &$block)
        {
            $priority = $block->getTypeData('priority');

            if (strtolower($block->getID()) == strtolower($raw_keyword))
            {
                // ID Match! The game is over and we found our winner!
                $results = array();
                $results[] =& $this->data['blocks'][$id];
                return $results;
            }
            
            $title = $this->_distill($block->getKeyword());
            if (($title == $keyword) &&
                (
                  (is_null($parent_keyword)) ||
                  (in_array($block->getParent(), $parents))
                )
               )
            {
                $results[] = array(
                    'block' => &$this->data['blocks'][$id],
                    'priority' => $priority);
            }
        }
        
        usort($results, array(&$this, '_sortResults'));
        
        // Return just the blocks
        $return = array();
        foreach ($results as &$result)
            { $return[] =& $result['block']; }
            
        return $return;
    }
    
    function _sortResults($a, $b)
    {
        // $a and $b are in the form: { priority: 512, block: ScBlock() }
        
        if ($a == $b) { return 0; }
        $ap = (int) $a['priority'];
        $bp = (int) $b['priority'];
        return ($ap > $bp) ? -1 : 1;
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
            $this->__ancestry = array();
        }
        elseif (!is_null($block->_supposed_parent))
        {
            // If there's a supposed parent, assume all previous ancestry
            // is broken and we'll be the thing
            $this->__ancestry = array();
        }
        else
        {
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

        }
        
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
    
    /* ======================================================================
     * Private functions
     * ====================================================================== */
    
    /*
     * Function: _verifyBlockTypes()
     * Verifies the block types; called by the constructor.
     * [Private, grouped under "Private functions"]
     */

    function _verifyBlockTypes()
    {
        if (!isset($this->options['block_types']))
            { return ScStatus::error("Block types is not valid!"); }
            
        if (!is_array($this->options['block_types']))
            { return ScStatus::error("Block types is not valid!"); }

        $this->options['type_keywords'] = array();

        foreach ($this->options['block_types'] as $id => &$block_type)
        {
            if (!isset($block_type['title_plural']))
                { $block_type['title_plural'] = strtoupper(substr($id,0,1)) . substr($id,1,999)."s"; }
                
            if (!isset($block_type['has_brief']))
                { $block_type['has_brief'] = FALSE; }
                
            if (!isset($block_type['parent_in_id']))
                { $block_type['parent_in_id'] = array(); }
                
            if (!isset($block_type['tags']))
                { $block_type['tags'] = array(); }
                
            if (!isset($block_type['priority']))
                { $block_type['priority'] = NULL; }
                
            if (!is_array($block_type['tags']))
                { $block_type['tags'] = (array) $block_type['tags']; }
                
            if (!is_array($block_type['parent_in_id']))
                { $block_type['parent_in_id'] = (array) $block_type['parent_in_id']; }
                
            if (!isset($block_type['short']))
                { $block_type['short'] = $id; }
                
            if (!isset($block_type['starts_group_for']))
                { $block_type['starts_group_for'] = array(); }
                
            if (!is_array($block_type['starts_group_for']))
                { $block_type['starts_group_for'] = (array) $block_type['starts_group_for']; }
                
            if (!isset($block_type['synonyms']))
                { $block_type['synonyms'] = array(); }
                
            if (!is_array($block_type['synonyms']))
                { $block_type['synonyms'] = (array) $block_type['synonyms']; }

            $this->options['type_keywords'][$id] = $id;
            foreach ($block_type['synonyms'] as $alias)
                { $this->options['type_keywords'][$alias] = $id; }
        }
    }
    
    /*
     * Function: _loadConfig()
     * Loads the configuration; called by the constructor.
     * [Private, grouped under "Private functions"]
     */

    function _loadConfig(&$Sc)
    {
        // Verify each required field
        foreach (array('name', 'output') as $required_field) {
            if (!isset($Sc->_config[$required_field]))
            {
                ScStatus::error(
                    'Configuration is missing the ' .
                    'required field "' . $required_field . '".');
                return;
            }
        }
        
        // Migrate defaults
        foreach (array('block_types', 'file_specs', 'tags', 'tag_synonyms') as $k)
        {
            $this->options[$k] = $Sc->defaults[$k];
        }
        
        // Load configuration variables
        foreach (array('block_types', 'name','output','src_path','exclude', 'tags') as $k)
        {
            if (!isset($Sc->_config[$k])) { continue; }
            $this->options[$k] = $Sc->_config[$k];
        }
            
        if (isset($Sc->_config['tags']))
        {
            if (isset($Sc->_config['reset_tags']))
                { $this->options['tags'] = array(); }
                
            $this->options['tags'] = array_merge(
                $this->options['tags'],
                (array) $Sc->_config['tags']);
        }
        
        if (isset($Sc->_config['tag_synonyms']))
        {
            $this->options['tag_synonyms'] = array_merge_recursive(
                $this->options['tag_synonyms'],
                (array) $Sc->_config['tag_synonyms']);
        }
        
        
            
        if (isset($Sc->_config['block_types']))
        {
            if (isset($Sc->_config['reset_block_types']))
                { $this->options['block_types'] = array(); }
                
            if (!is_array($Sc->_config['block_types']))
                { return ScStatus::error('block_types defined is not an array'); }
                
            foreach ($Sc->_config['block_types'] as $id => $data)
            {
                if (!isset($this->options['block_types'][$id]))
                    { $this->options['block_types'][$id] = $data; continue; }
                
                if (!is_array($data))
                    { return ScStatus::error('block_types has an invalid definition for "' . $id . '"'); }
                    
                foreach ($data as $k => $v)
                {
                    if (in_array($k, array('synonyms','parent_in_id','starts_group_for')))
                    {
                        $this->options['block_types'][$id][$k] =
                            array_merge((array) $this->options['block_types'][$id][$k], (array) $v);
                    }
                    else
                        { $this->options['block_types'][$id][$k] = $v; }
                }
            }
        }
    }
    
    
    /*
     * Function: _verifyConfig()
     * Verifies the configuration; called by the constructor.
     * [Private, grouped under "Private functions"]
     */
     
    function _verifyConfig(&$Sc)
    {
        // Check output
        if ((!is_array($this->options['output'])) ||
            (count($this->options['output']) == 0))
        {
            return ScStatus::error("You must define at least one output.");
        }
        
        // Check output
        foreach ($this->options['output'] as $id => $output)
        {
            if (!is_array($output))
                { return ScStatus::error("Output #$id is invalid."); }
            if (!isset($output['driver'])) { return ScStatus::error("Output #$id is missing a driver."); }
            if (!isset($output['path']))   { return ScStatus::error("Output #$id ({$output['driver']}) is missing it's output path."); }
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
                { return ScStatus::error('Source path is invalid: "' . $path . '"'); }
        }
    }
    
    function _fillOptionalConfig(&$Sc)
    {
        // Add Default output to spit out the .sourcescribe_index file
        $this->options['output']['serial'] = array('driver' => 'serial'); 
    }
    
    
    /*
     * Function: _loadOutputDrivers()
     * Loads the output drivers. Used in the build process.
     * [Grouped under "Private functions"]
     */

    function _loadOutputDrivers()
    {
        if (count($this->outputs) > 0)
            { return; }
            
        foreach ($this->options['output'] as $id => $output_options)
        {
            $this->outputs[$id] =&
                $this->Sc->loadOutputDriver($output_options['driver'],
                $this, $options);
                       
            if (!$this->outputs[$id]) {
                $this->Sc->notice('No output driver for ' . $driver . '.');
                continue;
            }
        }
    }
    
    /*
     * Function: _doPostBuild()
     * Does post-build actions like modifying the homepage.
     * [Private, grouped under "Private functions"]
     */

    function _doPostBuild()
    {
        ScStatus::updateStart("Post build");
        // If there's no homepage,
        // Make one!
        if (is_null($this->data['home']))
        {
            $this->register("Page: " . $this->getName());
        }
        
        // Finalize everything
        $block_count = 0;
        foreach ($this->data['blocks'] as &$block)
        {
            $block->preFinalize();
            if (++$block_count % 5 == 0)
                { ScStatus::update($block_count); }
        }
                
        // [1] If there's a home, [2] each of the tree firstlevels
        // [3] that isn't the homepage [4] will be the child of the homepage.
        if (!is_null($this->data['home']))
            foreach ($this->data['tree'] as $i => &$block)
                if ($block->getID() != 'index')
                    { $this->data['home']->registerChild($this->data['tree'][$i]); }
                    
        // Finalize everything
        $block_count = 0;
        foreach ($this->data['blocks'] as &$block)
        {
            $block->finalize();
            if (++$block_count % 5 == 0)
                { ScStatus::update($block_count); }
        }
        
        ScStatus::updateDone("$block_count blocks.");
    }
    
    /* ======================================================================
     * End
     * ====================================================================== */
}