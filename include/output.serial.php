<?php

class SerialOutput extends ScOutput
{
    function run($path)
    {
        $path = (isset($this->options['path'])) ?
                  trim((string) $this->options['path']) :
                  '.';
                  
        $fname = (isset($this->options['filename'])) ?
                  trim((string) $this->options['filename']) :
                  '.sourcescribe_index';
            
        file_put_contents($this->Project->cwd.DS.$path.DS.$fname, serialize($this->Project->Sc));
    }
}