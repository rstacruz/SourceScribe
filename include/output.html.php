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
        @mkdir($path . '/assets', 0744, true);
        foreach (glob($template_path . DS . 'assets' . DS . '*') as $file)
        {
            @copy($file, $path.DS.'assets'.DS.basename($file));
        }
        
        $index_file = $path . '/index.html';
        ob_start();
        $blocks = $project->data['blocks'];
        include($template_path. '/index.tpl');
        file_put_contents($index_file, ob_get_clean());
    }
}