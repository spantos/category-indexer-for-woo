<?php
/**
 * Plugin Name: Category Indexer for WooCommerce
 *
 * @package PluginPackage
 * Plugin URI:
 * Description: Display and manage meta robots index and canonical options for WooCommerce categories and subcategories.
 * Requires at least: 6.5
 * Requires PHP: 7.0
 * Version: 2.0.0
 * Author: Slobodan Pantovic
 * Requires Plugins: woocommerce
 * Author URI:
 * Text Domain: category-indexer-for-woocommerce
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
defined( 'CATEGORY_INDEXER_VERSION' ) || define( 'CATEGORY_INDEXER_VERSION', '2.0.0' );
defined( 'CATEGORY_INDEXER_PLUGIN_FILE' ) || define( 'CATEGORY_INDEXER_PLUGIN_FILE', basename( __FILE__ ) );
defined( 'CATEGORY_INDEXER_PLUGIN_DIR' ) || define( 'CATEGORY_INDEXER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
defined( 'CATEGORY_INDEXER_PLUGIN_URL' ) || define( 'CATEGORY_INDEXER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
defined( 'CATEGORY_INDEXER_TEXT_DOMAIN' ) || define( 'CATEGORY_INDEXER_TEXT_DOMAIN', 'category-indexer-for-woocommerce' );

/**
 * Includes the Category_Indexer_For_Woo_Admin class and registers the plugin activation hook.
 *
 * This code ensures that the Category_Indexer_For_Woo_Admin class is loaded when the plugin
 * is used on the admin pages of the website. It is responsible for initializing and
 * managing the admin-specific functionality of the Category Indexer for WooCommerce plugin.
 *
 * The `plugin_activation_check` method of the Category_Indexer_For_Woo_Admin class is
 * registered as a hook for the plugin activation event, allowing the plugin to perform
 * any necessary setup or checks during activation.
 *
 * @since 1.0.0
 * @package PluginPackage
 */
if ( is_admin() && file_exists( CATEGORY_INDEXER_PLUGIN_DIR . 'includes/admin/class-category-indexer-for-woo-admin.php' ) ) {
require_once CATEGORY_INDEXER_PLUGIN_DIR . 'includes/admin/class-category-indexer-for-woo-admin.php';
register_activation_hook( __FILE__, array( 'Category_Indexer_For_Woo_Admin', 'plugin_activation_check' ) );
}


/**
 * Includes the Category_Indexer_For_Woo_Frontend class, which provides the core functionality
 * for the Category Indexer for WooCommerce plugin.
 *
 * This code ensures that the Category_Indexer_For_Woo_Frontend class is loaded when the plugin
 * is used on the front-end (non-admin) pages of the website. It is responsible
 * for initializing and managing the category indexing functionality.
 *
 * @since 1.0.0
 * @package PluginPackage
 */
if ( ! is_admin() && file_exists( CATEGORY_INDEXER_PLUGIN_DIR . 'includes/class-category-indexer-for-woo-frontend.php' ) ) {
require_once CATEGORY_INDEXER_PLUGIN_DIR . 'includes/class-category-indexer-for-woo-frontend.php';
}


/**
 * Initializes the Category Indexer for WooCommerce plugin.
 *
 * This function checks if the current context is the admin area, and creates an instance of the
 * Category_Indexer_For_Woo_Admin class if so. Otherwise, it creates an instance of the Category_Indexer_For_Woo_Frontend
 * class to handle the front-end functionality of the plugin.
 *
 * The `category_indexer_init` function is hooked to the `plugins_loaded` action, ensuring that
 * the plugin is properly initialized when WordPress loads the plugins.
 *
 * @since 1.0.0
 * @return void
 */
if ( ! function_exists( 'category_indexer_init' ) ) {
    
    function category_indexer_init() {
        if ( is_admin() && class_exists( 'Category_Indexer_For_Woo_Admin' ) ) {
                new Category_Indexer_For_Woo_Admin ();
            } elseif ( ! is_admin() && class_exists( 'Category_Indexer_For_Woo_Frontend' ) ) {
                new Category_Indexer_For_Woo_Frontend ();        
        } else {
            return;
        }
    } 
}
add_action( 'plugins_loaded', 'category_indexer_init' );
