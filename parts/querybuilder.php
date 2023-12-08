<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly 
?>
<?php if (isset($blockinstance) && is_array($blockinstance) && !isset($block_instance)) {
    $block_instance = $blockinstance;
} ?>
<?php $postid = get_the_ID(); ?>
<?php $typeclass = (!empty($block_instance['attrs']['post_type'])) ? ' type-' . $block_instance['attrs']['post_type'] : '' ?>
<?php $typeclass = apply_filters('gspbgrid_item_class', $typeclass, $postid, $block_instance); ?>

<li class="gspbgrid_item swiper-slide post-<?php echo (int)$postid; ?><?php echo esc_attr($typeclass); ?>">
    <?php if (!empty($block_instance['attrs']['container_link'])) {
        $postlink = get_the_permalink($postid);
        $postlink = apply_filters('gspbgrid_item_link', $postlink, $postid, $block_instance);
        $newWindow = (!empty($block_instance['attrs']['linkNewWindow'])) ? ' target="_blank"' : '';
        echo '<a class="gspbgrid_item_link" title="' . get_the_title($postid) . '" href="' . $postlink . '"'.$newWindow.'></a>';
    } ?>
    <?php if (!empty($block_instance['attrs']['container_image'])) {
        ?>
            <?php $size = (!empty($block_instance['attrs']['container_image_size'])) ? $block_instance['attrs']['container_image_size'] : 'medium'; ?>
            <div class="gspbgrid_item_image_bg gspbgrid_item_image_bg_<?php echo esc_attr($block_instance['attrs']['id']);?>">
                <div class="gspb_backgroundOverlay"></div>
                <?php echo get_the_post_thumbnail($postid, $size); ?>
            </div>
        <?php
    }
    ?>
    <?php if (!empty($block_instance['attrs']['container_image'])) {
        ?>
            <div class="gspbgrid_item_inner">
        <?php
    }
    ?>
    <?php
    $block_content = (new \WP_Block(
        $block_instance,
        array(
            'postId'   => $postid,
        )
    )
    )->render(array('dynamic' => false));
    echo $block_content;
    ?>
    <?php if (!empty($block_instance['attrs']['container_image'])) {
        ?>
            </div>
        <?php
    }
    ?>
</li>