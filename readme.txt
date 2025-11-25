=== Category Indexer for WooCommerce ===
Contributors: tihi
Tags: seo, woocommerce, meta robots, canonical, link juice
Tested up to: 6.8
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Control meta robots tags, canonical URLs, and link juice flow on WooCommerce shop and category pages for better SEO performance.

== Description ==
Category Indexer for WooCommerce is a powerful SEO tool designed to help WooCommerce store owners efficiently manage the indexing of their category and shop pages. By selectively controlling which pages are indexed and followed by search engines, this plugin can help save on Google's crawling budget, particularly for large WooCommerce stores. This means better resource allocation and potentially improved SEO performance.
This plugin gives you complete control over three critical SEO elements that directly impact your WooCommerce store's search engine performance: **meta robots tags**, **canonical URLs**, and **link juice distribution**.

**Control Meta Robots Tags with Precision**
Unlike basic SEO plugins that offer only site-wide settings, Category Indexer for WooCommerce allows you to configure meta robots tags (index, noindex, follow, nofollow) at multiple levels. You can set global defaults for all shop and category pages, then override these settings for individual categories that need special treatment. This granular control means you can index your high-value category pages while preventing search engines from wasting resources on low-value pagination pages - all through an intuitive interface that requires no coding knowledge.

**Automatic Canonical Tag Implementation**
Duplicate content is one of the biggest SEO challenges for WooCommerce stores. When you have category pages with pagination, sorting options, or filtering parameters, search engines can see multiple similar pages competing for the same rankings. This plugin automatically adds canonical tags to all your category and shop pages, ensuring that pagination pages always point back to the primary page as the authoritative version. This consolidates your SEO authority and prevents Google from penalizing your site for duplicate content.

**Optimize Link Juice Flow Through Your Store**
Every link on your website passes SEO value (link juice) to the pages it points to. Without proper management, this valuable link equity gets diluted across hundreds of pagination pages that don't need it. By strategically applying nofollow meta robots tags to pagination pages, you keep link juice concentrated on your most important pages - your main category pages and product pages. This means better rankings for the pages that actually drive sales, while still allowing customers to navigate your entire catalog.

**Understanding Key SEO Concepts**

= Meta Robots Tag =
The **meta robots tag** is an HTML element that tells search engine crawlers how to index and follow your web pages. This plugin automatically adds the appropriate meta robots tag to your WooCommerce category and shop pages, allowing you to control:
* **Index/Noindex:** Whether search engines should include the page in their search results
* **Follow/Nofollow:** Whether search engines should follow the links on that page
* **Crawl Budget Optimization:** By using noindex on paginated category pages, you prevent search engines from wasting crawl budget on duplicate or low-value pages

For example, you might want to index the first page of your "Electronics" category but noindex pagination pages (page 2, 3, etc.) to avoid duplicate content issues.

= Canonical Tag =
A **canonical tag** (rel="canonical") is an HTML element that helps prevent duplicate content issues by telling search engines which version of a page is the "master" or preferred version. This is crucial for WooCommerce stores because:
* Category pages often have multiple URL variations (with sorting, filtering, or pagination parameters)
* Duplicate content can dilute your SEO rankings
* Search engines may split ranking signals across multiple similar pages

This plugin automatically sets canonical URLs on category and shop pages, ensuring that all pagination pages point back to the first page as the canonical version. This consolidates ranking signals and prevents search engines from indexing multiple similar pages.

= Link Juice =
**Link juice** (also known as link equity or link authority) refers to the SEO value and authority that is passed from one page to another through hyperlinks. When a page links to another page, it's essentially "voting" for that page, passing along some of its authority. Here's how this plugin helps with link juice management:
* **Nofollow on Pagination:** By setting pagination pages to "nofollow," you prevent link juice from being diluted across dozens of paginated category pages
* **Focus Link Equity:** Keep link juice concentrated on your main category pages (page 1) rather than spreading it thin across pagination
* **Internal Link Optimization:** Control which pages pass link juice to your product pages, ensuring maximum SEO benefit for your most important products
* **Preserve Crawl Budget:** By preventing crawlers from following low-value links, you ensure they spend more time on your valuable product and category pages

**Features:**
* **Meta Robots Tag Control:** Add noindex, nofollow, index, or follow meta robots tags to shop and category pages
* **Canonical URL Management:** Automatically set canonical tags on paginated pages to prevent duplicate content
* **Indexing Control:** Choose whether the first page or all other pages of your shop and product categories should be indexed or noindexed
* **Follow Control:** Decide if search engines should follow or nofollow links on the first page or all other pages of your shop and product categories
* **Link Juice Optimization:** Control the flow of link equity through your category structure
* **Shop Page Settings:** Apply specific meta robots tag and canonical settings for the shop page
* **Category-Specific Settings:** Apply unique meta robots and canonical settings to individual product categories for granular control
* **Compatibility:** Fully compatible with Rank Math and Yoast SEO plugins, seamlessly integrating with your existing SEO setup
* **Translation Ready:** Easily translatable, making it accessible for non-English users

