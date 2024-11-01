<?php

defined( 'ABSPATH' ) or die( "Oops! This is a WordPress plugin and should not be called directly.\n" );

/**
 * Class for Video Blogster Lite YouTube support
 */
if ( ! class_exists( 'Video_Blogster_Lite_YouTube' )) {
    class Video_Blogster_Lite_YouTube {

	private $vbp=0;	// will point to main Video_Blogster_Lite instance
	private $apiKey=0;
	private $youtube_categories=0;		// fetch once per instance only when needed
	private $query_fields = array();	// options for current query
    	private $APIs = array(
		'videos.list' 		=> 'https://www.googleapis.com/youtube/v3/videos',
		'search.list' 		=> 'https://www.googleapis.com/youtube/v3/search',
		'categories.list' 	=> 'https://www.googleapis.com/youtube/v3/videoCategories',
	);
	private $batch_limit = 50;             // max amount we can request per query

	/**
	 * Create YouTube video source
	 * Point back to Video Blogster Lite object to use common functions
	 * Save the query fields for easy access
	 * Requires app API key
	 */
        public function __construct( $vbp, $query_fields, $key ) {
		$this->vbp = $vbp;
		$this->query_fields = $query_fields;
		$this->apiKey = $key;
	}

	/**
	 * Shortcut to proper API url
	 */
	private function getApi( $type )
	{
		return $this->APIs[$type];
	}

	/**
	 * Make the query and check for errors.
	 */
	private function queryApi( $url )
	{
                $this->vbp->info_message( __FUNCTION__ . ': ' . htmlentities( $url ), 'updated fade' );
                $response = wp_remote_get( $url, array( 'sslverify' => FALSE ) );
    		if ( is_wp_error( $response) ) {
			return $this->vbp->info_message( 'WP error in ' . __FUNCTION__ . ' - ' . $response->get_error_message() );
		}
                $data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( isset( $data->error ) ) {
                        return $this->vbp->info_message( 'YouTube returned Error: ' . $data->error->message );
		}
                if ( empty( $data->items ) )  {
                        $this->vbp->info_message( 'YouTube Notice: no videos found for this query.', 'updated fade', 1 );
                }
		return $data;
	}

	/**
	 * Query YouTube for existing categories
	 */
	public function grab_categories() {
		if ( $this->youtube_categories )	{
			return $this->youtube_categories;
		}
        	$query_args = array(
                	'part'		=> 'snippet',
                	'regionCode'	=> 'US',
                	'key'		=> $this->apiKey
        	);
		$url = $this->getApi( 'categories.list' ) . '?' . http_build_query( $query_args );
		$data = $this->queryApi($url);
		$this->youtube_categories = isset( $data->items ) ? $data->items : array();
		return $this->youtube_categories;
	}

	/**
	 * Have to make a separate query for the video details and stats after a search query
	 * accepts a search result from query and/or a specific videoID
	 * https://developers.google.com/youtube/v3/docs/videos/list
	 */
	private function grab_videolist_details( $videos, $videoIDs='' ) {
		$this->vbp->info_message( 'Getting video details from results...', 'updated fade', 1 );
		$base_url = $this->getApi( 'videos.list' );
		if ( ! empty( $videoIDs ) ) {
			$commaList = $videoIDs;
		}
		else {
			$ids = array();
			foreach ( $videos as $video )  {
				if ( isset( $video->id->videoId ) ) {
					$ids[] = $video->id->videoId;
				}
			}
			$commaList = implode( ',', $ids );
		}

        	$query_args = array(
                	'part'		=> 'snippet,contentDetails,statistics',
                	'id'		=> $commaList,
                	'key'		=> $this->apiKey
        	);
		$url = $base_url . '?' . http_build_query( $query_args );
		$data = $this->queryApi( $url );
		return isset( $data->items ) ? $data->items : 0;
	}

	/**
	 * Extract video details into our generic videoInfo array
	 */
	private function get_video_info( $video ) {
		$videoInfo = array();

		// snippet info: ===================================================
		$videoInfo['title'] = isset( $video->snippet->title ) ? $video->snippet->title : '';
		$videoInfo['desc'] = isset( $video->snippet->description ) ? $video->snippet->description : '';
		$videoInfo['channel'] = isset( $video->snippet->channelTitle ) ? $video->snippet->channelTitle : '';
		$videoInfo['association'] = $videoInfo['channel'];
		$videoInfo['categoryID'] = isset( $video->snippet->categoryId ) ? $video->snippet->categoryId : 0;
		$videoInfo['videoID'] = isset( $video->id ) ? $video->id : 0;
		$videoInfo['url'] = 'https://www.youtube.com/watch?v=' . $videoInfo['videoID'];

		$videoInfo['img'] = 0;
		// grab best standard thumbnail
		if ( isset( $video->snippet->thumbnails->default ) ) {				// ratio 4:3
			$videoInfo['img'] = $video->snippet->thumbnails->default->url;		// 120x90
		}
		if ( isset( $video->snippet->thumbnails->medium ) ) {				// ratio 16:9
			$videoInfo['img'] = $video->snippet->thumbnails->medium->url;		// 320x180
		}
		if ( isset( $video->snippet->thumbnails->standard ) ) {				// ratio 4:3
			$videoInfo['img'] = $video->snippet->thumbnails->standard->url;		// 640x480
		}
		// grab best high def img if available
		if ( isset( $video->snippet->thumbnails->high ) ) {				// ratio 4:3
			$videoInfo['img'] = $video->snippet->thumbnails->high->url;		// 480x360
		}
		if ( isset( $video->snippet->thumbnails->maxres ) ) {				// ratio 16:9
			$videoInfo['img'] = $video->snippet->thumbnails->maxres->url;		// 1280x720
		}

		// contentDetails info: =============================================
		$videoInfo['duration'] = '';
		if ( isset( $video->contentDetails->duration ) ) {
			$duration = new DateInterval($video->contentDetails->duration);
			if ( $duration->h > 0 ) {
				$videoInfo['duration'] .= $duration->h . ':';
			}
			$videoInfo['duration'] .= sprintf( '%02s:%02s' ,$duration->i,$duration->s);
		}


		// statistics info: ==================================================
		// not available on playlists :(
		$videoInfo['viewCount'] = isset( $video->statistics->viewCount ) ? $video->statistics->viewCount : 0;
		$videoInfo['likeCount'] = isset( $video->statistics->likeCount ) ? $video->statistics->likeCount : 0;
		$videoInfo['dislikeCount'] = isset( $video->statistics->dislikeCount ) ? $video->statistics->dislikeCount : 0;
		$videoInfo['favoriteCount'] = isset( $video->statistics->favoriteCount ) ? $video->statistics->favoriteCount : 0;
		$videoInfo['commentCount'] = isset( $video->statistics->commentCount ) ? $video->statistics->commentCount : 0;

		$videoInfo['videoSource'] = 'YouTube';
		return $videoInfo;
	}

	/**
	 * Takes an array of video details to process and create posts
	 */
	private function save_videos( $videoDetails ) {

		if ( ! $videoDetails ) {
			return;
		}

		foreach ( $videoDetails as $video )  {

			$this->vbp->info_message( 'Checking video: [' . $video->snippet->title . ']', 'updated fade' );
			$videoInfo = $this->get_video_info( $video );
			$this->vbp->info_message( 'YouTube video source url: ' . $videoInfo['url'], 'updated fade' );

			if ( ! $this->vbp->process_query_results( $this->query_fields, $videoInfo ) ) {
				continue;
			}
			$postID = $this->vbp->create_the_post( $this->query_fields, $videoInfo );
			
    			if ( is_wp_error( $postID ) )  {
				continue;
			}

			$this->vbp->info_message( 'YouTube video [' . $videoInfo['post_title'] . '] saved successfully with post id ' . $postID . '.', 'updated fade', 1 );

			$thumbID = $this->vbp->grab_thumbnail( $postID, $videoInfo );
			if ( ! $thumbID )  {
				$this->vbp->info_message( "No thumb saved for: \n" . htmlentities( $videoInfo['url'] ) );
			}

			if ($thumbID && FALSE !== stripos( $videoInfo['post_content'],'%VideoImage%' ) ) {
				//if %videoimage% tag in template we need to update_post we just created.
				$this->vbp->process_the_thumbnail( $postID, $thumbID, $videoInfo );
			}

			if ( $this->query_fields['cPostStatus'] == 'publish' ) {
				$this->vbp->publish_the_post( $postID );
				$this->vbp->info_message( 'YouTube video [' . $videoInfo['post_title'] . '] published successfully with post id ' . $postID . '.', 'updated fade', 1 );
			}
		}
	}

////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * https://developers.google.com/youtube/v3/docs/search/list
	 */
	public function grab_videos() {
		$totalVids = $this->query_fields['qNumVideos'];
		
        	$query_args = array(
			'part' 			=> 'snippet',
			'maxResults' 		=> $totalVids > $this->batch_limit ? $this->batch_limit : $totalVids,
			'order'			=> $this->query_fields['qOrderBy'],
			'type' 			=> 'video',
			'videoDuration'		=> $this->query_fields['qDuration'],
                	'key'			=> $this->apiKey
		);
		if ( $this->query_fields['qKeyphrase'] ) {
			$query_args['q'] = $this->query_fields['qKeyphrase'];
		}
		if ( $this->query_fields['qCategory'] ) {
			$query_args['videoCategoryId'] = $this->query_fields['qCategory'];
		}

		$query_args = apply_filters( 'vbl_youtube_args', $query_args );

		$this->vbp->info_message( 'Searching for videos with keyphrase: ' . $this->query_fields['qKeyphrase'], 'updated fade', 1 );
		$url = $this->getApi( 'search.list' ) . '?' . http_build_query( $query_args );
		$data = $this->queryApi( $url );
		// initial search query has limited data, so make a separate query to get video details
		$videoDetails = ( isset( $data->items ) ) ? $this->grab_videolist_details( $data->items, '' ) : 0;
		$this->save_videos( $videoDetails );
	}

	/**
	 * Make sure the query has one of the necessary fields to make a request
	 */
	public function is_query_valid () {
		if ( ! $this->query_fields['qNumVideos'] ) {
			return $this->vbp->info_message( "Error: number of videos cannot be 0" );
		}
		else if ( ! $this->query_fields['qKeyphrase'] ) {
			return $this->vbp->info_message( "Error: must set keyphrase query" );
		}
		return 1;
	}

    } // END class Video_Blogster_Lite_YouTube
} // END if ( ! class_exists( 'Video_Blogster_Lite_YouTube' ) )
?>
