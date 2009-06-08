<?php
    $title = $project->getName() . ' - ' . $blocks[0]->getTitle();
    include dirname(__FILE__) . '/header.php';
?>
    <div id="index">
        <?php if (!is_array($tree)) { ?><ul><li><?php } ?>
        <?php if (!is_callable('show_single_index')) { function show_single_index($node, $depth, $block) { ?>
            
            <?php if (is_callable(array($node, 'getTitle'))) { ?>
                <a href="<?php echo $node->getID().'.html'; ?>"><strong><?php echo $node->getTitle(); ?></strong></a>
                <span class="type"><?php echo strtolower($node->typename); ?></span>
            <?php } ?>
            <?php $children = ((is_callable(array($node, 'getChildren'))) ? ($node->getChildren()) : ((array) $node)); ?>
            <?php if ((count($children) > 0) && (($depth < 1) || ($block->getID() == $node->getID()))) { ?>
                <ul>    
                    <?php foreach ($children as $id => $data) { ?>
                        <li>
                            <?php show_single_index($data, $depth+1, $block); ?>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        <?php } } ?>
        <?php show_single_index($tree, 0, $blocks[0]); ?>
        <?php if (!is_array($tree)) { ?></li></ul><?php } ?>
    </div>

    <div id="all">
        <?php foreach ($blocks as $bid => $block) { ?>
            <div class="block-c"><div class="block block-<?php echo strtolower($block->typename) ?> blocktype-<?php echo strtolower($block->type) ?>">
            <?php if ($block->hasParent()) { $parent =& $block->getParent(); ?>
                <p class="parent">
                    <a href="<?php echo $parent->getID().'.html'; ?>">&uarr; <?php echo $parent->getType() . ' ' . $parent->getTitle(); ?></a>
                </p>
            <?php } ?>
            <a name="<?php echo $block->getID(); ?>" class="anchor"></a>
                <!--<p class="type"><?php echo $block->typename ?></p>-->
                <h2><span><?php echo $block->getTitle(); ?></span></h2>
                <div class="brief"><?php echo $block->getBrief(); ?></div>
                <div class="description"><?php echo str_replace(array('h2>'), array('h3>'), $block->getContent()); ?></div>
                <?php if ($block->hasTags()) { ?>
                <div class="description">
                    <h3><span>Tags</span></h3>
                    <ul>
                        <?php foreach ($block->getTags() as $tag) { ?>
                            <li><?php echo $tag; ?></li>
                        <?php } ?>
                    </ul>
                </div>
                <?php } ?>
                <?php if (!is_null($block->getGroup())) { ?>
                <div class="description">
                    <h3><span>Group</span></h3>
                    <ul>
                        <li><?php echo $block->getGroup(); ?></li>
                    </ul>
                </div>
                <?php } ?>
                <div class="members">
                <?php foreach ($block->getMemberLists() as $member_list) { ?>
                    <?php if (count($member_list['members']) == 0) { continue; } ?>
                    <h3><span><?php echo $member_list['title']; ?></span></h3>
                    <dl>
                        <?php foreach ($member_list['members'] as $node) { ?>
                            <dt><a href="<?php echo $node->getID().'.html'; ?>"><?php echo $node->getTitle(); ?></a></dt>
                            <dd><?php echo strip_tags($node->getBrief()); ?></dd>
                        <?php } ?>
                    </dl>
                <?php } ?>
                </div>
            </div></div>
        <?php } ?>
    </div>

<?php include dirname(__FILE__) . '/footer.php'; ?>