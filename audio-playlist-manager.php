<?php
/*
 * Plugin Name: Tierra Audio Playlist Manager
 * Plugin URI: http://tierra-innovation.com/wordpress-cms/2009/10/16/audio-playlist-manager/
 * Description: Create, manage and embed MP3 playlists within the WordPress admin panel. Playlists can be embedded using the included swf player or played via third-party <a target="_blank" href="http://xspf.xiph.org/applications/">XSPF-compatible music players</a>.
 * Version: 2.2
 * Author: Tierra Innovation
 * Author URI: http://www.tierra-innovation.com/
 */

/*
 * This is a modified version (under the MIT License) of a plugin
 * originally developed by Tierra Innovation for WNET.org.
 * 
 * This plugin is currently available for use in all personal
 * or commercial projects under both MIT and GPL licenses. This
 * means that you can choose the license that best suits your
 * project, and use it accordingly.
 *
 * MIT License: http://www.tierra-innovation.com/license/MIT-LICENSE.txt
 * GPL2 License: http://www.tierra-innovation.com/license/GPL-LICENSE.txt
 */

/*

Changes:
 2.2 	- Fixed widget randomize bug
 		- Fixed syntax error on certain browsers with output
 2.1 	- Fixed RSS 'array' bug introduced with WP 3.0
 2.0 	- Added widget support
		- Fixed loading bug upon activation in WP 3.0.1
		- Fixed plugin to work correctly in WP 3.0.1
		- Fixed rendering bug on file upload if max_file_size is less than file attempted to upload
 1.1.0 	- Added "randomize" to playlist shortcode for default player
		- Added loading indicator to default player
		- Fixed bug in player causing multiple instances of track to play
		- Changed preview to use text area for shortcode selection
 1.0.9  - Fixed bug in admin playlist display reported to affect IE users
 1.0.8 	- Fixed bug in player that could lead to simultaneous sound playing
		- Added logo to admin page.
 1.0.7 	- Fixed glitches in Admin UI lightbox
		- Updated ti-player.swf to better accomodate long album, artist and track names
		- Removed visual glitch when ti-player.swf is launched without autoplay (Player would expand and shrink upon load)
		- Fixed error thrown when selecting tracks from right-click menu while player was paused
 1.0.6 Ð Changed Roles & Capabilities user level code to check against edit_others_posts per some users permissions issues.
 1.0.5 Ð Fixed the template embed code to render the player inside a theme.
 1.0.4 - Changed embed code to increase compatibility with older XSPF players
 1.0.3 - Added to Plugins menu, changed default permissions required
*/

// This is the minimum level required to perform many of the functions within this plugin. Uploading still requires level 7
define( 'TI_APM_LEVEL_REQUIRED', 4);

	
@ini_set('upload_max_size','100M');
@ini_set('post_max_size','105M');
@ini_set('max_execution_time','300');

require_once (ABSPATH . WPINC . '/pluggable.php');
global $userdata;
get_currentuserinfo();

	
wp_enqueue_script('thickbox');
wp_enqueue_script ('ac_run_active_content', WP_PLUGIN_URL . "/tierra-audio-playlist-manager/js/AC_RunActiveContent.js");
wp_enqueue_style('thickbox');


// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', get_option('siteurl') . '/wp-content/plugins' );


// Height and width of the preview Thickbox
$ti_apm_prev_height = 330;
$ti_apm_prev_width = 500;



// module globals
$_audio_playlist_manager_db_version = 2.2;

// these need to be declared global so they are in scope for the activation hook
global  $wpdb, $_audio_playlist_manager_db_version, $_audio_playlist_manager, $ti_apm_base_query, $userdata,  $ti_apm_prev_width, $ti_apm_prev_height;



$_audio_playlist_manager = $wpdb->prefix . "ti_audio_playlist_manager";



$ti_apm_base_query =  $_SERVER["QUERY_STRING"];

// installer
register_activation_hook(__FILE__, '_ti_apm_install');



if (isset($_GET['action']))	{
	
	if (isset ($_GET['ti_apm_playlist_id']) && $_GET['action'] == 'ti_apm_view_playlist')	{
		
		ti_apm_return_playlist_html(intval($_GET['ti_apm_playlist_id']));
		exit;
	}

	//ti_apm_return_playlist_xml
	
	if (isset ($_GET['ti_apm_playlist_id']) && $_GET['action'] == 'xml')	{
		ti_apm_return_playlist_xml(intval($_GET['ti_apm_playlist_id']));
		exit;
	}	
	
	if (isset ($_GET['ti_apm_playlist_id']) && $_GET['action'] == 'ti_apm_view_player')	{
		ti_apm_print_player(intval($_GET['ti_apm_playlist_id']));
		exit;
	}	
	
	
	
	if ($_GET['action'] == 'ti_apm_get_playlist_options')	{
		ti_apm_return_playlist_options();
		exit;
	}
	if ($_GET['action'] == 'ti_apm_add_playlist' && isset($_GET['playlist_title']))	{
		ti_apm_create_new_playlist($_GET['playlist_title']);
		exit;
	}
	
	//ti_apm_delete_playlist
	if ($_GET['action'] == 'ti_apm_delete_playlist' && isset($_GET['ti_apm_playlist_id']))	{
		ti_apm_delete_current_playlist($_GET['ti_apm_playlist_id']);
		exit;
	}

	if ($_GET['action'] == 'ti_apm_add_tracks_to_playlist' && isset($_GET['tracks']) && isset($_GET['ti_apm_playlist_id']))	{
		ti_apm_add_tracks_to_playlist($_GET['tracks'], $_GET['ti_apm_playlist_id']);
		exit;
	}

	// Both ti_apm_reorder_playlist and ti_apm_remove_from_playlist simply replace the existing playlist with a new one provided within
	// the tracks property. Thus, they function identically.
	if ($_GET['action'] == 'ti_apm_remove_from_playlist' && isset($_GET['tracks']) && isset($_GET['ti_apm_playlist_id']))	{
		ti_apm_return_tracks_in_playlist($_GET['tracks'], $_GET['ti_apm_playlist_id']);
		exit;
	}

	// ti_apm_reorder_playlist
	if ($_GET['action'] == 'ti_apm_reorder_playlist' && isset($_GET['tracks']) && isset($_GET['ti_apm_playlist_id']))	{
		ti_apm_return_tracks_in_playlist($_GET['tracks'], $_GET['ti_apm_playlist_id']);
		exit;
	}

}
				

