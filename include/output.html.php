<?php

class HtmlOutput extends ScOutput
{
    function run($path)
    {
        $project =& $this->Project;
        
        global $Sc;
        
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
        foreach (array('assets', 's') as $folder)
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
        // $this->out_full($path, $project, $template_path);
        $this->out_content_index($path, $project, $template_path);
        $this->out_singles($path, $project, $template_path);
    }
    
    /*
     * Function: out_full()
     * Outputs the single-file megaindex.
     */
     
    function out_full($path, $project, $template_path)
    {
        $index_file = $path . '/index.html';
        ob_start();
        
        // Template
        $blocks = $this->Project->data['blocks'];
        $tree   = $this->Project->data['tree'];
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
     
    function out_content_index($path, $project, $template_path)
    {
        $index_file = $path . '/index.html';
        ob_start();
        
        // Template
        $blocks = $this->Project->data['blocks'];
        $tree   = $this->Project->data['tree'];
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
     
    function out_singles($path, $project, $template_path)
    {
        global $Sc;
        foreach ($this->Project->data['blocks'] as $block)
        {
            $index_file = $path . '/' . $block->getID() . '.html';
            ob_start();
        
            // Template
            $blocks = array($block);
            $assets_path = 'assets/';
            $id = $block->getID();
            if ($block->hasParent())
            {
                $parent = $block->getParent();
                $tree = $parent; //->getChildren();
            }
            else
                { $tree = $this->Project->data['tree']; }
            include($template_path. '/single.php');
        
            // Out
            $output = ob_get_clean();
            file_put_contents($index_file, $output);
        }
    }
}