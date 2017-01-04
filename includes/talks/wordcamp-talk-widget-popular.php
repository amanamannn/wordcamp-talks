<?php

/**
 * List the most popular talks
 *
 * Popularity can be the average rate for some, or
 * the number of comments for others.. I guess tracking
 * page views would be another way to measure popularity..
 * But that's not supported and i doubt, i'll adventure
 * in this way in the future.
 *
 * @package WordCamp Talks
 * @subpackage talks/widgets
 *
 * @since 1.0.0
 */
 class WordCamp_Talk_Widget_Popular extends WP_Widget {

 	/**
	 * Constructor
	 *
	 * @package WordCamp Talks
	 * @subpackage talks/widgets
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$widget_ops = array( 'description' => __( 'List the most popular talks', 'wordcamp-talks' ) );
		parent::__construct( false, $name = __( 'WordCamp Talks Popular Talks', 'wordcamp-talks' ), $widget_ops );
	}

	/**
	 * Register the widget
	 *
	 * @package WordCamp Talks
	 * @subpackage talks/widgets
	 *
	 * @since 1.0.0
	 */
	public static function register_widget() {
		register_widget( 'WordCamp_Talk_Widget_Popular' );
	}

	/**
	 * Display the widget on front end
	 *
	 * @package WordCamp Talks
	 * @subpackage talks/widgets
	 *
	 * @since 1.0.0
	 */
	public function widget( $args = array(), $instance = array() ) {
		// Default to comment_count
		$orderby = 'comment_count';

		if ( ! empty( $instance['orderby'] ) ) {
			$orderby = $instance['orderby'];
		}

		// Default per_page is 5
		$number = 5;

		// No nav items to show !? Stop!
		if ( ! empty( $instance['number'] ) ) {
			$number = (int) $instance['number'];
		}

		// Default title is nothing
		$title = '';

		if ( ! empty( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		}

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// Popular argumments.
		$talk_args = apply_filters( 'wct_talks_popular_args', array(
			'per_page'  => $number,
			'orderby'   => $orderby,
			'is_widget' => true,
		) );

		if ( 'rates_count' == $orderby ) {
			wct_set_global( 'rating_widget', true );
		}

		// Display the popular talks
		if ( wct_talks_has_talks( $talk_args ) ) : ?>

		<ul>

			<?php while ( wct_talks_the_talks() ) : wct_talks_the_talk(); ?>

				<li>
					<a href="<?php wct_talks_the_permalink();?>" title="<?php wct_talks_the_title_attribute(); ?>"><?php wct_talks_the_title(); ?></a>
					<span class="count">
						<?php if ( 'comment_count' == $orderby ) :?>
							(<?php wct_talks_the_comment_number();?>)
						<?php else : ?>
							(<?php wct_talks_the_average_rating();?>)
						<?php endif ;?>
					</span>
				</li>

		<?php endwhile ;

		// Reset post data
		wct_maybe_reset_postdata(); ?>

		</ul>
		<?php
		endif;

		if ( 'rates_count' == $orderby ) {
			wct_set_global( 'rating_widget', false );
		}

		echo $args['after_widget'];
	}

	/**
	 * Update widget preferences
	 *
	 * @package WordCamp Talks
	 * @subpackage talks/widgets
	 *
	 * @since 1.0.0
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		if ( ! empty( $new_instance['title'] ) ) {
			$instance['title'] = strip_tags( wp_unslash( $new_instance['title'] ) );
		}

		$instance['orderby'] = sanitize_text_field( $new_instance['orderby'] );
		$instance['number'] = (int) $new_instance['number'];

		return $instance;
	}

	/**
	 * Display the form in Widgets Administration
	 *
	 * @package WordCamp Talks
	 * @subpackage talks/widgets
	 *
	 * @since 1.0.0
	 */
	public function form( $instance = array() ) {
		// Default to nothing
		$title = '';

		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		}

		// Available 'orderbys'
		$orderby = wct_talks_get_order_options();

		// The date choice is default so let's unset it
		unset( $orderby['date'] );

		// comment count is default, as it's possible to deactivate ratings
		$current_order = 'comment_count';

		if ( ! empty( $instance['orderby'] ) ) {
			$current_order = sanitize_text_field( $instance['orderby'] );
		}

		// Number default to 5
		$number = 5;

		if ( ! empty( $instance['number'] ) ) {
			$number = absint( $instance['number'] );
		}
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'wordcamp-talks' ) ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php esc_html_e( 'Type:', 'wordcamp-talks' ) ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>">

				<?php foreach ( $orderby as $key_order => $order_name ) : ?>

					<option value="<?php echo esc_attr( $key_order ) ?>" <?php selected( $key_order, $current_order ) ?>><?php echo esc_html( $order_name );?></option>

				<?php endforeach; ?>

			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of talks to show:', 'wordcamp-talks' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" />
		</p>


		<?php
	}
}
