<?php

class ScProject
{
    // ========================================================================
    /* Class: ScProject
     * The project.
     * 
     * Description:
     *   This is a sub-singleton of class [[Scribe]], and is initialized on 
     *   it's constructor. It may be accessed through the main singleton (the 
     *   `$Sc` global variable) using something like the example below: 
     * 
     *      global $Sc;
     *      echo "The project's name is:" . $Sc->Project->getName();
     * 
     * [Filed under "API reference"]
     */
     
    function ScProject(&$Sc)
    {
        /* Function: ScProject()
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
         * [Grouped under "Constructor", internal]
         */
         
        $this->Sc =& $Sc;
        $this->cwd = $Sc->cwd;
        $Sc->Config->load();
        $this->options =& $Sc->Config->options;
    }
    
    /* Functions
     * ====================================================================== */

    function build()
    {
        /* Function: build()
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
         *   2. These readers will parse out the files and call [[register()]]
         *      when it finds a block.
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
         
        ScStatus::status('Starting build process:');
        // Scan the files
        ScStatus::updateStart("Scanning files");
        $file_count = 0;
        $options = array('recursive' => 1, 'mask' => '/./', 'fullpath' => 1);

        // Take the exclusions list into consideration
        if (isset($this->options['exclude']))
        {
            $options['exclude'] = array();
            foreach ($this->options['exclude'] as $ex) {
            $options['exclude'][] = ':' . $ex . ':';
            }
        }
        
        // For each source path...
        foreach ((array) $this->options['src_path'] as $path)
        {
            // Parse each of the files
            $files = aeScandir($path, $options);
            foreach ($files as $file)
            {
                // Find out which reader it's assigned to
                foreach ($this->options['include']
                         as $spec => $reader_name)
                {
                    if (preg_match("~$spec~", $file) == 0) { continue; }
                
                    // Show status of what file we're reading
                    $file_min = substr(realpath($file),
                        1 + strlen(realpath($this->cwd)));
                    ScStatus::update("[$reader_name] $file_min");
                    $file_count++;
                
                    // And read it
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
    
    function& lookup($raw_keyword, $reference = NULL)
    {
        /* Function: lookup()
         * Looks up a keyword and returns the matching blocks.
         *
         * Usage:
         *     $this->lookup($keyword, $reference)
         *
         * Returns:
         *   Unspecified.
         */
         
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
            
            
            // Check if the last search keyword matches that of the block's
            // (If last keyword is a match, and the parent keyword is a match too if it applies)
            $title = $this->_distill($block->getKeyword());
            if (($title == $keyword) &&
                ((is_null($parent_keyword)) || (in_array($block->getParent(), $parents)))
               )
            {
                if ((!is_null($reference)) && (is_callable(array($reference, 'hasParent'))))
                {
                    // Not really working
                    // Child bonus (reference is the parent of the block)
                    if (($block->hasParent()) && ($block->getParent() == $reference))
                        { $priority += 256; }
                    
                    // Not really working
                    // Sibling bonus (the block and the reference share same parent)
                    if (($block->hasParent()) &&
                        ($reference->hasParent()) &&
                        ($block->getParent() == $reference->getParent()))
                        { $priority += 128; }
                }
                
                $results[] = array(
                    'block' => &$this->data['blocks'][$id],
                    'priority' => $priority);
            }
        }
        
        // Sort by priorities
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
     
    function register($blockData)
    {
        /* Function: register()
         * Registers a block.
         * 
         * Description:
         *   This is called by readers.
         * 
         *   Sample input would be something like below.
         *
         *     "Function: test()\nDescription.\n\nEtc etc"
         */
         
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
    
    function getName()
    {
    /* Function: getName()
     * Returns the name of the project.
     *
     * Usage:
     *      $this->getName()
     *
     * Returns:
     *   The name of the project as defined in the configuration file.
     */
        // This should never have to be done; 'name' is a required field
        return isset($this->options['name']) ? $this->options['name'] : 'Manual';
    }
    
    /* Private functions
     * ====================================================================== */

    function _loadOutputDrivers()
    {
    /* Function: _loadOutputDrivers()
     * Loads the output drivers. Used in the build process.
     * [Grouped under "Private functions"]
     */
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
    
    function _doPostBuild()
    {
    /* Function: _doPostBuild()
     * Does post-build actions like modifying the homepage.
     * [Private, grouped under "Private functions"]
     */
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
    
    /* Properties
     * ====================================================================== */
     
    /* Property: $Sc
     * Reference to the [[Scribe]] singleton.
     */
    var $Sc;
    
    /* Property: $cwd
     * The current working directory of the project (i.e., where the config is).
     */
    var $cwd;
    
    /* Property: $outputs
     * Array of output drivers ([[ScOutput]] instances).
     */
    var $outputs = array();
    
    /* Data properties
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
     
    /* Property: $options['include']
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
     *     array('\.php3', '\.git/')
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
}