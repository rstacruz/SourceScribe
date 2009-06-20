<?php

/*
 * Class: ScBlock
 * A page of sorts.
 * 
 *   Every comment block is defined as an `ScBlock`. A reader ([[ScReader]])
 *   will scan files, and for every comment block it encounters, it uses
 *   [[ScProject::register()]] to register a block instance to the project,
 *   which is created through [[factory()]].
 * 
 *   Each block has: 
 * 
 *    - A block type
 *    - Title
 *    - Brief description (optional)
 *    - Content
 *    - Members
 * 
 * Subclassing:
 *   This class provides a few protected methods. These methods are provided
 *   to be overridden for subclasses. 
 * 
 * [Filed under "API reference"]
 */

class ScBlock
{     
    // ========================================================================
    // Factory
    // ========================================================================
    
    /*
     * Function: parse()
     * Test
     */
     
    /*
     * Function: factory()
     * Creates an ScBlock instance with the right class as needed.
     * [Static, grouped under "Factory"]
     */

    function& factory($input, &$project, $the_classname = 'ScBlock')
    {
        $return = new $the_classname($input, $project);
        $classname = $return->getTypeData('block_class');
        if (!is_null($classname))
            { $returnx = new $classname($return, $project); return $returnx; }
        return $return;
    }
    
    // ========================================================================
    // ...
    // ========================================================================
    
    /*
     * Constructor: ScBlock()
     * The constructor. (Don't call)
     * 
     * [Protected, grouped under "Protected methods"]
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
     * Parses a tag (A delegate task of [[ScBlock()]]).
     * 
     * Usage:
     *     $block->parseTag($tag_line)
     *     (Don't call this function.)
     * 
     * Parameters:
     *   $tag_line  - The line; the text inside the `[` and `]` brackets
     * 
     * Description:
     *   This function parses out a tag line in the comment block
     *   (e.g., `[read-only, private]`). It will then set [[$_tags]] and other
     *   effects as needed. It is called by the [[ScBlock()]] constructor
     *   during the process of parsing the comment block.
     * 
     *   You may override this function parse out special commands from the
     *   tag line (`[...]`) blocks.
     *
     * Returns:
     *   Nothing; this function will do things (set groups, add tags, etc)
     *   in place.
     */

