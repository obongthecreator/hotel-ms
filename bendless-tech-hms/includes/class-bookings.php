<?php
/**
 * Booking data service.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles booking creation, pricing, and payment sync.
 */
class HRM_Bookings {

	/**
	 * Get a booking by ID.
	 *
	 * @param int $booking_id Booking ID.
	 * @param int $hotel_id   Hotel ID. Uses current hotel when omitted.
	 * @return object|false
	 */
	public function get( $booking_id, $hotel_id = 0 ) {
		global $wpdb;

		$booking_id = absint( $booking_id );
		$hotel_id   = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );

		if ( ! $booking_id ) {
			return false;
		}

		if ( $hotel_id ) {
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}hrm_bookings WHERE id = %d AND hotel_id = %d LIMIT 1",
					$booking_id,
					$hotel_id
				)
			);
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hrm_bookings WHERE id = %d LIMIT 1",
				$booking_id
			)
		);
	}

	/**
	 * Get a booking by Paystack reference.
	 *
	 * @param string $reference Paystack reference.
	 * @return object|false
	 */
	public function get_by_paystack_reference( $reference ) {
		global $wpdb;

		$reference = sanitize_text_field( $reference );
		if ( '' === $reference ) {
			return false;
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hrm_bookings WHERE paystack_reference = %s LIMIT 1",
				$reference
			)
		);
	}

	/**
	 * Return admin booking rows with guest and room data.
	 *
	 * @param int   $hotel_id Hotel ID.
	 * @param array $filters  Optional filters.
	 * @return array
	 */
	public function admin_list( $hotel_id = 0, $filters = array() ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		if ( ! $hotel_id ) {
			return array();
		}

		$bookings_table = HRM_Database::table( 'bookings' );
		$rooms_table    = HRM_Database::table( 'rooms' );
		$guests_table   = HRM_Database::table( 'guests' );
		$where          = array( 'b.hotel_id = %d' );
		$params         = array( $hotel_id );

		if ( ! empty( $filters['status'] ) && 'all' !== $filters['status'] ) {
			$where[]  = 'b.status = %s';
			$params[] = sanitize_key( $filters['status'] );
		}

		if ( ! empty( $filters['guest_id'] ) ) {
			$where[]  = 'b.guest_id = %d';
			$params[] = absint( $filters['guest_id'] );
		}

		if ( ! empty( $filters['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
			$where[]  = '(g.full_name LIKE %s OR g.phone LIKE %s OR r.room_number LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT b.*, r.room_number, r.room_type, g.full_name AS guest_name, g.phone AS guest_phone, g.email AS guest_email, g.id_type AS guest_id_type, g.id_number AS guest_id_number, g.flag AS guest_flag
			FROM {$bookings_table} b
			INNER JOIN {$rooms_table} r ON r.id = b.room_id AND r.hotel_id = b.hotel_id
			INNER JOIN {$guests_table} g ON g.id = b.guest_id AND g.hotel_id = b.hotel_id
			WHERE " . implode( ' AND ', $where ) . '
			ORDER BY b.check_in DESC, b.created_at DESC
			LIMIT 200';

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Return a single admin booking row with guest and room data.
	 *
	 * @param int $booking_id Booking ID.
	 * @param int $hotel_id   Hotel ID.
	 * @return object|false
	 */
	public function get_full( $booking_id, $hotel_id = 0 ) {
		global $wpdb;

		$booking_id = absint( $booking_id );
		$hotel_id   = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );

		if ( ! $booking_id || ! $hotel_id ) {
			return false;
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT b.*, r.room_number, r.room_type, g.full_name AS guest_name, g.phone AS guest_phone, g.email AS guest_email
				FROM {$wpdb->prefix}hrm_bookings b
				INNER JOIN {$wpdb->prefix}hrm_rooms r ON r.id = b.room_id AND r.hotel_id = b.hotel_id
				INNER JOIN {$wpdb->prefix}hrm_guests g ON g.id = b.guest_id AND g.hotel_id = b.hotel_id
				WHERE b.id = %d AND b.hotel_id = %d
				LIMIT 1",
				$booking_id,
				$hotel_id
			)
		);
	}

	/**
	 * Create or update an admin booking.
	 *
	 * @param int   $hotel_id Hotel ID.
	 * @param array $data     Booking data.
	 * @param int   $user_id  Creator user ID.
	 * @return int|WP_Error
	 */
	public function save_admin_booking( $hotel_id, $data, $user_id = 0 ) {
		global $wpdb;

		$hotel_id       = absint( $hotel_id );
		$user_id        = absint( $user_id );
		$booking_id     = isset( $data['booking_id'] ) ? absint( $data['booking_id'] ) : 0;
		$room_id        = isset( $data['room_id'] ) ? absint( $data['room_id'] ) : 0;
		$check_in       = isset( $data['check_in'] ) ? $this->normalize_date( $data['check_in'] ) : '';
		$check_out      = isset( $data['check_out'] ) ? $this->normalize_date( $data['check_out'] ) : '';
		$amount_paid    = isset( $data['amount_paid'] ) ? max( 0, (float) $data['amount_paid'] ) : 0;
		$payment_method = isset( $data['payment_method'] ) ? sanitize_key( $data['payment_method'] ) : 'cash';
		$status         = ! empty( $data['quick_checkin'] ) ? 'checked_in' : ( isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'confirmed' );
		$notes          = isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '';

		if ( ! $hotel_id || ! $room_id || ! $check_in || ! $check_out ) {
			return new WP_Error( 'hrm_booking_required', __( 'Room and booking dates are required.', 'hrm-pro' ) );
		}

		$hotel = HRM_License::get_hotel( $hotel_id );
		if ( ! $hotel || ! $hotel->is_active() || ! HRM_License::hotel_can( $hotel_id, 'basic_bookings' ) ) {
			return new WP_Error( 'hrm_booking_locked', __( 'Booking management is not available for this hotel.', 'hrm-pro' ) );
		}

		$room = ( new HRM_Rooms() )->get( $room_id, $hotel_id );
		if ( ! $room ) {
			return new WP_Error( 'hrm_room_missing', __( 'The selected room could not be found.', 'hrm-pro' ) );
		}

		if ( $this->has_conflict( $room_id, $check_in, $check_out, $hotel_id, $booking_id ) ) {
			return new WP_Error( 'hrm_room_unavailable', __( 'This room is already booked for the selected dates.', 'hrm-pro' ) );
		}

		$guests   = new HRM_Guests();
		$guest_id = $guests->upsert_from_frontend(
			$hotel_id,
			array(
				'full_name' => isset( $data['guest_name'] ) ? $data['guest_name'] : '',
				'phone'     => isset( $data['guest_phone'] ) ? $data['guest_phone'] : '',
				'email'     => isset( $data['guest_email'] ) ? $data['guest_email'] : '',
				'id_type'   => isset( $data['id_type'] ) ? $data['id_type'] : '',
				'id_number' => isset( $data['id_number'] ) ? $data['id_number'] : '',
			)
		);

		if ( is_wp_error( $guest_id ) ) {
			return $guest_id;
		}

		$rate = $this->calculate_rate( $room_id, $check_in, $check_out, $hotel_id );
		if ( ! $rate ) {
			return new WP_Error( 'hrm_rate_failed', __( 'The rate could not be calculated.', 'hrm-pro' ) );
		}

		$total_amount   = (float) $rate['total'];
		$amount_paid    = min( $amount_paid, $total_amount );
		$balance        = max( 0, $total_amount - $amount_paid );
		$payment_status = 0 >= $amount_paid ? 'unpaid' : ( $balance > 0 ? 'part_paid' : 'paid' );
		$rate_per_night = $rate['nights'] > 0 ? round( $rate['subtotal'] / $rate['nights'], 2 ) : 0;

		$allowed_statuses = array( 'pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show' );
		$status           = in_array( $status, $allowed_statuses, true ) ? $status : 'confirmed';
		$allowed_methods  = array( 'cash', 'transfer', 'pos', 'paystack' );
		$payment_method   = in_array( $payment_method, $allowed_methods, true ) ? $payment_method : 'cash';

		$row = array(
			'hotel_id'        => $hotel_id,
			'room_id'         => $room_id,
			'guest_id'        => $guest_id,
			'booking_source'  => 'admin',
			'check_in'        => $check_in,
			'check_out'       => $check_out,
			'total_nights'    => (int) $rate['nights'],
			'rate_per_night'  => $rate_per_night,
			'subtotal'        => (float) $rate['subtotal'],
			'vat_rate'        => (float) $rate['vat_rate'],
			'vat_amount'      => (float) $rate['vat_amount'],
			'total_amount'    => $total_amount,
			'payment_method'  => $payment_method,
			'payment_status'  => $payment_status,
			'amount_paid'     => $amount_paid,
			'balance'         => $balance,
			'transfer_ref'    => isset( $data['transfer_ref'] ) ? sanitize_text_field( $data['transfer_ref'] ) : '',
			'status'          => $status,
			'notes'           => $notes,
		);

		$formats = array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%s', '%s', '%s' );

		$is_new_booking  = ! $booking_id;
		$previous_status = '';

		if ( $booking_id ) {
			$existing = $this->get( $booking_id, $hotel_id );
			if ( ! $existing ) {
				return new WP_Error( 'hrm_booking_missing', __( 'The booking could not be found.', 'hrm-pro' ) );
			}
			$previous_status = (string) $existing->status;

			$updated = $wpdb->update(
				HRM_Database::table( 'bookings' ),
				$row,
				array(
					'id'       => $booking_id,
					'hotel_id' => $hotel_id,
				),
				$formats,
				array( '%d', '%d' )
			);

			if ( false === $updated ) {
				return new WP_Error( 'hrm_booking_update_failed', __( 'Booking could not be updated.', 'hrm-pro' ) );
			}
		} else {
			$row['created_by'] = $user_id;
			$row['created_at'] = current_time( 'mysql' );
			$inserted          = $wpdb->insert(
				HRM_Database::table( 'bookings' ),
				$row,
				array_merge( $formats, array( '%d', '%s' ) )
			);

			if ( ! $inserted ) {
				return new WP_Error( 'hrm_booking_insert_failed', __( 'Booking could not be created.', 'hrm-pro' ) );
			}

			$booking_id = (int) $wpdb->insert_id;
		}

		$this->update_room_for_status( $room_id, $hotel_id, $status );
		$this->send_workflow_notifications( $booking_id, $status, $previous_status, $is_new_booking );

		return $booking_id;
	}

	/**
	 * Update a booking workflow status.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status     New status.
	 * @param int    $hotel_id   Hotel ID.
	 * @return bool|WP_Error
	 */
	public function update_status( $booking_id, $status, $hotel_id = 0 ) {
		global $wpdb;

		$booking_id = absint( $booking_id );
		$hotel_id   = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$status     = sanitize_key( $status );

		if ( ! in_array( $status, array( 'pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show' ), true ) ) {
			return new WP_Error( 'hrm_invalid_booking_status', __( 'Invalid booking status.', 'hrm-pro' ) );
		}

		$booking = $this->get( $booking_id, $hotel_id );
		if ( ! $booking ) {
			return new WP_Error( 'hrm_booking_missing', __( 'The booking could not be found.', 'hrm-pro' ) );
		}

		$updated = $wpdb->update(
			HRM_Database::table( 'bookings' ),
			array( 'status' => $status ),
			array(
				'id'       => $booking_id,
				'hotel_id' => $hotel_id,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'hrm_booking_status_failed', __( 'Booking status could not be updated.', 'hrm-pro' ) );
		}

		$this->update_room_for_status( (int) $booking->room_id, $hotel_id, $status );
		$this->send_workflow_notifications( $booking_id, $status, (string) $booking->status, false );

		return true;
	}

	/**
	 * Calculate room rate for a date range.
	 *
	 * @param int    $room_id   Room ID.
	 * @param string $check_in  Check-in date.
	 * @param string $check_out Check-out date.
	 * @param int    $hotel_id  Hotel ID. Uses current hotel when omitted.
	 * @return array|false
	 */
	public function calculate_rate( $room_id, $check_in, $check_out, $hotel_id = 0 ) {
		global $wpdb;

		$room_id   = absint( $room_id );
		$hotel_id  = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$check_in  = $this->normalize_date( $check_in );
		$check_out = $this->normalize_date( $check_out );

		if ( ! $room_id || ! $hotel_id || ! $check_in || ! $check_out || strtotime( $check_out ) <= strtotime( $check_in ) ) {
			return false;
		}

		$room = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hrm_rooms WHERE id = %d AND hotel_id = %d",
				$room_id,
				$hotel_id
			)
		);

		if ( ! $room ) {
			return false;
		}

		$weekend_days = explode( ',', strtolower( (string) HRM_Settings::get( 'weekend_days', 'friday,saturday', $hotel_id ) ) );
		$weekend_days = array_values( array_filter( array_map( 'trim', $weekend_days ) ) );
		$peak_seasons = json_decode( (string) HRM_Settings::get( 'peak_season_dates', '[]', $hotel_id ), true );
		$peak_seasons = is_array( $peak_seasons ) ? $peak_seasons : array();
		$vat_rate     = (float) HRM_Settings::get( 'vat_rate', '7.5', $hotel_id );
		$hotel        = HRM_License::get_hotel( $hotel_id );

		if ( ! $hotel || ! $hotel->is_active() ) {
			return false;
		}

		$current   = strtotime( $check_in );
		$end       = strtotime( $check_out );
		$nights    = 0;
		$subtotal  = 0;
		$breakdown = array();

		while ( $current < $end ) {
			$date_str   = date( 'Y-m-d', $current );
			$day_name   = strtolower( date( 'l', $current ) );
			$rate       = (float) $room->price_per_night;
			$rate_label = 'standard';

			if ( HRM_License::hotel_can( $hotel_id, 'peak_season_pricing' ) ) {
				foreach ( $peak_seasons as $season ) {
					if ( empty( $season['from'] ) || empty( $season['to'] ) ) {
						continue;
					}

					if ( $date_str >= $season['from'] && $date_str <= $season['to'] && null !== $room->peak_rate ) {
						$rate       = (float) $room->peak_rate;
						$rate_label = 'peak';
						break;
					}
				}
			}

			if ( 'standard' === $rate_label && HRM_License::hotel_can( $hotel_id, 'weekend_pricing' ) ) {
				if ( in_array( $day_name, $weekend_days, true ) && null !== $room->weekend_rate ) {
					$rate       = (float) $room->weekend_rate;
					$rate_label = 'weekend';
				}
			}

			$breakdown[] = array(
				'date'           => $date_str,
				'rate'           => $rate,
				'rate_formatted' => HRM_Settings::money( $rate, $hotel_id ),
				'type'           => $rate_label,
			);

			$subtotal += $rate;
			$nights++;
			$current = strtotime( '+1 day', $current );
		}

		$vat_amount = round( $subtotal * ( $vat_rate / 100 ), 2 );
		$total      = round( $subtotal + $vat_amount, 2 );

		return compact( 'nights', 'breakdown', 'subtotal', 'vat_rate', 'vat_amount', 'total' );
	}

	/**
	 * Create a frontend booking after payment or from a trusted internal flow.
	 *
	 * @param int   $hotel_id Hotel ID.
	 * @param array $data     Booking and guest data.
	 * @return array|WP_Error
	 */
	public function create_frontend_booking( $hotel_id, $data ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$room_id  = isset( $data['room_id'] ) ? absint( $data['room_id'] ) : 0;
		$check_in  = isset( $data['check_in'] ) ? $this->normalize_date( $data['check_in'] ) : '';
		$check_out = isset( $data['check_out'] ) ? $this->normalize_date( $data['check_out'] ) : '';
		$notes     = isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '';

		if ( ! $hotel_id || ! $room_id || ! $check_in || ! $check_out ) {
			return new WP_Error( 'hrm_booking_required', __( 'Booking details are incomplete.', 'hrm-pro' ) );
		}

		$hotel = HRM_License::get_hotel( $hotel_id );
		if ( ! $hotel || ! $hotel->is_active() || ! HRM_License::hotel_can( $hotel_id, 'frontend_booking' ) ) {
			return new WP_Error( 'hrm_booking_unavailable', __( 'Online booking is currently unavailable for this hotel.', 'hrm-pro' ) );
		}

		$rooms = new HRM_Rooms();
		$room  = $rooms->get( $room_id, $hotel_id );
		if ( ! $room ) {
			return new WP_Error( 'hrm_room_missing', __( 'The selected room could not be found.', 'hrm-pro' ) );
		}

		$guest_count = isset( $data['guest_count'] ) ? max( 1, absint( $data['guest_count'] ) ) : 1;
		if ( $guest_count > (int) $room->max_guests ) {
			return new WP_Error( 'hrm_room_capacity', __( 'The selected room cannot accommodate this number of guests.', 'hrm-pro' ) );
		}

		if ( ! $rooms->is_available( $room_id, $check_in, $check_out, $hotel_id ) ) {
			return new WP_Error( 'hrm_room_unavailable', __( 'This room is no longer available for the selected dates.', 'hrm-pro' ) );
		}

		$guests   = new HRM_Guests();
		$guest_id = $guests->upsert_from_frontend(
			$hotel_id,
			array(
				'full_name' => isset( $data['full_name'] ) ? $data['full_name'] : '',
				'phone'     => isset( $data['phone'] ) ? $data['phone'] : '',
				'email'     => isset( $data['email'] ) ? $data['email'] : '',
				'id_type'   => isset( $data['id_type'] ) ? $data['id_type'] : '',
				'id_number' => isset( $data['id_number'] ) ? $data['id_number'] : '',
			)
		);

		if ( is_wp_error( $guest_id ) ) {
			return $guest_id;
		}

		if ( $guests->is_blacklisted( $guest_id, $hotel_id ) ) {
			return new WP_Error( 'hrm_guest_blacklisted', __( 'We are unable to complete this reservation. Please contact the hotel directly.', 'hrm-pro' ) );
		}

		$rate = $this->calculate_rate( $room_id, $check_in, $check_out, $hotel_id );
		if ( ! $rate ) {
			return new WP_Error( 'hrm_rate_failed', __( 'The booking price could not be calculated.', 'hrm-pro' ) );
		}

		$total_amount       = (float) $rate['total'];
		$verified_total     = isset( $data['verified_total_amount'] ) ? (float) $data['verified_total_amount'] : 0;
		$amount_paid        = isset( $data['amount_paid'] ) ? min( max( 0, (float) $data['amount_paid'] ), $total_amount ) : 0;
		$payment_status     = isset( $data['payment_status'] ) ? sanitize_key( $data['payment_status'] ) : ( $amount_paid >= $total_amount ? 'paid' : 'unpaid' );
		$payment_method     = isset( $data['payment_method'] ) ? sanitize_key( $data['payment_method'] ) : 'paystack';
		$status             = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'pending';
		$allowed_statuses   = array( 'pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show' );
		$allowed_methods    = array( 'paystack', 'transfer', 'cash' );
		$allowed_payments   = array( 'unpaid', 'part_paid', 'paid', 'transfer_pending' );
		$payment_method     = in_array( $payment_method, $allowed_methods, true ) ? $payment_method : 'paystack';
		$payment_status     = in_array( $payment_status, $allowed_payments, true ) ? $payment_status : 'unpaid';
		$status             = in_array( $status, $allowed_statuses, true ) ? $status : 'pending';
		$balance            = max( 0, $total_amount - $amount_paid );
		$paystack_reference = isset( $data['paystack_reference'] ) ? sanitize_text_field( $data['paystack_reference'] ) : '';
		$transfer_ref       = isset( $data['transfer_ref'] ) ? sanitize_text_field( $data['transfer_ref'] ) : '';

		if ( $verified_total > 0 && abs( $verified_total - $total_amount ) > 1 ) {
			return new WP_Error( 'hrm_booking_amount_mismatch', __( 'The verified payment amount does not match the current booking price.', 'hrm-pro' ) );
		}

		$rate_per_night = $rate['nights'] > 0 ? round( $rate['subtotal'] / $rate['nights'], 2 ) : 0;
		$inserted       = $wpdb->insert(
			HRM_Database::table( 'bookings' ),
			array(
				'hotel_id'        => $hotel_id,
				'room_id'         => $room_id,
				'guest_id'        => $guest_id,
				'booking_source'  => 'frontend',
				'check_in'        => $check_in,
				'check_out'       => $check_out,
				'total_nights'    => (int) $rate['nights'],
				'rate_per_night'  => $rate_per_night,
				'subtotal'        => (float) $rate['subtotal'],
				'vat_rate'        => (float) $rate['vat_rate'],
				'vat_amount'      => (float) $rate['vat_amount'],
				'total_amount'    => (float) $rate['total'],
				'payment_method'  => $payment_method,
				'payment_status'  => $payment_status,
				'amount_paid'     => $amount_paid,
				'balance'         => $balance,
				'transfer_ref'    => $transfer_ref,
				'paystack_reference' => $paystack_reference,
				'status'          => $status,
				'notes'           => $notes,
				'created_by'      => null,
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'hrm_booking_insert_failed', __( 'The booking could not be created.', 'hrm-pro' ) );
		}

		$booking_id = (int) $wpdb->insert_id;
		HRM_Activity_Log::log(
			$hotel_id,
			0,
			'frontend_booking_created',
			'booking',
			$booking_id,
			wp_json_encode(
				array(
					'room_id' => $room_id,
					'total'   => $rate['total'],
				)
			)
		);

		return array(
			'booking_id' => $booking_id,
			'guest_id'   => $guest_id,
			'rate'       => $rate,
		);
	}

	/**
	 * Return a rich confirmation payload.
	 *
	 * @param int $booking_id Booking ID.
	 * @return array|false
	 */
	public function confirmation_payload( $booking_id ) {
		global $wpdb;

		$booking = $this->get( $booking_id, 0 );
		if ( ! $booking ) {
			return false;
		}

		$hotel = HRM_License::get_hotel( (int) $booking->hotel_id );
		if ( ! $hotel ) {
			return false;
		}

		$room  = ( new HRM_Rooms() )->get( (int) $booking->room_id, (int) $booking->hotel_id );
		$guest = ( new HRM_Guests() )->get( (int) $booking->guest_id, (int) $booking->hotel_id );

		if ( ! $room || ! $guest ) {
			return false;
		}

		return array(
			'booking' => $booking,
			'hotel'   => $hotel,
			'room'    => $room,
			'guest'   => $guest,
			'paid'    => 'paid' === $booking->payment_status,
			'ref'     => '#HRM' . (int) $booking->id,
			'total'   => HRM_Settings::money( $booking->total_amount, $booking->hotel_id ),
			'paid_amount' => HRM_Settings::money( $booking->amount_paid, $booking->hotel_id ),
			'payment_method' => (string) $booking->payment_method,
			'payment_status' => (string) $booking->payment_status,
			'transfer_ref'   => (string) $booking->transfer_ref,
			'transfer_pending' => 'transfer' === $booking->payment_method && 'paid' !== $booking->payment_status,
			'room_label'  => trim( $room->room_number . ' (' . ucwords( str_replace( '_', ' ', $room->room_type ) ) . ')' ),
			'date_label'  => date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) . ' - ' . date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ),
		);
	}

	/**
	 * Mark rooms occupied when check-in date has arrived.
	 *
	 * @param int $hotel_id Hotel ID, or 0 for all hotels.
	 * @return int
	 */
	public function sync_due_room_statuses( $hotel_id = 0 ) {
		global $wpdb;

		$hotel_id       = absint( $hotel_id );
		$bookings_table = HRM_Database::table( 'bookings' );
		$rooms_table    = HRM_Database::table( 'rooms' );
		$now            = current_time( 'timestamp' );
		$today          = date( 'Y-m-d', $now );

		// Checkout time is 12:00 noon. Rooms are freed after that hour passes.
		$current_hour    = (int) current_time( 'H' );
		$past_noon       = $current_hour >= 12;
		$checkout_cutoff = $past_noon ? $today : date( 'Y-m-d', strtotime( '-1 day', $now ) );

		if ( $hotel_id ) {
			$bookings = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT hotel_id, room_id FROM {$bookings_table} WHERE hotel_id = %d AND check_in <= %s AND check_out > %s AND status IN ('confirmed','checked_in')",
					$hotel_id,
					$today,
					$today
				)
			);

			// Find expired bookings whose rooms are still marked occupied.
			$expired = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT b.id, b.hotel_id, b.room_id, b.status
					FROM {$bookings_table} b
					INNER JOIN {$rooms_table} r ON r.id = b.room_id AND r.hotel_id = b.hotel_id AND r.status = 'occupied'
					WHERE b.hotel_id = %d AND b.check_out <= %s AND b.status IN ('confirmed','checked_in')",
					$hotel_id,
					$checkout_cutoff
				)
			);
		} else {
			$bookings = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT hotel_id, room_id FROM {$bookings_table} WHERE check_in <= %s AND check_out > %s AND status IN ('confirmed','checked_in')",
					$today,
					$today
				)
			);

			// Find expired bookings whose rooms are still marked occupied.
			$expired = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT b.id, b.hotel_id, b.room_id, b.status
					FROM {$bookings_table} b
					INNER JOIN {$rooms_table} r ON r.id = b.room_id AND r.hotel_id = b.hotel_id AND r.status = 'occupied'
					WHERE b.check_out <= %s AND b.status IN ('confirmed','checked_in')",
					$checkout_cutoff
				)
			);
		}

		$count = 0;
		foreach ( $bookings as $booking ) {
			$updated = $wpdb->update(
				$rooms_table,
				array( 'status' => 'occupied' ),
				array(
					'hotel_id' => (int) $booking->hotel_id,
					'id'       => (int) $booking->room_id,
				),
				array( '%s' ),
				array( '%d', '%d' )
			);

			if ( false !== $updated ) {
				$count++;
			}
		}

		// Free rooms for expired/past-noon checkouts.
		foreach ( $expired as $booking ) {
			// Set room to 'cleaning' when the guest was physically checked in;
			// set to 'available' when the booking was confirmed but never checked in.
			$new_room_status = ( 'checked_in' === $booking->status ) ? 'cleaning' : 'available';

			$wpdb->update(
				$rooms_table,
				array( 'status' => $new_room_status ),
				array(
					'id'       => (int) $booking->room_id,
					'hotel_id' => (int) $booking->hotel_id,
					'status'   => 'occupied', // Only override occupied; leave cleaning/maintenance alone.
				),
				array( '%s' ),
				array( '%d', '%d', '%s' )
			);

			// Auto-checkout bookings that were physically checked in.
			if ( 'checked_in' === $booking->status ) {
				$wpdb->update(
					$bookings_table,
					array( 'status' => 'checked_out' ),
					array( 'id' => (int) $booking->id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}

		return $count;
	}

	/**
	 * Check whether a booking conflicts with an existing booking.
	 *
	 * @param int    $room_id            Room ID.
	 * @param string $check_in           Check-in date.
	 * @param string $check_out          Check-out date.
	 * @param int    $hotel_id           Hotel ID.
	 * @param int    $exclude_booking_id Booking ID to ignore.
	 * @return bool
	 */
	private function has_conflict( $room_id, $check_in, $check_out, $hotel_id, $exclude_booking_id = 0 ) {
		global $wpdb;

		$room_id            = absint( $room_id );
		$hotel_id           = absint( $hotel_id );
		$exclude_booking_id = absint( $exclude_booking_id );

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}hrm_bookings
				WHERE hotel_id = %d
				AND room_id = %d
				AND id <> %d
				AND status IN ('pending','confirmed','checked_in')
				AND check_in < %s
				AND check_out > %s",
				$hotel_id,
				$room_id,
				$exclude_booking_id,
				$check_out,
				$check_in
			)
		);

		return $count > 0;
	}

	/**
	 * Sync room status from booking workflow state.
	 *
	 * @param int    $room_id  Room ID.
	 * @param int    $hotel_id Hotel ID.
	 * @param string $status   Booking status.
	 * @return void
	 */
	private function update_room_for_status( $room_id, $hotel_id, $status ) {
		$rooms = new HRM_Rooms();

		if ( 'checked_in' === $status ) {
			$rooms->update_status( $room_id, 'occupied', $hotel_id );
			return;
		}

		if ( 'checked_out' === $status ) {
			$rooms->update_status( $room_id, 'cleaning', $hotel_id );
			return;
		}

		if ( in_array( $status, array( 'cancelled', 'no_show' ), true ) ) {
			$rooms->update_status( $room_id, 'available', $hotel_id );
		}
	}

	/**
	 * Send WhatsApp messages for booking workflow transitions.
	 *
	 * @param int    $booking_id      Booking ID.
	 * @param string $status          Current status.
	 * @param string $previous_status Previous status.
	 * @param bool   $is_new_booking  Whether this was just created.
	 * @return void
	 */
	private function send_workflow_notifications( $booking_id, $status, $previous_status, $is_new_booking ) {
		$booking_id = absint( $booking_id );
		$status     = sanitize_key( $status );

		if ( ! $booking_id ) {
			return;
		}

		$whatsapp = new HRM_WhatsApp();
		$booking  = $this->get( $booking_id, 0 );

		if ( $is_new_booking && in_array( $status, array( 'confirmed', 'checked_in' ), true ) ) {
			$whatsapp->send( '', 'confirmation', $booking_id );
			if ( $booking && 'paid' === $booking->payment_status ) {
				$whatsapp->send( '', 'receipt', $booking_id );
			}
		}

		if ( $previous_status === $status ) {
			return;
		}

		if ( 'checked_in' === $status ) {
			$whatsapp->send( '', 'checkin', $booking_id );
			return;
		}

		if ( 'checked_out' === $status ) {
			$whatsapp->send( '', 'checkout', $booking_id );
			$whatsapp->send( '', 'receipt', $booking_id );
		}
	}

	/**
	 * Normalize a date string.
	 *
	 * @param string $date Date string.
	 * @return string
	 */
	private function normalize_date( $date ) {
		$timestamp = strtotime( sanitize_text_field( $date ) );

		return $timestamp ? date( 'Y-m-d', $timestamp ) : '';
	}
}