function _ti_apm_install() {

	global $wpdb, $_audio_playlist_manager_db_version, $_audio_playlist_manager;
	
	if ($wpdb->get_var("SHOW TABLES LIKE '$_audio_playlist_manager'") != $_audio_playlist_manager) {
			
			$sql = "CREATE TABLE $_audio_playlist_manager (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`title` varchar(64) DEFAULT NULL,
					`description` varchar(255) DEFAULT NULL,
					`image` varchar(255) DEFAULT NULL,
					`random` int(1) DEFAULT NULL,
					`autostart` int(1) DEFAULT NULL,
					`license` text,
					`tracks` text,
					`modification_date` datetime DEFAULT NULL,
					`creation_date` datetime DEFAULT NULL,
					`last_play_date` datetime DEFAULT NULL,
					`views` int(11) DEFAULT NULL,
					UNIQUE KEY `id` (`id`),
					UNIQUE KEY `title` (`title`)
			)";


		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option("audio_playlist_manager_db_version", $_audio_playlist_manager_db_version);
		
		
	}
}




function ti_apm_test_for_activation() {
	global $wpdb, $_audio_playlist_manager;

	$tables = array($_audio_playlist_manager);
	
	$ok = true;
	foreach ($tables as $table) {
		if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
			$ok = false;
			break;
		}
	}
	
	// 3.0 will choke if we output anything before the activation is complete.
	if (!$ok && get_bloginfo('version') < '3.0')	{
		echo get_bloginfo('version') . "<p><strong>Plugin Check:</strong> <span style='color: red;'>It looks like this plugin did not fully activate.  Please <a href='plugins.php'>click here</a> and toggle the plugin off and on to redo the activation.</span></p>";
	}
}

ti_apm_test_for_activation();

// set admin screen
function ti_apm_modify_audio_menu() {

	add_options_page(
		'Tierra Audio Playlist Manager', // page title
		'Tierra Audio Playlists', // sub-menu title
		'manage_options', // access/capa
		'audio-playlist-manager.php', // file
		'ti_apm_admin_audio_options' // function
	);
	
	add_management_page(
		'Tierra Audio Playlist Manager', // page title
		'Tierra Audio Playlists', // sub-menu title
		'edit_others_posts', // access/capa
		'audio-playlist-manager.php', // file
		'ti_apm_admin_audio_options' // function
	);
}

add_shortcode('ti_audio', 'ti_apm_print_player');
add_action('admin_menu', 'ti_apm_modify_audio_menu');

add_action('the_excerpt_rss', 'ti_apm_remove_shortcode_from_rss');
add_action('the_content_rss', 'ti_apm_remove_shortcode_from_rss');


register_sidebar_widget(__('Tierra Audio Playlist Player'), 'ti_apm_widget_output');
register_widget_control(__('Tierra Audio Playlist Player'), 'ti_apm_widget_output_control', 250, 200 );

function ti_apm_widget_output($args) {
  extract($args);
  $opts = get_option ("ti_apm_widget_settings");
  echo $before_widget;
  echo $before_title;
  echo $opts['title'];
  echo $after_title;
 
	$array =  array(
								name=>$opts['title'],
								//id => intval($opts['id']),
								height=>intval($opts['height']) >= 120 ? intval($opts['height'] ) : 32,
								width=>	intval($opts['width']) >= 120 ? intval($opts['width'] ) : 160,
								volume=> intval($opts['volume']) <= 100 ? intval($opts['volume']) : 100,
								autoplay=> $opts['autoplay'],
								repeat=> $opts['repeat'],
								randomize=> $opts['random'],
							);
	if (strlen ($opts['skin']) > 4)	{
		$array['skin'] = $opts['skin'];
	}

 
  echo ti_apm_print_player(	$array);
  echo $after_widget;
}
	
function ti_apm_widget_output_control() {
	global $wpdb;
	//update_option ("ti_apm_widget_settings", array('autoplay' => true, 'repeat'=>true, 'random' => true, title=> 'Test', id=>'200'));


	if (intval( $_POST['ti_apm_widget_controller_submit'] ) == 1)	{
		$ap = isset($_POST['ti_apm_autoplay_checkbox']) ? ti_apm_admin_booleanValue($_POST['ti_apm_autoplay_checkbox']) : false;
		$repeat = isset($_POST['ti_apm_repeat_checkbox']) ? ti_apm_admin_booleanValue($_POST['ti_apm_repeat_checkbox']) : false;
		$random = isset($_POST['ti_apm_random_checkbox']) ? ti_apm_admin_booleanValue($_POST['ti_apm_random_checkbox']) : false;
		$title = $wpdb->escape($_POST['ti_apm_playlist_name']) ? $wpdb->escape($_POST['ti_apm_playlist_name']) : "Unknown";
		$skin = $wpdb->escape($_POST['ti_apm_playlist_skin']) ? $wpdb->escape($_POST['ti_apm_playlist_skin']) : null;
		//$id = intval($wpdb->escape($_POST['ti_apm_playlist_id'])) > 0 ? intval($wpdb->escape($_POST['ti_apm_playlist_id'])) : '';
	
		$volume = (intval($wpdb->escape($_POST['ti_apm_playlist_volume'])) <= 100) && (intval($wpdb->escape($_POST['ti_apm_playlist_volume'])) >= 0)? intval($wpdb->escape($_POST['ti_apm_playlist_volume'])) : 100;
		$width = intval($wpdb->escape($_POST['ti_apm_playlist_width'])) >= 120 ? intval($wpdb->escape($_POST['ti_apm_playlist_width'])) : 160;
		
		$height = intval($wpdb->escape($_POST['ti_apm_playlist_height'])) >= 1 ? intval($wpdb->escape($_POST['ti_apm_playlist_height'])) : 32;
	
		$array =  array('autoplay' => $ap, 'repeat'=>$repeat, 'random' => $random, 'title'=> $title, 'id'=>$id, 'volume'=>$volume, 'width'=>$width, 'height'=>$height, 'skin'=>$skin);
	
		update_option ("ti_apm_widget_settings", $array);
	}
	$opts = get_option ("ti_apm_widget_settings");
 
	$w = $opts['width'] ? $opts['width'] : 160;
	$h = $opts['height'] ? $opts['height'] : 32;

	$cb_auto = ($opts[autoplay] == true) ? "CHECKED" : "";
	$cb_repeat = ($opts[repeat] == true) ? "CHECKED" : "";
	$cb_random = ($opts[random] == true) ? "CHECKED" : "";
  
	 print <<<__END_OF_FORM__
		<input type='hidden' id='ti_apm_widget_controller_submit' name='ti_apm_widget_controller_submit'  value='1'/>
		<p>Playlist name <input type='text' id='ti_apm_playlist_name' name='ti_apm_playlist_name' value ='$opts[title]' />
		<p>Player size: <input type='text'  size="3" id='ti_apm_playlist_width' name='ti_apm_playlist_width'  value ='$w' />
		x <input type='text'  size="3" id='ti_apm_playlist_height' name='ti_apm_playlist_height'  value ='$h' /> pixels</p>

		<p><input type='checkbox' name='ti_apm_autoplay_checkbox' id='ti_apm_autoplay_checkbox' $cb_auto /> Begin playing automatically. </p>
		<p><input type='checkbox' name='ti_apm_repeat_checkbox' id='ti_apm_repeat_checkbox'  $cb_repeat/> Repeat when playlist ends. </p> 
		<p><input type='checkbox' name='ti_apm_random_checkbox' id='ti_apm_random_checkbox'  $cb_random/> Create random order playlist. </p>
		<p>Initial volume (0-100): <input type='text'  size="3" id='ti_apm_playlist_volume' name='ti_apm_playlist_volume'  value ='$opts[volume]' /></p>
		<p><br></p>
		<p>Custom XSPF player SWF, if desired:<br/>
		<input type='text' size='40' id='ti_apm_playlist_skin' name='ti_apm_playlist_skin' value ='$opts[skin]' />
__END_OF_FORM__
; 
	
	
}

	
function ti_apm_admin_booleanValue($val, $defaultValue = false) {
	if (!isset($val))	{
		$val = $defaultValue;
	}
	$isBoolean = 0;
	switch ( strtolower($val) )	{
		case "1":
		case "true":
		case "yes":
		case "ok":
		case "y":
		case "on":
		case "enabled":
			$isBoolean = 1;
		break;
	}
	return $isBoolean;  
}