    function parseTag($tags)
    {
        // Input looks like: "read-only, grouped under mygroup, private"
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
            
            // Filed under ...
            preg_match('~^(?:filed )?under(?: the (?:.*?))? (?:")?(.*?)(?:")?$~i',
              $tag, $m);
            if (count($m) > 0)
            {
                $parent_keyword = $m[count($m)-1];
                $this->_supposed_parent = $parent_keyword;
                continue;
            }
            
            // Inherits/extends ...
            preg_match('~^(?:inherits|extends) (?:")?(.*?)(?:")?$~i',
              $tag, $m);
            if (count($m) > 0)
            {
                $parent_keyword = $m[count($m)-1];
                $this->_inherit_parent = $parent_keyword;
                continue;
            }
            
            // No brief
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
     * Description:
     *   This function returns a thesaurus of sorts as key-value pairs. The
     *   keys are the valid tag names (including synonyms), and the values
     *   associated with them are what they are a synonym for.
     * 
     *     Array
     *     (
     *         "deprecated" => "deprecated",
     *         "deprec"     => "deprecated",
     *         "read-only"  => "read-only",
     *         "readonly"   => "read-only",
     *     )
     * 
     * See also:
     *   [[getTags()]]
     *   : To retrieve the actual tags defined for the block
     * 
     *   [[hasTags()]]
     *   : To check if the there are tags defined
     * 
     * [Grouped under "Tag functions"]
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
     * Checks if the current block has tags defined.
     *
     * Usage:
     *     $this->hasTags()
     *
     * Returns:
     *   `TRUE` or `FALSE`.
     * 
     * See also:
     *   [[getTags()]]
     *   : To retrieve the actual tags
     * 
     *   [[getValidTags()]]
     *   : To check what tags the block is allowed to have
     * 
     * [Grouped under "Tag functions"]
     */

    function hasTags($p = NULL)
    {
        return (count($this->_tags) > 0) ? TRUE : FALSE;
    }
    
    /*
     * Function: getTags()
     * Returns the tags for the block.
     *
     * Usage:
     *     $block->getTags()
     *
     * Description:
     *   For the descriptions that containing tag lines (in the format
     *   `[Private, read-only]`), this function will return those tags associated
     *   with the blocks, which in the example is *private* and *read-only*.
     * 
     *   The tags allowed for any block is defined in it's `block_types`
     *   definition in the configuration, and in the project's configuration
     *   for `tags`.
     * 
     *   Tag synonyms defined in the configuration (`tag_synonyms`) are also
     *   taken into account. For instance, if a description contains `[readonly]`
     *   and *readonly* is considered as a synonym for *read-only*, the tag
     *   that will be returned will be `read-only`.
     *
     * Returns:
     *   An array of tag strings.
     * 
     * Example:
     *   This example will retrieve tags.
     * 
     *     echo "Tags for the home page:\n";
     *     foreach ($Project->data['home']->getTags() as $tag)
     *       { echo "- $tag\n"; }
     *
     *   Possible output: 
     *   
     *     Tags for the home page: 
     *     - deprecated
     *     - private
     * 
     * See also:
     *   [[hasTags()]]
     *   : To check if the there are tags defined for the block
     *     (equivalent to `count(getTags()) > 0`)
     * 
     *   [[getValidTags()]]
     *   : To check what tags the block is allowed to have
     * 
     * [Grouped under "Tag functions"]
     */

    function getTags()
    {
        return $this->_tags;
    }
    
    /*
     * Function: getGroup()
     * Returns the group where the block belongs to.
     *
     * Usage:
     *     $this->getGroup()
     *
     * Description:
     *   For the descriptions that contain `[Grouped under "My group name"]`
     *   lines, this will return the group for the current block (which in the
     *   example is "My group name").
     * 
     * Returns:
     *   NULL of no group, otherwise a string of the group
     */

    function getGroup()
    {
        return $this->_group;
    }
    
    /*
     * Function: isHomePage()
     * Checks if the current block is the home page.
     * 
     * Usage:
     *     $block->isHomePage()
     * 
     * Description:
     *   The home page block is defined as the one having the same title as
     *   the `name` defined in the configuration. For instance, if `name` is
     *   set to "SourceScribe manual", having a page with that name will
     *   automatically assign that to be a homepage.
     * 
     *   An empty home page will be created if there is none defined.
     * 
     *   Home page blocks always have the ID of `index`, as you can find out
     *   with [[getID()]].
     * 
     * Returns:
     *   `TRUE` if the block is the homepage block, otherwise `FALSE`.
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
     * Function: hasContent()
     * Checks if the block has content.
     * [Grouped under "Data functions"]
     */

    function hasContent()
    {
        return (trim((string) $this->content) == '') ? FALSE : TRUE;
    }
    
    /*
     * Function: getPreContent()
     * Virtual function that lets subclasses make content before the content.
     * 
     * See also:
     *  - [[getContent()]]
     *  - [[getPostContent()]]
     * 
     * [Protected, grouped under "Protected methods"]
     */
     
    function getPreContent()
    {
        return '';
    }
    
    /*
     * Function: getPostContent()
     * Virtual function that lets subclasses make content after the content.
     * 
     * See also:
     *  - [[getContent()]]
     *  - [[getPreContent()]]
     * 
     * [Protected, grouped under "Protected methods"]
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
        $title = $this->title;
        
        $pre = $this->getTypeData('title_prefix');
        if ((is_string($pre)) &&
            (strtolower(substr($title, 0, strlen($pre))) !=
             strtolower($pre)))
            { $title = $pre . $title; }

        $suf = $this->getTypeData('title_suffix');
        if ((is_string($suf)) &&
            (strtolower(substr($title, strlen($title)-strlen($suf), strlen($suf))) !=
             strtolower($suf)))
            { $title = $title . $suf; }
            
        return
            $title;
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
     * 
     * [Grouped under "Block type functions"]
     */

    function getType()
    {
        return $this->type;
    }
    
    /*
     * Function: getTypeName()
     * Returns the type name as shown in the first line.
     * 
     * [Grouped under "Block type functions"]
     */

    function getTypeName()
    {
        return $this->typename;
    }
    
    /*
     * Function: getTypeData()
     *   Returns the data for the type in the class, as defined
     *   in [[Scribe::$Options]].
     * 
     * [Grouped under "Block type functions"]
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
     * [Grouped under "Data functions"]
     */

    function getID()
    {
        if (($this->isHomePage()) && (is_null($this->_id)))
            { $this->_id = 'index'; return 'index'; }
            
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
            if ((isset($td['short'])) && ($td['short'] != ''))
                { $id_tokens[] = $this->_toID($td['short']); }
            
            // Add our own title, and finalize
            $id_tokens[] = $this->_toID($this->title);
            $this->_id = implode('.', $id_tokens);
            return $this->_id;
        }
        
        return $this->_id;
    }
    
    /* ======================================================================
     * Protected methods
     * ====================================================================== */
    
    /*
     * Function: _toID()
     * Converts a string input (usually the title) into an ID.
     * 
     * [Protected, Grouped under "Protected methods"]
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
     * [Protected, grouped under "Protected methods"]
     */
     
