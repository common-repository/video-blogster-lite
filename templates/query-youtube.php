	<table class='form-table'>
	<tr>
		<th scope='row'><div class="dashicons dashicons-editor-help"></div> <label for="feed_numVideos">Build the Query:</label></th>
		<td>
			Import 
			<input type="text" size="2" maxlength="3" name="feed_numVideos" id="feed_numVideos" value="<?php echo $this->get_value( $args, 'qNumVideos', '1' ); ?>" />
			videos by keyphrase
			<input type="text" size="60" maxlength="256" name="feed_keyphrase" id="feed_keyphrase" value="<?php echo $this->get_value( $args, 'qKeyphrase', '' ); ?>" />
			<br /><br />
			Keyphrases: Your request can also use the Boolean NOT (-) and OR (|) operators to exclude videos or to find videos that are associated with one of several search terms. For example, to search for videos matching either "boating" or "sailing", set the keyphrase to <strong>boating|sailing</strong>. Similarly, to search for videos matching either "boating" or "sailing" but not "fishing", set the keyphrase to <strong>boating|sailing -fishing</strong>.
			<br /><br />
			Order by
			<?php $optValue = $this->get_value( $args, 'qOrderBy', '' ); ?>
			<select name="feed_orderby">
				<option value="relevance" <?php echo ($optValue == 'relevance') ? "SELECTED" : "" ?>>relevance</option>
				<option value="date" <?php echo ($optValue == 'date') ? "SELECTED" : "" ?>>date</option>
				<option value="rating" <?php echo ($optValue == 'rating') ? "SELECTED" : "" ?>>rating</option>
				<option value="title" <?php echo ($optValue == 'title') ? "SELECTED" : "" ?>>title</option>
				<option value="viewCount" <?php echo ($optValue == 'viewCount') ? "SELECTED" : "" ?>>viewCount</option>
			</select>
			Filter by category
			<?php $optValue = $this->get_value( $args, 'qCategory', 0 ); ?>
			<select name="feed_search_category">
				<option value="">Any</option>
			<?php
			$this->create_video_source( array( 'videoSource' => 'YouTube' ) );
			if ( isset( $this->video_source ) ) {
				$categories = $this->video_source->grab_categories();
				foreach ( $categories as $cat ) { 
			?>
					<option value="<?php echo $cat->id;?>" <?php echo ($optValue == $cat->id) ? "SELECTED" : "" ?>><?php echo $cat->snippet->title; ?></option>
			<?php 
				} 
			}
			?>
			</select>
			Filter by duration
			<?php $optValue = $this->get_value( $args, 'qDuration', 'any' ); ?>
			<select name="feed_search_duration">
				<option value="any">Any</option>
				<option value="long" <?php echo ($optValue == 'long') ? "SELECTED" : "" ?>>20+ minutes</option>
				<option value="medium" <?php echo ($optValue == 'medium') ? "SELECTED" : "" ?>>4 to 20 minutes</option>
				<option value="short" <?php echo ($optValue == 'short') ? "SELECTED" : "" ?>>< 4 minutes</option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan='2'>
			<hr class='video-blogster-divider' />
		</td>
	</tr>
	</table>
