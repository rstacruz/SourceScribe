<?php

/*
 * Class: ScBlock
 * A block
 */

class ScBlock
{
    var $_data;
    var $valid = FALSE;
    
    // Property: $typename
    // The name of the type as defined in the first line
    var $typename = '';
    
    // Property: $type
    // The proper name of the type
    var $type;
    var $title;
    var $content;
    var $brief;
    
    // Property: $_id
    // Read only
    var $_id = null;
    
    // Property: $_index
    // Reference to it's location in the index tree.
    var $_index;
    
    // Property: $_parent
    // Reference to the parent in the index tree.
    var $_parent;
    
    // Property: $_children
    var $_children = array();
    
    function ScBlock($str)
    {
        global $Sc;
        
        // Get the lines
        $this->_data = $str;
        $lines = str_replace(array("\r\n","\r"), array("\n","\n"), $str);
        $this->_lines = explode("\n", $str);
        
        // Check: the first line has to have a type
        $title_line = $this->_lines[0];
        if (strpos($title_line, ':') === FALSE) { return; }
        
        // Get the type keyword.
        // For instance, in ("Class: MyClass"), it's 'class'
        // Then check if it exists in the defined type_keywords
        $type_str    = trim(substr($title_line, 0, strpos($title_line, ':')));
        $this->title = trim(substr($title_line, strpos($title_line, ':')+1, 99999)); 
        $this->typename = $type_str;
        $type_str = trim(strtolower($type_str));
        $this->_lines = array_slice($this->_lines, 1);
        
        // Check: the first line has to have a *valid* type
        if (!in_array($type_str, array_keys($Sc->Options['type_keywords'])))
            { return; }
        
        $this->type = $Sc->Options['type_keywords'][$type_str];
        
        // If it can have a brief
        if ((isset($this->type['has_brief'])) && ($this->type['has_brief']))
        {
            // Look for a blank line
            $offset = array_search('', $this->_lines);
            if ($offset !== FALSE)
            {
                // Break at the first blank line
                $this->brief = array_slice($this->_lines, 0, $offset);
                $this->brief = $this->mkdn($this->brief);
                $this->_lines = array_slice($this->_lines, $offset+1);
            } else {
                // Everything is a brief description
                $this->brief = $this->mkdn($this->_lines);
                $this->_lines = array();
            }
        }
        
        $this->content = $this->mkdn($this->_lines);
        $this->valid = TRUE;
        
        unset($this->_lines);
        unset($this->_data);
    }
    
    function _toID($p, $limit=128, $underscore = '', $lower = FALSE)
    {	
    	preg_match_all('/[^a-zA-Z0-9]+|(.)/',$p, $m);
    	$ff = ''; $f = '';
    	foreach ($m[1] as $ch)  { $ff .= ($ch != '') ? $ch : ' '; }
    	//foreach (explode(' ', trim($ff)) as $word)
    	//    { $f .= strtoupper(substr($word,0,1)) . (substr($word,1)); }
    	$f = str_replace(' ', '_', trim($ff));
    	return substr($f, 0, $limit);
    }
    
    function getContent()
    {
        return $this->content;
    }
    
    function getTitle()
    {
        return $this->title;
    }
    
    function getBrief()
    {
        return $this->brief;
    }
    
    /*
     * Function: getTypeData()
     * Returns the data for the type in the class, as defined in `$Sc->Options`.
     */
     
    function getTypeData($p = NULL)
    {
        global $Sc;
        if (!isset($Sc->Options['block_types'])) { return; }
        if (!isset($Sc->Options['block_types'][(string) $this->type])) { return; }
        
        $type = $Sc->Options['block_types'][(string) $this->type];
        if (is_string($p)) {
            if (isset($type[$p])) { return $type[$p]; }
            else { return NULL; }
        }
            
        return $type;
    }
    
    function mkdn($lines)
    {
        if (is_array($lines)) { $str = implode("\n", $lines); }
        else { $str = (string) $lines; }
        
        // Convert "Usage:" to H2's
        $str = preg_replace('~(?:^|\\r|\\n)([A-Za-z0-9\- ]+):[\\r\\n]~sm',
            "\n## \\1\n\n", $str);
            
        // Convert to dl/dt/dd
        $str = preg_replace('~ *([a-zA-Z0-9_\$\.\*]+) +- (.*?)([\\r\\n$])~s',
            "\n\\1\n: \\2\\3", $str);
        
        // return '<pre>' . htmlentities($str) . '</pre>';
        $str = markdown($str);
        $str = preg_replace('~(<h[1-6]>)(.*?)(</h[1-6]>)~s', '\\1<span>\\2</span>\\3', $str);
        
        return $str;
    }
    
    /*
     * Function: registerChild()
     * Registers a block as a child of this block.
     *
     * Usage:
     * > $block->registerChild($child)
     * 
     * Parameters:
     *   $child   - (ScBlock) The child.
     * 
     * Description:
     *   This is called by `ScProject::register()`.
     *
     * Returns:
     *   Nothing.
     */

    function registerChild(&$child_block)
    {
        $this->_children[] =& $child_block;
        $child_block->_parent =& $this;
        return;
    }
    
    /*
     * Function: factory()
     * Creates an ScBlock instance with the right class as needed.
     * [Static]
     *
     * Usage:
     * > ScBlock::factory()
     *
     * Returns:
     *   Unspecified.
     * 
     */

    function& factory($input)
    {
        $return = new ScBlock($input);
        return $return;
    }
    
    /*
     * Function: getChildren()
     * Returns the list of children.
     *
     * Usage:
     * > $block->getChildren()
     *
     * Returns:
     *   An array of ScBlock instances.
     */

    function& getChildren()
    {
        return $this->_children;
    }
    
    /*
     * Function: getParent()
     * Returns the parent node.
     *
     * Usage:
     * > $block->getParent()
     *
     * Returns:
     *   The parent (ScBlock instance).
     */

    function& getParent()
    {
        return $this->_parent;
    }
    
    /*
     * Function: getID()
     * Returns the unique ID string for this node.
     *
     * Usage:
     * > $block->getID()
     *
     * Returns:
     *   Unspecified.
     */

    function getID()
    {
        if (is_null($this->_id))
        {
            // Initialize
            $id_tokens = array();
            $parent =& $this->getParent();
            $td = $this->getTypeData();
            
            // Add the parent as another suffix, if we want it there
            if ((isset($td['parent_in_id'])) &&
                (is_callable(array($parent, 'getTypeData'))) &&
                (in_array(strtolower($parent->type), $td['parent_in_id'])))
            {
                $id_tokens[] = $this->_toID($parent->title);
            }
            
            // Add a suffix of the abbreviation of the type
            // (e.g., "function" => "fn")
            if (isset($td['short']))
                { $id_tokens[] = $this->_toID($td['short']); }
            
            // Add our own title, and finalize
            $id_tokens[] = $this->_toID($this->title);
            $this->_id = implode('.', $id_tokens);
        }
        return $this->_id;
    }
}