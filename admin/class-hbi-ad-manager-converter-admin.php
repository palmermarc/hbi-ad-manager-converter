<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    HBI_Ad_Manager_Converter
 * @subpackage HBI_Ad_Manager_Converter/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    HBI_Ad_Manager_Converter
 * @subpackage HBI_Ad_Manager_Converter/admin
 * @author     Marc Palmer <mapalmer@hbi.com>
 */
class HBI_Ad_Manager_Converter_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $hbi_ad_manager_converter    The ID of this plugin.
	 */
	private $hbi_ad_manager_converter;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $hbi_ad_manager_converter       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $hbi_ad_manager_converter, $version ) {

		$this->hbi_ad_manager_converter = $hbi_ad_manager_converter;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in HBI_Ad_Manager_Converter_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The HBI_Ad_Manager_Converter_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->hbi_ad_manager_converter, plugin_dir_url( __FILE__ ) . 'css/hbi-ad-manager-converter-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in HBI_Ad_Manager_Converter_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The HBI_Ad_Manager_Converter_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->hbi_ad_manager_converter, plugin_dir_url( __FILE__ ) . 'js/hbi-ad-manager-converter-admin.js', array( 'jquery' ), $this->version, false );

	}
	
	function convert_from_acm_mods_page() {
        add_submenu_page( 'edit.php?post_type=ad_unit', 'Convert ACM Mods to HBI AD Manager', 'Convert AMC Ads', 'manage_options', 'convert-acm', array( $this, 'convert_acm_mods_page' ) );
    }
    
    function convert_acm_mods_page() {
        include_once( plugin_dir_path( __FILE__ ) . 'partials/hbi-ad-manager-converter-admin-display.php' );
    }
    
    function process_acm_ad_conversion() {
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'process_acm_ad_conversion' ) )
            wp_die();
        
        global $wpdb;
        
        $acm_tags = ACM_Mods::other_acm_ad_tag_ids( array() );
        foreach( $acm_tags as $ad ) {
			
            $ad_height = $ad['url_vars']['height'];
            $ad_width = $ad['url_vars']['width'];
            
            $query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND ( post_name = %s OR post_title = %s )", 'acm-tag', sanitize_title($ad['tag']), $ad['tag'] );
            $result = $wpdb->get_row( $query, 'OBJECT' );
            
            if( $result !== NULL ) {
                
                $ad_code_query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'tag' AND meta_value=%s LIMIT 500", $ad['tag']  );
                $ad_code = $wpdb->get_row( $ad_code_query, 'OBJECT' );
				
                if( NULL !== $ad_code ) {
                    update_post_meta( $result->ID, 'dfp_ad_unit', get_post_meta( $ad_code->post_id, 'tag_name', TRUE ) );
                    update_post_meta( $result->ID, 'dfp_network_code', get_post_meta( $ad_code->post_id, 'dfp_id', TRUE ) );
                    update_post_meta( $result->ID, 'tag_id', get_post_meta( $ad_code->post_id, 'tag_id', TRUE ) );
                    update_post_meta( $result->ID, 'conditionals', get_post_meta( $ad_code->post_id, 'conditionals', TRUE ) );
                    update_post_meta( $result->ID, 'operator', get_post_meta( $ad_code->post_id, 'operator', TRUE ) );
                    update_post_meta( $result->ID, 'priority', get_post_meta( $ad_code->post_id, 'priority', TRUE ) );
                }
                
                $wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = 'ad_height' WHERE meta_key = '_ad_height' AND post_id = $result->ID"); // dfp_id (DFP Network Code)
                $wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = 'ad_width' WHERE meta_key = '_ad_width' AND post_id = $result->ID"); // dfp_id (DFP Network Code)
                
                set_post_type( $result->ID, 'ad_unit' );
                update_post_meta( $result->ID, 'admap_to_use', 0 );
            }
            
        }

        $old_widgets = get_option( 'sidebars_widgets' );
        $new_widgets = array();
        foreach( $old_widgets as $sidebar => $the_widgets ) {
            if( is_array( $the_widgets ) ) {
                $new_widgets[$sidebar] = array();
                if( !empty( $the_widgets ) ) {
                    foreach( $the_widgets as $the_widget ) {
                        $new_widgets[$sidebar][] = str_replace( 'acm_ad_zones', 'dfp_ad_unit', $the_widget );
                    }
                }
            } else {
                $new_widgets[$sidebar] = $the_widgets;
            }
        }
        
        update_option( 'sidebars_widgets', $new_widgets );

        // Update the widgets to the new widget
        $wpdb->query( "UPDATE $wpdb->options SET option_name = 'widget_dfp_ad_unit' WHERE option_name = 'widget_acm_ad_zones';");

        wp_die( 'Successfully converted from ACM Mods to HBI Ad Manager' );
    }
}