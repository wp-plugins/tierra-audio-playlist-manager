<?php
require_once('../../../wp-config.php');
require_once('../../../wp-settings.php');


echo'<html><head><title>Tierra Audio Playlist</title>';
wp_head();
wp_enqueue_script( ÔjqueryÕ );


$media_id =  htmlspecialchars($_REQUEST['media_id']);
$playlist_id = htmlspecialchars($_REQUEST['ti_apm_playlist_id']);
$playlist_url =  urlencode( WP_PLUGIN_URL . "/tierra-audio-playlist-manager/playlist.php?media_id=$media_id&id=$playlist_id");

$embedcode = '[ti_audio ';

if ($media_id > 0)	{
	$embedcode .= 'media=&quot;' . $media_id .'&quot;';
}	elseif ($_REQUEST['name'] !="") {
	$embedcode .= 'name=&quot;' . htmlspecialchars(stripslashes($_REQUEST['name'])) . '&quot;';
} else{
	$embedcode .= 'name=&quot;' . $playlist_id . '&quot;';
}


$pluginURL = WP_PLUGIN_URL;

echo<<<__END_OF_PREVIEW___
	<link rel='stylesheet' href='/wp-admin/load-styles.php?c=1&amp;dir=ltr&amp;load=global,wp-admin' type='text/css' media='all' />
	<style>
	
	.audioPlayer {
		width:290px;
		margin:15px auto;
		padding:0;
		text-align:center;
	}
	.centerLine	{
		width:340px;
		margin:10px auto;
	}
	.floatRight	{
		float:right;
	}
	
	.embedCode {
		height:75px;
		width:340px;
		margin:15px auto;
		text-align:center;
	}
	
	</style>

	</head>
	<body>



	<div class="audioPlayer">
	<script language="javascript">
	
		var autoplayStatus = '';
		var randomStatus = '';
		var repeatStatus = '';
		var volume = '';
			

		AC_FL_RunContent(
			'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0',
			'width', '290',
			'height', '32',
			'src', 'swf/ti-player',
			'quality', 'high',
			'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
			'align', 'middle',
			'play', 'true',
			'loop', 'true',
			'scale', 'showall',
			'wmode', 'window',
			'devicefont', 'false',
			'id', 'ti-player',
			'bgcolor', '#ffffff',
			'name', 'ti-player',
			'menu', 'true',
			'allowFullScreen', 'false',
			'allowScriptAccess','sameDomain',
		
			'flashvars', 'autoplay=1&repeat=0&playlist_url=$playlist_url',
			'movie', 'swf/ti-player',
			'salign', ''
		); //end AC code

		
		
		function updateSnippet()	{
			jQuery(".embedCode").html("$embedcode" + autoplayStatus +  repeatStatus + randomStatus + volume + "]");
			
		}
		
		jQuery(function() {
	
	
			jQuery("input#chk_repeat").change(function()	{
				 if(jQuery(this).attr('checked'))	{
					repeatStatus = ' repeat="1"';
				 } else	{
					repeatStatus = '';
				 }
				 
				updateSnippet();			
			});
			
			jQuery("input#chk_auto").change(function()	{
				 if(jQuery(this).attr('checked'))	{
					autoplayStatus = ' autoplay="1"';
				 } else	{
					autoplayStatus = '';
				 }
				 
				updateSnippet();			
			});
	
				
			jQuery("input#chk_randomize").change(function()	{
				 if(jQuery(this).attr('checked'))	{
					randomStatus = ' randomize="1"';
				 } else	{
					randomStatus = '';
				 }
				 
				updateSnippet();			
			});
			
	
			jQuery("input#volume_input").change(function()	{
				// Volume must be between 0 and 100
				var v = Math.min(100, Math.max(jQuery(this).val(), 0));
				
				volume = ' volume="' + v + '"';
				 
				 
				updateSnippet();			
			});
	
	
	
			
		});
		
	</script>

	<noscript>
		<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="400" height="170" id="ti-player" align="middle">
		<param name="allowScriptAccess" value="sameDomain" />
		<param name="allowFullScreen" value="false" />
		<param name="movie" value="swf/ti-player.swf?autoplay=1&playlist_url=$playlist_url" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" />
		<embed src="swf/ti-player.swf?autoplay=1&playlist_url=$playlist_url" quality="high" bgcolor="#ffffff" width="400" height="170" name="ti-player" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
		</object>
	</noscript>
	</div>

	<hr>
	

	<form id="option_form"  class="centerLine">
		Begin playing automatically: <input class="floatRight" id="chk_auto" type="checkbox" value="on"/>
		<br/>Repeat when playlist ends: <input class="floatRight" id="chk_repeat" type="checkbox" />
		<br/>Create random order playlist: <input class="floatRight" id="chk_randomize" type="checkbox" />
		<br/>Initial volume (0-100):<input class="floatRight" id="volume_input" type="text" size="3" value="50"/> 
	</form>
	
	<p class="centerLine">The code used to embed this player in a post is as follows:</p>
	<p class="centerLine"><textarea class="embedCode" >$embedcode]</textarea></p>
	</body>
	</html>

__END_OF_PREVIEW___
;