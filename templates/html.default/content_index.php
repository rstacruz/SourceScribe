<?php
    $title = $project->getName();
    include dirname(__FILE__) . '/header.php';
?>

    <div id="index">
        <?php if (!is_callable('show_content_index')) { function show_content_index($node) { ?>
            
            <?php if (is_callable(array($node, 'getTitle'))) { ?>
                <a href="<?php echo ''.$node->getID().'.html'; ?>"><strong><?php echo $node->getTitle(); ?></strong></a>
                <span class="type"><?php echo strtolower($node->typename); ?></span>
            <?php } ?>
            <?php $children = ((is_callable(array($node, 'getChildren'))) ? ($node->getChildren()) : ((array) $node)); ?>
            <?php if (count($children) > 0) { ?>
                <ul>    
                    <?php foreach ($children as $id => $data) { ?>
                        <li>
                            <?php show_content_index($data); ?>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        <?php } } ?>
        <?php show_content_index($tree); ?>
    </div>

<?php include dirname(__FILE__) . '/footer.php'; ?>