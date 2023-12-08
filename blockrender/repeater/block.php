<?php


namespace greenshiftquery\Blocks;

defined('ABSPATH') or exit;


class RepeaterQuery
{

	public function __construct()
	{
		add_action('init', array($this, 'init_handler'));
	}

	public function init_handler()
	{
		register_block_type(
			__DIR__,
			array(
				'render_callback' => array($this, 'render_block'),
				'attributes'      => $this->attributes
			)
		);
	}

	protected $attributes = array(
		'dynamicGClasses' => array(
			'type' => 'array',
			'default' => []
		),
		'id' => array(
			'type'    => 'string',
			'default' => null,
		),
		'inlineCssStyles' => array(
			'type'    => 'string',
			'default' => '',
		),
		'animation' => array(
			'type' => 'object',
			'default' => array(),
		),
		'sourceType'       => array(
			'type'    => 'string',
			'default' => 'latest_item',
		),
		'repeaterType'       => array(
			'type'    => 'string',
			'default' => 'acf',
		),
		'postId'       => array(
			'type'    => 'number',
			'default' => 0,
		),
		'post_type' => array(
			'type' => 'string',
			'default' => 'post'
		),
		'dynamicField' => array(
			'type' => 'string',
			'default' => ''
		),
		'isSlider' => array(
			'type' => 'boolean',
			'default' => false,
		),
		'repeaterField' => array(
			'type' => 'string',
			'default' => ''
		),
		'interactionLayers' => array(
			'type' => 'array',
			'default' => array()
		),
		'animation' => array(
			'type' => 'object',
			'default' => array(),
		),
	);

	protected function normalize_arrays(&$settings, $fields = ['cat', 'tag', 'ids', 'taxdropids', 'field', 'cat_exclude', 'tag_exclude', 'postid', 'tax_slug', 'tax_slug_exclude', 'user_id'])
	{
		foreach ($fields as $field) {
			if (!isset($settings[$field]) || !is_array($settings[$field]) || empty($settings[$field])) {
				$settings[$field] = null;
				continue;
			}
			$ids = '';
			$last = count($settings[$field]);
			foreach ($settings[$field] as $item) {
				$ids .= $item['id'];
				if (0 !== --$last) {
					$ids .= ',';
				}
			}
			$settings[$field] = $ids;
		}
	}

	protected function loop_inner_atts($block, $innerblocks, $value)
	{
		foreach ($innerblocks as $index => $innerBlock) {
			if (!empty($innerBlock['attrs']['repeaterField'])) {
				$block['innerBlocks'][$index]['attrs']['repeaterArray'] = $value;
			}
			if (!empty($innerBlock['innerBlocks'])) {
				$this->loop_inner_atts($block['innerBlocks'][$index], $innerBlock['innerBlocks'], $value);
			}
		}
		return $block;
	}

	protected function addKeyToRepeaterLevels(&$array, $keyToAdd, $repeaterArray)
	{
		foreach ($array as $key => &$value) {
			if (is_array($value)) {
				if (isset($value['repeaterField'])) {
					$value[$keyToAdd] = $repeaterArray;
				}
				$this->addKeyToRepeaterLevels($value, $keyToAdd, $repeaterArray);
			}
		}
	}

	protected function loop_inner_blocks($blocks, $value)
	{
		// Loop through each block.
		foreach ($blocks as $block) {
			// Do something with the current block.
			// For example, you could output the block's content:
			//echo $block['innerHTML'];

			$this->addKeyToRepeaterLevels($block, 'repeaterArray', $value);

			$block_content = (new \WP_Block(
				$block
			)
			)->render(array('dynamic' => true));
			echo $block_content;
		}
	}

