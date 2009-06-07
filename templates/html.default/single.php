<?php
    $title = $project->getName();
    include dirname(__FILE__) . '/header.php';
?>

    <div id="all">
        <?php foreach ($blocks as $bid => $block) { ?>
            <div class="block-c"><div class="block block-<?php echo strtolower($block->typename) ?> blocktype-<?php echo strtolower($block->type) ?>">
            <a name="<?php echo $block->getID(); ?>" class="anchor"></a>
                <!--<p class="type"><?php echo $block->typename ?></p>-->
                <h2><span><?php echo $block->getTitle(); ?></span></h2>
                <div class="brief"><?php echo $block->getBrief(); ?></div>
                <div class="description"><?php echo str_replace(array('h2>'), array('h3>'), $block->getContent()); ?></div>
            </div></div>
        <?php } ?>
    </div>

<?php include dirname(__FILE__) . '/footer.php'; ?>