<?php

/*
 * Class: ScBlock
 * A block
 */

class ScBlock
{
    /* ======================================================================
     * Private properties
     * ====================================================================== */
     
    var $Project;
    
    var $_data;
    var $valid = FALSE;
    
    // Property: $typename
    // The name of the type as defined in the first line
    var $typename = '';
    
    /* 
     * Property: $type
     * The proper name of the type.
     *
     * Description:
     *   Access the type of the block through [[getType()]]
     *   and [[getTypeData()]].
     * 
     * [Read-only, private]
     */
     
    var $type;
    
    /*
     * Property: $title
     * The raw title of the block.
     * 
     * Description:
     *   This is a read-only property. To get the title, use [[getTitle()]] as
     *   it will provide additional stuff.
     * 
     * [Read-only, private]
     */
    var $title;
    
    /*
     * Property: $content
     * The content in HTML format, as already parsed by [[toHTML()]].
     * 
     * [Read-only, private]
     */
     
    var $content;
    
    /*
     * Property: $brief
     * The brief description in HTML format.
     * 
     * [Read-only, private]
     */
     
    var $brief;
    
    /*
     * Property: $_id
     * The ID.
     * 
     * Description:
     *   Access the ID of the block through [[getID()]]. The ID is
     *   automatically-determined; it can not be user-set.
     * 
     * [Read-only, private]
     */
    var $_id = null;
    
    /* Property: $_group
     * The group.
     * 
     * Description:
     *   Access via [[getGroup()]], or the parent's [[getMemberLists()]].
     * 
     * [Read-only]
     */
    var $_group = NULL;
    
    /*
     * Property: $_parent
     * Reference to the parent in the index tree.
     *
     * Description:
     *   This is a private variable only used by the `ScBlock` class.
     *
     *   - To get the parent of the block, use [[getParent()]].
     *   - To get all it's parents, use [[getAncestry()]].
     *   - To register a block as a child of another block,
     *     use [[registerChild()]].
     * 
     * See also:
     *  - [[getParent()]]
     *  - [[getChildren()]]
     *  - [[registerChild()]]
     * 
     * [Read-only, private]
     */
    var $_parent;
    
    /*
     * Property: $_children
     *   An array of references to `ScBlock` instances that are the children of
     *   the current block.
     *
     * Description:
     *   This is a private variable only used by the `ScBlock` class.
     *
     *   - To get the children of the block, use [[getChildren()]].
     *   - To get the children in a grouped manner, use [[getMemberLists()]].
     *   - To register a block as a child of another block,
     *     use [[registerChild()]].
     * 
     * See also:
     *  - [[getParent()]]
     *  - [[getChildren()]]
     *  - [[registerChild()]]
     */
    var $_children = array();
    
    /*
     * Property: $_tags
     * Tags
     */
    var $_tags = array(); 
    
    /*
     * Property: $_subgroups
     * Subgroups.
     * 
     * Example:
     *   See [[getMemberLists()]] for an example of the data.
     */
     
    var $_subgroups = array();
     
    // ========================================================================
    // Constructor
    // ========================================================================
    
    /*
     * Constructor: ScBlock()
     * Yes.
     * 
     * [In group "Constructor"]
     */
     
    function ScBlock($str, &$Project)
    {
        if (is_callable(array($str, 'getID')))
        {
            foreach ($str as $k => $v)
                { $this->{$k} = $v; }
            return;
        }
            
        $this->Project =& $Project;

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
        if (!in_array($type_str, array_keys($Project->options['type_keywords'])))
            { return; }
        
        $this->type = $Project->options['type_keywords'][$type_str];
        $td = $this->getTypeData();
        
        // Find the tag hash
        // TODO: 'All below'
        foreach ($this->_lines as $i => $line)
        {
            preg_match('~^\[(.*?)\]$~', $line, $m);
            if (count($m) > 0)
            {
                $match = $m[count($m)-1];
                $this->parseTag($match);
                array_splice($this->_lines, $i, 1, array());
                break;
            }
        }
        
        // Trim trailing blank lines
        while (count($this->_lines) > 0)
        {
            if (trim($this->_lines[0]) != '') { break; }
            array_splice($this->_lines, 0, 1, array());
        }
        
        // If it can have a brief
        if ((isset($td['has_brief'])) && ($td['has_brief'] == TRUE) &&
            ((!isset($this->_skip_brief)) || (!$this->_skip_brief)))
        {
            // Look for a blank line
            $offset = array_search('', $this->_lines);
            if ($offset !== FALSE)
            {
                // Break at the first blank line
                $this->brief = array_slice($this->_lines, 0, $offset);
                $this->brief = $this->toHTML($this->brief);
                $this->_lines = array_slice($this->_lines, $offset+1);
            } else {
                // Everything is a brief description
                $this->brief = $this->toHTML($this->_lines);
                $this->_lines = array();
            }
        }
        
        $this->content = $this->toHTML($this->_lines);
        $this->valid = TRUE;
        
        unset($this->_lines);
        unset($this->_data);
    }
    
