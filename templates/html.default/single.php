<?php
    $title = $project->getName() . ' - ' . $block->getTitle();
    include dirname(__FILE__) . '/header.php';
?>
    <?php if ($home) { ?>
        <h1><a href="<?php echo $home->getID() . '.html'; ?>"><span><?php echo $home->getTitle(); ?></span></a></h1>
    <?php } ?>
    
    <div id="side">
        <div id="parents"><div id="parents-c">
            <?php if (count($tree_parents) > 0) { ?>
                <ul>
                <?php foreach ($tree_parents as $i => $node) {  ?>
                    <li <?php if ($block->getID() == $node->getID()) { ?>class="active"<?php } ?>>
                        <a href="<?php echo $node->getID().'.html';  ?>"><span class="arrow">&uarr;</span><strong><?php echo $node->getTitle(); ?></strong></a>
                    </li>
                <?php }  ?>
                </ul>
            <?php } ?>
        </div></div><!-- #parents-c and #parents -->
    
        <div id="index"><div id="index-c">
            <?php if ((is_array($tree)) && (count($tree) > 0)) { ?>
                <ul>
                    <?php foreach ($tree as $i => $node) { ?>
                        <li <?php if ($block->getID() == $node->getID()) { ?>class="active"<?php } ?>>
                            <a href="<?php echo $node->getID().'.html'; ?>"><?php echo $node->getTitle(); ?></a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div></div><!-- #index-c and #index -->
    </div><!-- #side -->


    <div id="all">
        
        <!-- Breadcrumbs -->
        <div class="breadcrumbs"><div class="breadcrumbs-c">
            <ul>
                <?php foreach ($breadcrumbs as $i => $node) { ?>
                    <li class="item-<?php echo count($breadcrumbs) - (int)$i - 1; ?>"><a href="<?php echo $node->getID().'.html'; ?>"><?php echo $node->getTitle() ?></a></li>
                <?php } ?>
            </ul>
        </div></div>
        
    <div id="all-c">
        <div class="block block-<?php echo strtolower($block->typename) ?> blocktype-<?php echo strtolower($block->type) ?>"><div class="block-c">
            
            <!-- Heading -->
            <div class="heading"><div class="heading-c">
                <h2><span><?php echo $block->getTitle(); ?></span></h2>
                <div class="brief"><?php echo $block->getBrief(); ?></div>
            </div></div><!-- .heading-c and .heading -->
            
            <div class="content">
                <div class="description">
                    <?php echo str_replace(array('h2>'), array('h3>'), $block->getContent()); ?>
                </div>
                
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
                </div><!-- .members -->
            </div><!-- .content -->
        </div>
    </div></div><!-- #all-c and #all -->

<?php include dirname(__FILE__) . '/footer.php'; ?>