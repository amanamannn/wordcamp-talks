<?php
/**
 * WordCamp Talks Settings.
 *
 * Administration / Settings
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * The settings sections
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return array the setting sections
 */
function wct_get_settings_sections() {
	$settings_sections =  array(
		'wc_talks_settings_core' => array(
			'title'    => __( 'Main Settings', 'wordcamp-talks' ),
			'callback' => 'wct_settings_core_section_callback',
			'page'     => 'wc_talks',
		),
	);

	if ( is_multisite() ) {
		$settings_sections['wc_talks_settings_multisite'] = array(
			'title'    => __( 'Network users settings', 'wordcamp-talks' ),
			'callback' => 'wct_settings_multisite_section_callback',
			'page'     => 'wc_talks',
		);
	}

	/**
	 * @param array $settings_sections the setting sections
	 */
	return (array) apply_filters( 'wct_get_settings_sections', $settings_sections );
}

/**
 * The different fields for setting sections
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return array the settings fields
 */
function wct_get_settings_fields() {
	$setting_fields = array(
		/** Core Section **************************************************************/

		'wc_talks_settings_core' => array(

			// Post Type Archive page title
			'_wc_talks_archive_title' => array(
				'title'             => __( 'WordCamp Talks archive page', 'wordcamp-talks' ),
				'callback'          => 'wct_archive_title_setting_callback',
				'sanitize_callback' => 'sanitize_text_field',
				'args'              => array()
			),

			// Closing date for the call for speakers.
			'_wc_talks_closing_date' => array(
				'title'             => __( 'Closing date.', 'wordcamp-talks' ),
				'callback'          => 'wct_closing_date_setting_callback',
				'sanitize_callback' => 'wct_sanitize_closing_date',
				'args'              => array()
			),

			// Can we add links to content ?
			'_wc_talks_editor_link' => array(
				'title'             => __( 'Links', 'wordcamp-talks' ),
				'callback'          => 'wct_editor_link_setting_callback',
				'sanitize_callback' => 'absint',
				'args'              => array()
			),

			// Is there a specific message to show if Pending is default status ?
			'_wc_talks_moderation_message' => array(
				'title'             => __( 'Moderation message', 'wordcamp-talks' ),
				'callback'          => 'wct_moderation_message_setting_callback',
				'sanitize_callback' => 'sanitize_text_field',
				'args'              => array()
			),

			// Is there a specific message to show to not logged in users ?
			'_wc_talks_login_message' => array(
				'title'             => __( 'Not logged in message', 'wordcamp-talks' ),
				'callback'          => 'wct_login_message_setting_callback',
				'sanitize_callback' => 'sanitize_text_field',
				'args'              => array()
			),

			// Customize the hint list
			'_wc_talks_hint_list' => array(
				'title'             => __( 'Rating stars hover captions', 'wordcamp-talks' ),
				'callback'          => 'wct_hint_list_setting_callback',
				'sanitize_callback' => 'wct_sanitize_list',
				'args'              => array()
			),

			// Are user's talks to rate profile area enabled ?
			'_wc_talks_to_rate_disabled' => array(
				'title'             => __( 'Disable the &quot;To rate&quot; tab for the user\'s profile', 'wordcamp-talks' ),
				'callback'          => 'wct_to_rate_profile_setting_callback',
				'sanitize_callback' => 'absint',
				'args'              => array()
			),

			// Disable comments in talks post type
			'_wc_talks_allow_comments' => array(
				'title'             => __( 'Comments', 'wordcamp-talks' ),
				'callback'          => 'wct_allow_comments_setting_callback',
				'sanitize_callback' => 'absint',
				'args'              => array()
			),

			// Private fields (not shown on front-end)
			'_wc_talks_private_fields_list' => array(
				'title'             => __( 'Private user profile fields', 'wordcamp-talks' ),
				'callback'          => 'wct_fields_list_setting_callback',
				'sanitize_callback' => 'wct_sanitize_user_fields_list',
				'args'              => array( 'type' => 'private' )
			),

			// Public fields (shown on front-end)
			'_wc_talks_public_fields_list' => array(
				'title'             => __( 'Public user profile fields', 'wordcamp-talks' ),
				'callback'          => 'wct_fields_list_setting_callback',
				'sanitize_callback' => 'wct_sanitize_user_fields_list',
				'args'              => array( 'type' => 'public' )
			),

			// Signup fields (shown into the signup form)
			'_wc_talks_signup_fields' => array(
				'title'             => __( 'Fields to add to the signup form.', 'wordcamp-talks' ),
				'callback'          => 'wct_signup_fields_setting_callback',
				'sanitize_callback' => 'wct_sanitize_list',
				'args'              => array()
			),

			// Signup fields (shown into the signup form)
			'_wc_talks_autolog_enabled' => array(
				'title'             => __( 'Signups Autolog', 'wordcamp-talks' ),
				'callback'          => 'wct_autolog_signups_fields_setting_callback',
				'sanitize_callback' => 'absint',
				'args'              => array()
			),
		)
	);

	/**
	 * Disable some settings if ratings are disabled.
	 */
	if ( wct_is_rating_disabled() ) {
		unset(
			$setting_fields['wc_talks_settings_core']['_wc_talks_hint_list'],
			$setting_fields['wc_talks_settings_core']['_wc_talks_to_rate_disabled']
		);
	}

	if ( ! wct_is_signup_allowed_for_current_blog() ) {
		unset(
			$setting_fields['wc_talks_settings_core']['_wc_talks_signup_fields'],
			$setting_fields['wc_talks_settings_core']['_wc_talks_autolog_enabled']
		);
	}

	if ( is_multisite() ) {
		/** Multisite Section *********************************************************/
		$setting_fields['wc_talks_settings_multisite'] = array();

		if ( wct_is_signup_allowed() ) {
			$setting_fields['wc_talks_settings_multisite']['_wc_talks_allow_signups'] = array(
				'title'             => __( 'Sign-ups', 'wordcamp-talks' ),
				'callback'          => 'wct_allow_signups_setting_callback',
				'sanitize_callback' => 'absint',
				'args'              => array()
			);
		}

		$setting_fields['wc_talks_settings_multisite']['_wc_talks_user_new_talk_set_role'] = array(
			'title'             => __( 'Default role for network users', 'wordcamp-talks' ),
			'callback'          => 'wct_get_user_default_role_setting_callback',
			'sanitize_callback' => 'absint',
			'args'              => array()
		);
	}

	/**
	 * @param array $setting_fields the setting fields
	 */
	return (array) apply_filters( 'wct_get_settings_fields', $setting_fields );
}


