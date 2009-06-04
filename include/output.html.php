<?php

class HtmlOutput extends ScOutput
{
    function run($project, $path)
    {
        $index_file = $path . '/index.html';
        ob_start();
        foreach ($project->data['blocks'] as $block)
        {
            echo '<div>';
            echo '<h1>' . $block->getTitle() . '</h1>';
            echo $block->getContent();
            echo '</div>';
            echo '<hr/>';
        }
        file_put_contents($index_file, ob_get_clean());
    }
}