function ti_apm_admin_audio_options() {


	// This allows us to perform the basic functions...
	if (isset($_GET['action']))	{
		$action = strtolower($_GET['action']);
		switch ($action)	{
			case 'edit'	:
				if (isset($_GET['asset_id']))	{
					ti_apm_edit_existing_asset(intval($_GET['asset_id']));
				}
				break;
			
			case 'update'	:
				if (isset($_GET['asset_id']))	{
					ti_apm_update_existing_asset(intval($_GET['asset_id']));
					ti_apm_print_audio_form();
				}
				break;


			default:
				break;
			
			
		}

		
	}	else{
	
		if ( isset($_FILES['file']))	{
			ti_apm_upload_files();
		}
	
		ti_apm_print_audio_form();
	}
}


function ti_apm_return_tracks_in_playlist($tracks, $ti_apm_playlist_id)	{
	
	ti_apm_check_permissions();

	global $_audio_playlist_manager, $wpdb, $ti_apm_base_query;

	$sql = "UPDATE $_audio_playlist_manager set tracks =\"" . $wpdb->escape($tracks) . "\" where id = \"" . $wpdb->escape($ti_apm_playlist_id) ."\"";
	
	$result = $wpdb->query($sql);

	if ($result >= 0)	{
		ti_apm_return_playlist_html($wpdb->escape($ti_apm_playlist_id));
		return;
	}	else	{
		echo "!!!ERROR!!!: Cannot add modify tracks for selected playlist.";
		return;
	}
	return;

}

function ti_apm_check_permissions ($levelRequired =  TI_APM_LEVEL_REQUIRED , $str = 'You do not have permission to access this functionality.')	{
	global $userdata;
	if (!current_user_can('edit_others_posts'))	{
		echo("ACCESS ERROR: " . $str );
		exit;
	}
		
}

function ti_apm_add_tracks_to_playlist($tracks, $ti_apm_playlist_id)	{

	ti_apm_check_permissions();
	
	global $_audio_playlist_manager, $wpdb, $ti_apm_base_query;
	
	// First we want to get the existing tracks... 		
	$sql = 'select tracks from  ' . $_audio_playlist_manager . ' where id = ' . $wpdb->escape($ti_apm_playlist_id) . ' limit 1';
	$tmp = $wpdb->get_var($sql);

	// Split 'em up so we eliminate any empty tracks
	$tmpArray = split (',', $tmp);
	
	// merge 'em with the new tracks
	$tmpTracks = array_merge($tmpArray, split (',', $tracks));
	$tmpArray = array();
	for ($i = 0; $i < count($tmpTracks); $i++)	{
		if (intval($tmpTracks[$i]) > 0)	{
			array_push($tmpArray, $tmpTracks[$i]);
		}
	}
	// And finally, smash all that back into a string that holds both track numbers and their order
	$trackStr = join(',', $tmpArray);

	$sql = "UPDATE $_audio_playlist_manager set tracks =\"" . $wpdb->escape($trackStr) . "\" where id = \"" . $wpdb->escape($ti_apm_playlist_id) ."\"";
	
	$result = $wpdb->query($sql);
	if ($result >= 0)	{
		ti_apm_return_playlist_html($wpdb->escape($ti_apm_playlist_id));
		return;
	}	else	{
		echo "!!!ERROR!!!: Cannot add tracks to selected playlist.";
		return;
	}
	return;
}


function ti_apm_delete_current_playlist ($ti_apm_playlist_id)	{
	global $_audio_playlist_manager, $wpdb, $ti_apm_base_query;
	
	ti_apm_check_permissions(TI_APM_LEVEL_REQUIRED, 'You do not have permission delete playlists.');
	
	$sql = "DELETE from $_audio_playlist_manager where id = (\"" . $wpdb->escape($ti_apm_playlist_id) . "\")";
	
	$result = $wpdb->query($sql);
	if ($result >=0)	{

		ti_apm_return_playlist_options();
		
		return;
	}	else	{
		echo "!!!ERROR!!!: Playlist already exists.";
		return;
	}
	
}

function ti_apm_create_new_playlist($title)	{
	global $_audio_playlist_manager, $wpdb, $ti_apm_base_query;

	ti_apm_check_permissions(TI_APM_LEVEL_REQUIRED, 'You do not have permission to create a new playlist.');

	
	$sql = "INSERT into $_audio_playlist_manager (title, modification_date, creation_date, views) VALUES (\"" . $wpdb->escape($title) . "\", now(), now(), '0')";
	
	$result = $wpdb->query($sql);
	if ($result && $result >=0)	{
		ti_apm_return_playlist_options($wpdb->escape($title));
		return;
	}	else	{
		echo "!!!ERROR!!!: Playlist already exists.";
		return;
	}
}

