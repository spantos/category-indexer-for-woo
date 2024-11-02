<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = [
    'wc_category_indexer_option_shop',
    'wc_category_indexer_option_shop_canonical',
    'wc_category_indexer_option_orderby',
    'wc_category_indexer_option_url_parameters',
    'wc_category_indexer_category_options'
];

foreach ( $options as $option ) {
	delete_option( $option );
}