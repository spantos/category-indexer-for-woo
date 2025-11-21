<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Manages caching for the Category Indexer for WooCommerce plugin.
 *
 * This class provides caching functionality to improve performance when loading
 * large numbers of categories. It stores category data, parent relationships,
 * and options in WordPress transients.
 */
class Category_Indexer_Cache {

	/**
	 * Cache key prefix for category data.
	 *
	 * @var string
	 */
	const CACHE_KEY_CATEGORIES = 'category_indexer_categories';

	/**
	 * Cache key for category options.
	 *
	 * @var string
	 */
	const CACHE_KEY_OPTIONS = 'category_indexer_options';

	/**
	 * Cache key for parent categories map.
	 *
	 * @var string
	 */
	const CACHE_KEY_PARENTS = 'category_indexer_parents';

	/**
	 * Cache expiration time in seconds (24 hours).
	 *
	 * @var int
	 */
	const CACHE_EXPIRATION = DAY_IN_SECONDS;

	/**
	 * Gets all product categories with caching.
	 *
	 * @return array Array of WP_Term objects.
	 */
	public static function get_categories() {
		$cached = get_transient( self::CACHE_KEY_CATEGORIES );

		if ( false !== $cached ) {
			return $cached;
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $categories ) ) {
			return array();
		}

		set_transient( self::CACHE_KEY_CATEGORIES, $categories, self::CACHE_EXPIRATION );

		return $categories;
	}

	/**
	 * Gets category options with caching.
	 *
	 * @return array Category options array.
	 */
	public static function get_category_options() {
		$cached = get_transient( self::CACHE_KEY_OPTIONS );

		if ( false !== $cached ) {
			return $cached;
		}

		$options = get_option( 'category_indexer_category_options', array() );

		set_transient( self::CACHE_KEY_OPTIONS, $options, self::CACHE_EXPIRATION );

		return $options;
	}

	/**
	 * Gets parent categories map with caching.
	 *
	 * Creates a map of category_id => parent_term_object for quick lookup.
	 *
	 * @return array Map of parent categories.
	 */
	public static function get_parents_map() {
		$cached = get_transient( self::CACHE_KEY_PARENTS );

		if ( false !== $cached ) {
			return $cached;
		}

		$categories  = self::get_categories();
		$parents_map = array();

		foreach ( $categories as $category ) {
			if ( $category->parent !== 0 ) {
				$parent_term = get_term( $category->parent, 'product_cat' );
				if ( ! is_wp_error( $parent_term ) ) {
					$parents_map[ $category->term_id ] = $parent_term;
				}
			}
		}

		set_transient( self::CACHE_KEY_PARENTS, $parents_map, self::CACHE_EXPIRATION );

		return $parents_map;
	}

	/**
	 * Clears all category indexer cache.
	 *
	 * @return bool True if all caches were cleared successfully.
	 */
	public static function clear_cache() {
		$result1 = delete_transient( self::CACHE_KEY_CATEGORIES );
		$result2 = delete_transient( self::CACHE_KEY_OPTIONS );
		$result3 = delete_transient( self::CACHE_KEY_PARENTS );

		return $result1 || $result2 || $result3;
	}

	/**
	 * Refreshes the cache by clearing and rebuilding it.
	 *
	 * @return array Returns fresh category data.
	 */
	public static function refresh_cache() {
		self::clear_cache();

		return array(
			'categories'  => self::get_categories(),
			'options'     => self::get_category_options(),
			'parents_map' => self::get_parents_map(),
		);
	}
}
