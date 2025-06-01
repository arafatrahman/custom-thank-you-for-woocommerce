<?php

/**
 * @package              CTYW
 * @wordpress-plugin
 *
 * Plugin Name:          Custom Thank You for WooCommerce
 * Plugin URI:           http://wordpress.org/plugins/custom-thank-you-for-woocommerce
 * Description:          A WooCommerce extension that allows you to define your own custom thank you page after placing order.
 * Version:              1.1.3
 * Author:               Artios Media
 * Author URI:           http://www.artiosmedia.com
 * Developer:            Arafat Rahman
 * Copyright:            © 2019-2025 Artios Media (email : contact@artiosmedia.com).
 * License: GNU          General Public License v3.0
 * License URI:          http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:          product-code-for-woocommerce
 * Domain Path:          /languages
 * Tested up to:         6.7.2
 * WC requires at least: 6.5.0
 * WC tested up to:      9.7.1
 * PHP tested up to:     8.3.15
 */
if (!defined('ABSPATH')) {
   exit; // Exit if accessed directly
}

// Declare some global constants
define('CTYW_VERSION', '1.1.3');
define('CTYW_DB_VERSION', '1.1.3');
define('CTYW_ROOT', dirname(__FILE__));
define('CTYW_URL', plugins_url('/', __FILE__));
define('CTYW_BASE_FILE', basename(dirname(__FILE__)) . '/custom-thank-you-woocommerce.php');
define('CTYW_BASE_NAME', plugin_basename(__FILE__));
define('CTYW_PATH', plugin_dir_path(__FILE__)); //use for include files to other files
define('CTYW_CURRENT_THEME', get_stylesheet_directory());
define('CTYW_PRODUCT_NAME', 'Custom Thank You For WooCoomerce');
load_plugin_textdomain('ctyw', false, basename(dirname(__FILE__)) . '/languages');

/*
 * include utility classes
 */
if (!class_exists('CTYW_Utility')) {
   include(CTYW_ROOT . '/includes/class-ctyw-utility.php');
}

if (!class_exists('CTYW_Settings')) {
   include(CTYW_ROOT . '/includes/class-ctyw-settings.php');
}

add_action('before_woocommerce_init', function () {
   // Check if the FeaturesUtil class exists in the \Automattic\WooCommerce\Utilities namespace.
   if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
       // Declare compatibility with custom order tables using the FeaturesUtil class.
       \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
   }
});

/*
 * Main WC custom free init class
 * @class Wc_Custom_Thankyou_Free_Init
 * @since 1.0
 */

class CTYW_Init
{

   /**
    *  Set things up.
    *  @since 1.0
    */
   public function __construct()
   {
      //Add plugin description link
      add_filter('plugin_row_meta', array($this, 'add_description_link'), 10, 2);
      add_filter('plugin_row_meta', array($this, 'add_details_link'), 10, 4);

      //run on activation of plugin
      register_activation_hook(__FILE__, array($this, 'ctyw_activate'));

      //run on deactivation of plugin
      register_deactivation_hook(__FILE__, array($this, 'ctyw_deactivate'));

      //run on uninstall
      register_uninstall_hook(__FILE__, array('CTYW_Init', 'ctyw_uninstall'));

      // validate is woocommerce plugin exist
      add_action('admin_init', array($this, 'validate_parent_plugin_exists'));

      //add custom link for the plugin beside activate/deactivate links
      add_filter('plugin_action_links_' . CTYW_BASE_NAME, array($this, 'ctyw_plugin_action_links'));


      //load css and js files
      add_action('wp_enqueue_scripts', array($this, 'load_script_files'));
      add_action('admin_enqueue_scripts', array($this, 'load_admin_script_files'));

      add_action('admin_init', array($this, 'run_on_upgrade'));
   }

   public function add_description_link($links, $file)
   {
      if (plugin_basename(__FILE__) == $file) {
         $row_meta = array(
            'donation' => '<a href="' . esc_url(' https://www.zeffy.com/en-US/donation-form/your-donation-makes-a-difference-6') . '" target="_blank">' . esc_html__('Donation for Homeless', 'ctyw') . '</a>'
         );
         return array_merge($links, $row_meta);
      }
      return (array) $links;
   }

   public function add_details_link($links, $plugin_file, $plugin_data)
   {
      if (isset($plugin_data['PluginURI']) && false !== strpos($plugin_data['PluginURI'], 'http://wordpress.org/extend/plugins/')) {
         $slug = basename($plugin_data['PluginURI']);
         unset($links[2]);
         $links[] = sprintf('<a href="%s" class="thickbox" title="%s">%s</a>', self_admin_url('plugin-install.php?tab=plugin-information&amp;plugin=' . $slug . '&amp;TB_iframe=true&amp;width=772&amp;height=563'), esc_attr(sprintf(__('More information about %s', 'ctyw'), $plugin_data['Name'])), __('View Details', 'ctyw'));
      }
      return $links;
   }

   /**
    * Do things on plugin activation
    * @since 1.0
    */
   public function ctyw_activate($network_wide)
   {
      global $wpdb;
      $this->run_on_activation();
      if (function_exists('is_multisite') && is_multisite()) {
         // check if it is a network activation - if so, run the activation function for each blog id
         if ($network_wide) {
            // Get all blog ids
            $blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");
            foreach ($blogids as $blog_id) {
               switch_to_blog($blog_id);
               $this->run_for_site();
               restore_current_blog();
            }
            return;
         }
      }

      // for non-network sites only
      $this->run_for_site();
   }

   /**
    * deactivate the plugin
    * @since 1.0
    */
   public function ctyw_deactivate($network_wide)
   {
      // currently not deleting anything
   }

