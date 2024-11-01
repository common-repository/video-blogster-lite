	<table class='form-table'>
	<tr>
			<th scope="row"><div class="dashicons dashicons-migrate"></div> <label for="feed_limit_title_chars">Process the Results:</label></th>
		<td>
			Post Title Template 
			<?php $optValue = $this->get_value( $args, 'pTitleTemplate', '%videoTitle%' ); ?>
			<input type="text" size="60" maxlength="256" name="feed_title_template" id="feed_title_template" value="<?php echo $optValue; ?>" />
			<br /><br />
			Post Content Template 
			<br />
			<?php $optValue = $this->get_value( $args, 'pPostTemplate', "[embed width='800' height='600']%VideoUrl%[/embed]\n<p>%VideoDescription%</p>" ); ?>
			<textarea rows="4" cols="100" name="feed_post_template"><?php echo $optValue; ?></textarea>
			<br />
			<small>
			Supported template tags:
			%VideoTitle%
			%VideoDescription%
			%VideoUrl%
			%VideoID%
			%VideoAssociation%
			%VideoImage%
			%VideoDuration%
			</small>
			<br />
			<small>* KSES toggled off to allow items like Google Adsense</small>
			<br /><br />
		</td>
	</tr>
	<tr>
		<td colspan='2'>
			 <hr class='video-blogster-divider' />
		</td>
	</tr>
	</table>
