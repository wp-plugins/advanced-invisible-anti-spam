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
	private $key;

	public function init()
	{
		add_action( 'comment_form', array( $this, 'add_token' ) );
		add_filter( 'preprocess_comment', array( $this, 'check_token' ) );
		add_action( 'comment_form_top', array( $this, 'javascript_warning' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_action( 'wp_ajax_nopriv_aia_field_update', array( $this, 'generate_key' ) );
		add_action( 'wp_ajax_aia_field_update', array( $this, 'generate_key' ) );

		$this->key = $this->get_key();
	}

	public function add_token( $fields )
	{
		echo '<input id="'.$this->key.'" name="'.$this->key.'" type="hidden" value="" />';
	}

	public function check_token( $commentdata )
	{
		$post_key = isset( $_POST[ $this->key ] ) ? $_POST[ $this->key ] : false;

		if ( wp_verify_nonce( $post_key, 'aia_antispam_' . $this->key ) ) {
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

	public function generate_key()
	{
		echo wp_create_nonce( 'aia_antispam_' . $this->key );
		exit;
	}

	public function enqueue_script()
	{
		if ( is_singular() && comments_open() ) {
			wp_enqueue_script( 'advanced-invisible-antispam', AIA_PUBLIC_PATH . 'includes/aia.js', false, '1.0', true );
			wp_localize_script( 'advanced-invisible-antispam', 'AIA', array(
					'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
					'field'		=> $this->key
				)
			);
		}
	}

	private function get_key()
	{
		$field_key = get_option( 'aia_key' );

		if ( $field_key ) {
			return $field_key;
		}

		return $this->create_key();
	}

	public function create_key()
	{
		$field_key = wp_generate_password( 12, false );

		update_option( 'aia_key', $field_key );

		return $field_key;
	}

}

add_action(	'plugins_loaded', array( new Advanced_Invisible_AntiSpam, 'init' ) );

register_activation_hook( AIA_PLUGIN_FILE, array( new Advanced_Invisible_AntiSpam, 'create_key' ) );
