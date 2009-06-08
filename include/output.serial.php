<?php

class SerialOutput extends ScOutput
{
    function run($project, $path, $output_options)
    {
        $path = (isset($output_options['path'])) ?
                  trim((string) $output_options['path']) :
                  '.';
                  
        $fname = (isset($output_options['filename'])) ?
                  trim((string) $output_options['filename']) :
                  '.sourcescribe_index';
            
        file_put_contents($project->cwd.DS.$path.DS.$fname, serialize($project->Sc));
    }
}