**Benefits:**
* **Optimize Crawling Budget:** By managing which pages are indexed with proper meta robots tags, you ensure that Google's crawl budget is spent more effectively, leading to better indexing of your important pages
* **Prevent Duplicate Content:** Canonical tags prevent search engines from indexing multiple similar pages, consolidating ranking signals
* **Improve Link Juice Distribution:** Control how link equity flows through your site, keeping it concentrated on your most valuable pages
* **Better SEO Rankings:** Proper meta robots tag and canonical tag implementation can lead to improved search engine rankings
* **Avoid Indexing Waste:** Prevent low-value pagination pages from cluttering search results
* **Easy to Use:** Intuitive settings interface in the WordPress admin area, making it simple to configure meta robots tags, canonical URLs, and link juice flow

**Use Cases:**
* Large WooCommerce stores with hundreds of categories and pagination pages
* Stores experiencing duplicate content issues in Google Search Console
* Sites wanting to optimize crawl budget and focus on high-value pages
* Store owners who want fine-grained control over meta robots tags and canonical URLs
* SEO professionals managing WooCommerce sites who need to control link juice distribution

**Compatibility:**
* **Rank Math SEO:** Automatically integrates with Rank Math SEO free version to apply your meta robots tag and canonical preferences
* **Yoast SEO:** Seamlessly works with Yoast SEO free version to manage your category and shop page meta robots tags and canonical URLs

== Installation ==
* **Install via WordPress Admin:**
   - Navigate to `WP-Admin -> Plugins -> Add New`.
   - In the search bar, type "Category Indexer for WooCommerce".
   - Once you find the plugin, click on the "Install Now" button.
   - After the installation is complete, click on the "Activate Plugin" button.

* **Install via FTP:**
   - Download the plugin zip file from the WordPress.org repository.
   - Upload the plugin zip file to your WordPress installation's `wp-content/plugins/` directory.
   - Activate the plugin through the 'Plugins' menu in WordPress.

* After activation, go to `Settings -> Category Indexer` in your WordPress admin panel.
   - Adjust the settings according to your needs and save the changes.

== Frequently Asked Questions ==

= Do I need an SEO plugin to use Category Indexer for WooCommerce? =
Yes, free version Rank Math SEO or Yoast SEO must be installed and activated for Category Indexer for WooCommerce to function. The plugin integrates with these SEO plugins to add meta robots tags and canonical URLs.

= What is a meta robots tag and why do I need it? =
A meta robots tag is an HTML element that instructs search engines how to crawl and index your pages. You need it to control which pages appear in search results (index/noindex) and whether search engines should follow links on those pages (follow/nofollow). This is essential for managing your crawl budget and preventing duplicate content issues in WooCommerce stores.

= How does this plugin help with canonical tags? =
The plugin automatically adds canonical tags to your WooCommerce category and shop pages, especially on paginated pages. This tells search engines which page is the "master" version, preventing duplicate content penalties and consolidating ranking signals on your main category pages instead of spreading them across pagination.

= What is link juice and how does this plugin help optimize it? =
Link juice (link equity) is the SEO value passed from one page to another through links. This plugin helps you control link juice flow by allowing you to set nofollow on pagination pages, keeping link equity concentrated on your most important category and product pages rather than diluting it across dozens of paginated pages.

= Can I set different meta robots tags for different categories? =
Yes, you can configure meta robots tags (index/noindex, follow/nofollow) and canonical settings for each product category individually, giving you granular control over your WooCommerce SEO strategy.

= Will this plugin prevent duplicate content issues? =
Yes, by setting proper canonical tags and using noindex on pagination pages, this plugin helps prevent duplicate content issues that commonly affect WooCommerce stores with multiple category pages and pagination.

= How does this help with Google's crawl budget? =
By using meta robots tags to noindex low-value pagination pages, you prevent Google from wasting crawl budget on duplicate content. This means Google will spend more time crawling and indexing your valuable product and main category pages, improving overall site indexation.

= Is the plugin translation ready? =
Yes, Category Indexer for WooCommerce is fully translation ready, allowing you to translate it into your language easily.

= Does this plugin add canonical URLs automatically? =
Yes, the plugin automatically adds canonical URLs to paginated category and shop pages, pointing back to the first page as the canonical version. This is crucial for proper SEO and preventing duplicate content.

= Can I control link juice distribution to my product pages? =
Yes, by controlling the follow/nofollow meta robots tag settings on category pages, you can optimize how link juice flows from your categories to your individual product pages, ensuring maximum SEO benefit.

= Is the plugin compatible with block themes? =
Yes, Category Indexer for WooCommerce is fully compatible with block themes (FSE - Full Site Editing). The plugin works seamlessly with both classic themes and modern block themes.

== Screenshots ==
1. Admin settings page where you can configure indexing and follow settings for shop and category pages.

== Changelog ==

= 1.0.1 =
* Fixed plugin uninstallation.




