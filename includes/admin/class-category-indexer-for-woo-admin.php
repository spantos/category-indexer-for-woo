<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Manages the admin interface for the Category Indexer for WooCommerce plugin.
 *
 * This class is responsible for adding the "Category Indexer" menu page to the WordPress admin menu,
 * registering the plugin's settings, rendering the admin page, and enqueuing the necessary scripts.
 * It also includes functions for sanitizing the plugin's options and checking for required plugin dependencies.
 */
class Category_Indexer_For_Woo_Admin {

	private $category_section_title = true;
	private $counter                = 0;

	/**
	 * Initializes the Category_Indexer_For_Woo_Admin class.
	 *
	 * Hooks into the following WordPress actions:
	 * - `admin_enqueue_scripts` - Enqueues the necessary scripts for the admin area.
	 * - `admin_menu` - Adds the "Category Indexer" menu page to the WordPress admin menu.
	 * - `admin_init` - Registers the settings for the plugin.
	 */
	public function __construct() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Include cache class
		if ( file_exists( CATEGORY_INDEXER_PLUGIN_DIR . 'includes/admin/class-category-indexer-cache.php' ) ) {
			require_once CATEGORY_INDEXER_PLUGIN_DIR . 'includes/admin/class-category-indexer-cache.php';
		}

		// Initialize cache hooks for automatic cache invalidation
		if ( class_exists( 'Category_Indexer_Cache' ) ) {
			Category_Indexer_Cache::init();
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
		add_action( 'wp_ajax_reset_category_settings', array( $this, 'ajax_reset_category_settings' ) );
		add_action( 'wp_ajax_clear_category_cache', array( $this, 'ajax_clear_category_cache' ) );
		add_action( 'wp_ajax_update_categories_per_page', array( $this, 'ajax_update_categories_per_page' ) );
	}

	/**
	 * Adds the "Category Indexer" menu page to the WordPress admin menu.
	 *
	 * This function is hooked into the `admin_menu` action, which is triggered when the
	 * WordPress admin menu is being built. It adds a new top-level menu page with the
	 * title "Category Indexer" and the capability "manage_options", which means only
	 * users with the "Administrator" role can access it.
	 *
	 * The menu page is registered with the slug "wc-category-indexer" and the callback
	 * function `admin_page()` is used to render the content of the page.
	 *
	 * The menu page icon is set to the "dashicons-category" icon.
	 */
	public function add_admin_menu() {
		add_menu_page(
			esc_html__( 'Category Indexer', 'category-indexer-for-woocommerce' ),
			esc_html__( 'Category Indexer', 'category-indexer-for-woocommerce' ),
			'manage_options',
			'wc-category-indexer',
			array( $this, 'admin_page' ),
			'dashicons-category',
			56
		);
	}

	/**
	 * Sanitizes the Category Indexer for WooCommerce options.
	 *
	 * This function is used as the sanitization callback for the Category Indexer for WooCommerce options.
	 * It applies the `sanitize_text_field` function to each element in the `$options` array, ensuring
	 * that the options are properly sanitized before being saved to the database.
	 *
	 * @param array $options The Category Indexer for WooCommerce options to be sanitized.
	 * @return array The sanitized Category Indexer for WooCommerce options.
	 */
	public function sanitize_category_indexer_options( $options ) {
		$options = map_deep( $options, 'sanitize_text_field' );
		return $options;
	}

	/**
	 * Registers the settings for the Category Indexer for WooCommerce plugin.
	 *
	 * This function is used to register the various settings options for the plugin,
	 * including options for the shop page, WooCommerce order by filter, URL with
	 * parameters, and WooCommerce categories. The settings are registered with separate
	 * option groups to prevent settings conflicts between tabs.
	 */
	public function register_settings() {

		$register_setting_args = array(
			'sanitize_callback' => array( $this, 'sanitize_category_indexer_options' ),
			'type'              => 'array',
		);

		// Register settings for the General tab (shop, orderby, url parameters, search, pagination, category defaults)
		register_setting( 'category_indexer_general_options', 'category_indexer_option_shop', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_option_shop_canonical', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_option_orderby', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_option_url_parameters', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_option_search', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_option_pagination', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_global_category_defaults', $register_setting_args );

		// Register settings for the Categories tab
		register_setting( 'category_indexer_category_options_group', 'category_indexer_category_options', $register_setting_args );
	}

	/**
	 * Renders the admin page for the Category Indexer for WooCommerce plugin.
	 *
	 * This function is responsible for rendering the admin page for the Category Indexer for WooCommerce plugin. It calls
	 * various helper functions to render the different sections of the admin page, such as the shop section, order by
	 * section, URL with parameters section, and individual category sections.
	 */
	public function admin_page() {
		$this->render_admin_page();
	}

