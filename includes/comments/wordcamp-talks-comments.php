<?php

/**
 * Main Comments class
 *
 * Actions and filters organized in a class
 * Its main goal is to disjoin comments about talks
 * from regular comments (other post types)
 *
 * @package WordCamp Talks
 * @subpackage comments/classes
 *
 * @since 1.0.0
 */
class WordCamp_Talks_Comments {

	/**
	 * Constructor
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->hooks();
	}

	/**
	 * Starts the class
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 */
	public static function start() {
		$wct = wct();

		if ( empty( $wct->comments ) ) {
			$wct->comments = new self;
		}

		return $wct->comments;
	}

	/**
	 * Setups some globals
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {
		/** Rewrite ids ***************************************************************/
		$this->post_type = 'talks';
		$this->comments_count = false;
		$this->talk_comments_count = false;
	}

	/**
	 * Hooks to disjoin comments about talks
	 * & to filter the email notifications
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 */
	private function hooks() {

		add_filter( 'get_post_status', array( $this, 'get_post_status' ), 10, 2 );
		add_filter( 'comment_post_redirect', array( $this, 'comment_post_redirect' ), 10, 2 );


		add_action( 'pre_get_comments',     array( $this, 'maybe_talk_comments' ),       10, 1 );

		add_action( 'wct_init',  array( $this, 'cache_comments_count' ) );
		add_filter( 'wp_count_comments',    array( $this, 'adjust_comment_count' ),      10, 1 );
		add_filter( 'widget_comments_args', array( $this, 'comments_widget_dummy_var' ), 10, 1 );
		add_filter( 'comments_clauses',     array( $this, 'maybe_alter_comments_query'), 10, 2 );

		// Make sure the comment notifications respect talk authors capability
		add_filter( 'comment_moderation_recipients', array( $this, 'moderation_recipients' ), 10, 2 );
		add_filter( 'comment_notification_text',     array( $this, 'comment_notification' ),  10, 2 );
		add_filter( 'comment_moderation_text',       array( $this, 'comment_notification' ),  10, 2 );
	}

	function get_post_status( $post_status, WP_Post $post ) {
		if ( '/wp-comments-post.php' !== $_SERVER['PHP_SELF'] ) {
			return $post_status;
		}
		if ( 'talks' === $post->post_type ) {
			return 'publish';
		}
	}

	function comment_post_redirect( $location, WP_Comment $comment) {
		if ( 'talks' === get_post_type( $comment->comment_post_ID ) ) {
			wp_die( get_permalink( $comment->comment_post_ID ) );
		}
		return $location;
	}

	/**
	 * Makes sure the post type is set to talks when in Talks
	 * administration screens
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_Comment_Query $wp_comment_query
	 */
	function maybe_talk_comments( $wp_comment_query = null ) {
		// Bail if Ajax
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( wct_is_admin() ) {
			$wp_comment_query->query_vars['post_type'] = $this->post_type;
		}
	}

	/**
	 * Catches the "all comments" count
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 */
	public function cache_comments_count() {
		$this->comment_count = wp_cache_get( 'comments-0', 'counts' );

		if ( empty( $this->comment_count ) ) {
			$this->comment_count = wp_count_comments();
		}

		// For internal use only, please don't use this action.
		do_action( 'wct_cache_comments_count' );
	}

	/**
	 * Adjust the comment count
	 * by counting comments about talks
	 * by removing this count to the global comment count
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 *
	 * @param   array $stats empty array to override in the method
	 * @return  array adjusted comment count stats
	 */
	public function adjust_comment_count( $stats = array() ) {
		if ( did_action( 'wct_cache_comments_count' ) ) {
			$this->talk_comment_count = wct_comments_count_comments();

			// Catch this count
			wct_set_global( 'talk_comment_count', $this->talk_comment_count );

			if ( ! did_action( 'wct_comments_count_cached' ) ) {
				$talk_comment_count = clone $this->talk_comment_count;

				foreach ( $this->comment_count as $key => $count ) {
					if ( ! empty( $talk_comment_count->{$key} ) ) {
						$this->comment_count->{$key} = $count - $talk_comment_count->{$key};
						unset( $talk_comment_count->{$key} );
					}
				}

				// For internal use only, please don't use this action.
				do_action( 'wct_comments_count_cached' );
			}

			$stats = $this->comment_count;
		}

		return $stats;
	}

	/**
	 * Adds a dummy argument to comments widget in order
	 * to be able to remove a bit later comments about talks
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 */
	public function comments_widget_dummy_var( $comment_args = array() ) {
		if ( empty( $comment_args['post_type' ] ) || $this->post_type != $comment_args['post_type' ] ) {
			$comment_args['strip_talks'] = true;
		}

		/**
		 * @param  array $comment_args the arguments of the comment query of the widget
		 */
		return apply_filters( 'wct_comments_widget_disjoin_talks', $comment_args );
	}

	/**
	 * Make sure talks comments are not mixed with posts ones
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 *
	 * @param   array  $pieces
	 * @param   WP_Comment_Query $wp_comment_query
	 * @return  array  $pieces
	 */
	public function maybe_alter_comments_query( $pieces = array(), $wp_comment_query = null ) {

		// Bail if Ajax
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return $pieces;
		}

		/* Bail if not the talks post type */
		if ( $this->post_type == $wp_comment_query->query_vars['post_type'] || wct_is_admin() ) {
			return $pieces;
		}

		/* Bail if strip talks query var is not set on front */
		if ( ! is_admin() && empty( $wp_comment_query->query_vars['strip_talks'] ) ) {
			return $pieces;
		}

		// Override pieces
		return array_merge( $pieces, self::comments_query_pieces( $pieces ) );
	}

	/**
	 * Removes recipients from the moderation notification if needed
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 *
	 * @param  array   $emails     list of emails that will receive the moderation notification
	 * @param  integer $comment_id the comment ID
	 * @return array               the emails, without the author
	 */
	public function moderation_recipients( $emails = array(), $comment_id = 0 ) {
		// Return if no comment ID
		if ( empty( $comment_id ) ) {
			return $emails;
		}

		// Get the comment
		$comment = wct_comments_get_comment( $comment_id );

		// check if it relates to an talk
		if ( empty( $comment->comment_post_type ) || 'talks' != $comment->comment_post_type ) {
			return $emails;
		}

		// We have a comment about an talk, catch it for a later use
		$this->{'comment_post_' . $comment_id} = $comment;

		/**
		 * Talk's author will receive a moderation email but won't be able
		 * to moderate it in WordPress Admin, so we need to remove their
		 * email from recipients list.
		 */
		$author_email = wct_users_get_user_data( 'id', $comment->comment_post_author, 'user_email' );

		// Found author's email in the list ? If so, let's remove it.
		if ( ! empty( $author_email ) && in_array( $author_email, $emails ) ) {
			$emails = array_diff( $emails, array( $author_email ) );
		}

		return $emails;
	}

	/**
	 * Edit the new comment notification message
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $message    the content of the notification
	 * @param  integer $comment_id the comment ID
	 * @return string              the content, edited if needed
	 */
	public function comment_notification( $message = '', $comment_id = 0 ) {
		// Return if no comment ID
		if ( empty( $comment_id ) ) {
			return $message;
		}

		// Check caught value
		if ( ! empty( $this->{'comment_post_' . $comment_id} ) ) {
			$comment = $this->{'comment_post_' . $comment_id};

		// Get the comment to check if it relates to an talk
		} else {
			$comment = wct_comments_get_comment( $comment_id );
		}

		// Return if no user_id or the comment does not relate to an talk
		if ( empty( $comment->comment_post_author ) || empty( $comment->comment_post_type ) || 'talks' != $comment->comment_post_type ) {
			return $message;
		}

		// First add a post type var at the end of the links
		preg_match_all( '/(comment|comments).php\?(.*)\\r\\n/', $message, $matches );

		if ( ! empty( $matches[2] ) ) {
			foreach ( $matches[2] as $action ) {
				$message = str_replace( $action, $action . '&post_type=talks', $message );
			}
		}

		// It's not a notification to author return the message
		if ( empty( $comment->comment_approved ) ) {
			return $message;
		}

		/**
		 * If we arrive here, then WordPress is notifying the author of the talk
		 * that a new comment has been posted and approuved on his talk. So if the
		 * talk's author does not have the capability to moderate comments, we need
		 * to make sure he won't receive the links to delete|trash|spam the comment
		 * The easiest way is to completely replace the content of the message sent.
		 */
		if ( ! user_can( $comment->comment_post_author, 'moderate_comments' ) ) {
			// reset the message
			$message = sprintf( __( 'New comment on your talk "%s"', 'wordcamp-talks' ), $comment->comment_post_title ) . "\r\n";
			$message .= __( 'Comment: ', 'wordcamp-talks' ) . "\r\n" . $comment->comment_content . "\r\n\r\n";
			$message .= sprintf( __( 'Permalink to the comment: %s', 'wordcamp-talks' ), wct_comments_get_comment_link( $comment_id ) ) . "\r\n";
		}

		/**
		 * @param  object $comment the comment object
		 */
		do_action( 'wct_comments_notify_author', $comment );

		return $message;
	}

	/**
	 * Build pieces to remove comments about talks
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 *
	 * @global  $wpdb
	 * @param   array  $pieces the comment sql query pieces
	 * @return  array  $pieces
	 */
	public static function comments_query_pieces( $pieces = array() ) {
		global $wpdb;

		if ( ! empty( $pieces ) ) {
			$pieces = array(
				'join'  => "JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID",
				'where' => $pieces['where'] . ' ' . $wpdb->prepare( "AND {$wpdb->posts}.post_type != %s", 'talks' ),
			);
		}

		return $pieces;
	}

	/**
	 * Count user's comments about talks
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 *
	 * @global  $wpdb
	 * @param   int  $user_id
	 * @return  int  $stats number of comments for the user
	 */
	public static function count_user_comments( $user_id = 0 ) {
		global $wpdb;

		// Initialize vars
		$stats = 0;
		$sql = array();

		if ( empty( $user_id ) ) {
			return $stats;
		}

		$sql['select']  = 'SELECT COUNT( * )';
		$sql['from']    = "FROM {$wpdb->comments} LEFT JOIN {$wpdb->posts} ON ( {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID )";
		$sql['where'][] = $wpdb->prepare( "{$wpdb->posts}.post_type = %s", 'talks' );
		$sql['where'][] = $wpdb->prepare( "{$wpdb->comments}.user_id = %d", $user_id );
		$sql['where'][] = $wpdb->prepare( "{$wpdb->comments}.comment_approved = %d", 1 );

		//Merge where clauses
		$sql['where'] = 'WHERE ' . join( ' AND ', $sql['where'] );

		$query = apply_filters( 'wct_count_user_comments_query', join( ' ', $sql ), $sql );

		$stats = (int) $wpdb->get_var( $query );

		return $stats;
	}

	/**
	 * Count comments about talks
	 *
	 * @package WordCamp Talks
	 * @subpackage comments/classes
	 *
	 * @since 1.0.0
	 *
	 * @global  $wpdb
	 * @return  object  $stats list of comments by type (approved, pending, spam, trash...)
	 */
	public static function count_talks_comments() {
		global $wpdb;

		// Initialize vars
		$stats = array();
		$sql = array();

		$sql['select']  = 'SELECT comment_approved, COUNT( * ) AS num_comments';
		$sql['from']    = "FROM {$wpdb->comments} LEFT JOIN {$wpdb->posts} ON ( {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID )";
		$sql['where']   = $wpdb->prepare( "WHERE {$wpdb->posts}.post_type = %s", 'talks' );
		$sql['groupby'] = 'GROUP BY comment_approved';

		$query = apply_filters( 'wct_count_talks_comments_query', join( ' ', $sql ), $sql );
		$count = $wpdb->get_results( $query, ARRAY_A );

		$stats = array(
			'approved'            => 0,
			'awaiting_moderation' => 0,
			'spam'                => 0,
			'trash'               => 0,
			'post-trashed'        => 0,
			'total_comments'      => 0,
			'all'                 => 0,
		);

		foreach ( $count as $row ) {
			switch ( $row['comment_approved'] ) {
				case 'trash':
					$stats['trash'] = $row['num_comments'];
					break;
				case 'post-trashed':
					$stats['post-trashed'] = $row['num_comments'];
					break;
				case 'spam':
					$stats['spam'] = $row['num_comments'];
					$stats['total_comments'] += $row['num_comments'];
					break;
				case '1':
					$stats['approved'] = $row['num_comments'];
					$stats['total_comments'] += $row['num_comments'];
					$stats['all'] += $row['num_comments'];
					break;
				case '0':
					$stats['awaiting_moderation'] = $row['num_comments'];
					$stats['total_comments'] += $row['num_comments'];
					$stats['all'] += $row['num_comments'];
					break;
				default:
					break;
			}
		}

		$stats['moderated'] = $stats['awaiting_moderation'];
		unset( $stats['awaiting_moderation'] );

		return (object) $stats;
	}

	/**
	 * Make sure speakers won't be notified in case a comment has been added to their talks
	 *
	 * @since 1.0.0
	 *
	 * @param  array   $emails     list of emails
	 * @param  int     $comment_id the comment id
	 * @return array               list of emails without the speaker one
	 */
	function donot_notify_talk_authors( $emails = array(), $comment_id = 0 ) {
		if ( empty( $comment_id ) ) {
			return $emails;
		}

		$comment = wct_comments_get_comment( $comment_id );

		// check if it relates to a talk
		if ( empty( $comment->comment_post_type ) || 'talks' !== $comment->comment_post_type ) {
			return $emails;
		}

		// Get the speaker email
		$author_email = wct_users_get_user_data( 'id', $comment->comment_post_author, 'user_email' );

		// Found speaker's email in the list ? If so, let's remove it.
		if ( ! empty( $author_email ) && in_array( $author_email, $emails ) ) {
			$emails = array_diff( $emails, array( $author_email ) );
		}

		return $emails;
	}
}
