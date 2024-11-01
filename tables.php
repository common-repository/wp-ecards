<?php
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

if ( !class_exists( 'FT_WPECards_Tables') ) {
	class FT_WPECards_Tables{
		
		var $cards_table = 'ft_wpecards';
		
		function create_tables(){
			$this->create_cards_table();
		}
		
		function create_cards_table(){
			global $wpdb;
			
			$table_name = $wpdb->prefix.$this->cards_table;
			
			$sql = "CREATE TABLE " . $table_name . " (
			  card_ID bigint(20) NOT NULL AUTO_INCREMENT,
			  sender_name tinytext NOT NULL,
			  sender_email tinytext NOT NULL,
			  recipient_name tinytext NOT NULL,
			  recipient_email tinytext NOT NULL,
			  image_id bigint(20) NOT NULL,
			  message_text text NOT NULL,
			  post_id bigint(20) NOT NULL,
			  card_hash tinytext NOT NULL,
			  UNIQUE KEY card_ID (card_ID)
			);";
			
			dbDelta($sql);			
		}
	}
}

if ( class_exists('FT_WPECards_Tables') ){
	if ( !isset($ft_wpecards_tables) ){
		$ft_wpecards_tables = new FT_WPECards_Tables();
	}
}
?>