   /**
    *  Runs on plugin uninstall.
    *  a static class method or function can be used in an uninstall hook
    *
    *  @since 1.0
    */
   public static function ctyw_uninstall()
   {
      global $wpdb;

      CTYW_Init::run_on_uninstall();

      if (!is_plugin_active('custom-thank-you-woocommerce/custom-thank-you-woocommerce.php') || (!file_exists(plugin_dir_path(__DIR__) . 'custom-thank-you-woocommerce/custom-thank-you-woocommerce.php'))) {
         return;
      }

      if (function_exists('is_multisite') && is_multisite()) {
         //Get all blog ids; foreach of them call the uninstall procedure
         $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");

         //Get all blog ids; foreach them and call the install procedure on each of them if the plugin table is found
         foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            CTYW::delete_for_site();
            restore_current_blog();
         }
         return;
      }

      CTYW::delete_for_site();
   }

   /**
    * Validate parent Plugin Woocommerce exist and activated
    * @access public
    * @since 1.0
    */
   public function validate_parent_plugin_exists()
   {
      $plugin = plugin_basename(__FILE__);
      if ((!is_plugin_active('woocommerce/woocommerce.php')) || (!file_exists(plugin_dir_path(__DIR__) . 'woocommerce/woocommerce.php'))) {
         add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
         add_action('network_admin_notices', array($this, 'woocommerce_missing_notice'));
         deactivate_plugins($plugin);
         if (isset($_GET['activate'])) {
            // Do not sanitize it because we are destroying the variables from URL
            unset($_GET['activate']);
         }
      }
   }

   /**
    * If woocommerce plugin is not installed or activated then throw the error
    *
    * @access public
    * @return mixed error_message, an array containing the error message
    *
    * @since 1.0 initial version
    */
   public function woocommerce_missing_notice()
   {
      $plugin_error = CTYW_Utility::instance()->admin_notice(array(
         'type' => 'error',
         'message' => __('Custom Thank You for WooCommerce Add-on requires WooCommerce plugin to be installed and activated.', 'ctyw')
      ));
      echo $plugin_error;
   }

   /**
    * Add custom link for the plugin beside activate/deactivate links
    * @param array $links Array of links to display below our plugin listing.
    * @return array Amended array of links.    * 
    * @since 1.0
    */
   public function ctyw_plugin_action_links($links)
   {
      // We shouldn't encourage editing our plugin directly.
      unset($links['edit']);

      // Add our custom links to the returned array value.
      return array_merge(array(
         '<a href="' . admin_url('admin.php?page=wc-settings&tab=advanced') . '">' . __('Settings', 'ctyw') . '</a>'
      ), $links);
   }


   /**
    * enqueue JS/css files
    * @since 1.0
    */
   public function load_script_files()
   {
      $thankyou_page = get_option('ctyw_page_id');
      if (is_page($thankyou_page)) {
         wp_enqueue_style('ctyw-css', CTYW_URL . 'assets/css/custom-thankyou-woocommerce.css', CTYW_VERSION, true);
         wp_enqueue_script('ctyw-tabs', CTYW_URL . 'assets/js/ctyw_tabs.js', CTYW_VERSION, array('jquery'), true);
         wp_enqueue_script('ctyw-social-box', CTYW_URL . 'assets/js/ctyw_social_box.js', CTYW_VERSION, array('jquery'), true);
      }
   }

   public function load_admin_script_files()
   {
      if (is_admin()) {
         wp_enqueue_script('ctyw-admin', CTYW_URL . 'assets/js/ctyw_admin.js', CTYW_VERSION, array('jquery'), true);
      }
   }

   /**
    * Called on activation.
    * Creates the site_options (required for all the sites in a multi-site setup)
    * If the current version doesn't match the new version, runs the upgrade
    * @since 1.0
    */
   private function run_on_activation()
   {
      $plugin_options = get_site_option('ctyw_info');
      if (false === $plugin_options) {
         $ctyw_info = array(
            'version' => CTYW_VERSION,
            'db_version' => CTYW_DB_VERSION
         );
         update_site_option('ctyw_info', $ctyw_info);
      } else if (CTYW_DB_VERSION != $plugin_options['version']) {
         $this->run_on_upgrade();
      }
      update_option("ctyw_notice_dismiss", date('Y-m-d', strtotime('+30 days')));
   }

   /**
    * Called on activation.
    * Creates the options and DB (required by per site)
    * @since 1.0
    */
   private function run_for_site()
   {
      if (!get_option("ctyw_enable_fb_social_box")) {
         update_option("ctyw_enable_fb_social_box", "yes");
      }

      if (!get_option("ctyw_enable_twitter_social_box")) {
         update_option("ctyw_enable_twitter_social_box", "yes");
      }

      if (!get_option("ctyw_enable_pinterest_social_box")) {
         update_option("ctyw_enable_pinterest_social_box", "yes");
      }
   }

   /**
    * Called on uninstall - deletes site_options
    *
    * @since 1.0
    */
   private static function run_on_uninstall()
   {
      if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
         exit();
      }

      delete_site_option('wc_custom_page_info');
   }

   /**
    * called on upgrade. 
    * checks the current version and applies the necessary upgrades from that version onwards
    * @since 1.0
    */
   public function run_on_upgrade()
   {
      $plugin_options = get_site_option('ctyw_info');
      if (false === $plugin_options) {
         $ctyw_info = array(
            'version' => CTYW_VERSION,
            'db_version' => CTYW_DB_VERSION
         );
         update_site_option('ctyw_info', $ctyw_info);
      }
   }

   /**
    * Called on uninstall - deletes site specific options
    *
    * @since 1.0
    */
   private static function delete_for_site()
   {
      delete_option('ctyw_page_id');
   }
}

// Initialize the class
$init = new CTYW_Init();