function ti_apm_return_playlist_options($selectedTitle = null)	{
	global $_audio_playlist_manager, $wpdb, $ti_apm_base_query;
	$sql = "select id, title, description, tracks from $_audio_playlist_manager order by title";	
	$rows = $wpdb->get_results($sql);
	
	$options="";
	
	$i = 0;
	if ($rows) {	
		foreach ($rows as $row)	{
			$i++;
			// Either be the first or the  entry...
			$selectStatus = (($selectedTitle == null && $i == 1) || ($selectedTitle != null && $row->title == $selectedTitle)) ? "SELECTED" : "";
			

			$options .= "\n<option $selectStatus value=\"" . $wpdb->escape($row->id) . "\">" . htmlspecialchars(stripslashes($row->title)) ."  </option>"; 
		}
	}
	echo $options;
	
}

function ti_apm_list_playlists()	{
	global $_audio_playlist_manager, $wpdb, $ti_apm_base_query;
	$options = ti_apm_print_javascript();
	$options .= "<input type='text' name='new_playlist_name' id='new_playlist_name'>";
	$options .= "<input type='submit' name='create_playlist' class='button-primary' value='Create new playlist' onClick='javascript:createPlaylist();'/>";
	$options .= " or select an existing playlist: ";
 	$options .= '<select id="playlist_selection" name="playlist_selection" onChange="javascript:swapPlaylist()">';
	
	$options .= "<input type='submit' name='ti_apm_delete_playlist' class='button-primary alignright' value='Delete this playlist' onClick='javascript:deletePlaylist();'/>";


	$options .= "\n</select>";


	
	$options .=<<<__END_OF_HEADER__

	<table id='playlist_preview' name='playlist_preview' class='widefat'>
			<thead>
				<tr>
					<th scope='col'>#</th>
					<th scope='col'>Artist</th>
					<th scope='col'>Track</th>
					<th scope='col'>Album</th>
					
					<th scope='col'>Preview/Embed code</th>
					<th scope='col'>Edit</th>
					<th scope='col' class='delete'>Delete</th>
				</tr>
			</thead>
			<tbody id ="playlist">
			<tr><td></td></tr></tbody>
			</table>
			<span id="ie_workaround" name="ie_workaround" ><table><tbody><tr></tr></tbody></table></span>
__END_OF_HEADER__
;
$options .= "<br/><div><div class='alignleft'>
			<input type='button' value='Save Ordering' name='saveordering' class='button-secondary' onclick='changePlaylistOrder(); return false' />
        	</div>
			<div class='alignright'><input type='button' value='Remove' name='removeit' class='button-secondary remove' onclick='removeFromExistingPlaylist(); return false' /></div>
		  <div><br/>";
	
	return $options;
	
}


function ti_apm_print_javascript()	{

	global $ti_apm_base_query;
	$scripts =<<<__END_OF_SCRIPTS__

	<script>
	
	// Run when create_playlist is pressed
	//
	function createPlaylist()	{
		
		var nonspace = /\S/;
		
		if (jQuery('#new_playlist_name').val().search(nonspace)) {
			alert ('Please enter a name for the new playlist.');
			return;
		}
		
		jQuery.get(
					'?$ti_apm_base_query',
					{ 	action:	'ti_apm_add_playlist', playlist_title: jQuery('#new_playlist_name').val()},
					function(data)	{
				
						var error = "!!!ERROR!!!:";
						
						if (data.toString().match (error) )	{
							alert ("Unable to create a playlist with the given name. Does this playlist already exist?");
							return;
						}
						alert ("Created new playlist!");
						jQuery('#playlist_selection').children().remove();
						jQuery('#playlist_selection').append (data);
						swapPlaylist();
					}
		);
		return false;
		
	}
	
	function deletePlaylist()	{
		if (confirm("Are you sure you want to delete the current playlist?\\n\\n" + jQuery("#playlist_selection option:selected").text() ))	{
				jQuery.get(
					'?$ti_apm_base_query',
					{ 	action:	'ti_apm_delete_playlist', ti_apm_playlist_id: jQuery('#playlist_selection').val() },
					function(data)	{
						jQuery('#playlist_selection').children().remove();
						jQuery('#playlist_selection').append (data);
						
						swapPlaylist();
						
					}
			);
			
		}	else	{
			alert ("Playlist remains active.");
		}
		return false;
	}
	
	
	
	// Run when playlist_selection is changed.
	function swapPlaylist()	{
		
		jQuery.get(
					'?$ti_apm_base_query',
					{ 	action:	'ti_apm_view_playlist', ti_apm_playlist_id: jQuery('#playlist_selection').val() },
					function(data)	{
						spoonFeedIE(data);
					}
		);
		return false;
	}


	// The following function is to accommodate Microsoft Internet Explorer's absurd implementation of
	// innerHTML, which does not work on tbody. Otherwise, a single jQuery line would suffice:
	//
	//		jQuery('tbody#playlist')[0].innerHTML = data;
	//
	// Instead, we end up with this extra function and a placeholder <span/> in the page. Nice, IE.
	//
	function spoonFeedIE (data)	{
		var temp =jQuery('#ie_workaround')[0];
		temp.innerHTML =  '<table><tbody id="playlist">' + data + '</tbody></table>';
		var tb = jQuery('tbody#playlist')[0];
		tb.parentNode.replaceChild (temp.firstChild.firstChild, tb);
		tb_init('a.dynamicthickbox');
	}



	// Used as a sort parameter to allow us to sort the tracks by value (allowing us to resort the tracks)
	function compareSortProperty(a, b) {
		return a.value - b.value;
	}


	function changePlaylistOrder()	{
		var orderCodes = jQuery("input:text.sortOrder");
		orderCodes.sort(compareSortProperty);

		var str = '';
		for (var i = 0 ; i < orderCodes.length; i++)	{
			str += (orderCodes[i].id.split('_')[1] + ','); 
		}
		
		jQuery.get(
			'?$ti_apm_base_query',
			{ 	action:	'ti_apm_reorder_playlist' , tracks: str, ti_apm_playlist_id: jQuery('#playlist_selection').val() },
			function(data)	{
				spoonFeedIE(data);
			}
		);	
			
	}



	function addToExistingPlaylist()	{
		//var checks = $("#audioForm").toggleCheckboxes(".top5", true);
		var checked = jQuery("input:checkbox:checked.addMedia");
		var str = '';
		for (var i = 0 ; i < checked.length; i++)	{
			str += (checked[i].id.split('_')[1] + ','); 
		}
		
		jQuery.get(
			'?$ti_apm_base_query',
			{ 	action:	'ti_apm_add_tracks_to_playlist' , tracks: str, ti_apm_playlist_id: jQuery('#playlist_selection').val() },
			function(data)	{
				spoonFeedIE(data);
			}
		);
		
	}
	

	function removeFromExistingPlaylist()	{
		
		var checked = jQuery("input:checkbox:not(:checked).removeMedia");
		var str = '';
		for (var i = 0 ; i < checked.length; i++)	{
			str += (checked[i].id.split('_')[1] + ','); 
		}
		
		jQuery.get(
			'?$ti_apm_base_query',
			{ 	action:	'ti_apm_remove_from_playlist' , tracks: str, ti_apm_playlist_id: jQuery('#playlist_selection').val() },
			function(data)	{
				spoonFeedIE(data);
			}
		);
		
	}
		
	

	
	// Run on load to populate dropdowns
	jQuery(function() {
		
		jQuery.get(
					'?$ti_apm_base_query',
					{ 	action:	'ti_apm_get_playlist_options' },
					function(data)	{
						jQuery('#playlist_selection').append (data);
						swapPlaylist();
					}
		);
		
		
		// Q: Why not use 'change' instead of 'click' ? A: IE.
		jQuery("input[name='submissionType']").click(function()	{
			 
				// Hide all the submission type form fields...
				jQuery("input[name='submissionType']").each(function()	{
					jQuery(".ti_apm_" + jQuery(this).val() + "FileField").hide();
				});
				// But show the relevant one
				jQuery(".ti_apm_" + jQuery("input[@name='submissionType']:checked").val() + "FileField").show();
				
			}
		
		);
		

		
			// Hide the URL field to start
		jQuery('.ti_apm_externalFileField').hide();
		
	});


	
	</script>
	
__END_OF_SCRIPTS__
;

	return $scripts;
}

