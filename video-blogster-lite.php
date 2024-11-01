<?php
/**
 * Plugin Name: Video Blogster Lite
 * Plugin URI: http://www.superblogme.com/video-blogster-lite/
 * Description: Queries YouTube for content and automatically creates posts from the results. 
 * Version: 1.2
 * Released: September 28th, 2016
 * Author: Super Blog Me
 * Author URI: http://www.superblogme.com/
 */

define( 'VIDEO_BLOGSTER_LITE_VERSION', '1.2' );
define( 'VIDEO_BLOGSTER_LITE_DIR', plugin_dir_path( __FILE__ ) );

defined( 'ABSPATH' ) or die( "Oops! This is a WordPress plugin and should not be called directly.\n" );

/*
 * Class to handle the video feeds
 */
if ( ! class_exists( 'Video_Blogster_Lite' ) ) {

    class Video_Blogster_Lite {

	private $video_source = null;		// will point to the instance of the current video source

////////////////////////////////////////////////////////////////////////////////////////////

	/*
	 * Register actions and filters when object created
	 */
        public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_options_styles' ) );
		add_action( 'admin_menu', array( $this, 'add_menus' ) );
        }

	/**
	 * Load custom css into admin screen
	 */
	public function enqueue_options_styles( $page ) {
		if ( false !== strpos( $page, 'video_blogster-lite' ) ) {
			wp_register_style( 'video-blogster-lite', plugins_url( 'video-blogster-lite.css', __FILE__ ) );
        		wp_enqueue_style( 'video-blogster-lite' );
		}
	}


	/*
	 * Add these menus to the admin dashboard
	 */
	public function add_menus() {
		// create top level menu on dashboard sidebar
        	add_management_page( 
			'Video Blogster Lite',			// page title
			'Video Blogster Lite',			// menu title
			'manage_options',			// capability required
			'video_blogster-lite',			// menu slug
			array( $this, 'make_video_feed' )	// function to call
		);
	} 

	/*
	 * Create and set query object depending on the video source
	 */
	private function create_video_source( $args ) {
		if ( $args['videoSource'] == "YouTube" ) {
			require_once VIDEO_BLOGSTER_LITE_DIR . 'sources/class-youtube.php';
			$this->video_source = new Video_Blogster_Lite_YouTube( $this, $args, 'AIzaSyCaEjiKAkJMsnCPgN5_S3HmUz01Ei7oUHo' );
		}
		else { // shouldn't happen
			$this->info_message( 'Error: video source ' . $source . ' not recognized.');
			$this->video_source = 0;
		}
	}

	/*
	 * Extract form data into an array.
	 */
	private function get_video_feed_form() {
		// pre-process multiple post categories if set
		if ( isset( $_POST['feed_categories'] ) && is_array( $_POST['feed_categories'] ) ) {
			$cats = array();
			foreach ( $_POST['feed_categories'] as $name => $value ) {
				$cats[] = $value;
			}
			$theCats = implode( "," ,$cats );
		}
			$args = array(
				'videoSource'		=>	$_POST['video_source'],


//				--- Build the Query -------------------------------------

				'singleRequest'		=>	isset( $_POST['single_request'] ) ? trim( stripslashes( $_POST['single_request'] ) ) : '',
				'qNumVideos'		=>	(int)$_POST['feed_numVideos'],
				'qKeyphrase'		=>	isset( $_POST['feed_keyphrase'] ) ? trim( stripslashes( $_POST['feed_keyphrase'] ) ) : '',
				'qOrderBy'		=>	isset( $_POST['feed_orderby'] ) ? $_POST['feed_orderby'] : '',
				'qCategory'		=>	isset( $_POST['feed_search_category'] ) ? trim( stripslashes( $_POST['feed_search_category'] ) ) : '',
				'qDuration'		=>	isset( $_POST['feed_search_duration'] ) ? $_POST['feed_search_duration'] : '',

//                              --- Process the Results -------------------------------------

				'pTitleTemplate'        =>      isset( $_POST['feed_title_template'] ) ? trim( stripslashes( $_POST['feed_title_template'] ) ) : '',
				'pPostTemplate'         =>      isset( $_POST['feed_post_template'] ) ? trim( stripslashes( $_POST['feed_post_template'] ) ) : '',

//				--- Create the Post -------------------------------------

				'cUser'			=>	$_POST['feed_user'],
				'cPostType'		=>	$_POST['feed_post_type'],
				'cPostStatus'		=>	$_POST['feed_post_status'],
				'cCategories'		=>	isset( $theCats ) ? $theCats : ''
			);
		return $args;
	}

	/*
	 * Show and process video feed page
	 */
	public function make_video_feed() {
		$args = array( 'func' => 'Make', 'videoSource' => 'YouTube' );     // defaults 
		if ( isset( $_POST['grab_feed'] ) ) {
		// the Grab Videos now button was pressed
			$args = $this->get_video_feed_form();
			$this->create_video_source( $args );
			if ( $this->video_source ) {
				$this->video_source->grab_videos();
			}
		}
		include( sprintf( "%s/templates/video-feed.php", dirname( __FILE__ ) ) );
	}

	/*
	 * Replace template tags with the video info
	 */
	private function expand_template( $template, $videoInfo ) {
		$template = str_ireplace( "%videoassociation%", $videoInfo['channel'], $template);
		$template = str_ireplace( "%videodescription%", $videoInfo['desc'], $template);
		$template = str_ireplace( "%videoduration%", $videoInfo['duration'], $template);
		$template = str_ireplace( "%videotitle%", $videoInfo['title'], $template);
		$template = str_ireplace( "%videoID%", $videoInfo['videoID'], $template);
		$template = str_ireplace( "%videourl%", $videoInfo['url'], $template);
		return $template;
	}

	/*
	 * Process the video info depending on video feed settings
	 */
	public function process_query_results( $args, &$videoInfo ) {

		if ( $this->post_already_exists( $videoInfo ) ) {
			return $this->info_message( 'Video [' . $videoInfo['title'] . '] already grabbed. Skipping.', 'updated fade', 1 );
		}
		$videoInfo['post_title'] = $this->expand_template( $args['pTitleTemplate'], $videoInfo );
		$videoInfo['post_content'] = $this->expand_template( $args['pPostTemplate'], $videoInfo );
	return 1;
	}

	/*
	 * Create the post with the video info using the video feed settings
	 */
	public function create_the_post( $args, $videoInfo ) {

		$user = $args['cUser'];
		if ( $args['cUser'] == "(random)" ) {
			$users = $this->get_users();
			shuffle( $users );
			$user = $users[0]->ID;
		}

		$vidpost = array(
			'post_name'     => sanitize_title( $videoInfo['post_title'] ),
			'post_title'    => $videoInfo['post_title'],
			'post_content'  => $videoInfo['post_content'],
			'post_status'   => $args['cPostStatus'],
			'post_type'     => $args['cPostType'],
			'post_author'   => $user,
			'post_category' => explode( ",", $args['cCategories'] ),
			);

		if ( $args['cPostStatus'] == 'publish' ) {
			$vidpost['post_status'] = 'draft';	// we will publish it later, after we import thumbnail
		}

		kses_remove_filters();
		$postID = wp_insert_post( $vidpost, TRUE );
		kses_init_filters();

		if ( is_wp_error( $postID ) ) {
			$this->info_message( "Error: wp_insert_post returned - " . $postID->get_error_message() );
		}
		else {
			add_post_meta( $postID, 'VideoSource', $videoInfo['videoSource'] );
			add_post_meta( $postID, 'VideoID', $videoInfo['videoID'] );
			add_post_meta( $postID, 'VideoDuration', $videoInfo['duration'] );
			add_post_meta( $postID, 'VideoLikes', $videoInfo['likeCount'] );
			add_post_meta( $postID, 'VideoDislikes', $videoInfo['dislikeCount'] );
			add_post_meta( $postID, 'VideoFavorites', $videoInfo['favoriteCount'] );

			// use compatible name with WP-PostViews. easy.
			add_post_meta( $postID, 'views', $videoInfo['viewCount'] );

			// make compatible with WP-PostRatings. a little more difficult
			if ( defined( 'WP_POSTRATINGS_VERSION' ) ) {
				$postratings_max = intval( get_option( 'postratings_max' ) );
			}
			else {
				$postratings_max = 5; // default
			}
			$post_ratings_users = $post_ratings_score = $post_ratings_average = 0;

			if ( isset( $videoInfo['likeCount'] ) ) { // YouTube stats
				$post_ratings_users = $videoInfo['likeCount'] + $videoInfo['dislikeCount'];
				$post_ratings_score = $videoInfo['likeCount'] * $postratings_max;
				$post_ratings_average = $post_ratings_users ? round($post_ratings_score/$post_ratings_users, 2) : 0;
			}
			add_post_meta( $postID, 'ratings_users', $post_ratings_users );
			add_post_meta( $postID, 'ratings_score', $post_ratings_score );
			add_post_meta( $postID, 'ratings_average', $post_ratings_average );
		}
		return $postID;
	}


	public function publish_the_post( $postID ) {
                $vidpost = array(
                        'ID'            => $postID,
                        'post_status'   => 'publish',
                );
		kses_remove_filters();
		$postID = wp_update_post( $vidpost, TRUE );
		kses_init_filters();
	}

	/*
	 * Expands post template with the thumbnail if %VideoImage% tag is used
	 * Have to do this AFTER post was created so thumb could be fetched and attached.
	 */
	public function process_the_thumbnail ( $postID, $thumbID, $videoInfo ) {
		// if %videoimage% tag is in post template we need to update_post we just created.
		$thumburl = wp_get_attachment_url( $thumbID );
		$videoInfo['post_content'] = str_ireplace( '%VideoImage%', $thumburl, $videoInfo['post_content'] );
		$vidpost = array(
			'post_content'  => $videoInfo['post_content'],
			'ID'		=> $postID
			);
		kses_remove_filters();
		$postID = wp_update_post( $vidpost, TRUE );
		kses_init_filters();
		if ( is_wp_error( $postID ) ) {
			$this->info_message( "Error: wp_update_post returned - " . $postID->get_error_message() );
		}
		return $postID;
	}

	/*
	 * Fetches the thumbnail from the video site
	 * Saves thumbnail in the media library
	 * Sets the post_thumbnail
	 */
	public function grab_thumbnail( $postID, $videoInfo ) {
		$this->info_message( __FUNCTION__ . ', fetching image: ' . htmlentities( $videoInfo['img'] ), 'updated fade' );
		if ( ! $postID || ! $videoInfo['post_title'] || ! $videoInfo['img'] ) {
			return 0;
		}
		if ( '' != get_the_post_thumbnail( $postID ) ) {	// shouldn't ever happen but check anyway
			return $this->info_message( __FUNCTION__ . ' post already has thumbnail', 'updated fade', 1 );
		}
		if ( ! function_exists( 'download_url' ) || ! function_exists( 'media_handle_upload' ) )  {
			require_once(ABSPATH . 'wp-admin' . '/includes/image.php');
			require_once(ABSPATH . 'wp-admin' . '/includes/file.php');
			require_once(ABSPATH . 'wp-admin' . '/includes/media.php');
		}
		$ext = pathinfo( $videoInfo['img'], PATHINFO_EXTENSION );
    		$tmp = download_url( $videoInfo['img'] );
    		if ( is_wp_error( $tmp ) ) {
			return $this->info_message( 'WP error in ' . __FUNCTION__ . ' - ' . $tmp->get_error_message() );
		}
		$filename = 'yt-' . $postID . '-' . sanitize_file_name( $videoInfo['post_title'] ) . '.' . $ext;
		// remove any unsafe characters:
		$filename = preg_replace( "/[^a-zA-Z0-9_\-.]/", '', $filename );
    		$file_array = array(
			'name' => $filename,
			'tmp_name' => $tmp
		);
    		$thumbID = media_handle_sideload( $file_array, 0 );
    		if ( is_wp_error( $thumbID ) )  {
			@unlink( $tmp );
			return $this->info_message( 'WP error in ' . __FUNCTION__ . ' = ' . $thumbID->get_error_message() );
		}
		$mediaID = set_post_thumbnail( $postID, $thumbID );
		if ( ! $mediaID ) {
			@unlink( $tmp );
			return $this->info_message( 'Error: set_post_thumbnail(' . $postID . ', ' . $thumbID . ') returned FALSE.' );
		}
		if ( file_exists( $tmp ) ) {
			@unlink( $tmp );	
		}
		$this->info_message( 'Image [' . $filename . '] added to media library for post id ' . $postID . '.', 'updated fade', 1 );
		return $thumbID;
	}

	/*
	 * See if specific video already exists
	 * Checks using meta 'VideoSource' and the unique 'VideoID'
	 */
	public function post_already_exists( $videoInfo ) {
		$query_args = array(
			'meta_query'	=> array(
				array(
					'key' => 'VideoSource',
					'value' => $videoInfo['videoSource'],
				),
				array(
					'key' => 'VideoID',
					'value' => $videoInfo['videoID'],
				)
			),
			'post_type' => 'any',
			'post_status' => array( 'draft', 'future', 'pending', 'private', 'publish', 'trash' ),
			'posts_per_page' => 1,
		);
 		$query = get_posts( $query_args );
		if ( !empty( $query ) ) {
			return 1;
		}
		return 0;
	}

	/**
	 * Send message to screen or the log file depending on type and vars
	 * Verbose mode TRUE = process all messages.
	 * Verbose mode FALSE = only process error messages and important messages.
	 */
	public function info_message( $msg, $class="error", $important=0 ) {
		if ( ! $important && $class != "error" ) {
			return 0;
		}
		else {		// msg during admin
                        echo "<div id='message' class='$class'><p>" . $msg . "</p></div>";
			ob_flush();flush();
		}
		return 0;
	}

	/**
	 * Return a value from array by key or a default value if it does not exist
	 * noZero - if array[key] is 0 will return default value instead 
	 */
	protected function get_value( $args, $key, $default='', $noZero=0 ) {
		if ( ! isset( $args[$key] ) ) {
			return $default;
		}
		else if ( $args[$key] == 0 && $noZero ) {
			return $default;
		}
		return str_replace( '"', "'", $args[$key] );	// have to convert because form uses value="key"
	}

	/**
	 * Get WP Users - no further processing needed at this time
	 */
	protected function get_users() {
		return get_users();
	}

	/**
	 * Get WP Post Types - no further processing needed at this time
	 */
	protected function get_post_types() {
		return get_post_types( '', 'names' );
	}

	/**
	 * Get WP Categories. Include empty categories.
	 */
	protected function get_categories() {
		$query_args = array(
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => 0
		);
		$categories = get_categories( $query_args );
		return $categories;
	}

    } // END class Video_Blogster_Lite
} // END if ( ! class_exists( 'Video_Blogster_Lite' ) )

////////////////////////////////////////////////////////////////////////////////////////////

if ( class_exists( 'Video_Blogster_Lite' ) ) {

	// instantiate the plugin class
	$video_blogster = new Video_Blogster_Lite();
}

////////////////////////////////////////////////////////////////////////////////////////////
?>
