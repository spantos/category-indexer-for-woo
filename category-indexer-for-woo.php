<?php
/**
 * Plugin Name: Category Indexer for WooCommerce
 *
 * @package PluginPackage
 * Plugin URI:
 * Description: Display and manage meta robots index and canonical options for WooCommerce categories and subcategories.
 * Version: 1.0.0
 * Author URI:
 * Text Domain: category-indexer-for-woo
 * Domain Path: /languages
 * License: GPL2
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Defines constants for the Category Indexer for WooCommerce plugin.
 *
 * These constants are used throughout the plugin to provide version information,
 * file paths, and the text domain for internationalization.
 */
defined( 'CATEGORY_INDEXER_VERSION' ) || define( 'CATEGORY_INDEXER_VERSION', '1.0.0' );
defined( 'CATEGORY_INDEXER_PLUGIN_FILE' ) || define( 'CATEGORY_INDEXER_PLUGIN_FILE', basename( __FILE__ ) );
defined( 'CATEGORY_INDEXER_PLUGIN_DIR' ) || define( 'CATEGORY_INDEXER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
defined( 'CATEGORY_INDEXER_PLUGIN_URL' ) || define( 'CATEGORY_INDEXER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
defined( 'CATEGORY_INDEXER_TEXT_DOMAIN' ) || define( 'CATEGORY_INDEXER_TEXT_DOMAIN', 'category-indexer-for-woo' );


if ( ! function_exists( 'wc_category_indexer_load_textdomain' ) ) {

 /**
  * Loads the translated strings for the Category Indexer for WooCommerce plugin.
  *
  * This function is responsible for loading the translation files for the plugin,
  * allowing the plugin to be localized and translated into different languages.
  *
  * @since 1.0.0
  * @return void
  */
 function wc_category_indexer_load_textdomain() {
    load_plugin_textdomain( CATEGORY_INDEXER_TEXT_DOMAIN, false, basename( __DIR__ ) . '/languages' );
 }
}
 
add_action( 'plugins_loaded', 'wc_category_indexer_load_textdomain' );


/**
 * Includes the WC_Category_Indexer_Admin class and registers the plugin activation hook.
 *
 * This code ensures that the WC_Category_Indexer_Admin class is loaded when the plugin
 * is used on the admin pages of the website. It is responsible for initializing and
 * managing the admin-specific functionality of the Category Indexer for WooCommerce plugin.
 *
 * The `plugin_activation_check` method of the WC_Category_Indexer_Admin class is
 * registered as a hook for the plugin activation event, allowing the plugin to perform
 * any necessary setup or checks during activation.
 *
 * @since 1.0.0
 * @package PluginPackage
 */
if ( is_admin() ) {
require CATEGORY_INDEXER_PLUGIN_DIR . 'includes/admin/class-wc-category-indexer-admin.php';
register_activation_hook( __FILE__, array( 'WC_Category_Indexer_Admin', 'plugin_activation_check' ) );
}


/**
 * Includes the WC_Category_Indexer class, which provides the core functionality
 * for the Category Indexer for WooCommerce plugin.
 *
 * This code ensures that the WC_Category_Indexer class is loaded when the plugin
 * is used on the front-end (non-admin) pages of the website. It is responsible
 * for initializing and managing the category indexing functionality.
 *
 * @since 1.0.0
 * @package PluginPackage
 */
if ( ! is_admin()){
require_once CATEGORY_INDEXER_PLUGIN_DIR . 'includes/class-wc-category-indexer.php';
}


/**
 * Initializes the Category Indexer for WooCommerce plugin.
 *
 * This function checks if the current context is the admin area, and creates an instance of the
 * WC_Category_Indexer_Admin class if so. Otherwise, it creates an instance of the WC_Category_Indexer
 * class to handle the front-end functionality of the plugin.
 *
 * The `wc_category_indexer_init` function is hooked to the `plugins_loaded` action, ensuring that
 * the plugin is properly initialized when WordPress loads the plugins.
 *
 * @since 1.0.0
 * @return void
 */
if (!function_exists('wc_category_indexer_init')) {
    
    function wc_category_indexer_init() {
        if ( is_admin () ) {
        new WC_Category_Indexer_Admin ();
    } else {
        new WC_Category_Indexer ();
    }
} 
}
add_action( 'plugins_loaded', 'wc_category_indexer_init' );