/**
 * Gives the setting fields for section
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @param  string $section_id
 * @return array  the fields for the requested section
 */
function wct_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) )
		return false;

	$fields = wct_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	/**
	 * @param array $retval      the setting fields
	 * @param string $section_id the section id
	 */
	return (array) apply_filters( 'wct_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Disable a settings field if its value rely on another setting field value
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @param  string $function function to get the option value
 * @param  string $option   the option value
 * @return string HTML output
 */
function wct_setting_disabled( $function = '', $option = '', $operator = '=' ) {
	if ( empty( $function ) || empty( $option ) || ! function_exists( $function ) ) {
		return;
	}

	$compare = call_user_func( $function );

	if ( '!=' === $operator ) {
		disabled( $compare !== $option );
		return;
	}

	disabled( $compare === $option );
}

/**
 * Disable a settings field if another option is set
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @param  string $option_key the option key
 * @return string HTML output
 */
function wct_setting_disabled_option( $option = '' ) {
	if( ! get_option( $option, false ) ) {
		return;
	}

	disabled( true );
}

/** Core settings callbacks ***************************************************/

/**
 * Some text to introduce the core settings section
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_settings_core_section_callback() {
	?>

	<p><?php _e( 'Customize WordCamp Talks features', 'wordcamp-talks' ); ?></p>
	<p class="description"><?php printf( esc_html__( 'Url of WordCamp Talks&#39;s main page: %s', 'wordcamp-talks' ), '<code>' . wct_get_root_url() .'</code>' ) ;?></p>

	<?php
}

/**
 * Archive page title callback
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_archive_title_setting_callback() {
	?>

	<input name="_wc_talks_archive_title" id="_wc_talks_archive_title" type="text" class="regular-text code" value="<?php echo esc_attr( wct_archive_title() ); ?>" />

	<?php
}

/**
 * Callback function for Talks submission closing date
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_closing_date_setting_callback() {
	$closing = wct_get_closing_date();
	?>
	<input name="_wc_talks_closing_date" id="_wc_talks_closing_date" type="text" class="regular-text code" placeholder="YYYY-MM-DD HH:II" value="<?php echo esc_attr( $closing ); ?>" />
	<p class="description"><?php esc_html_e( 'Date when the call for speakers will end.', 'wordcamp-talks' ); ?></p>
	<?php
}

/**
 * WP Editor's link button callback
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_editor_link_setting_callback() {
	?>

	<input name="_wc_talks_editor_link" id="_wc_talks_editor_link" type="checkbox" value="1" <?php checked( wct_talk_editor_link() ); ?> />
	<label for="_wc_talks_editor_link"><?php esc_html_e( 'Allow users to add links to their talks', 'wordcamp-talks' ); ?></label>

	<?php
}

/**
 * Custom moderation message callback
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_moderation_message_setting_callback() {
	?>

	<label for="_wc_talks_moderation_message"><?php esc_html_e( 'Customize the moderation message', 'wordcamp-talks' ); ?></label>
	<textarea name="_wc_talks_moderation_message" id="_wc_talks_moderation_message" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( wct_moderation_message() );?></textarea>

	<?php
}

/**
 * Custom login message callback
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_login_message_setting_callback() {
	?>

	<label for="_wc_talks_login_message"><?php esc_html_e( 'You can customize the message shown to not logged in users on the new talk form', 'wordcamp-talks' ); ?></label>
	<textarea name="_wc_talks_login_message" id="_wc_talks_login_message" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( wct_login_message() );?></textarea>

	<?php
}

/**
 * List of captions for the rating stars
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_hint_list_setting_callback() {
	$hintlist = wct_get_hint_list();
	$csv_hinlist = join( ',', $hintlist );
	?>

	<label for="_wc_talks_hint_list"><?php esc_html_e( 'You can customize the hover captions used for stars by using a comma separated list of captions', 'wordcamp-talks' ); ?></label>
	<input name="_wc_talks_hint_list" id="_wc_talks_hint_list" type="text" class="large-text code" value="<?php echo esc_attr( $csv_hinlist ); ?>" />

	<?php
}

/**
 * User's Profile "To Rate" tab disabling callback
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_to_rate_profile_setting_callback() {
	?>

	<input name="_wc_talks_to_rate_disabled" id="_wc_talks_to_rate_disabled" type="checkbox" value="1" <?php checked( wct_is_user_to_rate_disabled() ); ?> />
	<label for="_wc_talks_to_rate_disabled"><?php esc_html_e( '&quot;To rate&quot; user\'s profile tab.', 'wordcamp-talks' ); ?></label>

	<?php
}

/**
 * Global "opened" comments callback
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_allow_comments_setting_callback() {
	?>

	<input name="_wc_talks_allow_comments" id="_wc_talks_allow_comments" type="checkbox" value="1" <?php checked( wct_is_comments_allowed() ); ?> />
	<label for="_wc_talks_allow_comments"><?php esc_html_e( 'Allow users to add comments on talks', 'wordcamp-talks' ); ?></label>

	<?php
}

/**
 * List of labels for the user's profile fields
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @param  array  $args  Whether to get private or public fields.
 * @return string        HTML output.
 */
function wct_fields_list_setting_callback( $args = array() ) {
	if ( empty( $args['type'] ) ) {
		return;
	}

	if ( 'public' === $args['type'] ) {
		$label_list = wct_user_public_fields_list();
		$option     = '_wc_talks_public_fields_list';
	} else {
		$label_list = wct_user_private_fields_list();
		$option     = '_wc_talks_private_fields_list';
	}

	$csv_list   = join( ',', $label_list );
	?>

	<label for="<?php echo esc_attr( $option ); ?>"><?php printf( esc_html__( 'Adding a comma separated list of fields label will generate new %s contact informations for the user.', 'wordcamp-talks' ), $args['type'] ); ?></label>
	<input name="<?php echo esc_attr( $option ); ?>" id="<?php echo esc_attr( $option ); ?>" type="text" class="large-text code" value="<?php echo esc_attr( $csv_list ); ?>" />

	<?php
}

/**
 * List of field keys to include in the signup form.
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output.
 */
function wct_signup_fields_setting_callback() {
	$fields = wct_users_get_all_contact_methods();
	$signup = array_flip( wct_user_signup_fields() );
	?>
	<ul>
		<?php foreach ( $fields as $field_key => $field_name ):?>

			<li style="display:inline-block;width:45%;margin-right:1em">
				<label for="wct-signup-field-cb-<?php echo esc_attr( $field_key ); ?>">
					<input type="checkbox" class="checkbox" id="wct-signup-field-cb-<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( $field_key ); ?>" name="_wc_talks_signup_fields[]" <?php checked( isset( $signup[ $field_key ] ) ); ?>>
					<?php echo esc_html( $field_name ); ?>
				</label>
			</li>

		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Signups autolog callback
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_autolog_signups_fields_setting_callback() {
	?>

	<input name="_wc_talks_autolog_enabled" id="_wc_talks_autolog_enabled" type="checkbox" value="1" <?php checked( (bool) wct_user_autolog_after_signup() ); ?> />
	<label for="_wc_talks_autolog_enabled"><?php esc_html_e( 'Automagically log in just signed up users.', 'wordcamp-talks' ); ?></label>

	<?php
}


/**
 * Some text to introduce the multisite settings section
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_settings_multisite_section_callback() {
	?>

	<p><?php esc_html_e( 'Define your preferences about network users', 'wordcamp-talks' ); ?></p>

	<?php
}

/**
 * Does the blog is allowing us to manage signups?
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_allow_signups_setting_callback() {
	?>

	<input name="_wc_talks_allow_signups" id="_wc_talks_allow_signups" type="checkbox" value="1" <?php checked( wct_allow_signups() ); ?> />
	<label for="_wc_talks_allow_signups"><?php esc_html_e( 'Allow WordCamp Talks to manage signups for your site', 'wordcamp-talks' ); ?></label>

	<?php
}

/**
 * Default role for users posting an talk on this site callback
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @return string HTML output
 */
function wct_get_user_default_role_setting_callback() {
	?>

	<input name="_wc_talks_user_new_talk_set_role" id="_wc_talks_user_new_talk_set_role" type="checkbox" value="1" <?php checked( wct_get_user_default_role() ); ?> />
	<label for="_wc_talks_user_new_talk_set_role"><?php esc_html_e( 'Automatically set this site&#39;s default role for users posting a new talk and having no role on this site.', 'wordcamp-talks' ); ?></label>

	<?php
}

/** Custom sanitization *******************************************************/

/**
 * 'Sanitize' the date
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @param  string $option
 * @return string closing date
 */
function wct_sanitize_closing_date( $option = '' ) {
	if ( empty( $option ) ) {
		delete_option( '_wc_talks_closing_date' );
	}

	$now    = strtotime( date_i18n( 'Y-m-d H:i' ) );
	$option = strtotime( $option );

	if ( $option <= $now ) {
		return wct_get_closing_date( true );

	} else {
		return $option;
	}
}

/**
 * Sanitize list
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @param  string $option the comma separated values choosed by the admin
 * @return string         the sanitized value
 */
function wct_sanitize_list( $option = '' ) {
	if ( is_array( $option ) ) {
		$items = $option;
	} else {
		$items = explode( ',', wp_unslash( $option ) );
	}

	if ( ! is_array( $items ) ) {
		return false;
	}

	$items = array_map( 'sanitize_text_field', $items );

	/**
	 * @param array $items the sanitized items
	 */
	return apply_filters( 'wct_sanitize_list', $items );
}

/**
 * Sanitize the user profile fields
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 *
 * @param  string $option the comma separated values choosed by the admin
 * @return string         the sanitized value
 */
function wct_sanitize_user_fields_list( $option = '' ) {
	if ( is_array( $option ) ) {
		$labels = $option;
	} else {
		$labels = explode( ',', wp_unslash( $option ) );
	}

	if ( ! is_array( $labels ) ) {
		return false;
	}

	$labels = array_map( 'sanitize_text_field', $labels );
	$keys   = array();

	foreach ( $labels as $label ) {
		$keys[] = 'wct_' . sanitize_key( $label );
	}

	$fields = array_combine( $keys, $labels );

	/**
	 * @param array $fields the sanitized fields
	 */
	return apply_filters( 'wct_sanitize_user_fields_list', $fields );
}

/**
 * Displays the settings page
 *
 * @package WordCamp Talks
 * @subpackage admin/settings
 *
 * @since 1.0.0
 */
function wct_settings() {
	?>
	<div class="wrap">

		<h2><?php esc_html_e( 'WordCamp Talks Settings', 'wordcamp-talks' ) ?></h2>

		<form action="options.php" method="post">

			<?php settings_fields( 'wc_talks' ); ?>

			<?php do_settings_sections( 'wc_talks' ); ?>

			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'wordcamp-talks' ); ?>" />
			</p>
		</form>
	</div>
	<?php
}
