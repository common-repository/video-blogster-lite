        <table class='form-table'>
	<tr>
                <th scope='row'><div class="dashicons dashicons-wordpress"></div> <label for="feed_user">Create the Posts:</label></th>
		<td>
			<div class='video-blogster-block span_5_of_12 video-blogster-vert-top'>
				<div class='video-blogster-block span_4_of_12'>
					Save as user
				</div>
				<div class='video-blogster-block span_8_of_12'>
					<?php $optValue = $this->get_value( $args, 'cUser', '' ); ?>
					<select name="feed_user">
						<option value="(random)">(random)</option>
					<?php
					$users = $this->get_users();
					foreach ( $users as $user ) { ?>
						<option value="<?php echo $user->ID;?>" <?php echo ($optValue == $user->ID) ? "SELECTED" : "" ?>><?php echo $user->display_name; ?></option>
					<?php } ?>
					</select>
				</div>
				<br />
				<div class='video-blogster-block span_4_of_12'>
					and type
				</div>
				<div class='video-blogster-block span_8_of_12'>
					<?php $optValue = $this->get_value( $args, 'cPostType', '' ); ?>
					<select name="feed_post_type">
					<?php
					$post_types = $this->get_post_types( '', 'names' );
					foreach ( $post_types as $post_type )  { ?>
						<option value="<?php echo $post_type;?>" <?php echo ( $optValue == $post_type ) ? "SELECTED" : "" ?>><?php echo $post_type; ?></option>
					<?php } ?>
					</select>
				</div>
				<br />
				<div class='video-blogster-block span_4_of_12'>
					with status
				</div>
				<div class='video-blogster-block span_8_of_12'>
				<?php $optValue = $this->get_value( $args, 'cPostStatus', '' ); ?>
				<select name="feed_post_status">
					<option value="draft">draft</option>
					<option value="pending" <?php echo ($optValue == 'pending') ? "SELECTED" : "" ?>>pending</option>
					<option value="private" <?php echo ($optValue == 'private') ? "SELECTED" : "" ?>>private</option>
					<option value="publish" <?php echo ($optValue == 'publish') ? "SELECTED" : "" ?>>publish</option>
				</select>
				</div>
			</div>

			<div class='video-blogster-block span_7_of_12 video-blogster-vert-top'>
				<?php $optValue = $this->get_value( $args, 'cCategories', '' ); $optArray = explode( ",", $optValue ); ?>
				in categories
				<select name="feed_categories[]" size="5" multiple>
				<?php
				$categories = $this->get_categories();
				foreach( $categories as $category ) { ?>
					<option value="<?php echo $category->term_id;?>" <?php echo ( in_array( $category->term_id, $optArray ) ) ? "SELECTED" : "" ?>><?php echo $category->name; ?></option>
				<?php } ?>
				</select>
			</div>

		</td>
	</tr>
	<tr>
		<td colspan='2'>
			<hr class='video-blogster-divider' />
		</td>
	</tr>
	</table>