function ti_apm_print_audio_form() {
	ti_apm_check_permissions(TI_APM_LEVEL_REQUIRED, 'You do not have permission to access this page.');

	
	global $_audio_playlist_manager, $_audio_playlist_manager_db_version, $wpdb, $ti_apm_base_query, $ti_apm_prev_width, $ti_apm_prev_height;



	$playlist_dropdown = ti_apm_list_playlists();

	// execute the form
	print "
	<div class='wrap'>

		<div id='icon-options-general' class='icon32'><img src='http://tierra-innovation.com/wordpress-cms/logos/src/audio_playlist_manager/$_audio_playlist_manager_db_version/default.gif' alt='' title='' /><br /></div>

		<h2 style='height:64px;'>Tierra Audio Playlist Manager $_audio_playlist_manager_db_version</h2>

		<div>
		<p>
		$playlist_dropdown
		
		</p>	
</div>
<br/>
<br/>




	<h3>Available Audio Files</h3>
		<p>To create a new file, add it to the section below.  Once added, you can edit it or add it to one of the playlists above.</p>

			<form id='audioForm' method='post' enctype='multipart/form-data'>

			<style type='text/css'>
			.widefat th.delete { text-align: right !important; }
			</style>

			<table id='available_media' class='widefat'>
			<thead>
				<tr>
					
					<th scope='col'>Artist</th>
					<th scope='col'>Track</th>
					<th scope='col'>Album</th>
					
					<th scope='col'>Preview/Embed code</th>
					<th scope='col'>Edit</th>
					<th scope='col' class='delete'>Add</th>
				</tr>
			</thead>
			<tbody>
	";
	
	
		
		$sql = 'select id, post_title as track, guid, post_date, post_modified from ' . $wpdb->posts . ' where post_type = "attachment" and post_mime_type rlike "audio/mp"';

		$rows = $wpdb->get_results($sql);
		

		$fileSizeWarning = "<div class='error'><p style='color: #c00;'><em><strong>**Warning:</strong> This server limits the size of data submitted through this page to " . ini_get( 'post_max_size' ) . " (With a maximum file upload size of " . ini_get( 'upload_max_filesize' )  . "). To publish larger files, please add the following settings to your .htaccess or server configuration file:</em></p>
	<p>
	<pre style='color: #c00;'>
	php_value post_max_size 100M (or more if needed)
	php_value upload_max_filesize 105M (or more if needed)
	php_value max_execution_time 500 (or longer if needed)
	</pre>
	</p></div>
	";
	
		
		
		if ($rows) {	
			foreach ($rows as $row)	{
				$metadata = get_post_meta($row->id, '_wp_attachment_metadata', true);
				
				print "<tr class='alternate author-self status-publish' valign='top'>
						
						<td scope='col'>" . htmlspecialchars(stripslashes($metadata['_ti_apm_artist'])) . "</td>
						<td scope='col'>" . htmlspecialchars(stripslashes($row->track)) . "</td>
						<td scope='col'>" . htmlspecialchars(stripslashes($metadata['_ti_apm_album'])) . "</td>
		
			
						<td><a name=\"Audio: " . htmlspecialchars( stripslashes($row->track)) . "\" id=\"Audio: " . htmlspecialchars( stripslashes($row->track)) . "\"
						class='thickbox' href='". WP_PLUGIN_URL .	"/tierra-audio-playlist-manager/preview.php?media_id="
						. 	$wpdb->escape($row->id) . "&keepThis=true&TB_iframe=true&height=" .$ti_apm_prev_height . "&width=" . $ti_apm_prev_width .
						"'>Preview/Embed</a></td>
						<td scope='col'><a href='?" . $ti_apm_base_query. "&asset_id=". $wpdb->escape($row->id) . "&action=edit' title=''>Edit</a></td>
						<td scope='col' align='right'><input  class='addMedia' id='cb_" . $wpdb->escape($row->id) . "' type='checkbox' /></td>
					</tr>
				";
			}
		}	else	{
			print "<tr>
					<th colspan='6'>No media results</th>
					</tr>";
		}
			
		print "</tbody>
			</table>

			<br class='clear'>

			<div class='tablenav'>



				<div class='alignright'>

					<input type='button' value='Add' name='addit' class='button-secondary add' onclick='addToExistingPlaylist(); return false' />
		 

				</div>

			</div>

		<!-- adding the form to add items.  The form should also serve as the edit function as well. -->

		<style type='text/css'>
			select.smore { width: 120px; }
		</style>

		<h3>Add New Media File</h3>
		
		<ul>
			<li><strong>Artist:</strong> <input type='text' name='artist' value='' /></li>
			<li><strong>Track Name:</strong> <input type='text' name='track' value='' /></li>
			<li><strong>Album Name:</strong> <input type='text' name='album' value='' /></li>
			<!--<li><strong>Upload File:</strong><input type='file' name='file' value='' /></li>-->
					<li><strong>Now select the media you wish to add.</strong>
				<ul>
			<li><input type='radio' name='submissionType' value='upload' checked='checked' />Upload file** <input class='ti_apm_uploadFileField' type='file' name='file' value='' /></li>
			<li><input type='radio' name='submissionType' value='external' />Use existing URL* <input type='text' class='ti_apm_externalFileField' name='externalURL' id='externalURL' size='50' value='' /></li>
			</ul></li>

		</ul>



				<br />
				<input type='submit' name='submit' class='button-primary' value='Add Media File' />

			</form>
<p><em><strong>*Note:</strong> Remote content must be accessible from this page, and you must have permission to use it.</em></p>
		<p>$fileSizeWarning</p>
		</div></div></div>

	";

}


