<?php
/**
 * Room data service.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles tenant-isolated room reads and availability checks.
 */
class HRM_Rooms {

	/**
	 * Get a single room.
	 *
	 * @param int $room_id  Room ID.
	 * @param int $hotel_id Hotel ID. Uses current hotel when omitted.
	 * @return object|false
	 */
	public function get( $room_id, $hotel_id = 0 ) {
		global $wpdb;

		$room_id  = absint( $room_id );
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );

		if ( ! $room_id || ! $hotel_id ) {
			return false;
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hrm_rooms WHERE id = %d AND hotel_id = %d LIMIT 1",
				$room_id,
				$hotel_id
			)
		);
	}

	/**
	 * Return all rooms for a hotel.
	 *
	 * @param int   $hotel_id Hotel ID.
	 * @param array $args     Filter arguments.
	 * @return array
	 */
	public function all( $hotel_id = 0, $args = array() ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		if ( ! $hotel_id ) {
			return array();
		}

		$table  = HRM_Database::table( 'rooms' );
		$where  = array( 'hotel_id = %d' );
		$params = array( $hotel_id );

		if ( ! empty( $args['room_type'] ) && 'all' !== $args['room_type'] ) {
			$where[]  = 'room_type = %s';
			$params[] = sanitize_key( $args['room_type'] );
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY CAST(room_number AS UNSIGNED), room_number ASC';

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Return a public-ready room payload.
	 *
	 * @param object $room Room row.
	 * @return array
	 */
	public function to_public_payload( $room ) {
		$amenities = $this->parse_list_field( $room->amenities );
		$images    = $this->parse_list_field( $room->image_urls );

		return array(
			'id'              => (int) $room->id,
			'hotel_id'        => (int) $room->hotel_id,
			'room_number'     => (string) $room->room_number,
			'room_type'       => (string) $room->room_type,
			'floor'           => (int) $room->floor,
			'price_per_night' => (float) $room->price_per_night,
			'weekend_rate'    => null === $room->weekend_rate ? null : (float) $room->weekend_rate,
			'peak_rate'       => null === $room->peak_rate ? null : (float) $room->peak_rate,
			'max_guests'      => (int) $room->max_guests,
			'status'          => (string) $room->status,
			'description'     => (string) $room->description,
			'amenities'       => $amenities,
			'images'          => $images,
			'placeholder'     => $this->placeholder_gradient( $room->room_type ),
			'price_formatted' => HRM_Settings::money( $room->price_per_night, $room->hotel_id ),
		);
	}

	/**
	 * Return all public-ready rooms.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return array
	 */
	public function public_payloads( $hotel_id ) {
		$rooms   = $this->all( $hotel_id );
		$payload = array();

		foreach ( $rooms as $room ) {
			$payload[] = $this->to_public_payload( $room );
		}

		return $payload;
	}

	/**
	 * Return room grid data for the admin dashboard.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return array
	 */
	public function grid_payload( $hotel_id = 0 ) {
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$payload  = array();

		foreach ( $this->all( $hotel_id ) as $room ) {
			$payload[] = array(
				'id'              => (int) $room->id,
				'room_number'     => (string) $room->room_number,
				'room_type'       => (string) $room->room_type,
				'floor'           => (int) $room->floor,
				'price_per_night' => (float) $room->price_per_night,
				'price_formatted' => HRM_Settings::money( $room->price_per_night, $hotel_id ),
				'max_guests'      => (int) $room->max_guests,
				'status'          => (string) $room->status,
				'status_label'    => ucwords( str_replace( '_', ' ', $room->status ) ),
			);
		}

		return $payload;
	}

	/**
	 * Return room status counts for a hotel.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return array
	 */
	public function status_counts( $hotel_id = 0 ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$counts   = array(
			'available'   => 0,
			'occupied'    => 0,
			'cleaning'    => 0,
			'maintenance' => 0,
			'total'       => 0,
		);

		if ( ! $hotel_id ) {
			return $counts;
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, COUNT(*) AS total FROM {$wpdb->prefix}hrm_rooms WHERE hotel_id = %d GROUP BY status",
				$hotel_id
			)
		);

		foreach ( $rows as $row ) {
			$status = sanitize_key( $row->status );
			if ( isset( $counts[ $status ] ) ) {
				$counts[ $status ] = (int) $row->total;
			}
			$counts['total'] += (int) $row->total;
		}

		return $counts;
	}

	/**
	 * Create or update a room.
	 *
	 * @param array $data     Room data.
	 * @param int   $hotel_id Hotel ID.
	 * @return int|WP_Error
	 */
	public function save( $data, $hotel_id = 0 ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$room_id  = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
		$hotel    = HRM_License::get_hotel( $hotel_id );

		if ( ! $hotel_id || ! $hotel ) {
			return new WP_Error( 'hrm_missing_hotel', __( 'No hotel is available for this request.', 'hrm-pro' ) );
		}

		if ( ! HRM_License::hotel_can( $hotel_id, 'room_management' ) ) {
			return new WP_Error( 'hrm_room_feature_locked', __( 'Room management is not available on this plan.', 'hrm-pro' ) );
		}

		if ( ! $room_id ) {
			$plan = HRM_License::get_plan( $hotel->plan );
			if ( $plan && (int) $plan['rooms_limit'] >= 0 ) {
				$current_count = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}hrm_rooms WHERE hotel_id = %d",
						$hotel_id
					)
				);

				if ( $current_count >= (int) $plan['rooms_limit'] ) {
					return new WP_Error( 'hrm_room_limit', __( 'This hotel has reached the room limit for its plan.', 'hrm-pro' ) );
				}
			}
		}

		$room_number = isset( $data['room_number'] ) ? sanitize_text_field( $data['room_number'] ) : '';
		if ( '' === $room_number ) {
			return new WP_Error( 'hrm_room_number_required', __( 'Room number is required.', 'hrm-pro' ) );
		}

		$existing_room = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}hrm_rooms WHERE hotel_id = %d AND room_number = %s AND id <> %d LIMIT 1",
				$hotel_id,
				$room_number,
				$room_id
			)
		);

		if ( $existing_room ) {
			return new WP_Error( 'hrm_duplicate_room', __( 'Another room already uses this room number.', 'hrm-pro' ) );
		}

		$room_types = array( 'single', 'double', 'suite', 'deluxe', 'executive' );
		$statuses   = array( 'available', 'occupied', 'cleaning', 'maintenance' );
		$room_type  = isset( $data['room_type'] ) ? sanitize_key( $data['room_type'] ) : 'single';
		$status     = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'available';

		$row = array(
			'hotel_id'          => $hotel_id,
			'room_number'       => $room_number,
			'room_type'         => in_array( $room_type, $room_types, true ) ? $room_type : 'single',
			'floor'             => isset( $data['floor'] ) ? max( 1, absint( $data['floor'] ) ) : 1,
			'price_per_night'   => isset( $data['price_per_night'] ) ? max( 0, (float) $data['price_per_night'] ) : 0,
			'weekend_rate'      => isset( $data['weekend_rate'] ) && '' !== $data['weekend_rate'] ? max( 0, (float) $data['weekend_rate'] ) : null,
			'peak_rate'         => isset( $data['peak_rate'] ) && '' !== $data['peak_rate'] ? max( 0, (float) $data['peak_rate'] ) : null,
			'max_guests'        => isset( $data['max_guests'] ) ? max( 1, absint( $data['max_guests'] ) ) : 2,
			'status'            => in_array( $status, $statuses, true ) ? $status : 'available',
			'description'       => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
			'amenities'         => isset( $data['amenities'] ) ? sanitize_textarea_field( $data['amenities'] ) : '',
			'image_urls'        => isset( $data['image_urls'] ) ? sanitize_textarea_field( $data['image_urls'] ) : '',
		);

		$formats = array( '%d', '%s', '%s', '%d', '%f', '%f', '%f', '%d', '%s', '%s', '%s', '%s' );

		if ( $room_id ) {
			$room = $this->get( $room_id, $hotel_id );
			if ( ! $room ) {
				return new WP_Error( 'hrm_room_missing', __( 'The room could not be found.', 'hrm-pro' ) );
			}

			$updated = $wpdb->update(
				HRM_Database::table( 'rooms' ),
				$row,
				array(
					'id'       => $room_id,
					'hotel_id' => $hotel_id,
				),
				$formats,
				array( '%d', '%d' )
			);

			return false === $updated ? new WP_Error( 'hrm_room_update_failed', __( 'Room could not be updated.', 'hrm-pro' ) ) : $room_id;
		}

		$row['created_at'] = current_time( 'mysql' );
		$inserted          = $wpdb->insert(
			HRM_Database::table( 'rooms' ),
			$row,
			array_merge( $formats, array( '%s' ) )
		);

		return $inserted ? (int) $wpdb->insert_id : new WP_Error( 'hrm_room_insert_failed', __( 'Room could not be created.', 'hrm-pro' ) );
	}

	/**
	 * Update a room status.
	 *
	 * @param int    $room_id  Room ID.
	 * @param string $status   Status.
	 * @param int    $hotel_id Hotel ID.
	 * @return bool|WP_Error
	 */
	public function update_status( $room_id, $status, $hotel_id = 0 ) {
		global $wpdb;

		$room_id  = absint( $room_id );
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$status   = sanitize_key( $status );

		if ( ! in_array( $status, array( 'available', 'occupied', 'cleaning', 'maintenance' ), true ) ) {
			return new WP_Error( 'hrm_invalid_room_status', __( 'Invalid room status.', 'hrm-pro' ) );
		}

		if ( ! $this->get( $room_id, $hotel_id ) ) {
			return new WP_Error( 'hrm_room_missing', __( 'The room could not be found.', 'hrm-pro' ) );
		}

		$updated = $wpdb->update(
			HRM_Database::table( 'rooms' ),
			array( 'status' => $status ),
			array(
				'id'       => $room_id,
				'hotel_id' => $hotel_id,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Delete a room when no active booking depends on it.
	 *
	 * @param int $room_id  Room ID.
	 * @param int $hotel_id Hotel ID.
	 * @return bool|WP_Error
	 */
	public function delete( $room_id, $hotel_id = 0 ) {
		global $wpdb;

		$room_id  = absint( $room_id );
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );

		if ( ! $this->get( $room_id, $hotel_id ) ) {
			return new WP_Error( 'hrm_room_missing', __( 'The room could not be found.', 'hrm-pro' ) );
		}

		$active_bookings = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}hrm_bookings WHERE hotel_id = %d AND room_id = %d AND status IN ('pending','confirmed','checked_in')",
				$hotel_id,
				$room_id
			)
		);

		if ( $active_bookings > 0 ) {
			return new WP_Error( 'hrm_room_has_bookings', __( 'This room has active bookings and cannot be deleted.', 'hrm-pro' ) );
		}

		return false !== $wpdb->delete(
			HRM_Database::table( 'rooms' ),
			array(
				'id'       => $room_id,
				'hotel_id' => $hotel_id,
			),
			array( '%d', '%d' )
		);
	}

	/**
	 * Check room availability for a date range.
	 *
	 * @param int    $room_id   Room ID.
	 * @param string $check_in  Check-in date.
	 * @param string $check_out Check-out date.
	 * @param int    $hotel_id  Hotel ID.
	 * @return bool
	 */
	public function is_available( $room_id, $check_in, $check_out, $hotel_id = 0 ) {
		global $wpdb;

		$room_id   = absint( $room_id );
		$hotel_id  = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$check_in  = $this->normalize_date( $check_in );
		$check_out = $this->normalize_date( $check_out );

		if ( ! $room_id || ! $hotel_id || ! $check_in || ! $check_out || strtotime( $check_out ) <= strtotime( $check_in ) ) {
			return false;
		}

		$room = $this->get( $room_id, $hotel_id );
		if ( ! $room || 'maintenance' === $room->status ) {
			return false;
		}

		$blocked = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}hrm_bookings
				WHERE hotel_id = %d
				AND room_id = %d
				AND status IN ('pending','confirmed','checked_in')
				AND check_in < %s
				AND check_out > %s",
				$hotel_id,
				$room_id,
				$check_out,
				$check_in
			)
		);

		return 0 === $blocked;
	}

	/**
	 * Return availability payloads for all rooms.
	 *
	 * @param int    $hotel_id   Hotel ID.
	 * @param string $check_in   Check-in date.
	 * @param string $check_out  Check-out date.
	 * @param int    $guest_count Number of guests.
	 * @return array
	 */
	public function availability_payloads( $hotel_id, $check_in, $check_out, $guest_count = 1 ) {
		$hotel_id     = absint( $hotel_id );
		$guest_count  = max( 1, absint( $guest_count ) );
		$bookings     = new HRM_Bookings();
		$availability = array();

		foreach ( $this->all( $hotel_id ) as $room ) {
			$available = (int) $room->max_guests >= $guest_count && $this->is_available( $room->id, $check_in, $check_out, $hotel_id );
			$rate      = $available ? $bookings->calculate_rate( $room->id, $check_in, $check_out, $hotel_id ) : false;

			$availability[] = array(
				'room_id'         => (int) $room->id,
				'available'       => $available,
				'status'          => $available ? 'available' : 'unavailable',
				'total'           => $rate ? (float) $rate['total'] : 0,
				'subtotal'        => $rate ? (float) $rate['subtotal'] : 0,
				'nights'          => $rate ? (int) $rate['nights'] : 0,
				'price_formatted' => $rate ? HRM_Settings::money( $rate['total'], $hotel_id ) : '',
				'breakdown'       => $rate ? $rate['breakdown'] : array(),
			);
		}

		return $availability;
	}

	/**
	 * Return icon names for amenities.
	 *
	 * @return array
	 */
	public function amenity_icons() {
		return array(
			'wifi'        => 'solar:global-linear',
			'air'         => 'solar:snowflake-linear',
			'ac'          => 'solar:snowflake-linear',
			'tv'          => 'solar:tv-linear',
			'breakfast'   => 'solar:cup-hot-linear',
			'parking'     => 'solar:garage-linear',
			'pool'        => 'solar:waterdrops-linear',
			'gym'         => 'solar:dumbbell-large-linear',
			'bar'         => 'solar:glass-linear',
			'laundry'     => 'solar:hanger-2-linear',
			'workspace'   => 'solar:laptop-linear',
			'balcony'     => 'solar:home-wifi-linear',
			'king bed'    => 'solar:bed-linear',
			'queen bed'   => 'solar:bed-linear',
			'room service'=> 'solar:bell-linear',
		);
	}

	/**
	 * Normalize a date string to Y-m-d.
	 *
	 * @param string $date Date string.
	 * @return string
	 */
	private function normalize_date( $date ) {
		$timestamp = strtotime( sanitize_text_field( $date ) );

		return $timestamp ? date( 'Y-m-d', $timestamp ) : '';
	}

	/**
	 * Parse JSON, newline, or comma-separated text fields.
	 *
	 * @param string $value Raw field value.
	 * @return array
	 */
	private function parse_list_field( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return array();
		}

		$json = json_decode( $value, true );
		if ( is_array( $json ) ) {
			return array_values( array_filter( array_map( 'sanitize_text_field', $json ) ) );
		}

		$separator = false !== strpos( $value, "\n" ) ? "\n" : ',';

		return array_values( array_filter( array_map( 'sanitize_text_field', array_map( 'trim', explode( $separator, $value ) ) ) ) );
	}

	/**
	 * Return a type-specific CSS gradient placeholder.
	 *
	 * @param string $room_type Room type.
	 * @return string
	 */
	private function placeholder_gradient( $room_type ) {
		switch ( sanitize_key( $room_type ) ) {
			case 'suite':
			case 'executive':
				return 'from-slate-900 via-primary-900 to-amber-600';
			case 'deluxe':
				return 'from-primary-700 via-blue-500 to-emerald-400';
			case 'double':
				return 'from-slate-700 via-slate-500 to-primary-400';
			case 'single':
			default:
				return 'from-slate-800 via-slate-600 to-sky-400';
		}
	}
}
