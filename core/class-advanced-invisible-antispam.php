<?php
/**
 * =======================================
 * Advanced Invisible AntiSpam
 * =======================================
 * 
 * 
 * @author Matt Keys <matt@mattkeys.me>
 */

if ( ! defined( 'AIA_PLUGIN_FILE' ) ) {
	die();
}

class Advanced_Invisible_AntiSpam
{
	private $key_name;

	public function init()
	{
		add_action( 'comment_form', array( $this, 'add_token_placeholder' ) );
		add_filter( 'preprocess_comment', array( $this, 'check_token' ) );
		add_action( 'comment_form_top', array( $this, 'javascript_warning' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_action( 'wp_ajax_nopriv_aia_field_update', array( $this, 'generate_key_value' ) );
		add_action( 'wp_ajax_aia_field_update', array( $this, 'generate_key_value' ) );
		add_filter( 'pre_transient_aia_field_key', array( $this, 'store_expired_key_name' ) );

		$this->key_name = $this->get_key_name();
	}

	public function add_token_placeholder()
	{
		echo '<input id="aia_placeholder" type="hidden">';
	}

	public function check_token( $commentdata )
	{
		if ( is_admin() ) {
			return $commentdata;
		}

		$post_key		= isset( $_POST[ $this->key_name ] ) ? $_POST[ $this->key_name ] : false;
		$nonce_action	= 'aia_antispam_' . $this->key_name;

		if ( ! $post_key ) {
			$previous_field_name = get_option( 'aia_previous_field_key' );
			$post_key = isset( $_POST[ $previous_field_name ] ) ? $_POST[ $previous_field_name ] : false;
			$nonce_action	= 'aia_antispam_' . $previous_field_name;
		}

		if ( wp_verify_nonce( $post_key, $nonce_action ) ) {
			return $commentdata;
		}

		$failure_message	= __( 'Sorry, your comment could not be added due to an AntiSpam error. Make sure that your browser has JavaScript enabled before submitting comments. If problems persist please contact an administrator', 'AIA' );
		$failure_title		= __( 'AntiSpam Error', 'AIA' );

		do_action( 'aia-token-failed', $_POST );

		wp_die(
			apply_filters( 'aia-failure-message', $failure_message ),
			apply_filters( 'aia-failure-title', $failure_title ),
			array( 'back_link' => true )
		);
	}

	public function javascript_warning()
	{
		$warning_text = __( 'JavaScript is required to submit comments. Please enable JavaScript before proceeding.', 'AIA' );
		echo apply_filters( 'aia-javascript-warning', '<noscript>' . $warning_text . '</noscript>', $warning_text );
	}

	public function generate_key_value()
	{
		echo json_encode( array(
			'field'	=> $this->key_name,
			'value'	=> wp_create_nonce( 'aia_antispam_' . $this->key_name )
		));

		exit;
	}

	public function enqueue_script()
	{
		if ( is_singular() && comments_open() ) {
			wp_enqueue_script( 'advanced-invisible-antispam', AIA_PUBLIC_PATH . 'includes/aia.js', false, '1.1', true );
			wp_localize_script( 'advanced-invisible-antispam', 'AIA', array(
					'ajaxurl'	=> admin_url( 'admin-ajax.php' )
				)
			);
		}
	}

	private function get_key_name()
	{
		$field_key = get_transient( 'aia_field_key' );

		if ( ! $field_key ) {
			return $this->create_key_name();
		}

		return $field_key;
	}

	public function create_key_name()
	{
		$field_key = wp_generate_password( 12, false );

		set_transient( 'aia_field_key', $field_key, HOUR_IN_SECONDS * 2 );

		return $field_key;
	}

	public function store_expired_key_name()
	{
		$transient_option	= '_transient_aia_field_key';
		$transient_timeout	= '_transient_timeout_aia_field_key';

		if ( get_option( $transient_timeout ) < time() ) {
			$previous_key = get_option( $transient_option );
			update_option( 'aia_previous_field_key', $previous_key );

			delete_option( $transient_option  );
			delete_option( $transient_timeout );

			return $this->create_key_name();
		}

		return false;
	}

}

add_action(	'plugins_loaded', array( new Advanced_Invisible_AntiSpam, 'init' ) );

register_activation_hook( AIA_PLUGIN_FILE, array( new Advanced_Invisible_AntiSpam, 'create_key_name' ) );
