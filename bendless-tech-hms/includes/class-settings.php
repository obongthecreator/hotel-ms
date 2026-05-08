<?php
/**
 * Tenant settings helper.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and writes hotel-scoped settings.
 */
class HRM_Settings {

	/**
	 * Get a hotel-scoped setting.
	 *
	 * @param string     $key      Setting key.
	 * @param mixed      $default  Default value.
	 * @param int|string $hotel_id Hotel ID. Uses current hotel when omitted.
	 * @return mixed
	 */
	public static function get( $key, $default = '', $hotel_id = 0 ) {
		global $wpdb;

		$key      = sanitize_key( $key );
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );

		if ( ! $hotel_id || '' === $key ) {
			return $default;
		}

		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT setting_value FROM {$wpdb->prefix}hrm_settings WHERE hotel_id = %d AND setting_key = %s LIMIT 1",
				$hotel_id,
				$key
			)
		);

		return null === $value ? $default : maybe_unserialize( $value );
	}

	/**
	 * Save a hotel-scoped setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $value    Setting value.
	 * @param int    $hotel_id Hotel ID. Uses current hotel when omitted.
	 * @return bool
	 */
	public static function set( $key, $value, $hotel_id = 0 ) {
		global $wpdb;

		$key      = sanitize_key( $key );
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );

		if ( ! $hotel_id || '' === $key ) {
			return false;
		}

		$serialized = maybe_serialize( $value );
		$table      = HRM_Database::table( 'settings' );
		$exists     = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT hotel_id FROM {$table} WHERE hotel_id = %d AND setting_key = %s LIMIT 1",
				$hotel_id,
				$key
			)
		);

		if ( $exists ) {
			return false !== $wpdb->update(
				$table,
				array( 'setting_value' => $serialized ),
				array(
					'hotel_id'    => $hotel_id,
					'setting_key' => $key,
				),
				array( '%s' ),
				array( '%d', '%s' )
			);
		}

		return (bool) $wpdb->insert(
			$table,
			array(
				'hotel_id'       => $hotel_id,
				'setting_key'    => $key,
				'setting_value'  => $serialized,
			),
			array( '%d', '%s', '%s' )
		);
	}

	/**
	 * Get the hotel currency symbol.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return string
	 */
	public static function currency_symbol( $hotel_id = 0 ) {
		return (string) self::get( 'currency_symbol', '₦', $hotel_id );
	}

	/**
	 * Format money for a hotel.
	 *
	 * @param float|int|string $amount   Amount.
	 * @param int              $hotel_id Hotel ID.
	 * @return string
	 */
	public static function money( $amount, $hotel_id = 0 ) {
		return self::currency_symbol( $hotel_id ) . number_format( (float) $amount, 2 );
	}
}
