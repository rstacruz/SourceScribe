<?php
    extract($options);
    $body_class = 'single';
    include dirname(__FILE__) . '/header.php';
?>
    <h1><a class="<?php echo $homepage['a_class']; ?>" href="<?php echo $homepage['a_href']; ?>"><span><?php echo $homepage['title']; ?></span></a></h1>
    
    <div id="all">
    <div id="all-c">
    <?php foreach ($blocks as $the_block) { ?>
    
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
                
                <?php if ($the_block['has_children']) { ?>
                    <div class="members">
                    <?php foreach ($the_block['member_lists'] as $member_list) { ?>
                        <?php if (count($member_list['members']) == 0) { continue; } ?>
                        <h3><span><?php echo $member_list['title']; ?></span></h3>
                        <dl>
                            <?php foreach ($member_list['members'] as $node) { ?>
                                <dt class="<?php echo $node['id_trimmed']; ?>"><span class="term"><a name="<?php echo $node['id'] ?>" class="<?php echo $node['a_class']; ?>" href="<?php echo $node['a_href']; ?>"><?php echo $node['title']; ?></a></span></dt>
                                <dd class="<?php echo $node['id_trimmed']; ?>">
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
    <?php } /* foreach $blocks */ ?>
    </div></div><!-- #all-c and #all -->

<?php include dirname(__FILE__) . '/footer.php'; ?>