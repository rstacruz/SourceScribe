<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title></title>
    <link rel="stylesheet" href="assets/style.css" type="text/css" media="screen" charset="utf-8" />
</head>
<body>
    <div id="all">
        <div class="block index">
            <ul>
            <?php foreach ($index as $id => $data) { ?>
                <li><?php echo $id; ?></li>
            <?php } ?>
            </ul>
        </div>
        
        <?php foreach ($blocks as $bid => $block) { ?>
            <div class="block block-<?php echo strtolower($block->typename) ?> blocktype-<?php echo strtolower($block->type) ?>">
                <p class="type"><?php echo $block->typename ?></p>
                <h2><span><?php echo $block->getTitle(); ?></span></h2>
                <div class="brief"><?php echo $block->getBrief(); ?></div>
                <div class="description"><?php echo str_replace(array('h2>'), array('h3>'), $block->getContent()); ?></div>
            </div>
        <?php } ?>
    </div>
</body>
</html>