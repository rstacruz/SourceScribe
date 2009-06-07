<?php
    $title = $project->getName();
    include dirname(__FILE__) . '/header.php';
?>

    <div id="index">
        <?php function show_index($node) { ?>
            
            <?php if (is_callable(array($node, 'getTitle'))) { ?>
                <a href="<?php echo '#'.$node->getID(); ?>"><strong><?php echo $node->getTitle(); ?></strong></a>
                <span class="type"><?php echo strtolower($node->typename); ?></span>
            <?php } ?>
            <?php $children = ((is_callable(array($node, 'getChildren'))) ? ($node->getChildren()) : ((array) $node)); ?>
            <?php if (count($children) > 0) { ?>
                <ul>    
                    <?php foreach ($children as $id => $data) { ?>
                        <li>
                            <?php show_index($data); ?>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        <?php } ?>
        <?php show_index($tree); ?>
    </div>
    
    <div id="all">
        <?php foreach ($blocks as $bid => $block) { ?>
            <div class="block-c"><div class="block block-<?php echo strtolower($block->typename) ?> blocktype-<?php echo strtolower($block->type) ?>">
            <?php if ($block->hasParent()) { $parent =& $block->getParent(); ?>
                <p class="parent">
                    <a href="<?php echo '#'.$parent->getID(); ?>">&uarr; <?php echo $parent->getType() . ' ' . $parent->getTitle(); ?></a>
                </p>
            <?php } ?>
            <a name="<?php echo $block->getID(); ?>" class="anchor"></a>
                <!--<p class="type"><?php echo $block->typename ?></p>-->
                <h2><span><?php echo $block->getTitle(); ?></span></h2>
                <div class="brief"><?php echo $block->getBrief(); ?></div>
                <div class="description"><?php echo str_replace(array('h2>'), array('h3>'), $block->getContent()); ?></div>
                <div class="members">
                <?php foreach ($block->getMemberLists() as $member_list) { ?>
                    <?php if (count($member_list['members']) == 0) { continue; } ?>
                    <h3><span><?php echo $member_list['title']; ?></span></h3>
                    <dl>
                        <?php foreach ($member_list['members'] as $node) { ?>
                            <dt><a href="<?php echo '#'.$node->getID(); ?>"><?php echo $node->getTitle(); ?></a></dt>
                            <dd><?php echo strip_tags($node->getBrief()); ?></dd>
                        <?php } ?>
                    </dl>
                <?php } ?>
                </div>
            </div></div>
        <?php } ?>
    </div>

<?php include dirname(__FILE__) . '/footer.php'; ?>