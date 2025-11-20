<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Category Cache Manager for Category Indexer for WooCommerce.
 *
 * Implements a two-tier caching system for product categories:
 * 1. In-memory cache (valid for current request only)
 * 2. WordPress transient cache (persistent across requests)
 *
 * Uses Singleton pattern to ensure only one instance exists.
 *
 * @package Category_Indexer_For_Woo
 * @since 1.0.0
 */
class Category_Indexer_Cache {

	/**
	 * The single instance of the class.
	 *
	 * @var Category_Indexer_Cache|null
	 */
	private static $instance = null;

	/**
	 * In-memory cache for categories.
	 *
	 * @var array|null
	 */
	private $cached_categories = null;

	/**
	 * Cache key for WordPress transients.
	 *
	 * @var string
	 */
	private $cache_key = 'cifw_product_categories';

	/**
	 * Cache duration in seconds.
	 *
	 * @var int
	 */
	private $cache_duration = 12 * HOUR_IN_SECONDS; // 12 hours default

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		$this->register_hooks();
	}

	/**
	 * Prevent cloning of the instance.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the instance.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Gets the singleton instance of the cache manager.
	 *
	 * @return Category_Indexer_Cache The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers WordPress hooks for cache invalidation.
	 */
	private function register_hooks() {
		// Automatically clear cache when categories are modified
		add_action( 'create_product_cat', array( $this, 'clear_cache' ) );
		add_action( 'edit_product_cat', array( $this, 'clear_cache' ) );
		add_action( 'delete_product_cat', array( $this, 'clear_cache' ) );
	}

	/**
	 * Gets product categories with two-tier caching.
	 *
	 * First checks in-memory cache, then WordPress transient cache,
	 * and finally fetches from database if needed.
	 *
	 * @return array Array of WP_Term objects for product categories.
	 */
	public function get_categories() {
		// Check in-memory cache first (fastest)
		if ( $this->cached_categories !== null ) {
			return $this->cached_categories;
		}

		// Check transient cache (fast)
		$categories = get_transient( $this->cache_key );

		if ( false === $categories ) {
			// Fetch from database (slowest)
			$categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
				)
			);

			// Handle errors
			if ( is_wp_error( $categories ) ) {
				return array();
			}

			// Store in transient cache
			set_transient( $this->cache_key, $categories, $this->cache_duration );
		}

		// Store in-memory cache for this request
		$this->cached_categories = $categories;

		return $categories;
	}

	/**
	 * Clears all cache (both in-memory and transient).
	 *
	 * Called automatically when categories are created, edited, or deleted.
	 * Can also be called manually.
	 */
	public function clear_cache() {
		delete_transient( $this->cache_key );
		$this->cached_categories = null;
	}

	/**
	 * Sets the cache duration.
	 *
	 * @param int $hours Number of hours to cache categories.
	 */
	public function set_cache_duration( $hours ) {
		$this->cache_duration = absint( $hours ) * HOUR_IN_SECONDS;
	}

	/**
	 * Gets the current cache duration in hours.
	 *
	 * @return int Number of hours categories are cached.
	 */
	public function get_cache_duration_hours() {
		return $this->cache_duration / HOUR_IN_SECONDS;
	}

	/**
	 * Checks if categories are currently cached in transient.
	 *
	 * @return bool True if cached, false otherwise.
	 */
	public function is_cached() {
		return false !== get_transient( $this->cache_key );
	}

	/**
	 * Gets cache statistics.
	 *
	 * @return array Array with cache stats (is_cached, in_memory, duration_hours).
	 */
	public function get_cache_stats() {
		return array(
			'is_cached'      => $this->is_cached(),
			'in_memory'      => $this->cached_categories !== null,
			'duration_hours' => $this->get_cache_duration_hours(),
		);
	}
}