	/**
	 * Renders the admin page for the Category Indexer for WooCommerce plugin.
	 *
	 * This function is responsible for rendering the admin page for the Category Indexer for WooCommerce plugin. It calls
	 * various helper functions to render the different sections of the admin page, such as the shop section, order by
	 * section, URL with parameters section, and individual category sections.
	 */
	public function render_admin_page() {
		if ( ! is_admin() ) {
			return;
		}

		// Get the active tab, default to 'general'
		$active_tab = 'general'; // Default value
		if ( isset( $_GET['tab'] ) ) {
			// Verify nonce for tab navigation
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'category_indexer_tab_' . sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) ) {
				wp_die( esc_html__( 'Security verification failed. Please try again.', 'category-indexer-for-woocommerce' ) );
			}
			$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Category Indexer for WooCommerce', 'category-indexer-for-woocommerce' ) . '</h1>';

		// Render tabs
		echo '<h2 class="nav-tab-wrapper">';
		$general_url    = add_query_arg(
			array(
				'page' => 'wc-category-indexer',
				'tab'  => 'general',
			),
			admin_url( 'admin.php' )
		);
		$general_url    = wp_nonce_url( $general_url, 'category_indexer_tab_general' );
		$categories_url = add_query_arg(
			array(
				'page' => 'wc-category-indexer',
				'tab'  => 'categories',
			),
			admin_url( 'admin.php' )
		);
		$categories_url = wp_nonce_url( $categories_url, 'category_indexer_tab_categories' );
		echo '<a href="' . esc_url( $general_url ) . '" class="nav-tab ' . ( $active_tab === 'general' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'General', 'category-indexer-for-woocommerce' ) . '</a>';
		echo '<a href="' . esc_url( $categories_url ) . '" class="nav-tab ' . ( $active_tab === 'categories' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Categories', 'category-indexer-for-woocommerce' ) . '</a>';
		echo '</h2>';

		// Start form with the correct option group for the active tab
		echo '<form method="post" action="options.php">';

		if ( $active_tab === 'general' ) {
			settings_fields( 'category_indexer_general_options' );
			do_settings_sections( 'category_indexer_general_options' );
			$this->render_general_tab_content();
		} else {
			settings_fields( 'category_indexer_category_options_group' );
			do_settings_sections( 'category_indexer_category_options_group' );
			$this->render_categories_tab_content();
		}

		// Submit button
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Renders the content for the General tab.
	 */
	public function render_general_tab_content() {
		echo '<div class="ci-settings-grid">';
		$this->render_shop_section_content();
		$this->render_category_defaults_section();
		$this->render_orderby_section();
		$this->render_url_with_parameters();
		$this->render_search_section();
		$this->render_pagination_section();
		echo '</div>';
	}

	/**
	 * Renders the content for the Categories tab with pagination.
	 */
	public function render_categories_tab_content() {
		// Get all data from cache (single database access)
		if ( class_exists( 'Category_Indexer_Cache' ) ) {
			$categories  = Category_Indexer_Cache::get_categories();
			$options     = Category_Indexer_Cache::get_category_options();
			$parents_map = Category_Indexer_Cache::get_parents_map();
		} else {
			$categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);
			$options     = get_option( 'category_indexer_category_options', array() );
			$parents_map = array();
		}

		// Pagination settings
		$per_page     = get_option( 'category_indexer_per_page', 20 );
		$current_page = 1; // Default value
		if ( isset( $_GET['paged'] ) ) {
			// Verify nonce for pagination
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'category_indexer_pagination' ) ) {
				wp_die( esc_html__( 'Security verification failed. Please try again.', 'category-indexer-for-woocommerce' ) );
			}
			$current_page = max( 1, intval( wp_unslash( $_GET['paged'] ) ) );
		}
		$total_items  = count( $categories );
		$total_pages  = ceil( $total_items / $per_page );
		$offset       = ( $current_page - 1 ) * $per_page;

		// Get categories for current page
		$paged_categories = array_slice( $categories, $offset, $per_page );

		// Render pagination info
		echo '<div class="tablenav top" style="margin: 20px 0;">';
		echo '<div class="tablenav-pages">';
		/* translators: %s: number of categories */
		echo '<span class="displaying-num">' . sprintf( esc_html__( '%s categories', 'category-indexer-for-woocommerce' ), number_format_i18n( $total_items ) ) . '</span>';

		if ( $total_pages > 1 ) {
			echo '<span class="pagination-links">';

			// First page
			if ( $current_page > 1 ) {
				$first_page_url = wp_nonce_url( add_query_arg( 'paged', 1 ), 'category_indexer_pagination' );
				$prev_page_url  = wp_nonce_url( add_query_arg( 'paged', $current_page - 1 ), 'category_indexer_pagination' );
				echo '<a class="first-page button" href="' . esc_url( $first_page_url ) . '">&laquo;</a> ';
				echo '<a class="prev-page button" href="' . esc_url( $prev_page_url ) . '">&lsaquo;</a> ';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled">&laquo;</span> ';
				echo '<span class="tablenav-pages-navspan button disabled">&lsaquo;</span> ';
			}

			echo '<span class="paging-input">';
			/* translators: 1: current page number, 2: total number of pages */
			echo '<span class="tablenav-paging-text">' . sprintf( esc_html__( '%1$s of %2$s', 'category-indexer-for-woocommerce' ), number_format_i18n( $current_page ), number_format_i18n( $total_pages ) ) . '</span>';
			echo '</span> ';

			// Next and last page
			if ( $current_page < $total_pages ) {
				$next_page_url = wp_nonce_url( add_query_arg( 'paged', $current_page + 1 ), 'category_indexer_pagination' );
				$last_page_url = wp_nonce_url( add_query_arg( 'paged', $total_pages ), 'category_indexer_pagination' );
				echo '<a class="next-page button" href="' . esc_url( $next_page_url ) . '">&rsaquo;</a> ';
				echo '<a class="last-page button" href="' . esc_url( $last_page_url ) . '">&raquo;</a>';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled">&rsaquo;</span> ';
				echo '<span class="tablenav-pages-navspan button disabled">&raquo;</span>';
			}

			echo '</span>';
		}

		echo '</div>';
		echo '</div>';

		// Render categories for current page
		foreach ( $paged_categories as $category ) {
			$this->render_category_section( $category, $options, $parents_map );
		}

		// Bottom pagination
		if ( $total_pages > 1 ) {
			echo '<div class="tablenav-pages" style="margin-top: 20px;">';
			/* translators: %s: number of categories */
			echo '<span class="displaying-num">' . sprintf( esc_html__( '%s categories', 'category-indexer-for-woocommerce' ), number_format_i18n( $total_items ) ) . '</span>';
			echo '<span class="pagination-links">';

			if ( $current_page > 1 ) {
				$first_page_url = wp_nonce_url( add_query_arg( 'paged', 1 ), 'category_indexer_pagination' );
				$prev_page_url  = wp_nonce_url( add_query_arg( 'paged', $current_page - 1 ), 'category_indexer_pagination' );
				echo '<a class="first-page button" href="' . esc_url( $first_page_url ) . '">&laquo;</a> ';
				echo '<a class="prev-page button" href="' . esc_url( $prev_page_url ) . '">&lsaquo;</a> ';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled">&laquo;</span> ';
				echo '<span class="tablenav-pages-navspan button disabled">&lsaquo;</span> ';
			}

			echo '<span class="paging-input">';
			/* translators: 1: current page number, 2: total number of pages */
			echo '<span class="tablenav-paging-text">' . sprintf( esc_html__( '%1$s of %2$s', 'category-indexer-for-woocommerce' ), number_format_i18n( $current_page ), number_format_i18n( $total_pages ) ) . '</span>';
			echo '</span> ';

			if ( $current_page < $total_pages ) {
				$next_page_url = wp_nonce_url( add_query_arg( 'paged', $current_page + 1 ), 'category_indexer_pagination' );
				$last_page_url = wp_nonce_url( add_query_arg( 'paged', $total_pages ), 'category_indexer_pagination' );
				echo '<a class="next-page button" href="' . esc_url( $next_page_url ) . '">&rsaquo;</a> ';
				echo '<a class="last-page button" href="' . esc_url( $last_page_url ) . '">&raquo;</a>';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled">&rsaquo;</span> ';
				echo '<span class="tablenav-pages-navspan button disabled">&raquo;</span>';
			}

			echo '</span>';
			echo '</div>';
		}
	}

	/**
	 * Enqueues the admin JavaScript and CSS file for the Category Indexer for WooCommerce plugin.
	 *
	 * This function is responsible for enqueuing the admin.js JavaScript file, which is used to provide
	 * additional functionality for the Category Indexer for WooCommerce plugin's admin page.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wc-category-indexer-admin', CATEGORY_INDEXER_PLUGIN_URL . 'assests/js/admin.js', array( 'jquery' ), CATEGORY_INDEXER_VERSION, true );
		wp_enqueue_style( 'wc-category-indexer-admin-css', CATEGORY_INDEXER_PLUGIN_URL . 'assests/css/admin.css', array(), CATEGORY_INDEXER_VERSION, 'all' );

		// Localize script for AJAX
		wp_localize_script(
			'wc-category-indexer-admin',
			'categoryIndexerAjax',
			array(
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'category_indexer_nonce' ),
				'reset_nonce'       => wp_create_nonce( 'reset_category_settings_nonce' ),
				'clear_cache_nonce' => wp_create_nonce( 'clear_category_cache_nonce' ),
				'i18n'              => array(
					'confirmReset'          => esc_html__( 'Are you sure you want to reset all category settings to default? This action cannot be undone.', 'category-indexer-for-woocommerce' ),
					'confirmClearCache'     => esc_html__( 'Are you sure you want to clear the category cache? The cache will be rebuilt automatically on the next page load.', 'category-indexer-for-woocommerce' ),
					'resetting'             => esc_html__( 'Resetting...', 'category-indexer-for-woocommerce' ),
					'clearing'              => esc_html__( 'Clearing...', 'category-indexer-for-woocommerce' ),
					'resetButton'           => esc_html__( 'Reset All Categories to Default', 'category-indexer-for-woocommerce' ),
					'clearCacheButton'      => esc_html__( 'Clear Cache', 'category-indexer-for-woocommerce' ),
					'errorOccurred'         => esc_html__( 'An error occurred.', 'category-indexer-for-woocommerce' ),
					'errorResetting'        => esc_html__( 'An error occurred while resetting settings.', 'category-indexer-for-woocommerce' ),
					'errorClearing'         => esc_html__( 'An error occurred while clearing cache.', 'category-indexer-for-woocommerce' ),
					'errorUpdating'         => esc_html__( 'An error occurred while updating settings.', 'category-indexer-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Checks if the required plugins are installed and activated before activating the Category Indexer for WooCommerce plugin.
	 *
	 * This function checks if the WooCommerce plugin and either the Rank Math SEO or Yoast SEO plugin are installed and activated.
	 * If either of these required plugins are not installed and activated, the Category Indexer for WooCommerce plugin is deactivated and a message is displayed to the user.
	 * It also checks if the PHP version is at least 7.0, and deactivates the plugin if the requirement is not met.
	 */
	
	 public static function plugin_activation_check() {
		/*
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			deactivate_plugins( CATEGORY_INDEXER_PLUGIN_FILE );
			wp_die(
				esc_html__( 'The plugin requires the WooCommerce plugin to be installed and activated', 'category-indexer-for-woocommerce' ),
				'',
				array( 'back_link' => true )
			);
		}
		*/

		if ( ! is_plugin_active( 'seo-by-rank-math/rank-math.php' ) && ! is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
			deactivate_plugins( CATEGORY_INDEXER_PLUGIN_FILE );
			wp_die(
				esc_html__( 'This plugin requires Rank Math SEO or Yoast SEO to be installed and activated. Please install and activate one of these plugins before activating Category Indexer for WooCommerce.', 'category-indexer-for-woocommerce' ),
				'',
				array( 'back_link' => true )
			);
		}
		if ( version_compare( phpversion(), '7.0', '>=' ) ){
			return true;
		}  else {
			deactivate_plugins( CATEGORY_INDEXER_PLUGIN_FILE );
			wp_die(
				esc_html__( 'This plugin requires PHP version 7.0 or higher. Please update your PHP version before activating Category Indexer for WooCommerce.', 'category-indexer-for-woocommerce' ),
				'',
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Renders the shop section content (without form wrapper).
	 *
	 * This function is responsible for rendering the shop pages settings section of the plugin's admin page.
	 * It includes options for setting the index and follow status of the first page and all other pages of the shop.
	 * It also includes an option for setting the canonical tag for pages after the first page.
	 */
	public function render_shop_section_content() {
		$options           = get_option( 'category_indexer_option_shop' );
		$canonical_options = get_option( 'category_indexer_option_shop_canonical' );
		?>
		<div class="ci-settings-card ci-full-width">
			<div class="ci-card-header">
				<h3><?php esc_html_e( 'Shop Pages Settings', 'category-indexer-for-woocommerce' ); ?></h3>
			</div>
			<div class="ci-card-body">
				<!-- First Page Settings -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'First Page', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-two-column-radios">
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_option_shop[shop_first_page_index]" value="index" <?php checked( 'index', $options['shop_first_page_index'] ?? '' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_shop[shop_first_page_index]" value="noindex" <?php checked( 'noindex', $options['shop_first_page_index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_option_shop[shop_first_page_follow]" value="follow" <?php checked( 'follow', $options['shop_first_page_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_shop[shop_first_page_follow]" value="nofollow" <?php checked( 'nofollow', $options['shop_first_page_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="ci-divider"></div>

				<!-- All Other Pages Settings -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'All Other Pages', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-two-column-radios">
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_option_shop[shop_all_other_page_index]" value="index" <?php checked( 'index', $options['shop_all_other_page_index'] ?? '' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_shop[shop_all_other_page_index]" value="noindex" <?php checked( 'noindex', $options['shop_all_other_page_index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_option_shop[shop_all_other_page_follow]" value="follow" <?php checked( 'follow', $options['shop_all_other_page_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_shop[shop_all_other_page_follow]" value="nofollow" <?php checked( 'nofollow', $options['shop_all_other_page_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="ci-divider"></div>

				<!-- Canonical Tag Settings -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'Canonical tag for pages after the first page', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-radio-group">
						<label>
							<input type="radio" name="category_indexer_option_shop_canonical[pages_after_first]" value="default" <?php checked( 'default', $canonical_options['pages_after_first'] ?? '' ); ?>>
							<?php esc_html_e( 'Default (No Change)', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_option_shop_canonical[pages_after_first]" value="first_page" <?php checked( 'first_page', $canonical_options['pages_after_first'] ?? '' ); ?>>
							<?php esc_html_e( 'First page as a canonical link', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the global category and subcategory SEO defaults section.
	 *
	 * This function is responsible for rendering the HTML for the global SEO settings that apply
	 * to all categories and subcategories unless overridden by specific category settings.
	 */
	public function render_category_defaults_section() {
		$options = get_option( 'category_indexer_global_category_defaults' );
		?>
		<div class="ci-settings-card ci-full-width">
			<div class="ci-card-header">
				<h3><?php esc_html_e( 'Category & Subcategory SEO Defaults', 'category-indexer-for-woocommerce' ); ?></h3>
				<p class="description" style="margin-top: 8px; font-weight: normal;">
					<?php esc_html_e( 'These settings apply to all categories and subcategories unless overridden by specific category settings in the Categories tab.', 'category-indexer-for-woocommerce' ); ?>
				</p>
			</div>
			<div class="ci-card-body">
				<!-- Categories Section -->
				<div class="ci-subsection-header">
					<h4><?php esc_html_e( 'Product Categories', 'category-indexer-for-woocommerce' ); ?></h4>
				</div>

				<!-- Category First Page Settings -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'First Page', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-two-column-radios">
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[category_first_page_index]" value="index" <?php checked( 'index', $options['category_first_page_index'] ?? 'index' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[category_first_page_index]" value="noindex" <?php checked( 'noindex', $options['category_first_page_index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[category_first_page_follow]" value="follow" <?php checked( 'follow', $options['category_first_page_follow'] ?? 'follow' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[category_first_page_follow]" value="nofollow" <?php checked( 'nofollow', $options['category_first_page_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="ci-divider"></div>

				<!-- Category Other Pages Settings -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'Other Pages in Pagination', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-two-column-radios">
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[category_other_pages_index]" value="index" <?php checked( 'index', $options['category_other_pages_index'] ?? 'index' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[category_other_pages_index]" value="noindex" <?php checked( 'noindex', $options['category_other_pages_index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[category_other_pages_follow]" value="follow" <?php checked( 'follow', $options['category_other_pages_follow'] ?? 'follow' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[category_other_pages_follow]" value="nofollow" <?php checked( 'nofollow', $options['category_other_pages_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="ci-divider"></div>

				<!-- Category Canonical Tag Settings -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'Canonical Tag for Other Pages', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-radio-group">
						<label>
							<input type="radio" name="category_indexer_global_category_defaults[category_other_pages_canonical]" value="default" <?php checked( 'default', $options['category_other_pages_canonical'] ?? 'default' ); ?>>
							<?php esc_html_e( 'Points to itself (each page has its own canonical)', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_global_category_defaults[category_other_pages_canonical]" value="first_page" <?php checked( 'first_page', $options['category_other_pages_canonical'] ?? '' ); ?>>
							<?php esc_html_e( 'Points to first page (all pages use category base URL)', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
				</div>

				<div class="ci-divider" style="margin: 30px 0;"></div>

				<!-- Subcategories Section -->
				<div class="ci-subsection-header">
					<h4><?php esc_html_e( 'Product Subcategories', 'category-indexer-for-woocommerce' ); ?></h4>
				</div>

				<!-- Subcategory First Page Settings -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'First Page', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-two-column-radios">
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[subcategory_first_page_index]" value="index" <?php checked( 'index', $options['subcategory_first_page_index'] ?? 'index' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[subcategory_first_page_index]" value="noindex" <?php checked( 'noindex', $options['subcategory_first_page_index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[subcategory_first_page_follow]" value="follow" <?php checked( 'follow', $options['subcategory_first_page_follow'] ?? 'follow' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[subcategory_first_page_follow]" value="nofollow" <?php checked( 'nofollow', $options['subcategory_first_page_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="ci-divider"></div>

				<!-- Subcategory Other Pages Settings -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'Other Pages in Pagination', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-two-column-radios">
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[subcategory_other_pages_index]" value="index" <?php checked( 'index', $options['subcategory_other_pages_index'] ?? 'index' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[subcategory_other_pages_index]" value="noindex" <?php checked( 'noindex', $options['subcategory_other_pages_index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[subcategory_other_pages_follow]" value="follow" <?php checked( 'follow', $options['subcategory_other_pages_follow'] ?? 'follow' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_global_category_defaults[subcategory_other_pages_follow]" value="nofollow" <?php checked( 'nofollow', $options['subcategory_other_pages_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="ci-divider"></div>

				<!-- Subcategory Canonical Tag Settings -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'Canonical Tag for Other Pages', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-radio-group">
						<label>
							<input type="radio" name="category_indexer_global_category_defaults[subcategory_other_pages_canonical]" value="default" <?php checked( 'default', $options['subcategory_other_pages_canonical'] ?? 'default' ); ?>>
							<?php esc_html_e( 'Points to itself (each page has its own canonical)', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_global_category_defaults[subcategory_other_pages_canonical]" value="first_page" <?php checked( 'first_page', $options['subcategory_other_pages_canonical'] ?? '' ); ?>>
							<?php esc_html_e( 'Points to first page (all pages use subcategory base URL)', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Renders the "Order By" settings section in the admin interface.
	 *
	 * This function is responsible for rendering the HTML form elements that allow the
	 * user to configure the "Order By" settings for the category indexer plugin.
	 * It includes options to set the "noindex" and "nofollow" attributes for the
	 * "Order By" filter.
	 */
	public function render_orderby_section() {
		$options = get_option( 'category_indexer_option_orderby' );
		?>
		<div class="ci-settings-card">
			<div class="ci-card-header">
				<h3><?php esc_html_e( 'WooCommerce Order By Filter Settings', 'category-indexer-for-woocommerce' ); ?></h3>
			</div>
			<div class="ci-card-body">
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'Filter "Order By" Settings', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-checkbox-group">
						<label>
							<input type="checkbox" name="category_indexer_option_orderby[noindex]" value="noindex" <?php checked( 'noindex', $options['noindex'] ?? '' ); ?>>
							<?php esc_html_e( 'Noindex', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="checkbox" name="category_indexer_option_orderby[nofollow]" value="nofollow" <?php checked( 'nofollow', $options['nofollow'] ?? '' ); ?>>
							<?php esc_html_e( 'Nofollow', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Renders the URL with parameters section of the admin settings page.
	 *
	 * This function is responsible for rendering the HTML for the URL with parameters section of the admin settings page.
	 * It displays options for controlling the indexing and following of URLs with parameters.
	 */
	public function render_url_with_parameters() {
		$options = get_option( 'category_indexer_option_url_parameters' );
		?>
		<div class="ci-settings-card">
			<div class="ci-card-header">
				<h3><?php esc_html_e( 'URL with Parameters', 'category-indexer-for-woocommerce' ); ?></h3>
			</div>
			<div class="ci-card-body">
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'URL with Parameters', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-two-column-radios">
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_option_url_parameters[index]" value="index" <?php checked( 'index', $options['index'] ?? '' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_url_parameters[index]" value="noindex" <?php checked( 'noindex', $options['index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_option_url_parameters[follow]" value="follow" <?php checked( 'follow', $options['follow'] ?? '' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_url_parameters[follow]" value="nofollow" <?php checked( 'nofollow', $options['follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the search section of the admin settings page.
	 *
	 * This function is responsible for rendering the HTML for the search pages settings section.
	 * It displays options for controlling the meta robots tag and canonical tag for site search results.
	 */
	public function render_search_section() {
		$options = get_option( 'category_indexer_option_search' );
		?>
		<div class="ci-settings-card">
			<div class="ci-card-header">
				<h3><?php esc_html_e( 'Site Search Settings', 'category-indexer-for-woocommerce' ); ?></h3>
			</div>
			<div class="ci-card-body">
				<!-- Meta Robots Tag -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'Meta Robots Tag', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-two-column-radios">
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_option_search[index]" value="index" <?php checked( 'index', $options['index'] ?? '' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_search[index]" value="noindex" <?php checked( 'noindex', $options['index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
						<div class="ci-radio-column">
							<label>
								<input type="radio" name="category_indexer_option_search[follow]" value="follow" <?php checked( 'follow', $options['follow'] ?? '' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_search[follow]" value="nofollow" <?php checked( 'nofollow', $options['follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="ci-divider"></div>

				<!-- Canonical Tag -->
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'Canonical Tag', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-checkbox-group">
						<label>
							<input type="checkbox" name="category_indexer_option_search[canonical_to_homepage]" value="yes" <?php checked( 'yes', $options['canonical_to_homepage'] ?? '' ); ?>>
							<?php esc_html_e( 'Set canonical tag to homepage', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
					<p class="description">
						<?php esc_html_e( 'When enabled, all search result pages will have a canonical tag pointing to the site homepage.', 'category-indexer-for-woocommerce' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the pagination SEO settings section.
	 *
	 * This function is responsible for rendering the HTML for the pagination SEO settings section.
	 * It displays options for controlling the /page/1/ URL behavior for SEO optimization.
	 */
	public function render_pagination_section() {
		$options = get_option( 'category_indexer_option_pagination' );
		?>
		<div class="ci-settings-card">
			<div class="ci-card-header">
				<h3><?php esc_html_e( 'Pagination SEO Settings', 'category-indexer-for-woocommerce' ); ?></h3>
			</div>
			<div class="ci-card-body">
				<div class="ci-setting-row">
					<label class="ci-setting-label"><?php esc_html_e( 'Remove /page/1/ from URLs', 'category-indexer-for-woocommerce' ); ?></label>
					<div class="ci-checkbox-group">
						<label>
							<input type="checkbox" name="category_indexer_option_pagination[remove_page_one]" value="yes" <?php checked( 'yes', $options['remove_page_one'] ?? '' ); ?>>
							<?php esc_html_e( 'Remove /page/1/ from pagination links and URLs', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
					<p class="description">
						<?php esc_html_e( 'When enabled, the first page of categories and shop will use clean URLs without /page/1/ suffix. This prevents duplicate content issues and is better for SEO. The URL will be changed in the browser without a 301 redirect.', 'category-indexer-for-woocommerce' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the category section of the admin settings page.
	 *
	 * This function is responsible for rendering the HTML for the category section of the admin settings page.
	 * It displays the category name, whether it is a subcategory, and options for controlling the indexing and following of the category pages.
	 *
	 * @param WP_Term $category The category object for which to render the settings.
	 * @param array   $options Category options array (passed from parent to avoid repeated DB calls).
	 * @param array   $parents_map Map of parent categories (passed from parent to avoid repeated DB calls).
	 */
	public function render_category_section( $category, $options = array(), $parents_map = array() ) {
		++$this->counter;

		// Use passed options or fetch them if not provided (backwards compatibility)
		if ( empty( $options ) ) {
			$options = get_option( 'category_indexer_category_options' );
		}

		$is_subcategory  = ( $category->parent !== 0 );
		$parent_category = null;

		if ( $is_subcategory ) {
			// Use parents map if available, otherwise fetch from DB
			if ( isset( $parents_map[ $category->term_id ] ) ) {
				$parent_category = $parents_map[ $category->term_id ];
			} else {
				$parent_category = get_term( $category->parent, 'product_cat' );
			}
		}

		if ( $this->category_section_title === true ) {
			$this->category_section_title = false;
			echo '<div class="ci-toolbar">';
			echo '<h2>' . esc_html__( 'Category Archive Settings', 'category-indexer-for-woocommerce' ) . '</h2>';

			echo '<div class="ci-toolbar-controls">';
			// Per page setting
			$per_page_setting = get_option( 'category_indexer_per_page', 20 );
			echo '<label for="categories-per-page">' . esc_html__( 'Categories per page:', 'category-indexer-for-woocommerce' ) . '</label>';
			echo '<select id="categories-per-page" class="small-text">';
			$per_page_options = array( 10, 20, 30, 50, 100 );
			foreach ( $per_page_options as $option ) {
				$selected = selected( $per_page_setting, $option, false );
				echo '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $option ) . '</option>';
			}
			echo '</select>';

			echo '<button type="button" id="reset-category-settings" class="button button-secondary">' . esc_html__( 'Reset All Categories to Default', 'category-indexer-for-woocommerce' ) . '</button>';
			echo '<button type="button" id="clear-category-cache" class="button button-secondary">' . esc_html__( 'Clear Cache', 'category-indexer-for-woocommerce' ) . '</button>';
			echo '</div>';
			echo '</div>';
		}
		?>

		<div class="ci-category-settings">
			<h3 class='category-section-title'>
				<?php
				echo esc_html( $category->name );
				esc_html_e( ' Pages', 'category-indexer-for-woocommerce' );
				?>
				<?php if ( $is_subcategory ) : ?>
					<span>
						<?php
						esc_html_e( ' - Subcategory of ', 'category-indexer-for-woocommerce' );
						echo ( esc_html( $parent_category->name ) );
						?>
					</span>
				<?php else : ?>
					<span><?php esc_html_e( ' - Category', 'category-indexer-for-woocommerce' ); ?></span>
				<?php endif; ?>
			</h3>

			<!-- Override Global Settings Checkbox -->
			<div class="ci-setting-row ci-override-checkbox">
				<label class="ci-override-label">
					<input
						type="checkbox"
						name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][use_custom_settings]"
						value="yes"
						class="ci-use-custom-settings-checkbox"
						data-category-id="<?php echo esc_attr( $category->term_id ); ?>"
						<?php checked( 'yes', $options[ $category->term_id ]['use_custom_settings'] ?? '' ); ?>
					>
					<span class="ci-override-text"><?php esc_html_e( 'Override Global Settings (use custom settings for this category)', 'category-indexer-for-woocommerce' ); ?></span>
				</label>
			</div>

			<div class="ci-divider"></div>

			<!-- First Page Settings -->
			<div class="ci-setting-row">
				<label class="ci-setting-label"><?php esc_html_e( 'First Page', 'category-indexer-for-woocommerce' ); ?></label>
				<div class="ci-two-column-radios">
					<div class="ci-radio-column">
						<label>
							<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][first_page_index]" value="index" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'index', $options[ $category->term_id ]['first_page_index'] ?? 'index' ); ?>>
							<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][first_page_index]" value="noindex" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'noindex', $options[ $category->term_id ]['first_page_index'] ?? '' ); ?>>
							<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
					<div class="ci-radio-column">
						<label>
							<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][first_page_follow]" value="follow" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'follow', $options[ $category->term_id ]['first_page_follow'] ?? 'follow' ); ?>>
							<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][first_page_follow]" value="nofollow" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'nofollow', $options[ $category->term_id ]['first_page_follow'] ?? '' ); ?>>
							<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
				</div>
			</div>

			<div class="ci-divider"></div>

			<!-- All Other Pages Settings -->
			<div class="ci-setting-row">
				<label class="ci-setting-label"><?php esc_html_e( 'All Other Pages', 'category-indexer-for-woocommerce' ); ?></label>
				<div class="ci-two-column-radios">
					<div class="ci-radio-column">
						<label>
							<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][all_other_pages_index]" value="index" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'index', $options[ $category->term_id ]['all_other_pages_index'] ?? 'index' ); ?>>
							<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][all_other_pages_index]" value="noindex" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'noindex', $options[ $category->term_id ]['all_other_pages_index'] ?? '' ); ?>>
							<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
					<div class="ci-radio-column">
						<label>
							<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][all_other_pages_follow]" value="follow" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'follow', $options[ $category->term_id ]['all_other_pages_follow'] ?? 'follow' ); ?>>
							<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][all_other_pages_follow]" value="nofollow" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'nofollow', $options[ $category->term_id ]['all_other_pages_follow'] ?? '' ); ?>>
							<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</div>
				</div>
			</div>

			<div class="ci-divider"></div>

			<!-- Canonical Tag Settings -->
			<div class="ci-setting-row">
				<label class="ci-setting-label"><?php esc_html_e( 'Canonical Tag for Pages After First', 'category-indexer-for-woocommerce' ); ?></label>
				<div class="ci-radio-group">
					<label>
						<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][canonical_all_other_pages]" value="default" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'default', $options[ $category->term_id ]['canonical_all_other_pages'] ?? 'default' ); ?>>
						<?php esc_html_e( 'Default (No Change)', 'category-indexer-for-woocommerce' ); ?>
					</label>
					<label>
						<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][canonical_all_other_pages]" value="from_first_page" data-category-id="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( 'from_first_page', $options[ $category->term_id ]['canonical_all_other_pages'] ?? '' ); ?>>
						<?php esc_html_e( 'First page as a canonical link', 'category-indexer-for-woocommerce' ); ?>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Shows admin notices for cache operations.
	 *
	 * This function displays success or error notices after cache operations
	 * like clearing the cache or resetting settings.
	 */
	public function show_admin_notices() {
		// Only show on our plugin's admin page
		if ( ! isset( $_GET['page'] ) || sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'wc-category-indexer' ) {
			return;
		}

		// Show cache cleared notice
		if ( isset( $_GET['cache_cleared'] ) && sanitize_text_field( wp_unslash( $_GET['cache_cleared'] ) ) === '1' ) {
			// Verify nonce for security
			if ( ! isset( $_GET['cache_cleared_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['cache_cleared_nonce'] ) ), 'clear_category_cache_nonce' ) ) {
				return;
			}

			echo '<div class="notice notice-success is-dismissible">';
			echo '<p><strong>' . esc_html__( 'Success!', 'category-indexer-for-woocommerce' ) . '</strong> ';
			echo esc_html__( 'Category cache has been cleared and rebuilt with fresh data.', 'category-indexer-for-woocommerce' );
			echo '</p>';
			echo '</div>';
		}
	}

	/**
	 * AJAX handler for resetting all category settings to default.
	 *
	 * This function handles the AJAX request to reset all category settings.
	 * It verifies the nonce for security, deletes the category options, and returns a JSON response.
	 */
	public function ajax_reset_category_settings() {
		// Check nonce for security
		check_ajax_referer( 'reset_category_settings_nonce', 'nonce' );

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'category-indexer-for-woocommerce' ),
				)
			);
		}

		// Delete the category options
		$deleted = delete_option( 'category_indexer_category_options' );

		// Clear cache after resetting settings
		if ( class_exists( 'Category_Indexer_Cache' ) ) {
			Category_Indexer_Cache::clear_cache();
		}

		if ( $deleted ) {
			wp_send_json_success(
				array(
					'message' => __( 'All category settings have been reset to default.', 'category-indexer-for-woocommerce' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to reset category settings. They may already be at default values.', 'category-indexer-for-woocommerce' ),
				)
			);
		}
	}

	/**
	 * AJAX handler for clearing the category cache.
	 *
	 * This function handles the AJAX request to clear all category cache.
	 * It verifies the nonce for security, clears the cache, and returns a JSON response.
	 */
	public function ajax_clear_category_cache() {
		// Check nonce for security
		check_ajax_referer( 'clear_category_cache_nonce', 'nonce' );

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'category-indexer-for-woocommerce' ),
				)
			);
		}

		// Clear the cache
		if ( class_exists( 'Category_Indexer_Cache' ) ) {
			$cleared = Category_Indexer_Cache::clear_cache();

			if ( $cleared ) {
				wp_send_json_success(
					array(
						'message' => __( 'Category cache has been cleared successfully.', 'category-indexer-for-woocommerce' ),
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => __( 'Failed to clear cache. It may already be empty.', 'category-indexer-for-woocommerce' ),
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Cache class not found.', 'category-indexer-for-woocommerce' ),
				)
			);
		}
	}

	/**
	 * AJAX handler for updating categories per page setting.
	 *
	 * This function handles the AJAX request to update the number of categories
	 * displayed per page in the admin interface.
	 */
	public function ajax_update_categories_per_page() {
		// Check nonce for security
		check_ajax_referer( 'category_indexer_nonce', 'nonce' );

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'category-indexer-for-woocommerce' ),
				)
			);
		}

		// Get and validate the per_page value
		$per_page = isset( $_POST['per_page'] ) ? intval( wp_unslash( $_POST['per_page'] ) ) : 20;

		// Ensure the value is one of the allowed options
		$allowed_values = array( 10, 20, 30, 50, 100 );
		if ( ! in_array( $per_page, $allowed_values, true ) ) {
			$per_page = 20; // Default to 20 if invalid value
		}

		// Update the option
		update_option( 'category_indexer_per_page', $per_page );

		wp_send_json_success(
			array(
				'message' => __( 'Categories per page setting updated successfully.', 'category-indexer-for-woocommerce' ),
			)
		);
	}
}
