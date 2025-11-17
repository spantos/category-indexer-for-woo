<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = [
    'category_indexer_option_shop',
    'category_indexer_option_shop_canonical',
    'category_indexer_option_orderby',
    'category_indexer_option_url_parameters',
    'category_indexer_category_options'
];

foreach ( $options as $option ) {
	delete_option( $option );
}