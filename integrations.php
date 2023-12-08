<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} 

//////////////////////////////////////////////////////////////////
// SEARCH AND FILTER PLUGIN
//////////////////////////////////////////////////////////////////

add_action( 'search-filter/settings/register', 'greenshift_saf_add_integration_option', 10 );
function greenshift_saf_add_integration_option() {
	// Get the setting.
	$integration_type_setting = \Search_Filter\Settings::get_setting( 'singleIntegration', 'query' );
	if ( ! $integration_type_setting ) {
		return;
	}
	
	$new_integration_type_option = array(
		'label' => __( 'Greenshift Query Builder', 'greenshiftquery' ),
		'value' => 'greenshift-blocks/query',
	);
	$integration_type_setting->add_option( $new_integration_type_option );
}

add_filter( 'search-filter/queries/query/get_attributes', 'greenshift_saf_update_query_attributes', 10, 2 );
function greenshift_saf_update_query_attributes( $attributes, $id ) {
	// We want `queryContainer` and `paginationSelector` to be set automatically.
	if ( ! isset( $attributes['integrationType'] ) ) {
		return $attributes;
	}
	$integration_type = $attributes['integrationType'];

	if ( ! isset( $attributes['singleIntegration'] ) ) {
		return $attributes;
	}
	$single_integration = $attributes['singleIntegration'];

	if ( $integration_type === 'single' && $single_integration === 'greenshift-blocks/query' ) {
		$attributes['queryContainer'] = '.wp-block-post-template';
		$attributes['paginationSelector'] = '.pagination a';
	}

	return $attributes;
}