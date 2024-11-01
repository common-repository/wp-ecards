<?php
if ( !class_exists('FT_WPECARDS') ){
	class FT_WPECARDS{
		
		// Class Vars
		var $post_id 			= '';
		var $post_permalink		= '';
		var $images 			= array();
		
		var $form_submitted		= false;
		var $card_text 			= '';
		var $selected_image 	= '';
		var $sender_name 		= '';
		var $sender_email 		= '';
		var $recipient_name 	= '';
		var $recipient_email 	= '';
		var $card_hash			= '';
		var $error_messages		= '';
		
		// Class constructor
		function ft_wpecards( $card=NULL ){
			
			$this->set_post_info();

			if ( isset( $_POST['ft_wpecards_submitted'] ) || isset( $_POST['ft_wpecards_card_sent'] ) ){
				$this->form_submitted = true;
				$this->validate_form_submission();
			}
		}
		
		// This method inits the loading of our post vars.
		function set_post_info(){
			$this->set_post_id();
			$this->set_post_permalink();
			$this->set_images();
		}
		
		// This method inits the loading of our form
		function validate_form_submission(){
			if ( !isset($_POST['ft_wpecards_image']) || empty($_POST['ft_wpecards_image'] ) ) { $this->set_error_message( 'image' , 'Please select an image before continuing' ); }	
			if ( !isset($_POST['ft_wpecards_message']) || empty($_POST['ft_wpecards_message'] ) ) { $this->set_error_message( 'message' , 'Please include a personal message before continuing' ); }
			if ( !isset($_POST['ft_wpecards_sname']) || empty($_POST['ft_wpecards_sname'] ) ) { $this->set_error_message( 'sname' , 'Please provide the sender\'s name before continuing' ); }	
			if ( !isset($_POST['ft_wpecards_semail']) || empty($_POST['ft_wpecards_semail'] ) ) { $this->set_error_message( 'semail' , 'Please provide the sender\'s email before continuing' ); }	
			if ( !is_email($_POST['ft_wpecards_semail']) ){ $this->set_error_message( 'semail' , 'The sender\'s email address is not valid. Please correct before continuing' ); }
			if ( !isset($_POST['ft_wpecards_rname']) || empty($_POST['ft_wpecards_rname'] ) ) { $this->set_error_message( 'rname' , 'Please provide the recipient\'s name before continuing' ); }	
			if ( !isset($_POST['ft_wpecards_remail']) || empty($_POST['ft_wpecards_remail'] ) ) { $this->set_error_message( 'remail' , 'Please provide the recipient\'s email before continuing' ); }
			if ( !is_email($_POST['ft_wpecards_remail']) ){ $this->set_error_message( 'remail' , 'The recipient\'s email address is not valid. Please correct before continuing' ); }
		
			if ( $errors = $this->get_error_messages() ) {
				return true;
			}else{
				$this->selected_image = $_POST['ft_wpecards_image'];
				$this->card_text = $_POST['ft_wpecards_message'];
				$this->sender_name = $_POST['ft_wpecards_sname'];
				$this->sender_email = $_POST['ft_wpecards_semail'];
				$this->recipient_name = $_POST['ft_wpecards_rname'];
				$this->recipient_email = $_POST['ft_wpecards_remail'];
				
				if ( isset( $_POST['ft_wpecards_card_sent'] ) ){
					$this->card_hash = $_POST['ft_wpecards_hash'];
					$this->insert_card();
				}else{
					$this->preview_card();
					$this->print_form( true );
				}
			}
		}
		
		// This method inserts the card info in the DB and redirects as needed
		function insert_card(){
			global $wpdb;
			$sql  = "INSERT INTO `".$wpdb->prefix."ft_wpecards` ";
			$sql .= "( sender_name , sender_email , recipient_name , recipient_email , image_id , message_text , post_id , card_hash ) ";
			$sql .= "VALUES ( '".$wpdb->prepare($this->sender_name)."' , '".$wpdb->prepare($this->sender_email)."' , '".$wpdb->prepare($this->recipient_name)."' , '".$wpdb->prepare($this->recipient_email)."' , '".$wpdb->prepare($this->selected_image)."' , '".$wpdb->prepare($this->card_text)."' , '".$wpdb->prepare($this->post_id)."' , '".$wpdb->prepare($this->card_hash)."')";
			if ( $wpdb->query($sql) ){
				$this->send_card();
			}else{
				die($sql);
			}
		}
		
		// This method sends the card
		function send_card(){
			$headers  = "From: " . $this->sender_email . "\r\n" ;
	 		$headers .= "Reply-To: " . $this->sender_email . "\r\n" ;
			$headers .= "X-Mailer: ".get_bloginfo('name');
			
			$to = $this->recipient_email;
			
			$message  = "Hi ".$this->recipient_name.", \r\n";
			$message .= "You have been sent an ecard from ".$this->sender_name.".\r\n";
			$message .= "To view it, visit ".get_bloginfo('home')."/viewcard/".$this->card_hash." \r\n";
			
			$subject = "An Ecard from ".$this->sender_name;
			
			if ( wp_mail($to,$subject,$message,$headers) ){
				echo "<p>Card sent.</p>";
				echo "<p><a href='".$this->post_permalink."'>Send another</a></p>";
			}
		}
		
		// This method loads the requested card based on the hash
		function load_card( $card_hash ){
			global $wpdb;
			$sql = "SELECT * FROM `".$wpdb->prefix."ft_wpecards` WHERE card_hash = '".$wpdb->prepare($card_hash)."'";
			if ( $card = $wpdb->get_row($sql) ){
				$this->post_id = $card->post_id;
				$this->card_text = $card->message_text;
				$this->selected_image = $card->image_id;
				$this->sender_name = $card->sender_name;
				$this->sender_email = $card->sender_email;
				$this->recipient_name = $card->recipient_name;
			}
		}
		
		// This method displays a card to be viewed.
		function view_card(){
			?>
			<div id="ft_wpecard_viewcard">
				<?php 
				if ( file_exists( TEMPLATEPATH . '/wpecards-header.php' ) ){
					include( TEMPLATEPATH . '/wpecards-header.php' );
				}
				if ( file_exists( TEMPLATEPATH . '/wpecards-sidebar-left.php' ) ){
					include( TEMPLATEPATH . '/wpecards-sidebar-left.php' );
				}
				?>
				<div id="ft_wpecards_body">
					<div id="ft_wpecard_vimage">
						<img src="<?php echo wp_get_attachment_url($this->selected_image);?>" />
					</div>
					<div id="ft_wpecard_vmessage">
						<p>To: <?php echo $this->recipient_name;?><br />
						From: <?php echo $this->sender_name;?></p>
						<?php echo stripslashes(apply_filters('the_content', $this->card_text ));?>
					</div>
				</div>
				<?php
				if ( file_exists( TEMPLATEPATH . '/wpecards-sidebar-right.php' ) ){
					include( TEMPLATEPATH . '/wpecards-sidebar-right.php' );
				}
				if ( file_exists( TEMPLATEPATH . '/wpecards-footer.php' ) ){
					include( TEMPLATEPATH . '/wpecards-footer.php' );
				}
				?>
				<div style="clear:left;"></div>
			</div>
			<?php
		}
		
		// This method previews the card after input has been validated
		function preview_card(){
			?>
			<div id="ft_wpecard_viewcard">
				<?php 
				if ( file_exists( TEMPLATEPATH . '/wpecards-header.php' ) ){
					include( TEMPLATEPATH . '/wpecards-header.php' );
				}
				if ( file_exists( TEMPLATEPATH . '/wpecards-sidebar-left.php' ) ){
					include( TEMPLATEPATH . '/wpecards-sidebar-left.php' );
				}
				?>
				<div id="ft_wpecards_body">
					<div id="ft_wpecard_vimage">
						<img src="<?php echo wp_get_attachment_url($this->selected_image);?>" />
					</div>
					<div id="ft_wpecard_vmessage">
						<p>To: <?php echo $this->recipient_name;?><br />
						From: <?php echo $this->sender_name;?></p>
						<?php echo stripslashes(apply_filters('the_content', $this->card_text ));?>
					</div>
				</div>
				<?php
				if ( file_exists( TEMPLATEPATH . '/wpecards-sidebar-right.php' ) ){
					include( TEMPLATEPATH . '/wpecards-sidebar-right.php' );
				}
				if ( file_exists( TEMPLATEPATH . '/wpecards-footer.php' ) ){
					include( TEMPLATEPATH . '/wpecards-footer.php' );
				}
				?>
				<div style="clear:left;"></div>
			
				<div id="ft_wpecard_confirm">
					<form action="<?php echo $this->post_permalink;?>" method="post">
						<input type="hidden" name="ft_wpecars_post" value="<?php echo $this->post_id;?>" />
						<input type="hidden" name="ft_wpecards_image" value="<?php echo $this->selected_image;?>" />
						<input type="hidden" name="ft_wpecards_message" value="<?php echo stripslashes($this->card_text);?>" />
						<input type="hidden" name="ft_wpecards_sname" value="<?php echo stripslashes($this->sender_name);?>" />
						<input type="hidden" name="ft_wpecards_semail" value="<?php echo stripslashes($this->sender_email);?>" />
						<input type="hidden" name="ft_wpecards_rname" value="<?php echo stripslashes($this->recipient_name);?>" />
						<input type="hidden" name="ft_wpecards_remail" value="<?php echo stripslashes($this->recipient_email);?>" />
						<input type="hidden" name="ft_wpecards_hash" value="<?php echo $this->generate_hash(6);?>" />
						<input type="hidden" name="ft_wpecards_card_sent" value="1" />
						<input type="submit" name="ft_wpecards_send_card" value="Send Card" /> <a href="" id="ft_wpecards_edit_card">edit card</a>
					</form>
				</div>
			</div>
			<?php
		}
		
		// This method generates a random string
		function generate_hash($length) {
			global $wpdb;
			$random= "";
			srand((double)microtime()*1000000);
			$char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$char_list .= "abcdefghijklmnopqrstuvwxyz";
			$char_list .= "1234567890";

			// Add the special characters to $char_list if needed
			for($i = 0; $i < $length; $i++) {
				$random .= substr($char_list,(rand()%(strlen($char_list))), 1);
			}
			
			$sql = "SELECT card_hash FROM `".$wpdb->prefix."ft_wpecards` WHERE card_hash = '".$random."'";
			if ( $testers = $wpdb->get_row($sql) ){
				if ( $testers->card_hash == $random ) {
					$this->generate_hash(6);
				}
			}
			return $random;
		} 
		
		// This method sets the post ID
		function set_post_id(){
			global $post;
			$this->post_id = $post->ID;
		}
		
		// This method sets the post permalink
		function set_post_permalink(){
			$this->post_permalink = get_permalink($this->post_id);
		}		
		
		// This method sets the posts images
		function set_images(){
			global $wpdb;
			if ( $images = get_children( 'post_type=attachment&post_mime_type=image&post_parent=' . (int) $this->post_id.'&orderby=menu_order&order=ASC' ) ){
				$this->images = $images;
			}
			return false;	
		}
		
		// This method gets returns the images array
		function get_images(){
			$images = $this->images;
			if ( empty($images) || !is_array($images ) ) {
				return false;
			}
			return $images;
		}		
		
		// This method sets / updates error messages
		function set_error_message( $field , $message ){
			$array = $this->get_error_messages();
			$array[$field] = $message;
			$this->error_messages = $array;
		}
		
		// This method gets returns the error messages array
		function get_error_messages(){
			$messages = $this->error_messages;
			return $messages;
		}
		
		// This method prints any errors
		function print_errors(){
			$messages = $this->get_error_messages();
			if ( is_array($messages) ) {
				?><ul id="ft_wpecards_error_list"><?php
				foreach( $messages as $key => $value ){
					?><li class='ft_wpecards_error_item'><?php echo $value; ?></li><?php
				}
				?></ul><?php
			}
		}
		
		// This methods prints the form
		function print_form( $is_preview=false ){
			global $post;
			if ( $is_preview ){ echo "<div id='ft_wpecards_previewing_card' style='display:none;'>";}
			?>
			<form method="post" action="<?php $this->post_permalink;?>" />
				<p class='ft_wpecards_image_instructions'>Select an image</p>
				<ul id="ft_wpecards_image_select_list">
					<?php
					if ( $images = $this->get_images() ) {
						//print_r($images);die();
						foreach ( $images as $key => $value ){
							?><li class="ft_wpecards_image_select_item"><a href="<?php echo wp_get_attachment_url($value->ID);?>" class='thickbox' rel="<?php echo 'wp_ecard_'.$post->ID;?>"><?php echo wp_get_attachment_image($value->ID, array(75,75), false); ?></a><span><input type="radio" name="ft_wpecards_image" value="<?php echo $value->ID; ?>" <?php $this->is_image_selected( $value->ID ); ?>/></span></li><?php
						}	
					}else{
						?><li class="ft_wpecards_image_select_error">No images exist for this page.</li><?php
					}
					?>
				</ul>
				
				<div id="ft_wpecards_sender_recipient_info">
					<ul id="ft_wpecards_sender">
						<li class="ft_wpecards_sname_item">
							<label for="ft_wpecards_sname"><span class="ft_wpecards_sname_label">Sender's Name</span></label>
							<input class="ft_wpecards_sname_field" name="ft_wpecards_sname" value="<?php $this->ft_wpecards_value( 'sname' ); ?>" />
						</li>
						<li class="ft_wpecards_semail_item">
							<label for="ft_wpecards_semail"><span class="ft_wpecards_semail_label">Sender's Email</span></label>
							<input class="ft_wpecards_semail_field" name="ft_wpecards_semail" value="<?php $this->ft_wpecards_value( 'semail' ); ?>" />
						</li>				
					</ul>
					
					<ul id="ft_wpecards_recipient">
						<li class="ft_wpecards_rname_item">
							<label for="ft_wpecards_rname" ><span class="ft_wpecards_rname_label">Recipient's Name</span></label>
							<input class="ft_wpecards_rname_field" name="ft_wpecards_rname" value="<?php $this->ft_wpecards_value( 'rname' ); ?>" />
						</li>
						<li class="ft_wpecards_remail_item">
							<label for="ft_wpecards_remail"><span class="ft_wpecards_remail_label">Recipient's Email</span></label>
							<input class="ft_wpecards_remail_field" name="ft_wpecards_remail" value="<?php $this->ft_wpecards_value( 'remail' ); ?>" />
						</li>				
					</ul>
				</div>
				
				<div id='ft_wpecards_message_group'>
					<p class='ft_wpecards_message_instructions'>Please type a message</p>
					<textarea id="ft_wpecards_message" name="ft_wpecards_message"><?php stripslashes($this->ft_wpecards_value( 'message' )); ?></textarea>
					<input type="hidden" name="ft_wpecards_submitted" value="1" />
					<p><input type="submit" id="ft_wpecards_submit_name="ft_wpecards_submit" value="Preview" /></p>
				</div>
			</form>
			<?php
			if ( $is_preview ){ echo "</div>";}			
		}
		
		// This method prints 'checked' if current image is in POST
		function is_image_selected( $id ){
			if ( isset($_POST['ft_wpecards_image']) && $id === $_POST['ft_wpecards_image']) {
				echo 'checked ';
			}
		}
		
		// This method prints the value of the field if it is in POST
		function ft_wpecards_value( $field ){
			if ( isset($_POST['ft_wpecards_' . $field]) ){
				echo stripslashes($_POST['ft_wpecards_' . $field]);
			}
		}
	}
}
?>