	public function gspb_grid_constructor($settings, $content, $block)
	{
		extract($settings);
		if (isset($align)) {
			if ($align == 'full') {
				$alignClass = 'alignfull';
			} elseif ($align == 'wide') {
				$alignClass = 'alignwide';
			} elseif ($align == '') {
				$alignClass = '';
			}
		} else {
			$alignClass = '';
		}
		if ($sourceType == 'latest_item') {
			global $post;
			if (is_object($post)) {
				$postId = $post->ID;
			}
		} else {
			$postId = (isset($postId) && $postId > 0) ? (int)$postId : 0;
			if ($postId == 0) {
				$args = array(
					'post_type' => $post_type,
					'posts_per_page'  => 1,
					'fields' => 'ids',
					'post_status' => 'publish'
				);
				$latest_cpt = get_posts($args);
				$postId = $latest_cpt[0];
			}
		}
		$result = [];
		if (empty($dynamicField)) {
			if (!empty($repeaterArray) && !empty($repeaterField)) {
				$getrepeatable = GSPB_get_value_from_array_field($repeaterField, $repeaterArray);;
			}
		}
		if (!empty($dynamicField)) {
			if ($repeaterType == 'acf' && function_exists('get_field')) {
				$getrepeatable = get_field($dynamicField, $postId);
			} else if ($repeaterType == 'relationpostobj' || $repeaterType == 'relationpostids') {
				if (function_exists('get_field')) {
					$getrepeatable = get_field($dynamicField, $postId);
				} else {
					$getrepeatable = get_post_meta($postId, $dynamicField, true);
				}
				if ($repeaterType == 'relationpostids') {
					if (!empty($getrepeatable) && !is_array($getrepeatable)) {
						$ids = wp_parse_id_list($getrepeatable);
					} else {
						$ids = $getrepeatable;
					}
					if(!empty($ids)){
						$args = array(
							'post__in' => $ids,
							'numberposts' => '-1',
							'orderby' => 'post__in',
							'ignore_sticky_posts' => 1,
							'post_type' => 'any'
						);
						$args = apply_filters('gspb_relationpostids_query_args', $args, $block);
						$getrepeatable = get_posts($args);
					}
				}
				if (!empty($getrepeatable)) {
					if (!is_array($getrepeatable)) {
						$getrepeatable = [$getrepeatable];
					}
					$posts = [];
					foreach ($getrepeatable as $key => $value) {
						if (is_object($value) && !empty($value->ID)) {
							$posts[$key] = (array) $value;
							$posts[$key]['thumbnail_url'] = get_the_post_thumbnail_url($value->ID, 'full');
							$posts[$key]['author'] = get_the_author_meta('display_name', $value->post_author);
							$posts[$key]['date'] = get_the_date('', $value->ID);
							$posts[$key]['modified_date'] = get_the_modified_date('', $value->ID);
	
							$remove_keys = ['post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_status', 'comment_status', 'ping_status', 'post_password', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_parent', 'menu_order', 'post_type', 'post_mime_type', 'filter'];
							foreach ($remove_keys as $keyname) {
								unset($posts[$key][$keyname]);
							}
							$custom_fields = get_post_meta($value->ID);
							foreach ($custom_fields as $fieldindex => $fieldvalue) {
								if (is_serialized($fieldvalue[0])) {
									$fieldvalue[0] = maybe_unserialize($fieldvalue[0]);
								}
								if (!empty($fieldvalue[0])) {
									$posts[$key][$fieldindex] = GSPB_field_array_to_value($fieldvalue[0], ', ');
								}
							}
						}
					}
					$getrepeatable = $posts;
				}
			} else {
				$getrepeatable = get_post_meta($postId, $dynamicField, true);
			}
		}
		if (!empty($getrepeatable) && is_array($getrepeatable)) {
			$result = $getrepeatable;
		}
		ob_start();
		$block_instance = (is_array($block)) ? $block : $block->parsed_block;
		//echo '<pre>'; print_r($block_instance); echo '</pre>';
		$blockId = 'gspbgrid_id-' . $block_instance['attrs']['id'];
		$wrapper_attributes = get_block_wrapper_attributes(array('class' => $blockId . ' ' . $alignClass));
?>
		<?php if (!empty($result) && !empty($block_instance['innerBlocks'])) : ?>
			<div <?php echo '' . $wrapper_attributes .gspb_AnimationRenderProps($animation, $interactionLayers); ?>>
				<div class="gspbgrid_list_builder <?php echo $isSlider ? 'swiper' : ''; ?>">
					<ul class="wp-block-repeater-template<?php echo $isSlider ? ' swiper-wrapper' : ''; ?>">
						<?php $i = 0;
						foreach ($result as $key => $value) : ?>
							<?php $i++; ?>
							<?php if (is_object($value)) {
								$value = (array)$value;
							} ?>
							<li class="gspbgrid_item swiper-slide post-id-<?php echo (int)$key; ?>">
								<?php $this->loop_inner_blocks($block_instance['innerBlocks'], $value); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>
<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	public function render_block($settings = array(), $inner_content = '', $block = '')
	{
		extract($settings);
		$output = $this->gspb_grid_constructor($settings, $inner_content, $block);
		return $output;
	}
}

new RepeaterQuery;