    /*
     * Function: parseTag()
     * Parses a tag
     *
     * Usage:
     *     $this->parseTag()
     *
     * Returns:
     *   Unspecified.
     * 
     * References:
     *   A delegate task of [[ScBlock()]].
     */

    function parseTag($tags)
    {
        $tag_list = array_map('trim', explode(',', $tags));
        $valid_tags = array_keys($this->Project->options['tags']);
        
        $vtags = $this->getValidTags();
        foreach ($tag_list as $tag)
        {
            // Match:
            // - [In [the]] group X
            // - [[filed] under [the]] group X
            // - Group[ed [under]] X
            // - Group X
            preg_match('~^(?:in (?:the )?|(?:(?:filed )?under (?:the )?))?group(?:ed(?: under)?)? (?:")?(.*?)(?:")?$~i',
              $tag, $m);
            if (count($m) > 0)
            {
                $this->_group = $m[count($m)-1];
                continue;
            }
            
            
            preg_match('~^(?:no|skip) (?:intro|introduction|brief|introductory(?: (?:paragraph|(?:desc(?:ription)))?)?)?$~i',
              $tag, $m);
            if (count($m) > 0)
            {
                $this->_skip_brief = 1;
                continue;
            }
             
            $tag = strtolower($tag);
            if (in_array($tag, array_keys($vtags)))
                { $this->_tags[] = $vtags[$tag]; }
        }
    }
    
    /*
     * Function: getValidTags()
     * Returns a list of valid tags in associative array format.
     * 
     * Sample output:
     *   It can output something like this.
     * 
     *     Array
     *     (
     *         "deprecated" => "deprecated",
     *         "deprec"     => "deprecated",
     *         "read-only"  => "read-only",
     *         "readonly"   => "read-only",
     *     )
     */

    function getValidTags()
    {
        
        $vtags = array();
        
        // Combine the project-wide tags and blocktype-specific tags...
        foreach (array_merge($this->Project->options['tags'], $this->getTypeData('tags')) as $tag)
            { $vtags[$tag] = $tag; }
            
        // And it's synonyms...
        $synonyms =& $this->Project->options['tag_synonyms'];
        foreach ($vtags as $vtag)
        {
            if (isset($synonyms[$vtag]))
            {
                $aliases = (array) $synonyms[$vtag];
                foreach ($aliases as $alias)
                    { $vtags[$alias] = $vtag; }
            }
        }
        
        // Into the valid tags
        return $vtags;
    }
    
    /*
     * Function: hasTags()
     * Returns if it has tags
     *
     * Usage:
     *     $this->hasTags()
     *
     * Returns:
     *   Unspecified.
     */

    function hasTags($p = NULL)
    {
        return (count($this->_tags) > 0) ? TRUE : FALSE;
    }
    
    /*
     * Function: getTags()
     * Returns the tags array
     *
     * Usage:
     *     $this->getTags()
     *
     * Returns:
     *   Unspecified.
     */

    function getTags()
    {
        return $this->_tags;
    }
    
    /*
     * Function: getGroup()
     * Returns the group
     *
     * Usage:
     *     $this->getGroup()
     *
     * Returns:
     *   NULL of no group, otherwise a string of the group
     */

