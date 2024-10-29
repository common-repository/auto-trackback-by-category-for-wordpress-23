<?php
/*
Plugin Name: Auto-TrackBack by Category for WordPress 2.3
Plugin URI: http://www.dohc.sytes.net/archives/769
Description: Enables authors to associate trackback URI's to WordPress categories, and WordPress will autofill the URI into the trackback field for any posts in that category.
Version: 1.1
Author: Paul Young(Original) / Inoue DAISUKE(Update for WordPress 2.3)
Author URI: http://www.dohc.sytes.net
*/

/*  Copyright 2004  Paul Young  (email: pt.young@gmail.com)
              2007  and Inoue DAISUKE, update for WordPress 2.3

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

/*

Many thanks to Keith McDuffee @ http://www.gudlyf.com/ whose Category Visibility 
plugin provided much of the framework for this plugin.
(from Paul Young)
*/

/*
Thank you, Mr. Paul Young.
Original of this plugin is very useful.
I can't stand left not working with WordPress 2.3.
(from Inoue DAISUKE)
*/

function atc_menu () {

	add_submenu_page('edit.php', 'Auto-TrackBack by Category', 'Auto-TrackBack by Category', 9, basename(__FILE__), 'atc_category_trackbacks');

}

function atc_category_trackbacks() {  
    
  global $wpdb;    	

	if($wpdb->query("CREATE TABLE IF NOT EXISTS wp_cat_tracks (			
			cat_ID bigint(20) UNIQUE NOT NULL,
			uri longtext NOT NULL
			)"));	

	if ($_POST["action"] == "atc_cat_tracks_edit"): 

?>

	<div class="updated"><p><?php echo "Auto-TrackBack URI's Updated."; ?></p></div>

<?php endif; ?>

	<div class="wrap">

	<h2><?php _e('Auto-TrackBack URI\'s by Category') ?> </h2>
	
	<form name="atc_cat_tracks" id="atc_cat_tracks" action="edit.php?page=<?php echo basename(__FILE__); ?>" method="post">
	
	<table width="100%" cellpadding="3" cellspacing="3">
		<tr>
			<th scope="col"><?php _e('ID') ?></th>
	        <th scope="col"><?php _e('Name') ?></th>        
	        <th scope="col"><?php _e('<label for="trackback"> <a href="http://wordpress.org/docs/reference/post/#trackback" title="Help on trackbacks"><strong>TrackBack</strong> <abbr title="Universal Resource Identifier">URI\'s</abbr></a>:</label> (Separate multiple <abbr title="Universal Resource Identifier">URI</abbr>s with spaces.)') ?></th>        
		</tr>

<?php
	if ($_POST["action"] == "atc_cat_tracks_edit") {
		atc_cat_tracks_edit();
	}	
	
	atc_cat_rows();

?>

</table>

<p>Note: To setup <strong>global</strong> trackback pings for all categories, such as Ping-O-Matic, use "Update Services" under the Options -> Writing sub-tab.</p>

<p class="submit"><input type="hidden" name="action" value="atc_cat_tracks_edit" /><input name="savecattracks" type="submit" id="savecattracks" value="Save Category Auto-TrackBack URI's &raquo;" /></p>
</form>
</div>

<?php
	
}

// Modified version of the cat_rows function found in admin-functions.php
function atc_cat_rows($parent = 0, $level = 0, $categories = 0) {
	global $wpdb, $class, $user_level;
	if (!$categories)
		$categories = get_categories( 'hide_empty=0' );

	if ( $categories ) {
		foreach ($categories as $category) {
			if ($category->parent == $parent) {
				$category->name = wp_specialchars($category->name);
				$cat_tracks = $wpdb->get_results("SELECT * FROM wp_cat_tracks WHERE cat_ID = $category->term_id");
				$cat_tracks = $cat_tracks[0];
				$pad = str_repeat('&#8212; ', $level);
				
				if ( $user_level > 3 )
					$edit = "<input type='text' name='trackback_uri[$category->term_id]' value='$cat_tracks->uri' style='width: 360px' /><br />\n";
				else
					$edit = '';
				
				$class = ('alternate' == $class) ? '' : 'alternate';
				
				echo "<tr class='$class'><th scope='row'>$category->term_id</th><td>$pad $category->name</td><td>$edit</td></tr>";
				
				atc_cat_rows($category->cat_ID, $level + 1, $categories);
			}
		}
	} else {
		return false;
	}
}

function atc_cat_tracks_edit() {

	global $wpdb;

  foreach ($_POST['trackback_uri'] as $term_ID => $uri_proper) {					
		$wpdb->query("REPLACE INTO wp_cat_tracks SET cat_ID=$term_ID, uri='$uri_proper'");
		}
}


function atc_cat_tracks_backfill($post_ID) {	
	
	global $wpdb;
		
	$atc_post_cats = wp_get_post_cats(1,$post_ID);
	
	foreach($atc_post_cats as $term_ID) {		
		$auto_uri .= $wpdb->get_var("SELECT uri FROM wp_cat_tracks WHERE cat_ID=$term_ID") . " ";			
	}		
	$old_uri = $wpdb->get_var("SELECT to_ping FROM wp_posts WHERE ID=$post_ID");
	$the_uri = "$old_uri $auto_uri";
	$wpdb->query("UPDATE wp_posts SET to_ping='$the_uri' WHERE ID=$post_ID");		
	return $post_ID;
}


add_action('save_post', 'atc_cat_tracks_backfill', 9);
// add_action('edit_post', 'atc_cat_tracks_backfill', 9);
// add_action('publish_post', 'atc_cat_tracks_backfill', 9);
add_action('admin_menu', 'atc_menu');

?>