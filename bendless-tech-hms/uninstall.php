<?php
/**
 * Uninstall cleanup for BENDLESS TECH HMS.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes plugin data on uninstall.
 */
class HRM_Pro_Uninstaller {

	/**
	 * Run uninstall cleanup.
	 *
	 * @return void
	 */
	public static function uninstall() {
		self::drop_tables();
		self::delete_options();
		self::remove_roles();
		self::clear_cron_jobs();
	}

	/**
	 * Drop all plugin database tables.
	 *
	 * @return void
	 */
	private static function drop_tables() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'hrm_hotels',
			$wpdb->prefix . 'hrm_rooms',
			$wpdb->prefix . 'hrm_guests',
			$wpdb->prefix . 'hrm_bookings',
			$wpdb->prefix . 'hrm_subscriptions',
			$wpdb->prefix . 'hrm_activity_log',
			$wpdb->prefix . 'hrm_settings',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}

	/**
	 * Delete all plugin options.
	 *
	 * @return void
	 */
	private static function delete_options() {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'hrm_' ) . '%'
			)
		);
	}

	/**
	 * Remove plugin roles and caps.
	 *
	 * @return void
	 */
	private static function remove_roles() {
		$capabilities = array(
			'hrm_manage_bookings',
			'hrm_manage_rooms',
			'hrm_manage_guests',
			'hrm_view_reports',
			'hrm_manage_settings',
			'hrm_manage_platform',
		);

		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( $capabilities as $capability ) {
				$administrator->remove_cap( $capability );
			}
		}

		remove_role( 'hrm_receptionist' );
	}

	/**
	 * Clear plugin cron hooks.
	 *
	 * @return void
	 */
	private static function clear_cron_jobs() {
		$crons = _get_cron_array();
		if ( ! is_array( $crons ) ) {
			return;
		}

		foreach ( $crons as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $events ) {
				if ( 0 !== strpos( $hook, 'hrm_' ) ) {
					continue;
				}

				foreach ( $events as $event ) {
					wp_unschedule_event( $timestamp, $hook, isset( $event['args'] ) ? $event['args'] : array() );
				}
			}
		}
	}
}

HRM_Pro_Uninstaller::uninstall();
