<div class='wrap video-blogster-section'>
	<h2 class='video-blogster-title'>Video Blogster Lite v<?php echo VIDEO_BLOGSTER_LITE_VERSION; ?></h2>
	<?php include( sprintf( "%s/buttons.php", dirname( __FILE__ ) ) ); ?>
	<table class='form-table'>
	<tr>
	<td>
	<p>
	<?php 
		printf( 
		esc_html__( 'This builds the query that will be sent to the %s API to retrieve videos using a public API key. %sBy using this query you agree to %sYouTube\'s Terms of Service%s and %sGoogle\'s Privacy Policy%s. %sNo user data is collected, stored, processed, or otherwise used.', 'video-blogster' ), 
		'YouTube', 
		'<br>',
		'<a target="_blank" href="' . esc_url('https://www.youtube.com/t/terms') . '">',
		'</a>',
		'<a target="_blank" href="' . esc_url('https://policies.google.com/privacy?hl=en-US') . '">',
		'</a>',
		'<br>'
		); 
	?>
	</p>
	</td>
	<td>
	<?php
		$file = __FILE__;
		$video_file = 'yt_logo_rgb_light.png';
		$iconFile = plugin_dir_path( $file ) .  '../images/' . strtolower( $video_file );
		$icon = plugins_url( '../images/' . strtolower( $video_file ), $file );
		if ( file_exists( $iconFile ) ) :
	?>
		<img height="64" src="<?php echo $icon;?>" \>
	<?php endif; ?>
	</td>
	</tr>
	</table>
</div>

<div class='wrap video-blogster-section'>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <input type="hidden" name="video_source" value="YouTube" />
	<h3 class='video-blogster-title'>Grab YouTube Videos</h3>
	<?php
	include( sprintf( "%s/query-youtube.php", dirname( __FILE__) ) );
	include( sprintf( "%s/process-results.php", dirname( __FILE__) ) );
	include( sprintf( "%s/create-posts.php", dirname( __FILE__) ) );
	?>
	<p>
	<input type="submit" name="grab_feed" value="Grab YouTube Videos Now" />
	</p>
	</form>
</div>