function ti_apm_return_playlist_xml($ti_apm_playlist_id)	{
	
	header('Location: ' . WP_PLUGIN_URL . '/tierra-audio-playlist-manager/playlist.php?id=' . $ti_apm_playlist_id);
	exit;

}



function ti_apm_update_existing_asset ()	{
	
	ti_apm_check_permissions( TI_APM_LEVEL_REQUIRED, 'You do not have permission to edit existing assets.');
	
		
	global $_audio_playlist_manager, $wpdb;
	
	
	
	$post = array(
		'ID' =>  $wpdb->escape($_POST['post_id']),
		'post_title' => $wpdb->escape($_POST['track']), 
		'post_name' => $wpdb->escape($_POST['track']),
		'post_author' => $user_ID,
		'ping_status' => get_option('default_ping_status'),
		'post_excerpt' => $wpdb->escape($_POST['description']),
		'post_content' => $wpdb->escape($_POST['caption']),
		'guid' =>  $wpdb->escape($_POST['guid']),

	  );

	$pID = wp_update_post($post);
	
	$metadata =  array (
		'_ti_apm_album' => $wpdb->escape($_POST['album'] ),
		'_ti_apm_artist' => $wpdb->escape($_POST['artist'] )
		
	);
	
		  
	update_post_meta(  $pID, '_wp_attachment_metadata',$metadata) or add_post_meta( $pID, '_wp_attachment_metadata', $metadata);
	

}


function ti_apm_edit_existing_asset ($asset_id)	{
	ti_apm_check_permissions(TI_APM_LEVEL_REQUIRED, 'You do not have permission to edit existing assets.');

	
	global $_audio_playlist_manager, $wpdb, $ti_apm_base_query;
	
	$sql = 'select id, post_title as track, guid, post_mime_type, post_content, post_excerpt, post_date, post_modified from ' . $wpdb->posts . ' where id = "' . intval($asset_id) . '"';

	$row = $wpdb->get_row($sql);
	
	if ($row) {	
		$metadata = get_post_meta($row->id, '_wp_attachment_metadata', true);
	}
	
	
	
	$track = htmlspecialchars(stripslashes($row->track));
	$album = htmlspecialchars(stripslashes($metadata[_ti_apm_album]));
	$artist = htmlspecialchars(stripslashes($metadata[_ti_apm_artist]));
	$caption = htmlspecialchars(stripslashes($row->post_content));
	$description = htmlspecialchars(stripslashes($row->post_excerpt));
	
	print<<<_END_OF_FORM
	<div class="wrap">
	<?php screen_icon(); ?>
<h2>Edit Media</h2>

	<form method="post" action="?$ti_apm_base_query&action=update&asset_id=$row->id" class="media-upload-form" id="media-single-form">
<div class="media-single">
<div id='media-item' class='media-item'>

	
	
	<input type="hidden" name="attachments" value="0" />
	
	<table class='slidetoggle describe form-table'>

		<thead class='media-item-info'>
		<tr>
			<td class='A1B1' rowspan='4'><img class='thumbnail' src='http://plugins.dev/wp-includes/images/crystal/audio.png' alt='' /></td>
			<td>$row->guid</td>
		</tr>
		<tr><td>$row->post_mime_type</td></tr>
		<tr><td>$row->post_modified</td></tr>

		<tr><td></td></tr>
		</thead>
		<tbody>
		<tr class='post_title'>
			<th valign='top' scope='row' class='label'><label for='track'><span class='alignleft'>Track</span><span class='alignright'></span><br class='clear' /></label></th>
			<td class='field'><input type='text' id='track' name='track' value="$track"/></td>
		</tr>
		
		<tr class='post_title'>
			<th valign='top' scope='row' class='label'><label for='artist'><span class='alignleft'>Artist</span><span class='alignright'></span><br class='clear' /></label></th>
			<td class='field'><input type='text' id='artist' name='artist' value="$artist"/></td>
		</tr>	

		<tr class='post_title'>
			<th valign='top' scope='row' class='label'><label for='album'><span class='alignleft'>Album</span><span class='alignright'></span><br class='clear' /></label></th>
			<td class='field'><input type='text' id='album' name='album' value="$album"/></td>
		</tr>
		
		<tr class='post_excerpt'>

			<th valign='top' scope='row' class='label'><label for='post_content'><span class='alignleft'>Caption</span><span class='alignright'></span><br class='clear' /></label></th>
			<td class='field'><input type='text' id='caption'  name='caption' value="$caption"/></td>
		</tr>
		<tr class='post_content'>
			<th valign='top' scope='row' class='label'><label for='description'><span class='alignleft'>Description</span><span class='alignright'></span><br class='clear' /></label></th>
			<td class='field'><textarea type='text' id='description' name='description'>$description</textarea></td>
		</tr>

		<tr class='image_url'>
			<th valign='top' scope='row' class='label'><label for='guid'><span class='alignleft'>File URL</span><span class='alignright'></span><br class='clear' /></label></th>
			<td class='field'><input type='text' class='urlfield' readonly="readonly" name='guid' value='$row->guid' /><br /><p class='help'>Location of the uploaded file.</p></td>
		</tr>
	</tbody>
	</table>
</div>
</div>

<p class="submit">
<input type="submit" class="button-primary" name="save" value="Update Media" />
<input type="hidden" name="post_id" id="post_id" value="$row->id" />
</form>
</div>


_END_OF_FORM
;
	

	
}


