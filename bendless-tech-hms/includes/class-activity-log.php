<?php
/**
 * Activity logging service.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records auditable per-hotel events.
 */
class HRM_Activity_Log {

	/**
	 * Insert an activity log row.
	 *
	 * @param int         $hotel_id    Hotel ID.
	 * @param int         $user_id     WordPress user ID.
	 * @param string      $action      Action key.
	 * @param string      $entity_type Entity type.
	 * @param int|null    $entity_id   Entity ID.
	 * @param string|null $details     JSON or text details.
	 * @return int|false
	 */
	public static function log( $hotel_id, $user_id, $action, $entity_type = '', $entity_id = null, $details = null ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$user_id  = absint( $user_id );
		$action   = sanitize_key( $action );

		if ( ! $hotel_id || '' === $action ) {
			return false;
		}

		$inserted = $wpdb->insert(
			HRM_Database::table( 'activity_log' ),
			array(
				'hotel_id'     => $hotel_id,
				'user_id'      => $user_id,
				'action'       => $action,
				'entity_type'  => sanitize_key( $entity_type ),
				'entity_id'    => null === $entity_id ? null : absint( $entity_id ),
				'details'      => null === $details ? '' : wp_kses_post( $details ),
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Return recent activity logs.
	 *
	 * @param int $hotel_id Hotel ID. Use 0 for all hotels.
	 * @param int $limit    Maximum rows.
	 * @return array
	 */
	public static function recent( $hotel_id = 0, $limit = 20 ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$limit    = max( 1, min( 100, absint( $limit ) ) );
		$table    = HRM_Database::table( 'activity_log' );

		if ( $hotel_id ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE hotel_id = %d ORDER BY created_at DESC LIMIT %d",
					$hotel_id,
					$limit
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Return recent activity logs with hotel names for platform pages.
	 *
	 * @param int $limit Maximum rows.
	 * @return array
	 */
	public static function recent_platform( $limit = 20 ) {
		global $wpdb;

		$limit          = max( 1, min( 100, absint( $limit ) ) );
		$activity_table = HRM_Database::table( 'activity_log' );
		$hotels_table   = HRM_Database::table( 'hotels' );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, h.hotel_name FROM {$activity_table} a LEFT JOIN {$hotels_table} h ON h.id = a.hotel_id ORDER BY a.created_at DESC LIMIT %d",
				$limit
			)
		);
	}
}
