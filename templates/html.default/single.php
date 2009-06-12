<?php
    $title = $project->getName() . ' - ' . $block->getTitle();
    include dirname(__FILE__) . '/header.php';
?>
    <?php if ($home) { ?>
        <h1><a class="<?php echo $this->linkClass($home); ?>" href="<?php echo $this->link($home); ?>"><span><?php echo $home->getTitle(); ?></span></a></h1>
    <?php } ?>
    
    <div id="all">
        <!-- Breadcrumbs -->
        <div class="breadcrumbs"><div class="breadcrumbs-c">
            <ul>
                <?php foreach ($breadcrumbs as $i => $node) { ?>
                    <li class="item-<?php echo count($breadcrumbs) - (int)$i - 1; ?>"><a class="<?php echo $this->linkClass($node); ?>" href="<?php echo $this->link($node); ?>"><?php echo $node->getTitle() ?></a></li>
                <?php } ?>
            </ul>
        </div></div>
        
    <div id="all-c">

        <!-- Sidebar -->
        <div id="side">
            <div id="parents"><div id="parents-c">
                <?php if (count($tree_parents) > 0) { ?>
                    <ul>
                    <?php foreach ($tree_parents as $i => $node) {  ?>
                        <li <?php if ($block->getID() == $node->getID()) { ?>class="active"<?php } ?>>
                            <a class="<?php echo $this->linkClass($node); ?>" href="<?php echo $this->link($node); ?>"><span class="arrow">&uarr;</span><strong><?php echo $node->getTitle(); ?></strong></a>
                        </li>
                    <?php }  ?>
                    </ul>
                <?php } ?>
            </div></div><!-- #parents-c and #parents -->
    
            <div id="index"><div id="index-c">
                <?php if ((is_array($tree)) && (count($tree) > 0)) { ?>
                    <ul>
                        <?php foreach ($tree as $subtree) { ?>
                            <li><h4><?php echo $subtree['title']; ?></h4><ul>
                            <?php foreach ($subtree['members'] as $i => $node) { ?>
                                <li <?php if ($block->getID() == $node->getID()) { ?>class="active"<?php } ?>>
                                    <a class="<?php echo $this->linkClass($node); ?>" href="<?php echo $this->link($node); ?>"><?php echo $node->getTitle(); ?></a>
                                </li>
                            <?php } ?>
                            </ul></li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div></div><!-- #index-c and #index -->
        </div><!-- #side -->


        <div class="block block-<?php echo strtolower($block->typename) ?> blocktype-<?php echo strtolower($block->type) ?>"><div class="block-c">
            
            <!-- Heading -->
            <div class="heading"><div class="heading-c">
                <h2><span><?php echo $block->getTitle(); ?></span></h2>
                <div class="brief">
                <?php echo strip_tags($this->_processContent($block->getBrief()), "<a><code><b><strong><em><i>"); ?>
                <?php if (count($block->getTags()) > 0) { ?>
                    <?php foreach ($block->getTags() as $tag) { ?>
                        <span class="tag"><?php echo $tag; ?></span>
                    <?php } ?>
                <?php } ?>
                </div>
            </div></div><!-- .heading-c and .heading -->
            
            <div class="content">
                <div class="description">
                    <?php echo $this->_processContent($block->getContent()); ?>
                </div>
                
                <div class="members">
                <?php foreach ($block->getMemberLists() as $member_list) { ?>
                    <?php if (count($member_list['members']) == 0) { continue; } ?>
                    <h3><span><?php echo $member_list['title']; ?></span></h3>
                    <dl>
                        <?php foreach ($member_list['members'] as $node) { ?>
                            <dt class="<?php echo str_replace('.','-',$node->getID()); ?>"><span class="term"><a name="<?php echo $node->getID(); ?>" class="<?php echo $this->linkClass($node); ?>" href="<?php echo $this->link($node); ?>"><?php echo $node->getTitle(); ?></a></span></dt>
                            <dd class="<?php echo str_replace('.','-',$node->getID()); ?>">
                                <?php if (count($node->getTags()) > 0) { ?>
                                    <?php foreach ($node->getTags() as $tag) { ?>
                                        <span class="tag"><?php echo $tag; ?></span>
                                    <?php } ?>
                                <?php } ?>
                                <?php echo strip_tags($this->_processContent($node->getBrief()), '<a><code><strong><b><i><em>'); ?>
                            </dd>
                        <?php } ?>
                    </dl>
                <?php } ?>
                </div><!-- .members -->
            </div><!-- .content -->
            
            <div class="clear"></div>
        </div>
    </div></div><!-- #all-c and #all -->

<?php include dirname(__FILE__) . '/footer.php'; ?>