function ti_apm_return_playlist_html ($ti_apm_playlist_id)	{
	global $_audio_playlist_manager, $wpdb, $ti_apm_base_query,  $ti_apm_prev_width, $ti_apm_prev_height;

	$sql = 'select tracks, title from  ' . $_audio_playlist_manager . ' where id = ' . $wpdb->escape($ti_apm_playlist_id);
	
	$tracklist = $wpdb->get_row($sql);
	
	$tracks = split (',' , $tracklist->tracks);
	$i = 0;

	if ($tracklist->tracks)	{
		foreach ($tracks as $track)	{ 
			$sql = 'select id, post_title as track, guid, post_date, post_modified from ' . $wpdb->posts . ' where id = "' . intval($track) .'"';
	
			$row = $wpdb->get_row($sql);
			
			if ($row) {
				$metadata = get_post_meta($row->id, '_wp_attachment_metadata', true);
					print "<tr class='alternate author-self status-publish' valign='top'>
						<td scope='col'><input type='text' id='sort_" . $wpdb->escape($row->id) . "' class='sortOrder'  value='" . ++$i ."' size='2'></td>
						<td scope='col'>" . htmlspecialchars(stripslashes($metadata['_ti_apm_artist'])) . "</td>
						<td scope='col'>" . htmlspecialchars(stripslashes($row->track)) . "</td>
						<td scope='col'>" . htmlspecialchars(stripslashes($metadata['_ti_apm_album'])) . "</td>

<td><a name=\"Audio: " . htmlspecialchars(stripslashes($row->track)) . "\" id=\"Audio: " .  htmlspecialchars(stripslashes($row->track)) . "\"
						class='dynamicthickbox thickbox' href='". WP_PLUGIN_URL .	"/tierra-audio-playlist-manager/preview.php?media_id="
						. 	$wpdb->escape($row->id) . "&keepThis=true&TB_iframe=true&height=" .$ti_apm_prev_height . "&width=" . $ti_apm_prev_width .
						"'>Preview/Embed</a></td>

<!--						<td scope='col'><a href='" . $wpdb->escape($row->guid) ."' target='_blank'>" . $wpdb->escape($row->guid) ."</a></td> -->
						<td scope='col'><a href='?" . $ti_apm_base_query. "&asset_id=". $wpdb->escape($row->id) . "&action=edit' title=''>Edit</a></td>
						<td scope='col' align='right'><input type='checkbox' id='cb_" . $wpdb->escape($row->id) . "' class='removeMedia'  /></td>
					</tr>
				";
			
			
			
			
			
			
			
			}
		}
	}	else	{
		print "<tr>
				<th colspan='7'>No playlist results</th>
				</tr>";
	}
	print "<tr><th colspan='2'><a target='_blank' href='" . WP_PLUGIN_URL ."/tierra-audio-playlist-manager/playlist.php?preview=true&id=$ti_apm_playlist_id' title=''>Download XML File</a> (Right-click and copy URL to use elsewhere)";
	print "<th colspan='5'>";
	
	print '<a class="dynamicthickbox thickbox alignright" name="Tracklist: ' . htmlspecialchars($tracklist->title) . '" id="Tracklist: ' . htmlspecialchars($tracklist->title) . '" href="'
			.  	WP_PLUGIN_URL 
			.	'/tierra-audio-playlist-manager/preview.php?name='
			.  urlencode($tracklist->title)
			. '&ti_apm_playlist_id='
			. 	$ti_apm_playlist_id
			. '&keepThis=true&TB_iframe=true&height=' . $ti_apm_prev_height . '&width=' . $ti_apm_prev_width . '">Preview Player</a>';
	
	
	print "</th></tr>";

}