    function getGroup()
    {
        return $this->_group;
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

    function& factory($input, &$project)
    {
        $return = new ScBlock($input, $project);
        $classname = $return->getTypeData('block_class');
        if (!is_null($classname))
            { $returnx = new $classname($return, $project); return $returnx; }
        return $return;
    }
    
    /*
     * Function: isHomePage()
     * Checks if the current block is the home page.
     *
     * Usage:
     *     $this->isHomePage()
     *
     * Returns:
     *   Unspecified.
     */

    function isHomePage()
    {
        if ($this->title == $this->Project->getName()) { return TRUE; }
        return FALSE;
    }
    
    // ========================================================================
    // Group: Content methods
    // [All below are grouped under "Content methods"]
    // ========================================================================
    
    /*
     * Function: getContent()
     * Returns the content of the block in HTML format.
     * 
     * Description:
     *   This also consults the virtual (overridable) functions
     *   [[getPreContent()]] and [[getPostContent()]] before returning the
     *   final output.
     * 
     * See also:
     *  - [[getPreContent()]]
     *  - [[getPostContent()]]
     * 
     * [Grouped under "Data functions"]
     */
     
    function getContent()
    {
        return $this->getPreContent() . $this->content . $this->getPostContent();
    }
    
    /*
     * Function: getPreContent()
     * Virtual function that lets subclasses make content before the content.
     * 
     * See also:
     *  - [[getContent()]]
     *  - [[getPostContent()]]
     */
     
    function getPreContent()
    {
        return '';
    }
    
    /*
     * Function: getPostContent()
     * Virtual function that lets subclasses make content before the content.
     * 
     * See also:
     *  - [[getContent()]]
     *  - [[getPreContent()]]
     */
     
    function getPostContent()
    {
        return '';
    }
    
    /*
     * Function: getTitle()
     * TBD
     * [Grouped under "Data functions"]
     */
     
    function getTitle()
    {
        return $this->title;
    }
    
    /*
     * Function: getLongTitle()
     * Returns a long title.
     * [Grouped under "Data functions"]
     */

    function getLongTitle()
    {
        $f = array();
        if ($this->hasParent())
        {
            $parent = $this->getParent();
            $f[] = $parent->getTitle() . ' ->';
        }
        $f[] = $this->getTitle();
        return implode(' ', $f);
    }
    
    /*
     * Function: getKeyword()
     * Returns the keyword for searching
     * [Grouped under "Data functions"]
     *
     * Usage:
     *     $this->getKeyword()
     *
     * Returns:
     *   Unspecified.
     */

    function getKeyword()
    {
        return $this->title;
    }
    
    /*
     * Function: getBrief()
     * TBD
     * [Grouped under "Data functions"]
     */
     
    function getBrief()
    {
        return $this->brief;
    }
    
    /*
     * Function: getType()
     * Returns the typename.
     *
     * Usage:
     *     $this->getType()
     *
     * Description:
     *   This returns the actual type, not the synonym used. For instance,
     *   a "Constructor: myclass()" block may return a type of `function`
     *   instead of 'constructor'. If you would like to see that instead
     *  (e.g., "constructor"), use [[getTypeName()]].
     * 
     * Returns:
     *   A string of the typename.
     * 
     * See also: 
     * - [[getTypeName()]]
     */

    function getType()
    {
        return $this->type;
    }
    
    /*
     * Function: getTypeName()
     * Returns the type name.
     */

    function getTypeName()
    {
        return $this->typename;
    }
    
    /*
     * Function: getTypeData()
     *   Returns the data for the type in the class, as defined
     *   in [[Scribe::$Options]].
     */
     
    function getTypeData($p = NULL)
    {
        global $Sc;
        if (!isset($this->Project->options['block_types'])) { return; }
        if (!isset($this->Project->options['block_types'][(string) $this->type])) { return; }
        
        $type = $this->Project->options['block_types'][(string) $this->type];
        if (is_string($p)) {
            if (isset($type[$p])) { return $type[$p]; }
            else { return NULL; }
        }
            
        return $type;
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
     * 
     * [Grouped under "Data functions"]
     */

    function getID()
    {
        if ((is_null($this->_id)) && ($this->isHomePage()))
            { $this->_id = 'index'; }
            
        elseif (is_null($this->_id))
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
    
    /* ======================================================================
     * Private methods
     * ====================================================================== */
    
    /*
     * Function: _toID()
     * Uhm.
     * 
     * [Grouped under "Private methods"]
     */
     
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

    /*
     * Function: toHTML()
     * Processes plaintext (ripped from the source files) and converts them
     * to HTML.
     * 
     * Description:
     *   This relies on the Markdown library to parse out the content.
     * 
     * [Grouped under "Private methods"]
     */
     
    function toHTML($lines)
    {
        if (is_array($lines)) { $str = implode("\n", $lines); }
        else { $str = (string) $lines; }
        
        // Convert "Usage:" to H2's
        $str = preg_replace('~(?:^|\\r|\\n)([A-Za-z0-9\- ]+):[\\r\\n]~sm',
            "\n## \\1\n\n", $str);
            
        // Convert to dl/dt/dd
        $str = preg_replace('~ *([a-zA-Z0-9`_\$\.\*]+) +- (.*?)([\\r\\n$])~s',
            "\n\\1\n: \\2\\3", $str);
        
        // Convert [[]] links
        $str = preg_replace('~\[\[(.*?)\]\]~s', "<a href=#>\\1</a>", $str);
        
        // return '<pre>' . htmlentities($str) . '</pre>';
        $str = markdown($str);

        // Wrap heading texts inside span elements
        $str = preg_replace('~(<h[1-6]>)(.*?)(</h[1-6]>)~s',
               '\\1<span>\\2</span>\\3', $str);
               
        // Wrap DTs inside span elements
        foreach (array('dt' => 'term',
                       'dd' => 'definition')
                 as $tag => $classname)
        {
            $str = preg_replace("~(<$tag>)(.*?)(</$tag>)~s",
                   '\\1<span class="'.$classname.'">\\2</span>\\3', $str);
        }
        
        return $str;
    }
    
    /*
     * Function: hasContent()
     * Checks if the block has content.
     */

    function hasContent()
    {
        return (trim((string) $this->content) == '') ? FALSE : TRUE;
    }
    
    function _toLinkCallback($m)
    {
        $url = '#';
        $results = $this->Project->lookup($m[1]);
        if (count($results) > 0)
            { $url = '#' . $results[0]->getID(); }
        return "<a href=\"$url\">$m[1]</a>";
    }
    
    /*
     * Function: finalize()
     * Ran when building is done
     */

    function finalize()
    {
       // "Finalizing: " . $this->getID() . "\n";
       $this->content = preg_replace_callback('~\<a href=#\>(.*?)\</a\>~s',
                 array($this, '_toLinkCallback'), $this->content);
       $this->brief = preg_replace_callback('~\<a href=#\>(.*?)\</a\>~s',
                 array($this, '_toLinkCallback'), $this->brief);
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
     *   This is called by [[ScProject::register()]]. The block passed onto
     *   this function should be already fully initialized.
     *
     * Returns:
     *   Nothing.
     * 
     * [Grouped under "Private methods"]
     */

    function registerChild(&$child_block)
    {
        // Register to children and parent
        $this->_children[] =& $child_block;
        $child_block->_parent =& $this;
        
        // Register to subgroups
        $group = $child_block->getGroup();
        if (is_null($group)) { $group = $child_block->getTypeData('title_plural'); }
        
        // Initialize the group if it hasn't been yet
        if (!isset($this->_subgroups[$group]))
        {
            $this->_subgroups[$group] = array(
                'title'  => $group,
                'members' => array()
            );
        }
        
        $this->_subgroups[$group]['members'][] = $child_block;
        return;
    }
    
    /* ======================================================================
     * Traversion functions
     * ====================================================================== */
    
    /*
     * Function: getChildren()
     * Returns the list of children.
     *
     * Usage:
     *     Array $block->getChildren()
     *
     * Returns:
     *   An array of [[ScBlock]] instances.
     * 
     * [Grouped under "Traversion functions"]
     */

    function& getChildren()
    {
        return $this->_children;
    }
    
    /*
     * Function: hasChildren()
     * Checks if the block has children.
     */

    function hasChildren()
    {
        return (count($this->_children) > 0) ? TRUE : FALSE;
    }
    
    /*
     * Function: getParent()
     * Returns the parent node.
     *
     * Usage:
     *     ScBlock $block->getParent()
     *
     * Returns:
     *   The parent ([[ScBlock]] instance).
     * 
     * [Grouped under "Traversion functions"]
     */

    function& getParent()
    {
        $return = NULL;
        
        # No parent for home mpage
        if ($this->isHomePage())
            { return $return; }
        
        if ((is_callable(array($this->_parent, 'getID'))) &&
            ($this->_parent != $this))
            { $return = $this->_parent; }
        else
            { $return = NULL; }
        
        return $return;
    }
    
    /*
     * Function: getAncestry()
     * Returns all the parent nodes.
     *
     * Usage:
     *     ScBlock $block->getAncestry([$options])
     *
     * Description:
     *   Options can be defined as an associative array. All of these are
     *   optional.
     * 
     *     exclude_home  - If the home page is to be excluded
     *     include_this  - If this block is to be included
     * 
     * Returns:
     *   An array of parent [[ScBlock]] instances. The last item will be
     *   the immediate parent, and the first item will be the most senior
     *   grandparent (i.e., home page).
     * 
     * Example:
     * 
     *   The output of this function would often look similar to this format.
     * 
     *     array(
     *       ScBlock(..), // Home
     *       ScBlock(..), // Class MyClass
     *       ScBlock(..), // myFunction()
     *     );
     * 
     * [Grouped under "Traversion functions"]
     */

    function& getAncestry($options = array())
    {
        $options = (array) $options;
        $block = $this;
        $f = array();
        $i = 0;
        $blocks = array();
        if ((isset($options['include_this'])) && ($options['include_this']))
        {
            if ((!$this->isHomePage()) || (!isset($options['exclude_home']))
                || (!$options['exclude_home']))
                    { $f[] =& $this; }
        }
            
        while (TRUE) {
            if (!$block->hasParent()) { break; }
            $parent =& $block->getParent();
            
            // Exclude the home page if we're asked to
            if ((!$parent->hasParent()) && (isset($options['exclude_home']))
                && ($options['exclude_home']))
                { break; }
            
            $blocks[$i] =& $block->getParent();
            array_unshift($f, &$blocks[$i]);
            $block =& $blocks[$i++];
        }
        return $f;
    }
    
    /*
     * Function: hasParent()
     * Checks if the current block has a parent.
     *
     * Usage:
     *     $this->hasParent()
     *
     * Returns:
     *   Unspecified.
     * 
     * [Grouped under "Traversion functions"]
     */

    function hasParent()
    {
        return (is_null($this->getParent())) ? FALSE : TRUE;
    }
    
    /*
     * Function: getMemberLists()
     * Returns a grouped list of member items under the block.
     *
     * Usage:
     *     $this->getMemberLists()
     *
     * Description:
     *   This is used by outputs to list down the members of a class
     *   (or any other block with children).
     * 
     * Example:
     *     var_dump($block->getMemberLists());
     * 
     * Possible output: 
     * 
     *     array
     *     (
     *         'properties' => array
     *         (
     *             'title' => 'Member properties',
     *             'members' => array( ScBlock, ScBlock, ScBlock, ... )
     *         ),
     *         'methods' => array
     *         (
     *             <same as above>
     *         ),
     *         ...and so on
     *     )
     * 
     * Returns:
     *   Unspecified.
     * 
     * [Grouped under "Traversion functions"]
     */

    function& getMemberLists()
    {
        return $this->_subgroups;
        
        $f = array();
        foreach ($this->getChildren() as $node)
        {
            $type = $node->getType();
            if (!isset($f[$type]))
            {
                $f[$type] = array();
                $f[$type]['title'] = 'Member ' . $type . 's';
                $f[$type]['members'] = array();
            }
            
            $f[$type]['members'][] = $node;
        }
        return $f;
    }
}

class ScClassBlock extends ScBlock
{
    function getBrief()
    {
        return parent::getBrief();
    }
    
    function getTitle()
    {
        return 'Class ' . $this->title;
    }
}