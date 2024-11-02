<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Category_Indexer' ) ) {

 /**
  * Manages the indexing and canonical URL settings for WooCommerce shop and product category pages.
  * This class handles the indexing and canonical URL settings for the WooCommerce shop page and product category pages.
  * It integrates with the Rank Math and Yoast SEO plugins to set the appropriate meta robots and canonical URL tags based on the configured options.
  * The class checks if the Rank Math or Yoast SEO plugins are active, and then sets the appropriate filters to modify the robots meta tag and canonical URL.
  * It also handles special cases, such as when the current page has query parameters or when the WooCommerce order by filter is used.
  * The class provides methods to set the meta robots tag and the canonical URL for the current page, based on the configured options for the shop page and product category pages.
  */
 class WC_Category_Indexer {

		private $rank_math_activated = false;
		private $yoast_activated     = false;
		private $request_url         = null;
		private $robots_noindex      = null;


  /**
   * Initializes the WC_Category_Indexer class and sets up the necessary filters for Rank Math and Yoast SEO plugins.
   *
   * This constructor method is responsible for the following tasks:
   * - Checks if the WooCommerce plugin is active, and returns if it is not.
   * - Retrieves the current request URL and stores it in the `$request_url` property.
   * - Checks if the Rank Math SEO plugin is active, and if so, sets up the necessary filters for the Rank Math plugin.
   * - Checks if the Yoast SEO plugin is active, and if so, sets up the necessary filters for the Yoast SEO plugin.
   * - If neither the Rank Math nor Yoast SEO plugins are active, the method returns without further action.
   */
  public function __construct() {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( ! $this->is_woocommerce_active() ) {
				return;
			}
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$this->request_url = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			}
			if ( $this->is_rank_math_active() ) {
				$this->rank_math_activated = true;
				$this->yoast_activated     = false;
				add_filter( 'rank_math/frontend/robots', array( $this, 'set_meta_robots_tag' ) );
				add_filter( 'rank_math/frontend/canonical', array( $this, 'set_canonical_url' ), 1 );
				$this->custom_rank_math_canonical();
			} elseif ( $this->is_yoast_seo_active() ) {
				$this->yoast_activated     = true;
				$this->rank_math_activated = false;
				add_filter( 'wpseo_robots', array( $this, 'set_meta_robots_tag' ) );
				add_filter( 'wpseo_canonical', array( $this, 'set_canonical_url' ), 9999 );
			} else {
				return;
			}
		}

		/**
		 * Checks if the Rank Math SEO plugin is active and sets the canonical URL if the robots meta tag is set to 'noindex'.   *
		 * This method is called when the 'wp_head' action is triggered. It retrieves the robots meta tag settings from the Rank Math Paper class
		 * and sets the canonical URL to an empty string if the robots meta tag is set to 'noindex'.
		 */
		public function custom_rank_math_canonical() {
			if ( ! class_exists( 'RankMath\Paper\Paper' ) && ! method_exists( 'RankMath\Paper\Paper', 'get' ) ) {
				return;
			}
			add_action(
				'wp_head',
				function () {
					$robots = RankMath\Paper\Paper::get()->get_robots();
					if ( isset( $robots['index'] ) && $robots['index'] === 'noindex' ) {
						$this->robots_noindex = 'noindex';
						$canonical_url        = '';
						$this->set_canonical_url( $canonical_url );
					}
				},
				1
			);
		}

		/**
		 * Checks if the given URL contains any query parameters.
		 *
		 * @param string $url The URL to check.
		 * @return bool True if the URL contains query parameters, false otherwise.
		 */
		private function has_url_parameters( $url ) {
			$url_query = wp_parse_url( $url, PHP_URL_QUERY );
			return $url_query;
		}

		private function is_woocommerce_active(){
			return is_plugin_active( 'woocommerce/woocommerce.php' );
		}

		/**
		 * Checks if the Rank Math SEO plugin is active.
		 *
		 * @return bool True if the Rank Math SEO plugin is active, false otherwise.
		 */
		private function is_rank_math_active() {
			return is_plugin_active( 'seo-by-rank-math/rank-math.php' );
		}

		/**
		 * Checks if the Yoast SEO plugin is active.
		 *
		 * @return bool True if the Yoast SEO plugin is active, false otherwise.
		 */
		private function is_yoast_seo_active() {
			return is_plugin_active( 'wordpress-seo/wp-seo.php' );
		}


		/**
		 * Sets the meta robots tag based on the current page and plugin settings.
		 *
		 * This method is called when the 'wp_head' action is triggered. It retrieves the current page and plugin settings,
		 * and sets the meta robots tag accordingly. It handles different scenarios for the shop page and product category pages,
		 * as well as the Rank Math and Yoast SEO plugins.
		 *
		 * @param array $robots The current robots meta tag settings.
		 * @return array The updated robots meta tag settings.
		 */
		public function set_meta_robots_tag( $robots ) {
			if ( ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_category' ) ) {
				return $robots;
			}

			if ( is_shop() || is_product_category() ) {
				$current_page = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
				if ( is_shop() ) {
					$shop_page_index_option    = ( get_option( 'wc_category_indexer_option_shop' ) );
					if ( $shop_page_index_option === false ) {
						return $robots;
					}
					$first_page_index_option   = $shop_page_index_option['shop_first_page_index'];
					$other_pages_index_option  = $shop_page_index_option['shop_all_other_page_index'];
					$first_page_follow_option  = $shop_page_index_option['shop_first_page_follow'];
					$other_pages_follow_option = $shop_page_index_option['shop_all_other_page_follow'];
				} else {
					$term                       = get_queried_object();
					$category_page_index_option = ( get_option( 'wc_category_indexer_category_options' ) );
					if ( $category_page_index_option === false ) {
						return $robots;
					}
					$first_page_index_option    = $category_page_index_option[ $term->term_id ]['first_page_index'];
					$other_pages_index_option   = $category_page_index_option[ $term->term_id ]['all_other_pages_index'];
					$first_page_follow_option   = $category_page_index_option[ $term->term_id ]['first_page_follow'];
					$other_pages_follow_option  = $category_page_index_option[ $term->term_id ]['all_other_pages_follow'];
				}

				if ( $current_page === 1 ) {
					$meta_robots_index  = $first_page_index_option;
					$meta_robots_follow = $first_page_follow_option;
				} else {
					$meta_robots_index  = $other_pages_index_option;
					$meta_robots_follow = $other_pages_follow_option;
				}
				if ( $this->rank_math_activated ) {
					$robots['index']  = $meta_robots_index;
					$robots['follow'] = $meta_robots_follow;
				}
				if ( $this->yoast_activated ) {
					$robots = $meta_robots_index . ',' . $meta_robots_follow;
				}

				$url_parameters_options = get_option( 'wc_category_indexer_option_url_parameters' );
				if ( $this->has_url_parameters( $this->request_url ) ) {
						$url_parameters_index = $url_parameters_options['index'] ?? false;
						$url_parameters_follow = $url_parameters_options['follow'] ?? false;
					if ( $this->rank_math_activated ) {
						$robots['index']  = $url_parameters_index ? $url_parameters_index : 'index';
						$robots['follow'] = $url_parameters_follow ? $url_parameters_follow : 'follow';
					}
					if ( $this->yoast_activated ) {
						$robots = ( $url_parameters_index ? $url_parameters_index : 'index' ) . ',' . ( $url_parameters_follow ? $url_parameters_follow : 'follow' );
					}
				}

				// Override with WooCommerce order by filter settings if they are set
				$orderby_filter_options = get_option( 'wc_category_indexer_option_orderby' );
				if ( isset( $_GET['orderby'] ) ) {
					if ( isset( $orderby_filter_options['noindex'] ) ) {
						$meta_robots_index = 'noindex';
					}
					if ( isset( $orderby_filter_options['nofollow'] ) ) {
						$meta_robots_follow = 'nofollow';
					}

					if ( $this->rank_math_activated ) {
						$robots['index']  = $meta_robots_index ? $meta_robots_index : 'index';
						$robots['follow'] = $meta_robots_follow ? $meta_robots_follow : 'follow';

					}
					if ( $this->yoast_activated ) {
						$robots = ( $meta_robots_index ? $meta_robots_index : 'index' ) . ',' . ( $meta_robots_follow ? $meta_robots_follow : 'follow' );
					}
				}
			}
			return $robots;
		}

		/**
		 * Sets the canonical URL for the current page.
		 *
		 * This function checks if the current page is the shop page or a product category page, and sets the canonical URL accordingly.
		 * For the shop page, it checks the configured options for the canonical URL on the first page and subsequent pages.
		 * For product category pages, it checks the configured options for the canonical URL on the first page and subsequent pages.
		 *
		 * @param string $canonical_url The current canonical URL.
		 * @return string The updated canonical URL.
		 */
		public function set_canonical_url( $canonical_url ) {
			if ( ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_category' ) ) {
				return esc_url( $canonical_url );
			}
			global $wp;
			$current_page = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
			if ( is_shop() ) {
				$canonical_option = get_option( 'wc_category_indexer_option_shop_canonical' );
				if ( $canonical_option === false ) {
					return esc_url( $canonical_url );
				}
				if ( $current_page > 1 && ( $canonical_option['default'] ?? null ) === 'default' ) {
					$canonical_url = home_url( $wp->request );
				} elseif ( $current_page > 1 && ( $canonical_option['pages_after_first'] ?? null ) === 'first_page' ) {
						$canonical_url = get_permalink( wc_get_page_id( 'shop' ) );
				} else {
					$canonical_url = home_url( $wp->request );
				}
			}
			if ( is_product_category() ) {
				$current_category           = get_queried_object();
				$category_canonical_options = get_option( 'wc_category_indexer_category_options' );
				if ( $category_canonical_options === false ) {
					return esc_url( $canonical_url );
				}
				$first_page_canonical       = $category_canonical_options[ $current_category->term_id ]['canonical_first_page'];
				if ( $first_page_canonical === 'default' ) {
					$canonical_first_page = home_url( add_query_arg( array(), $wp->request ) );
				} elseif ( $first_page_canonical === 'custom' ) {
					$canonical_first_page = esc_url( get_category_link( $category_canonical_options[ $current_category->term_id ]['custom_select'] ) );
				}
				if ( $current_page === 1 ) {
						$canonical_url = $canonical_first_page;
				} elseif ( $current_page > 1 && ( $category_canonical_options[ $current_category->term_id ]['canonical_all_other_pages'] ?? null ) === 'default' ) {
					$canonical_url = home_url( add_query_arg( array(), $wp->request ) );

				} elseif ( $current_page > 1 && ( $category_canonical_options[ $current_category->term_id ]['canonical_all_other_pages'] ?? null ) === 'from_first_page' ) {
					if ( $first_page_canonical === 'custom' ) {
						$canonical_url = $canonical_first_page;
					} else {
						$canonical_url = get_term_link( $current_category->term_id, 'product_cat' );
					}
				}
			}
			if ( $this->rank_math_activated ) {
				if ( $this->robots_noindex === 'noindex' ) {
					echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />';
					return;
				} else {
					return esc_url( $canonical_url );
				}
			}
			if ( $this->yoast_activated ) {
				return esc_url( $canonical_url );
			}
			return esc_url( $canonical_url );
		}
	}
}
