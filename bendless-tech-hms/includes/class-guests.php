<?php
/**
 * Guest data service.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles tenant-isolated guest records.
 */
class HRM_Guests {

	/**
	 * Get a guest by ID.
	 *
	 * @param int $guest_id Guest ID.
	 * @param int $hotel_id Hotel ID. Uses current hotel when omitted.
	 * @return object|false
	 */
	public function get( $guest_id, $hotel_id = 0 ) {
		global $wpdb;

		$guest_id = absint( $guest_id );
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );

		if ( ! $guest_id || ! $hotel_id ) {
			return false;
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hrm_guests WHERE id = %d AND hotel_id = %d LIMIT 1",
				$guest_id,
				$hotel_id
			)
		);
	}

	/**
	 * Find a guest by phone.
	 *
	 * @param string $phone    Phone number.
	 * @param int    $hotel_id Hotel ID.
	 * @return object|false
	 */
	public function find_by_phone( $phone, $hotel_id ) {
		global $wpdb;

		$phone    = $this->normalize_phone( $phone );
		$hotel_id = absint( $hotel_id );

		if ( '' === $phone || ! $hotel_id ) {
			return false;
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hrm_guests WHERE hotel_id = %d AND phone = %s LIMIT 1",
				$hotel_id,
				$phone
			)
		);
	}

	/**
	 * Create or update a frontend guest record.
	 *
	 * @param int   $hotel_id Hotel ID.
	 * @param array $data     Guest data.
	 * @return int|WP_Error
	 */
	public function upsert_from_frontend( $hotel_id, $data ) {
		global $wpdb;

		$hotel_id   = absint( $hotel_id );
		$full_name  = isset( $data['full_name'] ) ? sanitize_text_field( $data['full_name'] ) : '';
		$phone      = isset( $data['phone'] ) ? $this->normalize_phone( $data['phone'] ) : '';
		$email      = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
		$id_type    = isset( $data['id_type'] ) ? sanitize_text_field( $data['id_type'] ) : '';
		$id_number  = isset( $data['id_number'] ) ? sanitize_text_field( $data['id_number'] ) : '';

		if ( ! $hotel_id || '' === $full_name || '' === $phone ) {
			return new WP_Error( 'hrm_guest_required', __( 'Guest name and phone number are required.', 'hrm-pro' ) );
		}

		$guest = $this->find_by_phone( $phone, $hotel_id );

		if ( $guest ) {
			$updated = $wpdb->update(
				HRM_Database::table( 'guests' ),
				array(
					'full_name' => $full_name,
					'email'     => $email,
					'id_type'   => $id_type,
					'id_number' => $id_number,
				),
				array(
					'id'       => (int) $guest->id,
					'hotel_id' => $hotel_id,
				),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d', '%d' )
			);

			if ( false === $updated ) {
				return new WP_Error( 'hrm_guest_update_failed', __( 'Guest record could not be updated.', 'hrm-pro' ) );
			}

			return (int) $guest->id;
		}

		$inserted = $wpdb->insert(
			HRM_Database::table( 'guests' ),
			array(
				'hotel_id'   => $hotel_id,
				'full_name'  => $full_name,
				'phone'      => $phone,
				'email'      => $email,
				'id_type'    => $id_type,
				'id_number'  => $id_number,
				'flag'       => 'none',
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'hrm_guest_insert_failed', __( 'Guest record could not be created.', 'hrm-pro' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Return whether a guest is blacklisted.
	 *
	 * @param int $guest_id Guest ID.
	 * @param int $hotel_id Hotel ID.
	 * @return bool
	 */
	public function is_blacklisted( $guest_id, $hotel_id ) {
		$guest = $this->get( $guest_id, $hotel_id );

		return $guest && HRM_License::hotel_can( $hotel_id, 'guest_blacklist' ) && 'blacklist' === sanitize_key( $guest->flag );
	}

	/**
	 * Return enriched guest rows for the admin guest directory.
	 *
	 * @param int   $hotel_id Hotel ID.
	 * @param array $filters  Search and flag filters.
	 * @return array
	 */
	public function all( $hotel_id = 0, $filters = array() ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		if ( ! $hotel_id ) {
			return array();
		}

		$guests_table   = HRM_Database::table( 'guests' );
		$bookings_table = HRM_Database::table( 'bookings' );
		$where          = array( 'g.hotel_id = %d' );
		$params         = array( $hotel_id );

		if ( ! empty( $filters['flag'] ) && 'all' !== $filters['flag'] ) {
			$where[]  = 'g.flag = %s';
			$params[] = sanitize_key( $filters['flag'] );
		}

		if ( ! empty( $filters['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
			$where[]  = '(g.full_name LIKE %s OR g.phone LIKE %s OR g.email LIKE %s OR g.id_number LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT g.*,
				(SELECT COUNT(*) FROM {$bookings_table} b WHERE b.hotel_id = g.hotel_id AND b.guest_id = g.id) AS bookings_count,
				(SELECT COALESCE(SUM(b.amount_paid), 0) FROM {$bookings_table} b WHERE b.hotel_id = g.hotel_id AND b.guest_id = g.id) AS total_spent,
				(SELECT MAX(b.check_out) FROM {$bookings_table} b WHERE b.hotel_id = g.hotel_id AND b.guest_id = g.id) AS last_stay
			FROM {$guests_table} g
			WHERE " . implode( ' AND ', $where ) . '
			ORDER BY g.created_at DESC
			LIMIT 200';

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Return guest directory stats.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return array
	 */
	public function stats( $hotel_id = 0 ) {
		global $wpdb;

		$hotel_id       = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$guests_table   = HRM_Database::table( 'guests' );
		$bookings_table = HRM_Database::table( 'bookings' );

		if ( ! $hotel_id ) {
			return array(
				'total'     => 0,
				'vip'       => 0,
				'blacklist' => 0,
				'repeat'    => 0,
			);
		}

		$repeat_sql = "SELECT COUNT(*) FROM (
			SELECT guest_id FROM {$bookings_table}
			WHERE hotel_id = %d
			GROUP BY guest_id
			HAVING COUNT(*) > 1
		) repeat_guests";

		return array(
			'total'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$guests_table} WHERE hotel_id = %d", $hotel_id ) ),
			'vip'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$guests_table} WHERE hotel_id = %d AND flag = %s", $hotel_id, 'vip' ) ),
			'blacklist' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$guests_table} WHERE hotel_id = %d AND flag = %s", $hotel_id, 'blacklist' ) ),
			'repeat'    => (int) $wpdb->get_var( $wpdb->prepare( $repeat_sql, $hotel_id ) ),
		);
	}

	/**
	 * Return a complete profile with booking history.
	 *
	 * @param int $guest_id Guest ID.
	 * @param int $hotel_id Hotel ID.
	 * @return array|false
	 */
	public function profile( $guest_id, $hotel_id = 0 ) {
		global $wpdb;

		$guest_id = absint( $guest_id );
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$guest    = $this->get( $guest_id, $hotel_id );

		if ( ! $guest ) {
			return false;
		}

		$bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, r.room_number, r.room_type
				FROM {$wpdb->prefix}hrm_bookings b
				INNER JOIN {$wpdb->prefix}hrm_rooms r ON r.id = b.room_id AND r.hotel_id = b.hotel_id
				WHERE b.hotel_id = %d AND b.guest_id = %d
				ORDER BY b.check_in DESC, b.created_at DESC
				LIMIT 25",
				$hotel_id,
				$guest_id
			)
		);

		$booking_payloads = array();
		foreach ( $bookings as $booking ) {
			$booking_payloads[] = array(
				'id'             => (int) $booking->id,
				'ref'            => '#HRM' . (int) $booking->id,
				'room'           => trim( $booking->room_number . ' (' . ucwords( str_replace( '_', ' ', $booking->room_type ) ) . ')' ),
				'dates'          => date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) . ' - ' . date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ),
				'status'         => ucwords( str_replace( '_', ' ', $booking->status ) ),
				'payment_status' => ucwords( str_replace( '_', ' ', $booking->payment_status ) ),
				'total'          => HRM_Settings::money( $booking->total_amount, $hotel_id ),
			);
		}

		$guest->bookings_count = count( $bookings );

		return array(
			'guest'    => $this->to_admin_payload( $guest, $hotel_id ),
			'bookings' => $booking_payloads,
		);
	}

	/**
	 * Update guest VIP/blacklist status and manager notes.
	 *
	 * @param int    $guest_id Guest ID.
	 * @param int    $hotel_id Hotel ID.
	 * @param string $flag     Flag value.
	 * @param string $reason   Flag reason.
	 * @param string $notes    Internal notes.
	 * @return bool|WP_Error
	 */
	public function save_flag( $guest_id, $hotel_id, $flag, $reason = '', $notes = '' ) {
		global $wpdb;

		$guest_id = absint( $guest_id );
		$hotel_id = absint( $hotel_id );
		$flag     = sanitize_key( $flag );

		if ( ! in_array( $flag, array( 'none', 'vip', 'blacklist' ), true ) ) {
			return new WP_Error( 'hrm_invalid_guest_flag', __( 'Invalid guest flag.', 'hrm-pro' ) );
		}

		if ( 'blacklist' === $flag && ! HRM_License::hotel_can( $hotel_id, 'guest_blacklist' ) ) {
			return new WP_Error( 'hrm_guest_blacklist_locked', __( 'Guest blacklist is not available on this plan.', 'hrm-pro' ) );
		}

		if ( ! $this->get( $guest_id, $hotel_id ) ) {
			return new WP_Error( 'hrm_guest_missing', __( 'The guest could not be found.', 'hrm-pro' ) );
		}

		$updated = $wpdb->update(
			HRM_Database::table( 'guests' ),
			array(
				'flag'        => $flag,
				'flag_reason' => sanitize_textarea_field( $reason ),
				'notes'       => sanitize_textarea_field( $notes ),
			),
			array(
				'id'       => $guest_id,
				'hotel_id' => $hotel_id,
			),
			array( '%s', '%s', '%s' ),
			array( '%d', '%d' )
		);

		return false === $updated ? new WP_Error( 'hrm_guest_flag_failed', __( 'Guest flag could not be saved.', 'hrm-pro' ) ) : true;
	}

	/**
	 * Return safe public guest data.
	 *
	 * @param object $guest Guest row.
	 * @return array
	 */
	public function to_public_payload( $guest ) {
		return array(
			'id'          => (int) $guest->id,
			'full_name'   => (string) $guest->full_name,
			'phone'       => (string) $guest->phone,
			'email'       => (string) $guest->email,
			'flag'        => (string) $guest->flag,
			'flag_reason' => isset( $guest->flag_reason ) ? (string) $guest->flag_reason : '',
			'is_vip'      => 'vip' === sanitize_key( $guest->flag ),
			'is_blocked'  => isset( $guest->hotel_id ) && HRM_License::hotel_can( (int) $guest->hotel_id, 'guest_blacklist' ) && 'blacklist' === sanitize_key( $guest->flag ),
		);
	}

	/**
	 * Return admin-safe guest data for modals.
	 *
	 * @param object $guest    Guest row.
	 * @param int    $hotel_id Hotel ID.
	 * @return array
	 */
	public function to_admin_payload( $guest, $hotel_id = 0 ) {
		$hotel_id = absint( $hotel_id ? $hotel_id : $guest->hotel_id );

		return array_merge(
			$this->to_public_payload( $guest ),
			array(
				'id_type'        => isset( $guest->id_type ) ? (string) $guest->id_type : '',
				'id_number'      => isset( $guest->id_number ) ? (string) $guest->id_number : '',
				'notes'          => isset( $guest->notes ) ? (string) $guest->notes : '',
				'created_at'      => isset( $guest->created_at ) ? date_i18n( get_option( 'date_format' ), strtotime( $guest->created_at ) ) : '',
				'bookings_count' => isset( $guest->bookings_count ) ? (int) $guest->bookings_count : 0,
				'total_spent'    => isset( $guest->total_spent ) ? HRM_Settings::money( $guest->total_spent, $hotel_id ) : HRM_Settings::money( 0, $hotel_id ),
				'last_stay'      => ! empty( $guest->last_stay ) ? date_i18n( get_option( 'date_format' ), strtotime( $guest->last_stay ) ) : __( 'No stays yet', 'hrm-pro' ),
			)
		);
	}

	/**
	 * Return recent guests for admin lookup.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $search   Search term.
	 * @param int    $limit    Maximum rows.
	 * @return array
	 */
	public function search( $hotel_id, $search = '', $limit = 12 ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$search   = sanitize_text_field( $search );
		$limit    = max( 1, min( 50, absint( $limit ) ) );

		if ( ! $hotel_id ) {
			return array();
		}

		$table = HRM_Database::table( 'guests' );

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE hotel_id = %d AND (full_name LIKE %s OR phone LIKE %s OR email LIKE %s) ORDER BY created_at DESC LIMIT %d",
					$hotel_id,
					$like,
					$like,
					$like,
					$limit
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE hotel_id = %d ORDER BY created_at DESC LIMIT %d",
				$hotel_id,
				$limit
			)
		);
	}

	/**
	 * Normalize phone input for matching.
	 *
	 * @param string $phone Phone value.
	 * @return string
	 */
	private function normalize_phone( $phone ) {
		$phone = sanitize_text_field( $phone );
		$phone = preg_replace( '/[^\d+]/', '', $phone );

		return $phone ? $phone : '';
	}
}
