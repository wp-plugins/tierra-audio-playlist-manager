<?php

if (isset($_GET['preview']) && $_GET['preview'] == 'true' )	{
	header("Content-Type: application/xml;charset=utf-8");
}	else	{
	header("Content-Type: application/xspf+xml;charset=utf-8");

}

require_once('../../../wp-config.php');
require_once('../../../wp-settings.php');

global $wpdb, $_audio_playlist_manager_db_version, $_audio_playlist_manager, $baseurl, $pluginurl;

$_audio_playlist_manager = $wpdb->prefix . "ti_audio_playlist_manager";

$playlist_id = intval($_GET['id']);
$randomize = isset($_GET['random']) ? intval($_GET['random']) : 0;

$media_id = isset($_GET['media_id']) ? intval($_GET['media_id']) : -1;

$baseurl =  $_SERVER["QUERY_STRING"];
$pluginurl =  $_SERVER["REQUEST_URI"];

if ($media_id <= 0)	{

	$sql = 'select title, image, tracks, creation_date, license from  ' . $_audio_playlist_manager . ' where id = ' . $wpdb->escape($playlist_id);

}	else	{
	
	$sql = 'select id, post_title as title, "' . $media_id . '" as tracks, post_date as creation_date  from ' . $wpdb->posts . ' where id = ' . $media_id; 
	
}

$row = $wpdb->get_row($sql);

$license = $row->license ? htmlentities($row->license) : '';

$title = htmlentities(stripslashes($row->title));
$tracks = split (',' , $row->tracks);

if ($randomize > 0)	{
	shuffle($tracks);
}

$i = 0;

echo<<<__END_OF_HEADER__
<?xml version="1.0" encoding="UTF-8"?>
<playlist version="1" xmlns = "http://xspf.org/ns/0/">
	<title>$title</title>
	<creator>Tierra Audio Playlist Manager</creator>
	<annotation>Playlist generated via Tierra Audio Playlist Manager, part of the Tierra WordPress CMS Toolkit</annotation>
	<info>http://tierra-innovation.com/wordpress-cms/2009/10/16/audio-playlist-manager/</info>

	<license>$license</license>
	<date>$row->creation_date</date>
	<trackList>
__END_OF_HEADER__
;


if ($row->tracks)	{
	foreach ($tracks as $track)	{ 
		$sql = 'select id, post_title as track, guid, post_date, post_modified from ' . $wpdb->posts . ' where id = ' . $track;

		$row = $wpdb->get_row($sql);
		
		if ($row) {
			$metadata = get_post_meta($row->id, '_wp_attachment_metadata', true);
				print "
		<track>
			<location>" .  htmlspecialchars($row->guid) ."</location>
			
			<creator>" . ( $metadata['_ti_apm_artist'] ? htmlspecialchars($metadata['_ti_apm_artist']) : "Unknown artist" ) ."</creator>
			<album>" .  ( $metadata['_ti_apm_album'] ? htmlspecialchars($metadata['_ti_apm_album']) : "Unknown collection" ) . "</album>
			<title>" . ( $row->track ? htmlspecialchars($row->track) : "No title" ). "</title>
			<info>" .  htmlspecialchars($metadata['_ti_apm_info']) ."</info>
			<trackNum>" . $wpdb->escape($metadata['_ti_apm_tracknum']) ."</trackNum>
			<duration>" . $wpdb->escape($metadata['_ti_apm_duration']) ."</duration>
			
		</track>
				";			
		}
	}
}



print<<<__END_OF_XML__

	</trackList>
</playlist>	


__END_OF_XML__
;

?>

