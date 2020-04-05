<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Spam_Protect_for_Contact_Form7
 * @subpackage Spam_Protect_for_Contact_Form7/frontend
 */

/**
 * The public-facing functionality of the plugin.
 * 
 * @package    Spam_Protect_for_Contact_Form7
 * @subpackage Spam_Protect_for_Contact_Form7/frontend
 * @author     New York Software Lab
 * @link       https://nysoftwarelab.com
 */
class Spam_Protect_for_Contact_Form7_Front {

    /**
     * The unique identifier of this plugin.
     */
    private $plugin_name;

    /**
     * The current version of the plugin.
     */
    private $version;

    /**
     * Constructor of the class.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_filter('wpcf7_validate_email', array($this, 'spcf_check_email'), 10, 2); // Email field
        add_filter('wpcf7_validate_email*', array($this, 'spcf_check_email'), 10, 2); // Required Email field
        
        
        add_filter('wpcf7_validate_text', array($this, 'spcf_check_text'), 10, 2); // Text field
        add_filter('wpcf7_validate_text*', array($this, 'spcf_check_text'), 10, 2); // Required Text field

        add_filter('wpcf7_validate_textarea', array($this, 'spcf_check_text'), 10, 2); // Text field
        add_filter('wpcf7_validate_textarea*', array($this, 'spcf_check_text'), 10, 2); // Required Text field
    }
    
    public function spcf_write_log($log){
        $file = fopen("wp-content/spcf_spam_block.log", "a");
        fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $log); 
        fclose($file); 
    }
    
    public function spcf_check_email_and_domain($email, $post_id) {
        $blocked_emails_list_str = str_replace(" ", "", get_post_meta($post_id, "_wpcf7_block_email_list", true));
        $blocked_emails_domain_str = str_replace(" ", "", get_post_meta($post_id, "_wpcf7_block_email_domain", true));
        $wpcf7_block_logging = get_post_meta($post_id, "_wpcf7_block_logging", true);
        
        $blocked_emails = explode(",", trim($blocked_emails_list_str));
        $blocked_domains = explode(",", trim($blocked_emails_domain_str));
        
        $email_domain = strstr($email, '@');

        if (in_array($email_domain, $blocked_domains) || in_array($email, $blocked_emails)) {
            if ($wpcf7_block_logging=="yes"){ 
                $this->spcf_write_log("\tThe folowing email/domain is blocked by the plugin's rules : ".$email."\n");
            }
            return false;
        } else {
            return true;
        }
    }
    
    public function spcf_check_text_process($value, $blocked_words, $wpcf7_block_logging) {
       $lower_value = strtolower($value);
       foreach ($blocked_words as $bw){
           $bw = trim($bw);
           if (strlen(trim($bw))>0){
                if (strpos($lower_value, $bw)!==false){
                    if ($wpcf7_block_logging=="yes"){ 
                        $this->spcf_write_log("\tThe folowing text is blocked by the plugin's rules : \n".$lower_value."\n");
                    }
                    return false;
                }
           }
       }
       return true;
    }
    
    public function spcf_check_text($result, $tag) {
        //$type = $tag['type'];
        $name = $tag['name'];
        $basetype = $tag['basetype'];
        $post_id = sanitize_text_field($_POST['_wpcf7']);
        
        $wpcf7_block_email_error_msg = get_post_meta($post_id, "_wpcf7_block_email_error_msg", true);
        $blocked_words_str = str_replace(" ", "", get_post_meta($post_id, "_wpcf7_block_words", true));
        $blocked_words = explode(",", trim($blocked_words_str));
        $wpcf7_block_logging = get_post_meta($post_id, "_wpcf7_block_logging", true);
        
        if ($basetype == 'text' || $basetype == 'textarea') {
            $value = sanitize_text_field($_POST[$name]);
            
            if (!$this->spcf_check_text_process($value, $blocked_words, $wpcf7_block_logging)) {
                $result->invalidate($tag, $wpcf7_block_email_error_msg);
            }
        }
        
        return $result;
    }
    
    public function spcf_check_email($result, $tag) {
        //$type = $tag['type'];
        $name = $tag['name'];
        $basetype = $tag['basetype'];
        $post_id = sanitize_text_field($_POST['_wpcf7']);
               
        $wpcf7_block_email_error_msg = get_post_meta($post_id, "_wpcf7_block_email_error_msg", true);

        if ($basetype == 'email') {// Only apply to fields with the form field name of "your-email"
            $value = sanitize_text_field($_POST[$name]);
            if (!$this->spcf_check_email_and_domain($value, $post_id)) {
                $result->invalidate($tag, $wpcf7_block_email_error_msg);
            }
        }
        return $result;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function spcf7_enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/spam-protect-for-contact-form7.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function spcf7_enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/spam-protect-for-contact-form7.js', array('jquery'), $this->version, false);
    }

}