function ti_apm_upload_files()	{
	global $_audio_playlist_manager, $wpdb;

	ti_apm_check_permissions(7, 'You do not have permission to upload files.');
		
	$wpdir = wp_upload_dir();
	$submissionType = strtolower($_POST['submissionType']);
	
	
	if ($_FILES['file'])	{
		
		$uploaded_file = $_FILES['file']['tmp_name'];
		
		

		
		
		if ($submissionType == 'upload' && isset ($_FILES['file']['error']) && $_FILES['file']['error'] > 0)	{
			echo '<div id="message" class="error fade"><p>UPLOAD ERROR: ' . ti_apm_file_upload_error_message($_FILES['file']['error']) . ' </p></div>';
			return;
		};
		
		// Move the file to the correct location within the WP install
		if ($submissionType == 'upload' && isset($_FILES['file']['name']) && $_FILES['file']['name'] > '' )	{
		
			$newfile = wp_upload_bits( $_FILES['file']['name'], null,  file_get_contents($_FILES["file"]["tmp_name"] ));
			
		}	else {

			if ($submissionType == 'external')	{
					$info = get_headers(esc_url($_POST['externalURL']), 1);
					echo '<div id="message" class="updated fade"><p>URL entered. Please ensure <a target="_blank" href="' . esc_url($_POST['externalURL']) .'">'. esc_url($_POST['externalURL'])  . '</a> is the correct URL for your external media.</p><p>The remote server says the current mime type of this file is ' . $info['Content-Type'] .'. Please note that if this is not correct, the URL may not appear within the available items nor function correctly within the Audio Player.</p><p>Also, please note that remote files may only be used if the remote server includes this server within its crossdomain.xml file.</div>';
					
						
					$newfile = array ( 'url' => esc_url($_POST['externalURL']) , 'file' => array('name' =>esc_url($_POST['externalURL']), type => $info['Content-Type'], basedir=>esc_url($_POST['externalURL']), baseurl=>esc_url($_POST['externalURL']))  );
					
					
					// Otherwise, let's note the error
			}
		}
		
		if ($newfile->error)	{

			echo '<div id="message" class="error fade"><p>ERROR! Unable to create new file.</p></div>';
			
		}	else {
			echo '<div id="message" class="updated fade"><p>Media successfully added to collection.</p></div>';
		}
		
	}
	
	$post = array(
		'post_status' => 'inherit',
		'post_title' => $wpdb->escape($_POST['track']) ? $wpdb->escape($_POST['track']) : 'Unknown', 
		'post_name' => $wpdb->escape($_POST['track']) ? $wpdb->escape($_POST['track']) : 'Unknown',
		'post_type' => 'attachment',
		'post_mime_type' => $wpdb->escape($_FILES['file']['type']) ? $wpdb->escape($_FILES['file']['type']) : $wpdb->escape($info ? $info['Content-Type'] : 'unknown'),
		'post_author' => $user_ID,
		'ping_status' => get_option('default_ping_status'),
		'post_parent' => 0,
		'menu_order' => 0,
		
		'guid' =>  $newfile['url'],

	  );

	$pID = wp_insert_post($post);
	

/*
 
 
	if ($submissionType == 'upload')	{
		$attach_id = wp_insert_attachment( $attachment, $newfile['file'] );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $newfile['file']  );
		$combined_metadata = array_merge($attach_data, $ti_metadata);
	}	else {
		$attach_id = wp_insert_post( $attachment );
		$combined_metadata = $ti_metadata;
	}
	
	*/


	// Add the metadata so it shows up as the correct mime type within media basket
		if ($submissionType == 'upload')	{	
		   update_post_meta(  $pID, '_wp_attached_file',$wpdb->escape($_FILES['file']['name'] ) ) or add_post_meta( $pID, '_wp_attached_file',$wpdb->escape($_FILES["file"]['name'] ));
		}
	
		
		$metadata =  array (
			'_ti_apm_album' => $wpdb->escape($_POST['album']) ? $wpdb->escape($_POST['album'] ) : 'Unknown',
			'_ti_apm_artist' => $wpdb->escape($_POST['artist'])? $wpdb->escape($_POST['artist'] ) : 'Unknown'
			
		);
		
			  
		update_post_meta(  $pID, '_wp_attachment_metadata',$metadata) or add_post_meta( $pID, '_wp_attachment_metadata', $metadata);



}


	
// This is for shortcode use..	
// Skin is the path to the swf MINUS the '.swf' extension! (although if present, it's removed automatically)
function ti_apm_print_player ($atts)	{
	global $_audio_playlist_manager, $wpdb;
	
	extract(shortcode_atts(array(  
        "name" => "",
		"media" => "",
		"id" => 1,
		"skin" => WP_PLUGIN_URL . "/tierra-audio-playlist-manager/swf/ti-player.swf",
		"autoplay" => 0,
		"autoload" => 0,
		"volume" => 50,
		'randomize' => 0,
		"repeat" => 0,
		"width" => 290,
		"height" => 32,
		"title"	=> "Playlist managed by Tierra Audio Playlist Manager"
		
    ), $atts));
	
	// Width must be at least 120...	
	$width = ($width < 120) ? 120 : $width;
	
	// IF WE'VE BEEN PROVIDED A PLAYLIST NAME, LET'S USE THIS TO GLEAN THE CORRECT PLAYLIST ID
	if ($name != "")	{
		$sql = 'select id from  ' . $_audio_playlist_manager . ' where title = "' . addslashes($wpdb->escape($name)) . '" limit 1';
		$ti_apm_playlist_id= $wpdb->get_var($sql);
	}	else {
		$ti_apm_playlist_id= intval($id);
	}
	


	$media_id = "" ? "" : intval($media);
	$randomize = "" ? "" : intval($randomize);

	
	
	$playlistURL = WP_PLUGIN_URL . "/tierra-audio-playlist-manager/playlist.php?id=$ti_apm_playlist_id&media_id=$media_id&random=$randomize";
	
	
	$player =  preg_replace('/\.swf$/', '', $skin);
	$playerURL = $player . '.swf';
	
	$acURL =  WP_PLUGIN_URL ."/tierra-audio-playlist-manager/js/AC_RunActiveContent.js";
	$flashvars = ($autoplay ? "autoplay=" . urlencode($autoplay) : "")
				. ($autoload ? "&autoload=" . urlencode($autoload) : "")
				. ($repeat ? "&repeat_playlist=" . urlencode($repeat) : "")
				. ($title ? "&player_title=" . urlencode($title) : "")
				. ($volume ? "&volume=" . urlencode($volume) : "")
				. ($playlistURL ? "&playlist_url=" . urlencode($playlistURL) : "");
	
	
	$response=<<<__END_PLAYER_CODE__
	<div class='ti_player_align_class'>
	
	<script language="javascript">
	AC_FL_RunContent(
		'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0',
		'width', '$width',
		'height', '$height',
		'src', '$player',
		'quality', 'high',
		'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
		'align', 'middle',
		'play', 'true',
		'loop', 'true',
		'scale', 'showall',
		'wmode', 'transparent',
		'devicefont', 'false',
		'id', 'player_$ti_apm_playlist_id',
		'bgcolor', '#ffffff',
		'name', 'player_$ti_apm_playlist_id',
		'menu', 'true',
		'allowFullScreen', 'true',
		'allowScriptAccess','always',
		'flashvars', '$flashvars',
		'movie', '$player',
		'salign', ''
	); //end AC code	
	</script>
		
	<noscript>
		<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="$width" height="$height" id="player_$ti_apm_playlist_id" align="middle">
		<param name="allowScriptAccess" value="always" />
		<param name="allowFullScreen" value="true" />
		<param name="movie" value="$playerURL?$flashvars" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" />
		<embed src="$playerURL?$flashvars" quality="high" bgcolor="#ffffff" width="$width" height="$height" name="player_$ti_apm_playlist_id" align="middle" allowScriptAccess="always" allowFullScreen="true" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
		</object>
	</noscript>
	
	</div>

__END_PLAYER_CODE__
;

return $response;

}


function ti_apm_remove_shortcode_from_rss($content) {
	return preg_replace("/\[TI_([^\]]*)\]/i", "", $content);
}

function ti_apm_remove_shortcode_from_rss_excerpt($excerpt) {
	return preg_replace("/\[TI_([^\]]*)\]/i", "", $excerpt);
}


add_action('the_content_rss', array(&$this, 'ti_apm_remove_scriptcode_from_content_rss'));
add_action('the_excerpt_rss', array(&$this, 'ti_apm_remove_scriptcode_from_excerpt_rss'));

function ti_apm_remove_scriptcode_from_content_rss($content) {
	return preg_replace("|<p class='ti_player_align_class'>(.*)</p>|", "", $content);
}

function ti_apm_remove_scriptcode_from_excerpt_rss($excerpt) {
	return preg_replace("|<p class='ti_player_align_class'>(.*)</p>|", "", $excerpt);
}

function ti_apm_file_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
} 

?>