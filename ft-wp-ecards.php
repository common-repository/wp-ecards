<?php
/*
Plugin Name: WordPress e-Cards
Plugin URI: http://semperplugins.com/
Description: Converts a WordPress page or post into your very own e-card sending script.
Version: 0.2
Author: Michael Torbert and Semper Plugins
Author URI: https://semperplugins.com
*/

/*Copyright 2019 semperplugins.com
 
*/

require_once('class_ftwpecards.php');

define( 'FT_WPECARD_Version' , '0.1' );

// Define plugin path
if ( !defined('WP_CONTENT_DIR') ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content');
}
define('FT_WPECARD_PATH' , WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__)) );

// Define plugin URL
if ( !defined('WP_CONTENT_URL') ) {
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content' );
}
define( 'FT_WPECARD_URL' , WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)) );


// Setup form security
if ( !function_exists('wp_nonce_field') ) {
    function ft_wpecard_nonce_field($action = -1) { return; }
    $ft_wpecard_nonce = -1;
} else {
	if( !function_exists( 'ft_wpecard_nonce_field' ) ) {
	function ft_wpecard_nonce_field($action = -1,$name = 'ft_wpecard-update-checkers') { return wp_nonce_field($action,$name); }
	define('FT_WPECARD_NONCE' , 'ft_wpecard-update-checkers');
	}
}

//This function sets up my tables
if ( !function_exists('ft_wpecards_plugin_activation') ){
	function ft_wpecards_plugin_activation() {
		global $wpdb;
		include_once('tables.php');
		$ft_wpecards_tables->create_tables();
	}
}
register_activation_hook( __FILE__, 'ft_wpecards_plugin_activation' );

// Shortcode replacement
function ft_wp_ecard_insert(){
	global $wp_query;
	if ( is_page() || is_single() ){
		if ( class_exists('FT_WPECARDS') ){
			$cards = new FT_WPECARDS();
			if ( isset($wp_query->query_vars['ft_wpecards_view'])){
				$cards->load_card($wp_query->query_vars['card']);
				$cards->view_card();	
			}elseif ( (!$cards->form_submitted) || ($cards->form_submitted && is_array($cards->error_messages)) ) {
				$cards->print_errors();
				$cards->print_form();
			};
		}
	}
}
add_shortcode( 'wpecards' , 'ft_wp_ecard_insert' );

// adds styles to my header if on a [wpecards] shortcode enabled page/post
function ft_wpecards_styles(){
	global $wp_query,$wp_version;
	if ( isset($wp_query->queried_object->post_content) ){
		$pos = strpos($wp_query->queried_object->post_content, "[wpecards]");
	}
	
	if ( (isset($pos)) && ! (FALSE === $pos) ) {  // the === is important; see php docs
    	// set file name
		$css = '/ft_wpecards.css';
		
		if ( file_exists( TEMPLATEPATH . $css ) ){
			if ( $wp_version <= 2.7 ){
				?><link rel="stylesheet" type="text/css" media="screen" href="<?php bloginfo('stylesheet_directory').$css;?>" /><?php
			}else{
				wp_register_style( 'wp_ecards' , get_bloginfo('template_directory') . $css );
				wp_enqueue_style( 'wp_ecards' );			
			}
		}else{
			if ( file_exists( FT_WPECARD_PATH . $css ) ){
				
				if ( $wp_version <= 2.7 ){
					?><link rel="stylesheet" type="text/css" media="screen" href="<?php echo FT_WPECARD_URL.$css;?>" /><?php
				}else{
					wp_register_style( 'wp_ecards' , FT_WPECARD_URL . $css );					
					wp_enqueue_style( 'wp_ecards' , FT_WPECARD_URL . $css );
				}
			}
		}
		if ( $wp_version <= 2.7 ){
			?><link rel="stylesheet" type="text/css" media="screen" href="<?php bloginfo('home');?>/wp-includes/js/thickbox/thickbox.css" /><?php			
		}else{
			wp_enqueue_style('thickbox');
		}
	}
}

add_action('wp_print_scripts','ft_wpecards_styles');


