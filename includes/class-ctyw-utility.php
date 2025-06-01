<?php

/*
 * Utilities class for CTYW_Utility
 * @since  1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
   exit;
}

/**
 * Utilities class - singleton class
 * @since 1.0
 */
class CTYW_Utility {

   private function __construct() {
      // Do Nothing
   }

   /**
    * Get the singleton instance of the Custom_Thankyou_Wc_Utility class
    *
    * @return singleton instance of Custom_Thankyou_Wc_Utility
    */
   public static function instance() {
      static $instance = NULL;
      if (is_null($instance)) {
         $instance = new CTYW_Utility();
      }
      return $instance;
   }

   /**
    * Display error or success message in the admin section
    *
    * @param array $data containing type and message
    * @return string with html containing the error message
    * 
    * @since 1.0 initial version
    */
   public function admin_notice($data = array()) {
      // extract message and type from the $data array
      $message = isset($data['message']) ? $data['message'] : "";
      $message_type = isset($data['type']) ? $data['type'] : "";
      switch ($message_type) {
         case 'error':
            $admin_notice = '<div id="message" class="error notice is-dismissible">';
            break;
         case 'update':
            $admin_notice = '<div id="message" class="updated notice is-dismissible">';
            break;
         case 'update-nag':
            $admin_notice = '<div id="message" class="update-nag">';
            break;
         case 'review' :
            $admin_notice = '<div id="message" class="updated notice gs-adds is-dismissible">';
            break;
         default:
            $message = __('There\'s something wrong with your code...', 'ctyw');
            $admin_notice = "<div id=\"message\" class=\"error\">\n";
            break;
      }

      $admin_notice .= "    <p>" . $message . "</p>\n";
      $admin_notice .= "</div>\n";
      return $admin_notice;
   }

   /**
    * Utility function to get the current user's role
    *
    * @since 1.0
    */
   public function get_current_user_role() {
      global $wp_roles;
      foreach ($wp_roles->role_names as $role => $name) :
         if (current_user_can($role))
            return $role;
      endforeach;
   }

}
