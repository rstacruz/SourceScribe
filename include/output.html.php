<?php

class HtmlOutput extends ScOutput
{
    function run($project, $path, $output_options)
    {
        global $Sc;
        
        // Default template
        if ((!isset($output_options['template'])) ||
            (is_string($output_options['template'])))
            { $output_options['template'] = 'default'; }
            
        // Get template
        $template_path = SCRIBE_PATH .
            'templates/html.' . $output_options['template'];
        
        // Does it exist?
        if (!is_dir($template_path))
            { $Sc->error("Can't find template " . $output_options['template']); }
        
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
        $this->out_full($path, $project, $template_path);
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
        $blocks = $project->data['blocks'];
        $tree   = $project->data['tree'];
        $assets_path = 'assets/';
        include($template_path. '/full.php');
        
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
        foreach ($project->data['blocks'] as $block)
        {
            $index_file = $path . '/s/' . $block->getID() . '.html';
            ob_start();
        
            // Template
            $blocks = array($block);
            $assets_path = '../assets/';
            $id = $block->getID();
            if ($block->hasParent())
            {
                $parent = $block->getParent();
                $tree = $parent; //->getChildren();
            }
            else
                { $tree = $project->data['tree']; }
            include($template_path. '/single.php');
        
            // Out
            $output = ob_get_clean();
            file_put_contents($index_file, $output);
        }
    }
}