// adds JS to my header if on a [wpecards] shortcode enabled page/post
function ft_wpecards_scripts(){
	global $wp_query;
	if ( isset($wp_query->queried_object->post_content) ){
		$pos = strpos($wp_query->queried_object->post_content, "[wpecards]");
	}
	if ( (isset($pos)) && ! (FALSE === $pos) ) {  // the === is important; see php docs
    	// set file name
		$js = '/ft_wpecards.js';
		
		if ( file_exists( FT_WPECARD_PATH . $js ) ){
			wp_enqueue_script( 'wp_ecards_js' , FT_WPECARD_URL . $js , array('jquery') );
		}
		
		wp_enqueue_script( 'thickbox' );
	}
}
add_action('wp_print_scripts','ft_wpecards_scripts');


// Flush rewrite rules so that WP will add mine
function ft_wpecards_flush_rewrite_rules() {
   global $wp_rewrite;
   $wp_rewrite->flush_rules();
}
add_action('init', 'ft_wpecards_flush_rewrite_rules');

// Add rewrite rules for topics
function ft_wpecards_viewcard_rewrite_rules( $wp_rewrite ){

	$new_rules = array( 'viewcard/(.*)' => 'index.php?ft_wpecards_view=1&card='.$wp_rewrite->preg_index(1) );
	
	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	//echo "<pre>"; print_r($wp_rewrite->rules); echo "</pre>"; die();
}
add_action( 'generate_rewrite_rules' , 'ft_wpecards_viewcard_rewrite_rules' );

// Register query vars to use in script
function ft_wpecards_register_query_vars($query_vars){
	$query_vars[] = 'ft_wpecards_view';
	$query_vars[] = 'card';
	return $query_vars;
	die();
}
add_filter( 'query_vars' , 'ft_wpecards_register_query_vars' );

function init_wpecards_viewcard(){
	global $wp_query;
	if ( isset($wp_query->query_vars['ft_wpecards_view']) ){
		if ( $ft_wpecards_parentpost = ft_wpecards_get_post_id($wp_query->query_vars['card']) ){
			if ( ft_wpecards_get_post_type($wp_query->query_vars['card']) == 'post' ) {
				query_posts("p=".$ft_wpecards_parentpost."&ft_wpecards_view=1&card=".$wp_query->query_vars['card']);
				include(get_single_template());
				//exit;
			}else{
				query_posts("page_id=".$ft_wpecards_parentpost."&ft_wpecards_view=1&card=".$wp_query->query_vars['card']);
				include(get_page_template());
				//exit;
			}
		}else{
			include(get_404_template());
		}
		exit;
	}
	//die('tr');
}
add_action('template_redirect','init_wpecards_viewcard');

// this function gets the id of the post that generated the ecard being queried
function ft_wpecards_get_post_id( $card_hash ){
	global $wpdb;
	$sql = "SELECT post_id FROM `".$wpdb->prefix."ft_wpecards` WHERE card_hash = '".$wpdb->prepare($card_hash)."'";
	if ( $ft_wpecards_post = $wpdb->get_row($sql) ){
		return $ft_wpecards_post->post_id;
	}
	return false;
}

// this function gets the permalink of the post that generated the ecard being queried
function ft_wpecards_get_permalink( $card_hash ){
	global $wpdb;
	$sql = "SELECT post_id FROM `".$wpdb->prefix."ft_wpecards` WHERE card_hash = '".$wpdb->prepare($card_hash)."'";
	if ( $ft_wpecards_post = $wpdb->get_row($sql) ){
		return get_permalink($ft_wpecards_post->post_id);
	}
}

// this function gets the post_type of the post that generated the ecard being queried
function ft_wpecards_get_post_type( $card_hash ){
	global $wpdb;
	
	if ( $id = ft_wpecards_get_post_id( $card_hash ) ){
		$sql = "SELECT post_type FROM `".$wpdb->prefix."posts` WHERE ID = ".$wpdb->prepare($id);
		if ( $ft_wpecards_post = $wpdb->get_row($sql) ){
			return $ft_wpecards_post->post_type;
		}		
	}
}
?>