<?php
    extract($options);
    $body_class = 'single';
    include dirname(__FILE__) . '/header.php';
?>
    <h1><a class="<?php echo $homepage['a_class']; ?>" href="<?php echo $homepage['a_href']; ?>"><span><?php echo $homepage['title']; ?></span></a></h1>
    
    <div id="all">
        <!-- Breadcrumbs -->
        <div class="breadcrumbs"><div class="breadcrumbs-c">
            <ul>
                <?php foreach ($breadcrumbs as $node) { ?>
                    <li class="<?php echo $node['li_class']; ?>">
                        <a class="<?php echo $node['a_class']; ?>" href="<?php echo $node['a_href']; ?>"><?php echo $node['title']; ?></a>
                    </li>
                <?php } ?>
            </ul>
        </div></div>
        
    <div id="all-c">

        <!-- Sidebar -->
        <div id="side">
            <div id="parents"><div id="parents-c">
                <?php if ($has_tree_parents) { ?>
                    <ul>
                    <?php foreach ($tree_parents as $node) {  ?>
                        <li class="<?php echo $node['li_class']; ?>">
                            <a class="<?php echo $node['a_class']; ?>" href="<?php echo $node['a_href']; ?>">
                                <span class="arrow">&uarr;</span><strong><?php echo $node['title']; ?></strong>
                            </a>
                        </li>
                    <?php } ?>
                    </ul>
                <?php } ?>
            </div></div><!-- #parents-c and #parents -->
    
            <div id="index"><div id="index-c">
                <?php if ((is_array($tree)) && (count($tree) > 0)) { ?>
                    <ul>
                        <?php foreach ($tree as $subtree) { ?>
                            <li><h4><?php echo $subtree['title']; ?></h4><ul>
                            <?php foreach ($subtree['members'] as $i => $node) { ?>
                                <li class="<?php echo $node['li_class']; ?>">
                                    <a class="<?php echo $node['a_class']; ?>" href="<?php echo $node['a_href']; ?>"><?php echo $node['title']; ?></a>
                                </li>
                            <?php } ?>
                            </ul></li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div></div><!-- #index-c and #index -->
        </div><!-- #side -->


        <div class="block <?php echo $the_block['class']; ?>"><div class="block-c">
            
            <!-- Heading -->
            <div class="heading"><div class="heading-c">
                <h2><span><?php echo $the_block['title'] ?></span></h2>
                <div class="brief">
                <?php echo $the_block['brief'] ?>
                <?php if ($the_block['has_tags']) { ?>
                    <?php foreach ($the_block['tags'] as $tag) { ?>
                        <span class="tag"><?php echo $tag; ?></span>
                    <?php } ?>
                <?php } ?>
                </div>
            </div></div><!-- .heading-c and .heading -->
            
            <div class="content">
                <div class="description">
                    <?php echo $the_block['description']; ?>
                </div>
                
                <?php if (($the_block['has_children']) && ($the_block['show_members'])) { ?>
                    <div class="members">
                    <?php foreach ($the_block['member_lists'] as $member_list) { ?>
                        <?php if (count($member_list['members']) == 0) { continue; } ?>
                        <h3><span><?php echo $member_list['title']; ?></span></h3>
                        <dl>
                            <?php foreach ($member_list['members'] as $node) { ?>
                                <dt class="<?php echo (strlen($node['brief'])) ? "has-definition" : "no-definition"; ?> <?php echo $node['id_trimmed']; ?>"><span class="term"><a name="<?php echo $node['id'] ?>" class="<?php echo $node['a_class']; ?>" href="<?php echo $node['a_href']; ?>"><?php echo $node['title']; ?></a></span></dt>
                                <dd class="<?php echo (strlen($node['brief'])) ? "has-definition" : "no-definition"; ?> <?php echo $node['id_trimmed']; ?>">
                                    <?php if ($node['has_tags']) { ?>
                                        <?php foreach ($node['tags'] as $tag) { ?>
                                            <span class="tag"><?php echo $tag; ?></span>
                                        <?php } ?>
                                    <?php } ?>
                                    <?php echo $node['brief']; ?>
                                </dd>
                            <?php } ?>
                        </dl>
                    <?php } ?>
                    </div><!-- .members -->
                <?php } ?>
            </div><!-- .content -->
            
            <div class="clear"></div>
        </div>
    </div></div><!-- #all-c and #all -->

<?php include dirname(__FILE__) . '/footer.php'; ?>