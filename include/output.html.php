<?php

/*
 * Class: HtmlOutput
 * The HTML output class
 * 
 * [Filed under "API reference"]
 */
 
class HtmlOutput extends ScOutput
{
    var $folders = array('assets');

    function run($path)
    {
        $Sc =& $this->Project->Sc;
        
        // Default template
        if ((!isset($this->options['template'])) ||
            (is_string($this->options['template'])))
            { $this->options['template'] = 'default'; }
            
        // Get template
        $template_path = SCRIBE_PATH .
            'templates/html.' . $this->options['template'];
        
        // Does it exist?
        if (!is_dir($template_path))
            { ScStatus::error("Can't find template " . $this->options['template']); }
        
        // Clear the folder
        foreach(glob("$path/*") as $file)
            { @unlink($file); }
                
        // Make the assets folder
        foreach ($this->folders as $folder)
        {
            @mkdir("$path/$folder", 0744, TRUE);
            foreach(glob("$path/$folder/*") as $file)
                { @unlink($file); }
        }
        
        // Fill the assets folder
        foreach (glob($template_path . DS . 'assets' . DS . '*') as $file)
        {
            @copy($file, $path.DS.'assets'.DS.basename($file));
        }
        
        // Output
        // $this->out_content_index($path, $template_path);
        $this->out_singles($path, $template_path);
    }
    
    /*
     * Function: out_full()
     * Outputs the single-file megaindex.
     */
     
    function out_full($path, $template_path)
    {
        $index_file = $path . '/index.html';
        ob_start();
        
        // Template
        $blocks = $this->Project->data['blocks'];
        $tree   = $this->Project->data['tree'];
        $project =& $this->Project;
        $assets_path = 'assets/';
        include($template_path. '/full.php');
        
        // Out
        $output = ob_get_clean();
        file_put_contents($index_file, $output);
    }
    
    /*
     * Function: out_full()
     * Outputs the single-file megaindex.
     */
     
    function out_content_index($path, $template_path)
    {
        $index_file = $path . '/index.html';
        ob_start();
        
        // Template
        $blocks = $this->Project->data['blocks'];
        $tree   = $this->Project->data['tree'];
        $project =& $this->Project;
        $assets_path = 'assets/';
        include($template_path. '/content_index.php');
        
        // Out
        $output = ob_get_clean();
        file_put_contents($index_file, $output);
    }
    
    /*
     * Function: out_singles()
     * Outputs single files
     */
     
    function out_singles($path, $template_path)
    {
        global $Sc;
        $file_count = 0;
        foreach ($this->Project->data['blocks'] as &$block)
        {
            $file_count++;
            ScStatus::update($block->getID());
            $options = $this->_getOptions($block, $project);
            
            // Template
            ob_start();
            include($template_path. '/single.php');
            $output = ob_get_clean();
        
            // Out
            file_put_contents($path . '/' . $block->getID() . '.html', $output);
        }
        ScStatus::updateDone("$file_count files written.");
    }
    
