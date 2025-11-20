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
	private $cached_categories      = null; // Cache for categories within request

	/**
	 * Initializes the Category_Indexer_For_Woo_Admin class.
	 *
	 * Hooks into the following WordPress actions:
	 * - `admin_enqueue_scripts` - Enqueues the necessary scripts for the admin area.
	 * - `admin_menu` - Adds the "Category Indexer" menu page to the WordPress admin menu.
	 * - `admin_init` - Registers the settings for the plugin.
	 * - Category change hooks - Clears cache when categories are created/edited/deleted.
	 */
	public function __construct() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Cache invalidation hooks
		add_action( 'create_product_cat', array( $this, 'clear_categories_cache' ) );
		add_action( 'edit_product_cat', array( $this, 'clear_categories_cache' ) );
		add_action( 'delete_product_cat', array( $this, 'clear_categories_cache' ) );

		// Handle manual cache clear
		add_action( 'admin_post_cifw_clear_cache', array( $this, 'handle_manual_cache_clear' ) );
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

		// Register settings for the General tab (shop, orderby, url parameters, search)
		register_setting( 'category_indexer_general_options', 'category_indexer_option_shop', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_option_shop_canonical', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_option_orderby', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_option_url_parameters', $register_setting_args );
		register_setting( 'category_indexer_general_options', 'category_indexer_option_search', $register_setting_args );

		// Register settings for the Categories tab
		register_setting( 'category_indexer_category_options_group', 'category_indexer_category_options', $register_setting_args );
	}

	/**
	 * Gets categories with caching support.
	 *
	 * First checks in-memory cache, then WordPress transient cache,
	 * and finally fetches from database if needed.
	 *
	 * @return array Array of category term objects.
	 */
	private function get_cached_categories() {
		// Check in-memory cache first
		if ( $this->cached_categories !== null ) {
			return $this->cached_categories;
		}

		// Check transient cache
		$cache_key = 'cifw_product_categories';
		$categories = get_transient( $cache_key );

		if ( false === $categories ) {
			// Fetch from database
			$categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
				)
			);

			// Store in transient for 12 hours
			set_transient( $cache_key, $categories, 12 * HOUR_IN_SECONDS );
		}

		// Store in-memory for this request
		$this->cached_categories = $categories;

		return $categories;
	}

	/**
	 * Clears the categories cache.
	 *
	 * Called automatically when categories are created, edited, or deleted.
	 */
	public function clear_categories_cache() {
		delete_transient( 'cifw_product_categories' );
		$this->cached_categories = null;
	}

	/**
	 * Handles manual cache clear request from admin.
	 */
	public function handle_manual_cache_clear() {
		// Verify nonce
		if ( ! isset( $_POST['cifw_clear_cache_nonce'] ) || ! wp_verify_nonce( $_POST['cifw_clear_cache_nonce'], 'cifw_clear_cache_action' ) ) {
			wp_die( esc_html__( 'Security check failed', 'category-indexer-for-woocommerce' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action', 'category-indexer-for-woocommerce' ) );
		}

		// Clear the cache
		$this->clear_categories_cache();

		// Redirect back with success message
		$redirect_url = add_query_arg(
			array(
				'page' => 'wc-category-indexer',
				'tab'  => 'categories',
				'cache_cleared' => '1',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
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
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Category Indexer for WooCommerce', 'category-indexer-for-woocommerce' ) . '</h1>';

		// Render tabs
		echo '<h2 class="nav-tab-wrapper">';
		echo '<a href="?page=wc-category-indexer&tab=general" class="nav-tab ' . ( $active_tab === 'general' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'General', 'category-indexer-for-woocommerce' ) . '</a>';
		echo '<a href="?page=wc-category-indexer&tab=categories" class="nav-tab ' . ( $active_tab === 'categories' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Categories', 'category-indexer-for-woocommerce' ) . '</a>';
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
		$this->render_shop_section_content();
		$this->render_orderby_section();
		$this->render_url_with_parameters();
		$this->render_search_section();
	}

	/**
	 * Renders the content for the Categories tab with pagination and caching.
	 */
	public function render_categories_tab_content() {
		// Get cached categories (only 1 database query)
		$categories = $this->get_cached_categories();

		if ( empty( $categories ) ) {
			echo '<p>' . esc_html__( 'No categories found.', 'category-indexer-for-woocommerce' ) . '</p>';
			return;
		}

		$total_categories = count( $categories );
		$per_page = 50; // Show 50 categories per page
		$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$total_pages = ceil( $total_categories / $per_page );
		$offset = ( $current_page - 1 ) * $per_page;

		// Get categories for current page
		$paged_categories = array_slice( $categories, $offset, $per_page );

		// Display cache clear button and info
		$this->render_cache_info_section( $total_categories );

		// Display pagination above categories if needed
		if ( $total_pages > 1 ) {
			$this->render_pagination( $current_page, $total_pages );
		}

		// Render categories for current page
		foreach ( $paged_categories as $category ) {
			$this->render_category_section( $category );
		}

		// Display pagination below categories if needed
		if ( $total_pages > 1 ) {
			$this->render_pagination( $current_page, $total_pages );
		}
	}

	/**
	 * Renders the cache information section with clear cache button.
	 *
	 * @param int $total_categories Total number of categories.
	 */
	private function render_cache_info_section( $total_categories ) {
		// Show success message if cache was cleared
		if ( isset( $_GET['cache_cleared'] ) && $_GET['cache_cleared'] === '1' ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cache cleared successfully!', 'category-indexer-for-woocommerce' ) . '</p></div>';
		}

		echo '<div style="background: #f0f0f1; padding: 15px; margin: 20px 0; border-left: 4px solid #2271b1;">';
		echo '<p style="margin: 0 0 10px 0;"><strong>' . esc_html__( 'Categories Cache', 'category-indexer-for-woocommerce' ) . '</strong></p>';
		echo '<p style="margin: 0 0 10px 0;">';
		printf(
			esc_html__( 'Total categories: %d | Categories are cached for 12 hours for better performance.', 'category-indexer-for-woocommerce' ),
			$total_categories
		);
		echo '</p>';

		// Manual cache clear button
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display: inline;">';
		echo '<input type="hidden" name="action" value="cifw_clear_cache">';
		wp_nonce_field( 'cifw_clear_cache_action', 'cifw_clear_cache_nonce' );
		echo '<button type="submit" class="button button-secondary">' . esc_html__( 'Clear Categories Cache', 'category-indexer-for-woocommerce' ) . '</button>';
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Renders pagination controls.
	 *
	 * @param int $current_page Current page number.
	 * @param int $total_pages Total number of pages.
	 */
	private function render_pagination( $current_page, $total_pages ) {
		echo '<div class="tablenav"><div class="tablenav-pages">';
		echo '<span class="displaying-num">' . sprintf( esc_html__( 'Page %d of %d', 'category-indexer-for-woocommerce' ), $current_page, $total_pages ) . '</span>';

		$page_links = array();

		// First page link
		if ( $current_page > 1 ) {
			$page_links[] = sprintf(
				'<a class="button" href="%s">&laquo; %s</a>',
				esc_url( add_query_arg( array( 'page' => 'wc-category-indexer', 'tab' => 'categories', 'paged' => 1 ), admin_url( 'admin.php' ) ) ),
				esc_html__( 'First', 'category-indexer-for-woocommerce' )
			);
		}

		// Previous page link
		if ( $current_page > 1 ) {
			$page_links[] = sprintf(
				'<a class="button" href="%s">&lsaquo; %s</a>',
				esc_url( add_query_arg( array( 'page' => 'wc-category-indexer', 'tab' => 'categories', 'paged' => $current_page - 1 ), admin_url( 'admin.php' ) ) ),
				esc_html__( 'Previous', 'category-indexer-for-woocommerce' )
			);
		}

		// Page numbers
		$range = 3;
		for ( $i = max( 1, $current_page - $range ); $i <= min( $total_pages, $current_page + $range ); $i++ ) {
			if ( $i === $current_page ) {
				$page_links[] = '<span class="button disabled">' . $i . '</span>';
			} else {
				$page_links[] = sprintf(
					'<a class="button" href="%s">%d</a>',
					esc_url( add_query_arg( array( 'page' => 'wc-category-indexer', 'tab' => 'categories', 'paged' => $i ), admin_url( 'admin.php' ) ) ),
					$i
				);
			}
		}

		// Next page link
		if ( $current_page < $total_pages ) {
			$page_links[] = sprintf(
				'<a class="button" href="%s">%s &rsaquo;</a>',
				esc_url( add_query_arg( array( 'page' => 'wc-category-indexer', 'tab' => 'categories', 'paged' => $current_page + 1 ), admin_url( 'admin.php' ) ) ),
				esc_html__( 'Next', 'category-indexer-for-woocommerce' )
			);
		}

		// Last page link
		if ( $current_page < $total_pages ) {
			$page_links[] = sprintf(
				'<a class="button" href="%s">%s &raquo;</a>',
				esc_url( add_query_arg( array( 'page' => 'wc-category-indexer', 'tab' => 'categories', 'paged' => $total_pages ), admin_url( 'admin.php' ) ) ),
				esc_html__( 'Last', 'category-indexer-for-woocommerce' )
			);
		}

		echo '<span class="pagination-links">' . implode( ' ', $page_links ) . '</span>';
		echo '</div></div>';
	}

	/**
	 * Enqueues the admin JavaScript and CSS file for the Category Indexer for WooCommerce plugin.
	 *
	 * This function is responsible for enqueuing the admin.js JavaScript file, which is used to provide
	 * additional functionality for the Category Indexer for WooCommerce plugin's admin page.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wc-category-indexer-admin', CATEGORY_INDEXER_PLUGIN_URL . 'assests/js/admin.js', array(), false, true );
		wp_enqueue_style( 'wc-category-indexer-admin-css', CATEGORY_INDEXER_PLUGIN_URL . 'assests/css/admin.css', array(), false, 'all' );
	}

	/**
	 * Checks if the required plugins are installed and activated before activating the Category Indexer for WooCommerce plugin.
	 *
	 * This function checks if the WooCommerce plugin and either the Rank Math SEO or Yoast SEO plugin are installed and activated.
	 * If either of these required plugins are not installed and activated, the Category Indexer for WooCommerce plugin is deactivated and a message is displayed to the user.
	 * It also checks if the PHP version is at least 7.0, and deactivates the plugin if the requirement is not met.
	 */
	
	 public static function plugin_activation_check() {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			deactivate_plugins( CATEGORY_INDEXER_PLUGIN_FILE );
			wp_die(
				esc_html__( 'The plugin requires the WooCommerce plugin to be installed and activated', 'category-indexer-for-woocommerce' ),
				'',
				array( 'back_link' => true )
			);
		}

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
		$options = get_option( 'category_indexer_option_shop' );
		?>
		<h2><?php esc_html_e( 'Shop Pages Settings', 'category-indexer-for-woocommerce' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'First Page', 'category-indexer-for-woocommerce' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="category_indexer_option_shop[shop_first_page_index]" value="index" <?php checked( 'index', $options['shop_first_page_index'] ?? '' ); ?>>
							<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_option_shop[shop_first_page_index]" value="noindex" <?php checked( 'noindex', $options['shop_first_page_index'] ?? '' ); ?>>
							<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<br>
						<label>
							<input type="radio" name="category_indexer_option_shop[shop_first_page_follow]" value="follow" <?php checked( 'follow', $options['shop_first_page_follow'] ?? '' ); ?>>
							<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_option_shop[shop_first_page_follow]" value="nofollow" <?php checked( 'nofollow', $options['shop_first_page_follow'] ?? '' ); ?>>
							<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'All Other Pages', 'category-indexer-for-woocommerce' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="category_indexer_option_shop[shop_all_other_page_index]" value="index" <?php checked( 'index', $options['shop_all_other_page_index'] ?? '' ); ?>>
							<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_option_shop[shop_all_other_page_index]" value="noindex" <?php checked( 'noindex', $options['shop_all_other_page_index'] ?? '' ); ?>>
							<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<br>
						<label>
							<input type="radio" name="category_indexer_option_shop[shop_all_other_page_follow]" value="follow" <?php checked( 'follow', $options['shop_all_other_page_follow'] ?? '' ); ?>>
							<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_option_shop[shop_all_other_page_follow]" value="nofollow" <?php checked( 'nofollow', $options['shop_all_other_page_follow'] ?? '' ); ?>>
							<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<?php
				$options = get_option( 'category_indexer_option_shop_canonical' );
				?>
				<th><?php esc_html_e( 'Canonical tag for pages after the first page', 'category-indexer-for-woocommerce' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="category_indexer_option_shop_canonical[pages_after_first]" value="default" <?php checked( 'default', $options['pages_after_first'] ?? '' ); ?>>
							<?php esc_html_e( 'Default (No Change)', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="category_indexer_option_shop_canonical[pages_after_first]" value="first_page" <?php checked( 'first_page', $options['pages_after_first'] ?? '' ); ?>>
							<?php esc_html_e( 'First page as a canonical link', 'category-indexer-for-woocommerce' ); ?>
						</label>
						<br>

					</fieldset>
				</td>
			</tr>
		</table>
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
			<h2><?php esc_html_e( 'WooCommerce Order By Filter Settings', 'category-indexer-for-woocommerce' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Filter "Order By" Settings', 'category-indexer-for-woocommerce' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="category_indexer_option_orderby[noindex]" value="noindex" <?php checked( 'noindex', $options['noindex'] ?? '' ); ?>>
								<?php esc_html_e( 'Noindex', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="category_indexer_option_orderby[nofollow]" value="nofollow" <?php checked( 'nofollow', $options['nofollow'] ?? '' ); ?>>
								<?php esc_html_e( 'Nofollow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
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
			<h2><?php esc_html_e( 'URL with Parameters', 'category-indexer-for-woocommerce' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'URL with Parameters', 'category-indexer-for-woocommerce' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="category_indexer_option_url_parameters[index]" value="index" <?php checked( 'index', $options['index'] ?? '' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_url_parameters[index]" value="noindex" <?php checked( 'noindex', $options['index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="category_indexer_option_url_parameters[follow]" value="follow" <?php checked( 'follow', $options['follow'] ?? '' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_url_parameters[follow]" value="nofollow" <?php checked( 'nofollow', $options['follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>

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
			<h2><?php esc_html_e( 'Site Search Settings', 'category-indexer-for-woocommerce' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Meta Robots Tag', 'category-indexer-for-woocommerce' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="category_indexer_option_search[index]" value="index" <?php checked( 'index', $options['index'] ?? '' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_search[index]" value="noindex" <?php checked( 'noindex', $options['index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="category_indexer_option_search[follow]" value="follow" <?php checked( 'follow', $options['follow'] ?? '' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_option_search[follow]" value="nofollow" <?php checked( 'nofollow', $options['follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Canonical Tag', 'category-indexer-for-woocommerce' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="category_indexer_option_search[canonical_to_homepage]" value="yes" <?php checked( 'yes', $options['canonical_to_homepage'] ?? '' ); ?>>
								<?php esc_html_e( 'Set canonical tag to homepage', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, all search result pages will have a canonical tag pointing to the site homepage.', 'category-indexer-for-woocommerce' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			</table>

		<?php
	}

	/**
	 * Renders the category section of the admin settings page.
	 *
	 * This function is responsible for rendering the HTML for the category section of the admin settings page.
	 * It displays the category name, whether it is a subcategory, and options for controlling the indexing and following of the category pages.
	 *
	 * @param WP_Term $category The category object for which to render the settings.
	 */
	public function render_category_section( $category ) {
		++$this->counter;
		$options         = get_option( 'category_indexer_category_options' );
		$is_subcategory  = ( $category->parent !== 0 );
		$parent_category = null;

		if ( $is_subcategory ) {
			$parent_category = get_term( $category->parent, 'product_cat' );
		}
		if ( $this->category_section_title === true ) {
			$this->category_section_title = false;
			echo '<h2>' . esc_html__( 'Category Archive Settings', 'category-indexer-for-woocommerce' ) . '</h2>';
		}
		?>

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
				</span></h3>
			
			<?php else : ?>
				<span><?php esc_html_e( ' - Category', 'category-indexer-for-woocommerce' ); ?></span></h3>
			<?php endif; ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'First Page', 'category-indexer-for-woocommerce' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][first_page_index]" value="index" <?php checked( 'index', $options[ $category->term_id ]['first_page_index'] ?? 'index' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][first_page_index]" value="noindex" <?php checked( 'noindex', $options[ $category->term_id ]['first_page_index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][first_page_follow]" value="follow" <?php checked( 'follow', $options[ $category->term_id ]['first_page_follow'] ?? 'follow' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][first_page_follow]" value="nofollow" <?php checked( 'nofollow', $options[ $category->term_id ]['first_page_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'All Other Pages', 'category-indexer-for-woocommerce' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][all_other_pages_index]" value="index" <?php checked( 'index', $options[ $category->term_id ]['all_other_pages_index'] ?? 'index' ); ?>>
								<?php esc_html_e( 'Index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][all_other_pages_index]" value="noindex" <?php checked( 'noindex', $options[ $category->term_id ]['all_other_pages_index'] ?? '' ); ?>>
								<?php esc_html_e( 'No index', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][all_other_pages_follow]" value="follow" <?php checked( 'follow', $options[ $category->term_id ]['all_other_pages_follow'] ?? 'follow' ); ?>>
								<?php esc_html_e( 'Follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][all_other_pages_follow]" value="nofollow" <?php checked( 'nofollow', $options[ $category->term_id ]['all_other_pages_follow'] ?? '' ); ?>>
								<?php esc_html_e( 'No follow', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>

			<h2></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'All Other Pages Canonical Tag', 'category-indexer-for-woocommerce' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][canonical_all_other_pages]" value="default" <?php checked( 'default', $options[ $category->term_id ]['default'] ?? 'default' ); ?>>
									<?php esc_html_e( 'Default', 'category-indexer-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio" name="category_indexer_category_options[<?php echo esc_attr( $category->term_id ); ?>][canonical_all_other_pages]" value="from_first_page" <?php checked( 'from_first_page', $options[ $category->term_id ]['canonical_all_other_pages'] ?? '' ); ?>>
									<?php esc_html_e( 'First page as a canonical link', 'category-indexer-for-woocommerce' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
		<?php
	}
}