    function toHTML($lines)
    {
        if (is_array($lines)) { $str = implode("\n", $lines); }
        else { $str = (string) $lines; }
        
        // Convert "Usage:" to H2's
        $str = preg_replace('~(?:^|\\r|\\n)([A-Za-z0-9\- ]+):[\\r\\n]~sm',
            "\n## \\1\n\n", $str);
            
        // Convert to dl/dt/dd
        $str = preg_replace('~ *([\[\]\(\)/a-zA-Z0-9`_\$\.\*]+) +- (.*?)([\\r\\n$])~s',
            "\n\\1\n: \\2\\3", $str);
        
        // Convert [[]] links
        $str = preg_replace('~\[\[(.*?)\]\]~s', "<a href=\"##\\1\">\\1</a>", $str);
        
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
     * Function: finalize()
     * Ran when building is done.
     * 
     * Usage:
     *   $block->finalize()
     *   (Don't call this function.)
     * 
     * Description:
     *   You may override this to do more post-build actions for the block.
     * 
     * [Protected, grouped under "Protected methods"]
     */

    function finalize()
    {
    }
    
    function preFinalize()
    {     
        if ((!$this->hasParent()) && (!is_null($this->_supposed_parent)))
        {
            // Found a "Filed under", now look up it's supposed parent and
            // put them there
            $results =& $this->Project->lookup($this->_supposed_parent, $this);
            if (count($results) > 0)
            {
                $results[0]->registerChild($this);
                
                // Remove from tree
                foreach ($this->Project->data['tree'] as $id => &$item)
                    if ($item == $this)
                        { unset($this->Project->data['tree'][$id]); break; }
            }
        }
        
        $this->_doInheritance();
    }
    
    function _doInheritance()
    {
        if (!is_null($this->_inherit_parent))
        {
            // Found a "Inherits", now duplicate the children of it's
            // "parent class" and add it to this
            $results =& $this->Project->lookup($this->_inherit_parent, $this);
            if (count($results) > 0)
            {
                // Make sure the parent's inheritances are resolved before
                // we continue
                $results[0]->_doInheritance();
                
                // This next line will duplicate the children SUPPOSEDLY
                $childrenx = $results[0]->getChildren();
                $children = array();
                foreach ($childrenx as $child)
                    { $children []= _clone($child); }
                
                foreach ($children as &$child)
                {
                    // If there's already something with the same name, norget it
                    foreach ($this->getChildren() as $other_child)
                    {
                        if (($other_child->getType() == $child->getType()) &&
                            ($other_child->getTitle() == $child->getTitle()))
                            { continue 2; }
                    }
                    
                    $child->_tags[] = 'inherited';
                    
                    // Add to subgroups and children
                    $this->registerChild($child);
                    
                    // Cheap version of ScProject::register()
                    $this->Project->data['blocks'][] =& $child;
                }
            }
        }
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
     * [Protected, grouped under "Protected methods"]
     */

    function registerChild(&$child_block)
    {
        // Register to children and parent
        $this->_children[] =& $child_block;
        $child_block->_parent =& $this;
        
        // Reset the ID so it can be recomputed according to the new parent
        $child_block->_id = NULL;
        
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
     * 
     * [Grouped under "Traversion functions"]
     */

    function hasChildren()
    {
        return (count($this->_children) > 0) ? TRUE : FALSE;
    }
    
    /*
     * Function: getParent()
     * Returns the parent block, or `NULL` if none.
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

    /* ======================================================================
     * Private properties
     * ====================================================================== */
    
    var $Project;
    
    var $_data;
    var $valid = FALSE;
    
    /* Property: $typename
     * The name of the type as defined in the first line.
     * [Private]
     */
    var $typename = '';
    
    /* 
     * Property: $type
     * The proper name of the type.
     *
     * Description:
     *   Access the type of the block through [[getType()]]
     *   and [[getTypeData()]].
     * 
     * [Private]
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
     * [Private]
     */
    var $title;
    
    /*
     * Property: $content
     * The content in HTML format, as already parsed by [[toHTML()]].
     * 
     * [Private]
     */
     
    var $content;
    
    /*
     * Property: $brief
     * The brief description in HTML format.
     * 
     * [Private]
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
     * [Private]
     */
    var $_id = null;
    
    /* Property: $_group
     * The group.
     * 
     * Description:
     *   Access via [[getGroup()]], or the parent's [[getMemberLists()]].
     * 
     * [Private]
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
     * [Private]
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
     * 
     * [Private]
     */
    var $_children = array();
    
    /*
     * Property: $_tags
     * Tags. Please use [[getTags()]] instead
     * [Private]
     */
    var $_tags = array(); 
    
    /*
     * Property: $_subgroups
     * Subgroups.
     * 
     * Example:
     *   See [[getMemberLists()]] for an example of the data.
     * 
     * [Private]
     */
     
    var $_subgroups = array();
    
    /*
     * Property: $_supposed_parent
     * Used by finalize
     */
    
    var $_supposed_parent = NULL;
    
    /*
     * Property: $_inherit_parent
     * Used by finalize
     */
    
    var $_inherit_parent = NULL;
    
    /* ======================================================================
     * End
     * ====================================================================== */
}

class ScClassBlock extends ScBlock
{   
    function getTitle()
    {
        return 'Class ' . $this->title;
    }
}