    function& _getOptions(&$block, &$project)
    {
        /* Function: _getOptions()
         * To be documented.
         * [Private]
         */
         
        $result = array();     
        $null = array();
        
        // assets_path
        $result['assets_path'] = 'assets/';
        
        // tree_parents
        $tree_parents =& $block->getAncestry(array('exclude_home' => TRUE, 'include_this' => TRUE));
        $result['tree_parents'] = array();
        foreach ($tree_parents as $i => &$node)
        {
            $result['tree_parents'][] = $this->_getNodeOptions($node, $block, 1);
        }
        
        // breadcrumbs
        $breadcrumbs =& $block->getAncestry(array('include_this' => TRUE));
        $result['breadcrumbs'] = array();
        foreach ($breadcrumbs as $i => &$node)
        {
            $index = count($result['breadcrumbs']);
            $result['breadcrumbs'][$index] = $this->_getNodeOptions($node, $block, 1);
            $result['breadcrumbs'][$index]['li_class'] = 'item-'.(count($breadcrumbs) - (int)$i - 1);
        }

        // is_homepage
        $result['is_homepage'] = $block->isHomePage();
        
        // title
        $result['title'] = ($block->isHomePage()) ?
                            ($this->Project->getName()) :
                            ($block->getTitle() . ' &mdash; ' . $this->Project->getName());
        
        // homepage
        // Setting $result['home'] somehow trips something. I don't know why.
        $home    =& $this->Project->data['home'];
        $result['homepage'] = $this->_getNodeOptions($home, $block, 1);
             
        // Prepare for tree
        if ((!$block->hasChildren()) && ($block->hasParent()))
        {
            // Use it's siblings instead.
            $parent =& $block->getParent();
            $tree =& $parent->getMemberLists();
            
            // Pop one out of the tree parents
            array_splice($result['tree_parents'], count($result['tree_parents'])-1, 1, array());
        }
        else {
            $tree =& $block->getMemberLists();
        }
        
        // Tree
        $result['tree'] = array();
        foreach ($tree as $subtree)
        {
            $index = count($result['tree']);
            $result['tree'][$index] = array(
                'title' => $subtree['title'],
                'members' => array(),
            );
            foreach ($subtree['members'] as $i => &$node)
            {
                $result['tree'][$index]['members'][] = $this->_getNodeOptions($node, $block, 1);
            }
        }
        
        // has_tree_parents
        $result['has_tree_parents'] = (count($result['tree_parents'] > 0)) ? TRUE : FALSE;
        
        // has_tree
        $result['has_tree'] = (count($result['tree'] > 0)) ? TRUE : FALSE;
        
        // block
        $result['the_block'] = $this->_getNodeOptions($block, $block, 6);
        $result['the_block']['member_lists'] = array();
        foreach ($block->getMemberLists() as $member_list)
        {
            $index = count($result['the_block']['member_lists']);
            $result['the_block']['member_lists'][$index] = array
            (
                'title' => $member_list['title'],
                'members' => array()
            );
            foreach ($member_list['members'] as $node) {
                $index2 = count($result['the_block']['member_lists'][$index]['members']);
                $result['the_block']['member_lists'][$index]['members'][$index2] = $this->_getNodeOptions($node, $block, 3);
            }
        }
        
        
        return $result;
    }
    
    function& _getNodeOptions(&$block, &$reference, $level = 1)
    {
        $result = array();
        $result['class'       ] = ('block-' . strtolower($block->typename) . ' blocktype-' . strtolower($block->type));
        $result['title'       ] = $block->getTitle();
        $result['id'          ] = $block->getID();
        $result['id_trimmed'  ] = str_replace('.','-',$block->getID());
        $result['a_class'     ] = $this->linkClass($block);
        $result['a_href'      ] = $this->link($block);
        $result['li_class'    ] = ($block->getID() == $reference->getID()) ? 'active' : '';
        $result['has_children'] = $block->hasChildren();
        if ($level >= 3) {
            $result['brief'   ] = strip_tags($this->_processContent($block->getBrief()), "<a><code><b><br><strong><em><i>");
            $result['has_tags'] = (count($block->getTags()) > 0);
            $result['tags'    ] = $block->getTags();
        }
        if ($level >= 6) {
            $result['description'] = $this->_processContent($block->getContent());
        }
        return $result;
    }
    
    
    /*
     * Function: _processContent()
     * Resolves links
     */

    function _processContent($str)
    {
        $str = str_replace(array('h4>'), array('h5>'), $str);
        $str = str_replace(array('h3>'), array('h4>'), $str);
        $str = str_replace(array('h2>'), array('h3>'), $str);
        $str = preg_replace_callback("~\"(##(.*?))\"~", array(&$this, '_resolveLink'), $str);
        return $str;
    }
    
    function _resolveLink($m)
    {
        $id = $m[2];
        $b = $this->Project->lookup($id);
        if (count($b) == 0) { return '#'; }
        return $this->link($b[0]);
    }
     
    function link(&$block)
    {
        // If the block has it's own page/content,
        if ($block->isHomePage())
            { return 'index.html'; }
            
        if (($block->hasContent()) || ($block->hasChildren()))
            { return $block->getID() . '.html'; }
    
        else
        {
            if (!$block->hasParent())
                { return ''; /* Should never happen */ }
            else
                { return $this->link($block->getParent()) . '#' . $block->getID(); }
        }
    }
    
    /*
     * Function: linkClass()
     * Returns the link class to a certain block.
     */
    
    function linkClass(&$block)
    {
        if (strpos($this->link($block), '#') !== FALSE)
            { return 'stub'; }
        else
            { return ''; }
    }
}