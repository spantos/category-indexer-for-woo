<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Plugin options to delete
$category_indexer_options = [
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

foreach ( $category_indexer_options as $$category_indexer_option ) {
	delete_option( $$category_indexer_option );
}

// Delete transients used for caching
$category_indexer_transients = [
	'category_indexer_categories',
	'category_indexer_options',
	'category_indexer_parents'
];

foreach ( $category_indexer_transients as $category_indexer_transient ) {
	delete_transient( $category_indexer_transient );
	// Also delete directly from database to ensure complete cleanup
	delete_option( '_transient_' . $category_indexer_transient );
	delete_option( '_transient_timeout_' . $category_indexer_transient );
}