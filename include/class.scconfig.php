<?php

class ScConfig
{
    /* Class: ScConfig
     * Wee
     * 
     * [Filed under "API Reference"]
     */
    
    function ScConfig(&$Sc)
    {
        /* Function: ScConfig()
         * Constructor.
         */

        $this->Sc = $Sc;
        
        // Find config file
        $config_file = $Sc->findConfigFile();
        if ($config_file === FALSE)
            { ScStatus::error('No config file found. You may generate one using `ss makeconfig`.'); return; }
        
        // Load config file and validate
        $this->cwd = dirname($config_file);
        $this->_config = yaml($config_file);
        if (!is_array($this->_config))
            { ScStatus::error('Configuration file is invalid.'); return; }
    }
    
    
    function load()
    {
        /* Function: load()
         * Loads the configuration; called by the constructor.
         * [Private, grouped under "Private functions"]
         */
        
        $Sc =& $this->Sc;
     
        // Verify each required field.
        // It is loaded from $this->_config, which is the YAML file.
        foreach (array('name', 'output') as $required_field) {
            if (!isset($this->_config[$required_field]))
            {
                ScStatus::error(
                    'Configuration is missing the ' .
                    'required field "' . $required_field . '".');
                return;
            }
        }
        
        // Initialize options with defaults
        foreach ($this->defaults as $k => $v)
            { $this->options[$k] = $v; }
        
        // Load configuration variables
        foreach (array('name', 'output', 'src_path', 'exclude', 'tags') as $k)
        {
            if (!isset($this->_config[$k])) { continue; }
            $this->options[$k] = $this->_config[$k];
        }
            
        if (isset($this->_config['tags']))
        {
            if (isset($this->_config['reset_tags']))
                { $this->options['tags'] = array(); }
                
            $this->options['tags'] = array_merge(
                $this->options['tags'],
                (array) $this->_config['tags']);
        }
        
        if (isset($this->_config['exclude_tags']))
        {
            $this->options['exclude_tags'] = (array) $this->_config['exclude_tags'];
        }
        
        if (isset($this->_config['tag_synonyms']))
        {
            $this->options['tag_synonyms'] = array_merge_recursive(
                $this->options['tag_synonyms'],
                (array) $this->_config['tag_synonyms']);
        }
        
        
        if (isset($this->_config['include']))
        {
                
            if (!is_array($this->_config['include']))
                { return ScStatus::error('"include" defined is not an array'); }
            
            $this->options['include'] = array();
            foreach ($this->_config['include'] as $k => $v)
            {
                if (is_numeric($k))
                    { $this->options['include'][$v] = 'default'; }
                else
                    { $this->options['include'][$k] = $v; }
            }
        }    
            
        if (isset($this->_config['block_types']))
        {
            if (isset($this->_config['reset_block_types']))
                { $this->options['block_types'] = array(); }
                
            if (!is_array($this->_config['block_types']))
                { return ScStatus::error('block_types defined is not an array'); }
                
            foreach ($this->_config['block_types'] as $id => $data)
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
        
        $this->_verify();
        $this->_postVerify();
        $this->_verifyBlockTypes();
        return $this->options;
    }
    
    function _verify()
    {
        /* Function: _verify()
         * Verifies the configuration; called by [[load()]].
         * 
         * Returns:
         *   Returns TRUE on pass, exits otherwise.
         * 
         * [Private, grouped under "Private functions"]
         */
     
        $Sc =& $this->Sc;
        
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
        
        return TRUE;
    }

    function _postVerify()
    {
        // Add Default output to spit out the .sourcescribe_index file
        $this->options['output']['serial'] = array('driver' => 'serial'); 
    }
    
    function _verifyBlockTypes()
    {
        /* Function: _verifyBlockTypes()
         * Verifies the block types; called by [[load()]].
         * 
         * Returns:
         *   TRUE on success; dies if something is wrong.
         * 
         * [Private, grouped under "Private functions"]
         */
         
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
                
            if (!isset($block_type['default_order']))
                { $block_type['default_order'] = 0; }
                
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
        
        return TRUE;
    }
    
    /*
     * Property: $Sc
     * Reference to the [[Sc]] instance.
     */
     
    var $Sc;
    
    /*
     * Property: $_config
     * Raw config as loaded from the YAML file. 
     */
    
    var $_config;
    
    /*
     * Property: $options
     * The actual options.
     */
     
    var $options;
    
    /*
     * Property: $defaults
     * An array containing default options.
     */
     
    var $defaults = array
    (   
        'block_types' => array
        (
            'page' => array(
                'title_plural' => 'Pages',
                'short' => '',
                'has_brief' => FALSE,
                'synonyms' => array('page', 'section'),
                'default_order' => 10,
            ),
            'function' => array(
                'title_plural' => 'Functions',
                'has_brief' => TRUE,
                'parent_in_id' => array('class'),
                'short' => 'fn',
                'title_suffix' => '()',
                'synonyms' => array('constructor', 'ctor', 'destructor', 'dtor', 'method'),
                'tags' => array('static', 'private', 'public', 'protected', 'virtual'),
                'default_order' => 0,
            ),
            'var' => array(
                'title_plural' => 'Properties',
                'has_brief' => TRUE,
                'parent_in_id' => array('class'),
                'short' => 'var',
                'synonyms' => array('property'),
                'title_prefix' => '$',
                'tags' => array('read-only', 'private', 'public', 'protected', 'constant'),
                'default_order' => -5,
            ),
            'class' => array(
                'title_plural' => 'Classes',
                'has_brief' => TRUE,
                'starts_group_for' => array('var', 'function'),
                'priority' => 8,
                'title_prefix' => 'Class ',
                // 'block_class' => 'ScClassBlock',
                'tags' => array('interface', 'abstract'),
                'short' => 'class',
                'default_order' => 0,
            ),
            'module' => array(
                'title_plural' => 'Sections',
                'has_brief' => TRUE,
                'priority' => 4,
                'starts_group_for' => array('page', 'class', 'function', 'var'),
                'short' => 'mod',
                'synonyms' => array('file'),
                'default_order' => 20,
            ),
        ),
        
        'include' => array
        (
            '\.inc$'  => 'default',
            '\.rb$'   => 'default',
            '\.py$'   => 'default',
            '\.js$'   => 'default',
            '\.as$'   => 'default',
            '\.c$'    => 'default',
            '\.d$'    => 'default',
            '\.sql$'  => 'default',
            '\.nse$'  => 'default',
            '\.cpp$'  => 'default',
            '\.java$' => 'default',
            '\.m$'    => 'default',
            '\.sh$'   => 'default',
            '\.cs$'   => 'default',
            '\.h$'    => 'default',
            '\.pl$'   => 'default',
            '\.perl$' => 'default',
            '\.php[3-5]?$' => 'default',
            '\.doc.txt$' => 'default'
        ),
        
        'tags' => array('deprecated', 'unimplemented', 'internal'),
        
        'exclude_tags' => array(),
        
        'tag_synonyms' => array(
            'read-only' => array('readonly'),
            'deprecated' => array('deprec'),
            'constant' => array('const'),
        ),
    );
}