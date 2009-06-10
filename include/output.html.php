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
            { $Sc->error("Can't find template " . $this->options['template']); }
        
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
        foreach ($this->Project->data['blocks'] as $block)
        {
            // $Sc->status("* " . $block->getID() . ".html");
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
            $tree    =& $block->getChildren();
            
            // If no children, use siblings instead
            if ((count($tree) == 0) && ($block->hasParent()))
            {
                $parent =& $block->getParent();
                $tree =& $parent->getChildren();
                // Pop one out of crumbs
                array_splice($tree_parents, count($tree_parents)-1, 1, array());
            }
            else {
                $tree =& $block->getChildren();
            }
            include($template_path. '/single.php');
        
            // Out
            $output = ob_get_clean();
            file_put_contents($index_file, $output);
        }
    }
}