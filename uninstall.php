<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Plugin options to delete
$options = [
	'category_indexer_option_shop',
	'category_indexer_option_shop_canonical',
	'category_indexer_option_orderby',
	'category_indexer_option_url_parameters',
	'category_indexer_option_search',
	'category_indexer_option_pagination',
	'category_indexer_global_category_defaults',
	'category_indexer_per_page',
	'category_indexer_category_options'
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete transients used for caching
$transients = [
	'category_indexer_categories',
	'category_indexer_options',
	'category_indexer_parents'
];

foreach ( $transients as $transient ) {
	delete_transient( $transient );
	// Also delete directly from database to ensure complete cleanup
	delete_option( '_transient_' . $transient );
	delete_option( '_transient_timeout_' . $transient );
}