<?php

/*
 * Class: HtmlOutput
 * The HTML output class
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
        foreach ($this->Project->data['blocks'] as $block)
        {
            $file_count++;
            ScStatus::update($block->getID());
            $index_file = $path . '/' . $block->getID() . '.html';
            ob_start();
            
            // Template
            $blocks = array($block);
            $assets_path = 'assets/';
            $id = $block->getID();
            $project =& $this->Project;
            
            // Variables:
            // $block
            $home    =& $this->Project->data['home'];
            $tree_parents =& $block->getAncestry(array('exclude_home' => TRUE, 'include_this' => TRUE));
            $breadcrumbs =& $block->getAncestry(array('include_this' => TRUE));
            
            // If this node has no children...
            if ((!$block->hasChildren()) && ($block->hasParent()))
            {
                // Use it's siblings instead.
                $parent =& $block->getParent();
                $tree =& $parent->getMemberLists();
                
                // Pop one out of the tree parents
                array_splice($tree_parents, count($tree_parents)-1, 1, array());
            }
            else {
                $tree =& $block->getMemberLists();
            }
            include($template_path. '/single.php');
        
            // Out
            $output = ob_get_clean();
            file_put_contents($index_file, $output);
        }
        ScStatus::updateDone("$file_count files written.");
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