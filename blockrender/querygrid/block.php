<?php


namespace greenshiftquery\Blocks;

defined('ABSPATH') or exit;


class GridQuery
{

	public function __construct()
	{
		add_action('init', array($this, 'init_handler'));
		$this->action();
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
		'cat' => array(
			'type' => 'array',
			'default' => null
		),
		'tag' => array(
			'type' => 'array',
			'default' => null
		),
		'cat_exclude' => array(
			'type' => 'array',
			'default' => null
		),
		'tag_exclude' => array(
			'type' => 'array',
			'default' => null
		),
		'dynamicGClasses' => array(
			'type' => 'array',
			'default' => []
		),
		'tax_name' => array(
			'type' => 'string',
			'default' => '',
		),
		'tax_slug' => array(
			'type' => 'array',
			'default' => null
		),
		'tax_slug_exclude' => array(
			'type' => 'array',
			'default' => null
		),
		'user_id' => array(
			'type' => 'array',
			'default' => null
		),
		'type' => array(
			'type' => 'string',
			'default' => 'all',
		),
		'ids' => array(
			'type' => 'array',
			'default' => null
		),
		'order' => array(
			'type' => 'string',
			'default' => 'desc',
		),
		'orderby' => array(
			'type' => 'string',
			'default' => 'date',
		),
		'meta_key' => array(
			'type' => 'string',
			'default' => '',
		),
		'show' => array(
			'type' => 'number',
			'default' => 12,
		),
		'offset' => array(
			'type' => 'string',
			'default' => '',
		),
		'enable_pagination' => array(
			'type' => 'string',
			'default' => '0',
		),
		'isSlider' => array(
			'type' => 'boolean',
			'default' => false,
		),
		'align' => array(
			'type' => 'string',
		),
		'custom_field_key' => array(
			'type' => 'string',
			'default' => ''
		),
		'custom_field_value' => array(
			'type' => 'string',
			'default' => ''
		),
		'custom_field_compare' => array(
			'type' => 'string',
			'default' => 'equal'
		),
		'conditions_arr' => array(
			'type' => 'array',
			'default' => []
		),
		'type_of_condition' => array(
			'type' => 'string',
			'default' => 'and'
		),
        'is_enable_custom_code' => array(
            'type' => 'boolean',
            'default' => false
        ),
        'htmlCode' => array(
            'type' => 'array',
            'default' => []
        ),
		'container_image_size' => array(
			'type' => 'string',
			'default' => 'medium'
		),
		'container_image' => array(
			'type' => 'boolean',
			'default' => false
		),
		'noMoreLabel'	=> array(
			'type' => 'string',
			'default' => ''
		),
		'additional_field'	=> array(
			'type' => 'string',
			'default' => ''
		),
		'additional_type'	=> array(
			'type' => 'string',
			'default' => ''
		),
		'title'	=> array(
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
		'linkNewWindow' => array(
			'type' => 'boolean',
			'default' => false
		),
	);

	protected function action()
	{
		add_action('wp_ajax_gspb_grid_render_preview', array($this, 'render_preview'));
		add_action('wp_ajax_gspb_filter_render_preview', array($this, 'render_filter_preview'));
		add_action('wp_ajax_gspb_grid_convert_shortcode', array($this, 'render_grid_convert_shortcode'));
	}

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

	public function extractInlineCssStyles($array){
		$inlineCssStyles = '';
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$inlineCssStyles .= $this->extractInlineCssStyles($value);
			} elseif ($key == 'inlineCssStyles') {
				$inlineCssStyles .= $value;
				unset($array[$key]);
			}
		}
		return $inlineCssStyles;
	}

	public function gspb_grid_constructor($settings, $content, $block)
	{
		$defaults = array(
			'data_source' => 'cat',
			'cat' => '',
			'cat_name' => '',
			'tag' => '',
			'cat_exclude' => '',
			'tag_exclude' => '',
			'ids' => '',
			'orderby' => '',
			'order' => 'DESC',
			'meta_key' => '',
			'show' => 10,
			'user_id' => '',
			'type' => '',
			'offset' => '',
			'show_date' => '',
			'post_type' => '',
			'tax_name' => '',
			'tax_slug' => '',
			'tax_slug_exclude' => '',
			'enable_pagination' => '',
			'price_range' => '',
			'filterpanel' => '',
			'filterheading' => '',
			'taxdrop' => '',
			'taxdroplabel' => '',
			'taxdropids' => '',
			'listargs' => '',
            'is_enable_custom_code' => '',
            'htmlCode' => '',
			'additional_field' => '',
			'additional_type' => ''

		);
		$build_args = wp_parse_args($settings, $defaults);
		extract($build_args);
		if ($enable_pagination == '2') {
			$infinitescrollwrap = ' gspb_aj_pag_clk_wrap';
		} elseif ($enable_pagination == '3') {
			$infinitescrollwrap = ' gspb_aj_pag_auto_wrap';
		} else {
			$infinitescrollwrap = '';
		}
		$containerid = 'gspb_filterid_' . mt_rand();
		$ajaxoffset = (int)$show + (int)$offset;
		if (isset($align)) {
			if ($align == 'full') {
				$alignClass = 'alignfull';
			} elseif ($align == 'wide') {
				$alignClass = 'alignwide';
			} elseif ($align == '') {
				$alignClass = '';
			}
		} else {
			$alignClass = 'alignwide';
		}
		ob_start();
		$block_instance = (is_array($block)) ? $block : $block->parsed_block;
		$blockId = 'gspbgrid_id-' . $block_instance['attrs']['id'];
		$data_attributes = gspb_getDataAttributesfromDynamic($settings);
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => $blockId . ' gspbgrid-wrap-grid ' . $alignClass,
				...$data_attributes
			)
		);
		?>
		<div id="<?php echo esc_attr($blockId);?>" <?php echo '' . $wrapper_attributes . gspb_AnimationRenderProps($animation, $interactionLayers); ?>>
			<?php 		
			$new_instance = $block_instance;
			$inlineStyles = $this->extractInlineCssStyles($new_instance);
			if(!empty($inlineStyles)){
				echo '<style type="text/css" scoped data-type="gspb-grid-inline-css">' . $inlineStyles . '</style>';
				$block_instance = $new_instance;
			}
			?>
			<?php
			global $wp_query;
			$argsfilter = new \GSPB_Postfilters($build_args);
			$args = $argsfilter->extract_filters();

			$args = apply_filters('gspb_module_args_query', $args);
			$wp_query = new \WP_Query($args);
			do_action('gspb_after_module_args_query', $wp_query);

			?>
			<?php if ($wp_query->have_posts()) : ?>
				<?php if ($title) : ?>
					<div class="gspbgrid-block__title">
						<?php echo esc_html($title); ?>
					</div>
				<?php endif; ?>
				<?php gspb_vc_filterpanel_render($filterpanel, $containerid, $taxdrop, $taxdroplabel, $taxdropids, $filterheading); ?>
				<?php
				if (!empty($args['paged'])) {
					unset($args['paged']);
				}
				$jsonargs = json_encode($args);
				$json_innerargs = $listargs;
				$json_block = rawurlencode(json_encode($block_instance));
				?>
				<div class="gspbgrid_list_builder wp-block-query <?php echo $isSlider ? 'swiper' : ''; ?><?php echo '' . $infinitescrollwrap; ?>" data-filterargs='<?php echo '' . ($filterpanel || $enable_pagination == '2' || $enable_pagination == '3') ? $jsonargs : "" . ''; ?>' data-template="querybuilder" id="<?php echo esc_attr($containerid); ?>" data-innerargs='<?php echo '' . $json_innerargs . ''; ?>' data-blockinstance='<?php echo '' . ($filterpanel || $enable_pagination == '2' || $enable_pagination == '3') ? $json_block : "" . ''; ?>' data-perpage='<?php echo '' . $show . ''; ?>'>
					<ul class="wp-block-post-template<?php echo $isSlider ? ' swiper-wrapper' : ''; ?>">
						<?php $i = 0;
						while ($wp_query->have_posts()) : $wp_query->the_post();
							$i++;
                            ?>
							<?php include(GREENSHIFTQUERY_DIR_PATH . 'parts/querybuilder.php'); ?>
                            <?php if ($settings["is_enable_custom_code"]) {
                                foreach ($settings["htmlCode"] as $block) {
                                    if ($block["position"] == $i) {
                                        $content_filtered = wp_kses( $block["html"], 'post' );?>
                                        <li class="gspbgrid_item swiper-slide ad-id-<?php echo (int)$i; ?>">
                                            <?php echo ''.$content_filtered; ?>
                                        </li>
                                        <?php
                                    }
                                }
                            } ?>
						<?php endwhile; ?>
					</ul>
					<?php if ($enable_pagination == '1') : ?>
						<div class="clearfix"></div>
						<div class="pagination"><?php the_posts_pagination(); ?></div>
					<?php elseif ($enable_pagination == '2' || $enable_pagination == '3') : ?>
						<div class="gspb_ajax_pagination gspb_ajax_pagination_outer"><span data-offset="<?php echo esc_attr($ajaxoffset); ?>" data-containerid="<?php echo esc_attr($containerid); ?>" class="gspb_ajax_pagination_btn"></span></div>
					<?php endif; ?>
				</div>
				<div class="clearfix"></div>
			<?php else:?>
				<?php if($noMoreLabel):?>
					<div class="gspb_no_more_posts">
						<?php echo esc_html($noMoreLabel);?>
					</div>
				<?php endif;?>
			<?php endif;wp_reset_query(); ?>
		</div>
		<?php if($data_source == 'autoshop'):?>
			<div style="display:none">
				<?php echo do_blocks('<!-- wp:query {"queryId":10,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":true,"__woocommerceAttributes":[],"__woocommerceStockStatus":["instock","outofstock","onbackorder"]},"displayLayout":{"type":"flex","columns":4},"namespace":"woocommerce/product-query"} --><div class="wp-block-query"><!-- wp:post-template {"__woocommerceNamespace":"woocommerce/product-query/product-template"} --><!-- /wp:post-template --></div><!-- /wp:query -->');?>
			</div>
		<?php endif;?>
	<?php
		$output = ob_get_contents();
		if (ob_get_level() > 0) {
			ob_end_clean();
		}
		return $output;
	}

	public function gspb_grid_get_posts($settings)
	{
		$defaults = array(
			'data_source' => 'cat',
			'cat' => '',
			'cat_name' => '',
			'tag' => '',
			'cat_exclude' => '',
			'tag_exclude' => '',
			'ids' => '',
			'orderby' => '',
			'order' => 'DESC',
			'meta_key' => '',
			'show' => 10,
			'user_id' => '',
			'type' => '',
			'offset' => '',
			'show_date' => '',
			'post_type' => '',
			'tax_name' => '',
			'tax_slug' => '',
			'tax_slug_exclude' => '',
			'enable_pagination' => '',
			'price_range' => '',
			'filterpanel' => '',
			'filterheading' => '',
			'taxdrop' => '',
			'taxdroplabel' => '',
			'taxdropids' => '',
			'listargs' => '',
            'is_enable_custom_code' => '',
            'htmlCode' => '',
			'additional_field' => '',
			'additional_type' => '',
		);
		$build_args = wp_parse_args($settings, $defaults);
		extract($build_args);

		global $wp_query;
		$argsfilter = new \GSPB_Postfilters($build_args);
		$args = $argsfilter->extract_filters();

		$args = apply_filters('gspb_module_args_query', $args);
		$wp_query = new \WP_Query($args);
		do_action('gspb_after_module_args_query', $wp_query);
		if (count($wp_query->posts)) {
			$posts = $wp_query->posts;
			$postfull = array();
			foreach ($posts as $post) {
				$postid = $post->ID;
				$postdate = get_the_date('', $postid);
				$postdatemodified = get_the_modified_date('', $postid);
				$authorname = get_the_author_meta('display_name', $post->post_author);
				$imageMedium = get_the_post_thumbnail($postid, 'medium');
				$imageFull = get_the_post_thumbnail($postid, 'full');
				$postdata = array('postDate' => $postdate, 'postDateModified' => $postdatemodified, 'authorName' => $authorname, 'imageMedium' => $imageMedium, 'imageFull' => $imageFull, 'gsID' => $postid);
				if ($post->post_type == 'product') {
					$_product = wc_get_product($postid);
					$rating = $_product->get_average_rating();
					$postdata['wooRating'] = $rating;
					$postdata['wooStars'] = '<div class="star-rating" role="img">' . wc_get_star_rating_html($rating, $_product->get_rating_count()) . '</div>';
					if($_product->is_type( 'variable' )){
						$postdata['wooPrice'] = '<span class="gspb-variable-price">'.$_product->get_price_html().'</span>';
					}else{
						$postdata['wooPrice'] = $_product->get_price_html();
					}
					$postdata['wooCategories'] = wc_get_product_category_list($postid);
					$postdata['wooAvailability'] = empty($_product->get_availability()['availability']) ? __('In stock', 'greenshiftquery') : '<span class="' . $_product->get_availability()['class'] . '">' . $_product->get_availability()['availability'] . '</span>';
					$postdata['wooDiscount'] = self::get_discount_percentage($_product);
					$postdata['wooThumbnail'] = $_product->get_image();
					if ( $_product->is_on_sale() ) {
						$sale_end = get_post_meta( $postid, '_sale_price_dates_to', true );
						$postdata['wooSaleEnd'] = $sale_end;
					}
				}
				if ($post->post_type == 'post') {
					$postdata['postCategories'] = get_the_term_list($postid, 'category', '', ', ', '');
				}
				$postdata['imageSizes'] = gspb_get_image_sizes();
				$postfull[] = (object) array_merge((array)$post, $postdata);
			};
			return $postfull;
		} else {
			return 'no items';
		}
	}

	static function get_discount_percentage($product)
	{

		$percentage = '';

		if ($product->is_type('variable')) {
			$percentages = array();

			// Get all variation prices
			$prices = $product->get_variation_prices();

			// Loop through variation prices
			foreach ($prices['price'] as $key => $price) {
				// Only on sale variations
				if ($prices['regular_price'][$key] !== $price) {
					// Calculate and set in the array the percentage for each variation on sale
					$percentages[] = round(100 - (floatval($prices['sale_price'][$key]) / floatval($prices['regular_price'][$key]) * 100));
				}
			}
			// We keep the highest value
			if (count($percentages)) {
				$percentage = max($percentages) . '%';
			}
		} elseif ($product->is_type('grouped')) {
			$percentages = array();

			// Get all variation prices
			$children_ids = $product->get_children();

			// Loop through variation prices
			foreach ($children_ids as $child_id) {
				$child_product = wc_get_product($child_id);

				$regular_price = (float) $child_product->get_regular_price();
				$sale_price    = (float) $child_product->get_sale_price();

				if ($sale_price != 0 || !empty($sale_price)) {
					// Calculate and set in the array the percentage for each child on sale
					$percentages[] = round(100 - ($sale_price / $regular_price * 100));
				}
			}
			// We keep the highest value
			$percentage = max($percentages) . '%';
		} else {
			$regular_price = (float) $product->get_regular_price();
			$sale_price    = (float) $product->get_sale_price();

			if ($sale_price != 0 || !empty($sale_price)) {
				$percentage    = round(100 - ($sale_price / $regular_price * 100)) . '%';
			} else {
				return '';
			}
		}

		return $percentage;
	}

	public function render_preview()
	{
		$settings = $_POST['settings'];
		$this->normalize_arrays($settings);

		if (!empty($settings['filterpanel'])) {
			$settings['filterpanel'] = gspb_filter_empty_values($settings['filterpanel']);
			$settings['filterpanel'] = rawurlencode(json_encode($settings['filterpanel']));
		}
		$preview = $this->gspb_grid_get_posts($settings);
		wp_send_json_success($preview);
	}
	public function render_grid_convert_shortcode()
	{
		$content = $_POST['content'];
		$content = stripcslashes($content);
		$output = array(
			'content' => do_shortcode($content)
		);
		wp_send_json_success($output);
	}


	public function render_filter_preview()
	{
		$settings = $_POST['settings'];
		$this->normalize_arrays($settings);

		if (!empty($settings['filterpanel'])) {
			$settings['filterpanel'] = gspb_filter_empty_values($settings['filterpanel']);
			$settings['filterpanel'] = rawurlencode(json_encode($settings['filterpanel']));
		}
		$defaults = array(
			'data_source' => 'cat',
			'cat' => '',
			'cat_name' => '',
			'tag' => '',
			'cat_exclude' => '',
			'tag_exclude' => '',
			'ids' => '',
			'orderby' => '',
			'order' => 'DESC',
			'meta_key' => '',
			'show' => 10,
			'user_id' => '',
			'type' => '',
			'offset' => '',
			'show_date' => '',
			'post_type' => '',
			'tax_name' => '',
			'tax_slug' => '',
			'tax_slug_exclude' => '',
			'enable_pagination' => '',
			'price_range' => '',
			'filterpanel' => '',
			'filterheading' => '',
			'taxdrop' => '',
			'taxdroplabel' => '',
			'taxdropids' => '',
			'listargs' => '',
			'is_enable_custom_code' => '',
			'htmlCode' => '',

		);
		$build_args = wp_parse_args($settings, $defaults);
		extract($build_args);
		ob_start();
		gspb_vc_filterpanel_render($filterpanel, '', $taxdrop, $taxdroplabel, $taxdropids, $filterheading);
		$output = ob_get_contents();
		ob_end_clean();
		wp_send_json_success($output);
	}

	public function render_block($settings = array(), $inner_content = '', $block = '')
	{
		extract($settings);
		$this->normalize_arrays($settings);

		if (!empty($settings['filterpanel'])) {
			$settings['filterpanel'] = gspb_filter_empty_values($settings['filterpanel']);
			$settings['filterpanel'] = rawurlencode(json_encode($settings['filterpanel']));
		}
		$output = $this->gspb_grid_constructor($settings, $inner_content, $block);
		return $output;
	}
}

new GridQuery;
