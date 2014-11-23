<?php
/**
 * =======================================
 * Advanced Invisible AntiSpam Helpers
 * =======================================
 * 
 * 
 * @author Matt Keys <matt@mattkeys.me>
 */

if ( ! defined( 'AIA_PLUGIN_FILE' ) ) {
	die();
}

class AIA_Helpers
{
	private $key_name;

	public function init()
	{
		add_action( 'wp_ajax_nopriv_aia_field_update', array( $this, 'generate_key_value' ) );
		add_action( 'wp_ajax_aia_field_update', array( $this, 'generate_key_value' ) );
		add_filter( 'pre_transient_aia_field_key', array( $this, 'store_expired_key_name' ) );

		$this->key_name = self::get_key_name();
	}

	public function generate_key_value()
	{
		echo json_encode( array(
			'field'	=> $this->key_name,
			'value'	=> wp_create_nonce( 'aia_antispam_' . $this->key_name )
		));

		exit;
	}

	static function get_key_name()
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

add_action(	'plugins_loaded', array( new AIA_Helpers, 'init' ) );

register_activation_hook( AIA_PLUGIN_FILE, array( new AIA_Helpers, 'create_key_name' ) );
