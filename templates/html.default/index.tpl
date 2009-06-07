<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo $project->name ?></title>
    <link rel="stylesheet" href="assets/style.css" type="text/css" media="screen" charset="utf-8" />
</head>
<body>

        <div id="index">
            <?php function show_index($node) { ?>
                
            <?php if (is_callable(array($node, 'getTitle'))) { ?>
                <a href="<?php echo '#'.$node->getID(); ?>"><strong><?php echo $node->getTitle(); ?></strong></a>
                <span class="type"><?php echo strtolower($node->typename); ?></span>
            <?php } ?>
            <ul>    
                <?php $children = ((is_callable(array($node, 'getChildren'))) ? ($node->getChildren()) : ((array) $node)); ?>
                <?php foreach ($children as $id => $data) { ?>
                    <?php if ($id == '_data') { continue; } ?>
                    <li>
                        <?php show_index($data); ?>
                    </li>
                <?php } ?>
            </ul>
            <?php } ?>
            <?php show_index($tree); ?>
        </div>
    <div id="all">
        <?php foreach ($blocks as $bid => $block) { ?>
            <div class="block-c"><div class="block block-<?php echo strtolower($block->typename) ?> blocktype-<?php echo strtolower($block->type) ?>">
            <a name="<?php echo $block->id; ?>" class="anchor"></a>
                <!--<p class="type"><?php echo $block->typename ?></p>-->
                <h2><span><?php echo $block->getTitle(); ?></span></h2>
                <div class="brief"><?php echo $block->getBrief(); ?></div>
                <div class="description"><?php echo str_replace(array('h2>'), array('h3>'), $block->getContent()); ?></div>
            </div></div>
        <?php } ?>
    </div>
</body>
</html>