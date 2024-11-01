<?php
/**
 * Plugin Name: yBlog Stats
 * Plugin URI:  http://www.blog-tips.de
 * Text Domain: yBlog Stats
 * Domain Path: /languages
 * Description: This plugin shows a counter of pages/posts, all over words and comments in a widget.
 * Version:	    0.1.5
 * Author:      M. Roersch
 * Author URI:  http://www.blog-tips.de
 * License:     GPLv3
 */

/**
License:
==============================================================================
Copyright 2012 M. Roersch  (email : plugins@blog-tips.de)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/	   

// Plugin Activation

register_activation_hook(__FILE__,'yblogstats_db_create');

function activate (){
$wpost = "Posts in diesem Blog: ";
$wpage = "Pages in diesem Blog: ";
$wcomments = "Kommentare in diesem Blog: ";
$wwords = "Anzahl der geschriebenen Wörter im Blog: ";
$spost = '1';
$spage = '1';
$scomments = '1';
$swords = '1';
$yvisits = '0';
$yimps = '0';
$svisits = '0';
$simps = '0';

update_option('wpost', $wpost, '', 'no' );
update_option('wpage', $wpage, '', 'no' );
update_option('wcomments', $wcomments, '', 'no' );
update_option('wwords', $wwords, '', 'no' );
update_option('spost', $spost, '', 'no' );
update_option('spage', $spage, '', 'no' );
update_option('scomments', $somments, '', 'no' );
update_option('swords', $swords, '', 'no' );
update_option('yimps', $yimps, '', 'no' );
update_option('yvisits', $ytime, '', 'no' );
update_option('simps', $simps, '', 'no' );
update_option('svisits', $svisits, '', 'no' );
}

// Creating Database

global $yblogstats_db_version;
$yblogstats_db_version = "1.0";

function yblogstats_db_create() {
    yblogstats_db_create_yblog();
}

function yblogstats_db_create_yblog(){
    global $wpdb;
    $table_name = $wpdb->prefix . "yblog";
    global $yblogstats_db_version;
    $installed_ver = get_option( "yblogstats_db_version" );
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name
            ||  $installed_ver != $yblogstats_db_version ) {
        $sql = "CREATE TABLE " . $table_name . " (
        	  id int(11) NOT NULL auto_increment,
              timevis varchar(255) default NULL,
              ip varchar(255) default NULL,
              KEY ip (ip),
              KEY time (timevis),
              UNIQUE KEY id (id)
            );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option( "yblogstats_db_version", $yblogstats_db_version );
}
    add_option("yblogstats_db_version", $yblogstats_db_version);
}

// Counting Impressions

function counting_imp() {
	global $wpdb;
	$table_name = $wpdb->prefix . "yblog";
		$ipnorm = $_SERVER['REMOTE_ADDR'];
		$ip = substr($ipnorm, 0,-3);
		$timestamp = $_SERVER['REQUEST_TIME'];
		$insert = $wpdb->insert( $wpdb->prefix . "yblog", array( 'timevis' => $timestamp, 'ip' => $ip ) );
		$impressions = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name;" ) );
		return $impressions;
}

// Counting Unique Visits

function counting_uniqv() {
	global $wpdb;
	$table_name = $wpdb->prefix . "yblog";
		$dbvisits = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT ip) FROM $table_name;" ) );
		return $dbvisits;
}

// Counting Words

function wcount() {
    global $wpdb;
		$now = gmdate("Y-m-d H:i:s",time());
        $query = "SELECT post_content FROM $wpdb->posts WHERE post_status = 'publish' AND post_date < '$now'";
		$words = $wpdb->get_results($query);
	if ($words) {
    	foreach ($words as $word) {
        	$post = strip_tags($word->post_content);
        	$post = explode(' ', $post);
        	$count = count($post);
        	$totalcount = $count + $oldcount;
        	$oldcount = $totalcount;
    	}
	}
	 
	else {
    	$totalcount=0;
	}
	return number_format($totalcount);
}

// Widget

class Show_Counter extends WP_Widget { 

	public function __construct() {
		parent::__construct(
	 		'Show_Counter', 
			'yBlog Stats', 
			array( 'description' => __( 'yBlog Stats', 'text_domain' ), )
		);
	}

	public function widget( $args, $instance ) {
		extract( $args );
		
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		
		echo $before_title . $title . $after_title;
		?>

		<?php
			if(get_option('spost') == '1'){
				echo get_option('wpost');
				$published_posts = wp_count_posts();
				echo $published_posts->publish . "<br/>";
			}

			if(get_option('spage') == '1'){
				echo get_option('wpage'); 
				$count_pages = wp_count_posts('page');
				echo $count_pages->publish . "<br/>";
			}
		
			if(get_option('scomments') == '1'){
				echo get_option('wcomments');
				$comments_count = wp_count_comments();
		 		echo $comments_count->approved . "<br/>";
		 	}		 	

			if(get_option('swords') == '1'){ 
				echo get_option('wwords'); 
				echo wcount() . "<br/>";
			} 

			if(get_option('simps') == '1'){
				echo get_option('yimps');
				echo counting_imp() . "<br/>";
			}

			if(get_option('ssvisits') == '1'){
				echo get_option('yvisits');
				echo counting_uniqv() . "<br/>";
			}
		?>
		
		<?php
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}

} 

// ADMIN Backend

add_action('admin_menu', 'register_my_custom_submenu_page');

add_action( 'widgets_init', create_function( '', 'register_widget( "Show_Counter" );' ) );

function register_my_custom_submenu_page() {
	add_submenu_page( 'options-general.php', 'yBlog Stats', 'yBlog Stats', 'manage_options', 'yBlog-Stats', 'my_custom_submenu_page_callback' ); 
}

function my_custom_submenu_page_callback() {

if(isset($_POST['Save']))  {

        $text_saved1 = $_POST['a'];
        $text_saved2 = $_POST['b'];
        $text_saved3 = $_POST['c'];
        $text_saved4 = $_POST['d'];
        $text_saved5 = $_POST['sa'];
        $text_saved6 = $_POST['sb'];
        $text_saved7 = $_POST['sc'];
        $text_saved8 = $_POST['sd'];
        $text_saved9 = $_POST['se'];
        $text_saved10 = $_POST['sf'];
        $text_saved11 = $_POST['e'];
        $text_saved12 = $_POST['f'];

        update_option( 'wpost', $text_saved1 );
        update_option( 'wpage', $text_saved2 );
        update_option( 'wcomments', $text_saved3 );
        update_option( 'wwords', $text_saved4 );
        update_option( 'spost', $text_saved5 );
        update_option( 'spage', $text_saved6 );
        update_option( 'scomments', $text_saved7 );
        update_option( 'swords', $text_saved8 );
        update_option( 'simps', $text_saved9 );
        update_option( 'ssvisits', $text_saved10 );
        update_option( 'yimps', $text_saved11 );
        update_option( 'yvisits', $text_saved12 );

?>
<div class="updated"><p><strong><?php _e('Options saved.', 'my_custom_submenu_page_callback'); ?></strong></p></div>
<?php  }  ?>

<div class="wrap" style="margin-left:5px; padding:1px; border: thin solid black; width: 640px;">
		<h2 style="border-bottom:1px solid black; color: #900; background:#eee; padding:5px 10px;">yBlog Stats - Settings</h2><br />
			<form method="post" name="options" target="_self">
			<div style="margin-left: 10px; padding: 1px;">
			For <strong>Support</strong> or other <strong>Questions</strong> visit <a href="http://www.blog-tips.de/" target="_blank"><strong>Blog Tips</strong></a> or email me to 'plugins (at) blog-tips.de'!<br>
			<br>
			<strong>Thanks</strong> for using this tiny Plugin!
			</div>
  		<h3 style="border-bottom:1px solid black; color: #900; background:#eee; padding:5px 10px;"><strong>Display Options:</strong></h3>
  			<div style="margin-left: 7px; padding: 1px;">
    		<input type="checkbox" name="sa" value="1" <?php if (get_option('spost')==1) echo 'checked="checked"'; ?> /> Show Post Count <br />
    		<input type="checkbox" name="sb" value="1" <?php if (get_option('spage')==1) echo 'checked="checked"'; ?> /> Show Page Count <br />
    		<input type="checkbox" name="sc" value="1" <?php if (get_option('scomments')==1) echo 'checked="checked"'; ?> /> Show Comment Count <br />
    		<input type="checkbox" name="sd" value="1" <?php if (get_option('swords')==1) echo 'checked="checked"' ; ?> /> Show Word Count <br />
    		<input type="checkbox" name="se" value="1" <?php if (get_option('simps')==1) echo 'checked="checked"'; ?> /> Show Site Impressions Count <br />
    		<input type="checkbox" name="sf" value="1" <?php if (get_option('ssvisits')==1) echo 'checked="checked"' ; ?> /> Show Site Visits Count <br />
    		</div>
    	<h3 style="border-bottom:1px solid black; color: #900; background:#eee; padding:5px 10px;"><strong>Text Options:</strong></h3>
    		<div style="margin-left: 7px; padding: 1px;">
    		<input type="text" size="60" name="a" value="<?php echo get_option('wpost') ?>" $a/> Text: Post<br />
    		<input type="text" size="60" name="b" value="<?php echo get_option('wpage') ?>" $b/> Text: Page<br />
    		<input type="text" size="60" name="c" value="<?php echo get_option('wcomments') ?>" $c/> Text: Comments<br />
    		<input type="text" size="60" name="d" value="<?php echo get_option('wwords') ?>" $d/> Text: Word<br />
    		<input type="text" size="60" name="e" value="<?php echo get_option('yimps') ?>" $c/> Text: Impressions<br />
    		<input type="text" size="60" name="f" value="<?php echo get_option('yvisits') ?>" $d/> Text: Visits<br />
    		<p class="submit">
    		<input type="submit" name="Save" value="Update Settings" />
    		</p>
    		</div>
    	<h2 style="border-top:1px solid black; color: #900; background:#eee; padding:10px 5px; text-align: right;">
    	<a href="https://twitter.com/BlogTipsDE" class="twitter-follow-button" data-show-count="false" data-size="small">Follow @BlogTipsDE</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</h2>
			</form>
</div>

<?php 
}
?>