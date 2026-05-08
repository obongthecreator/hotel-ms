<?php
/**
 * Reporting and CSV export service.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds tenant-scoped operational and revenue reports.
 */
class HRM_Reports {

	/**
	 * Return all report datasets for a date range.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @return array
	 */
	public function dashboard( $hotel_id, $from = '', $to = '' ) {
		$range = $this->normalize_range( $from, $to );

		return array(
			'range'            => $range,
			'room_performance' => HRM_License::hotel_can( $hotel_id, 'advanced_reports' ) ? $this->room_performance( $hotel_id, $range['from'], $range['to'] ) : array( 'rows' => array(), 'summary' => array() ),
			'revenue_leakage'  => HRM_License::hotel_can( $hotel_id, 'revenue_leakage' ) ? $this->revenue_leakage( $hotel_id, $range['from'], $range['to'] ) : array( 'rows' => array(), 'summary' => array() ),
			'end_of_shift'     => HRM_License::hotel_can( $hotel_id, 'shift_reports' ) ? $this->end_of_shift( $hotel_id, $range['from'], $range['to'] ) : array( 'payments' => array(), 'bookings' => array(), 'summary' => array() ),
			'activity'         => $this->activity( $hotel_id, $range['from'], $range['to'], 100 ),
		);
	}

	/**
	 * Return frontend analytics datasets for hotel admins.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @return array
	 */
	public function analytics_dashboard( $hotel_id, $from = '', $to = '' ) {
		$hotel_id        = absint( $hotel_id );
		$range           = $this->normalize_range( $from, $to );
		$room_report     = $this->room_performance( $hotel_id, $range['from'], $range['to'] );
		$type_report     = $this->room_type_performance( $hotel_id, $range['from'], $range['to'] );
		$trend_report    = $this->booking_trends( $hotel_id, $range['from'], $range['to'] );
		$source_mix      = $this->booking_mix( $hotel_id, $range['from'], $range['to'], 'booking_source' );
		$status_mix      = $this->booking_mix( $hotel_id, $range['from'], $range['to'], 'status' );
		$payment_mix     = $this->booking_mix( $hotel_id, $range['from'], $range['to'], 'payment_method' );
		$period_days     = max( 1, (int) floor( ( strtotime( $range['to'] ) - strtotime( $range['from'] ) ) / DAY_IN_SECONDS ) + 1 );
		$room_summary    = isset( $room_report['summary'] ) ? $room_report['summary'] : array();
		$total_rooms     = isset( $room_summary['rooms'] ) ? (int) $room_summary['rooms'] : 0;
		$total_bookings  = isset( $room_summary['bookings'] ) ? (int) $room_summary['bookings'] : 0;
		$nights_sold     = isset( $room_summary['nights_sold'] ) ? (int) $room_summary['nights_sold'] : 0;
		$booked_revenue  = isset( $room_summary['booked_revenue'] ) ? (float) $room_summary['booked_revenue'] : 0;
		$paid_revenue    = isset( $room_summary['paid_revenue'] ) ? (float) $room_summary['paid_revenue'] : 0;
		$outstanding     = isset( $room_summary['outstanding_balance'] ) ? (float) $room_summary['outstanding_balance'] : 0;
		$available_nights = max( 1, $total_rooms * $period_days );
		$top_room        = $this->top_row( isset( $room_report['rows'] ) ? $room_report['rows'] : array(), 'room_number' );
		$top_room_type   = $this->top_row( isset( $type_report['rows'] ) ? $type_report['rows'] : array(), 'room_type' );

		return array(
			'range'                 => $range,
			'summary'               => array(
				'total_rooms'                 => $total_rooms,
				'total_bookings'              => $total_bookings,
				'nights_sold'                 => $nights_sold,
				'booked_revenue'              => $booked_revenue,
				'booked_revenue_formatted'    => HRM_Settings::money( $booked_revenue, $hotel_id ),
				'paid_revenue'                => $paid_revenue,
				'paid_revenue_formatted'      => HRM_Settings::money( $paid_revenue, $hotel_id ),
				'outstanding_balance'         => $outstanding,
				'outstanding_balance_formatted' => HRM_Settings::money( $outstanding, $hotel_id ),
				'occupancy'                   => min( 100, round( ( $nights_sold / $available_nights ) * 100, 1 ) ),
				'average_stay'                => $total_bookings > 0 ? round( $nights_sold / $total_bookings, 1 ) : 0,
				'average_daily_revenue'       => round( $booked_revenue / $period_days, 2 ),
				'average_daily_revenue_formatted' => HRM_Settings::money( round( $booked_revenue / $period_days, 2 ), $hotel_id ),
				'top_room'                    => $top_room,
				'top_room_type'               => $top_room_type,
			),
			'room_performance'      => $room_report,
			'room_type_performance' => $type_report,
			'booking_trends'        => $trend_report,
			'source_mix'            => $source_mix,
			'status_mix'            => $status_mix,
			'payment_mix'           => $payment_mix,
			'checkin_details'       => $this->checkin_details( $hotel_id, $range['from'], $range['to'] ),
		);
	}

	/**
	 * Return check-in detail rows for room performance analytics.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @return array
	 */
	public function checkin_details( $hotel_id, $from, $to ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$range    = $this->normalize_range( $from, $to );
		if ( ! $hotel_id ) {
			return array();
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.id, b.check_in, b.created_at, b.status, b.total_amount, b.amount_paid, r.room_number, r.room_type, g.full_name AS guest_name, COALESCE(checkin_user.display_name, creator.display_name, %s) AS checked_in_by, COALESCE(checkin_log.checked_in_at, b.created_at) AS checked_in_at
				FROM {$wpdb->prefix}hrm_bookings b
				INNER JOIN {$wpdb->prefix}hrm_rooms r ON r.id = b.room_id AND r.hotel_id = b.hotel_id
				INNER JOIN {$wpdb->prefix}hrm_guests g ON g.id = b.guest_id AND g.hotel_id = b.hotel_id
				LEFT JOIN (
					SELECT entity_id, MAX(created_at) AS checked_in_at, MAX(user_id) AS user_id
					FROM {$wpdb->prefix}hrm_activity_log
					WHERE hotel_id = %d AND entity_type = 'booking' AND action = 'booking_status_updated' AND details LIKE %s
					GROUP BY entity_id
				) checkin_log ON checkin_log.entity_id = b.id
				LEFT JOIN {$wpdb->users} checkin_user ON checkin_user.ID = checkin_log.user_id
				LEFT JOIN {$wpdb->users} creator ON creator.ID = b.created_by
				WHERE b.hotel_id = %d
				AND b.status NOT IN ('cancelled','no_show')
				AND b.check_in BETWEEN %s AND %s
				ORDER BY b.check_in DESC, checked_in_at DESC
				LIMIT 200",
				__( 'Online booking', 'hrm-pro' ),
				$hotel_id,
				'%"checked_in"%',
				$hotel_id,
				$range['from'],
				$range['to']
			)
		);

		$payload = array();
		foreach ( $rows as $row ) {
			$payload[] = array(
				'booking_ref'        => 'HRM' . (int) $row->id,
				'guest_name'         => (string) $row->guest_name,
				'room_number'        => (string) $row->room_number,
				'room_type'          => ucwords( str_replace( '_', ' ', (string) $row->room_type ) ),
				'status'             => ucwords( str_replace( '_', ' ', (string) $row->status ) ),
				'checked_in_by'      => (string) $row->checked_in_by,
				'check_in_date'      => (string) $row->check_in,
				'checked_in_at'      => (string) $row->checked_in_at,
				'checked_in_display' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->checked_in_at ) ),
				'total_formatted'    => HRM_Settings::money( (float) $row->total_amount, $hotel_id ),
				'paid_formatted'     => HRM_Settings::money( (float) $row->amount_paid, $hotel_id ),
			);
		}

		return $payload;
	}

	/**
	 * Room type performance report.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @return array
	 */
	public function room_type_performance( $hotel_id, $from, $to ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$range    = $this->normalize_range( $from, $to );
		$from     = $range['from'];
		$to       = $range['to'];

		if ( ! $hotel_id ) {
			return array( 'rows' => array(), 'summary' => array() );
		}

		$rooms_table    = HRM_Database::table( 'rooms' );
		$bookings_table = HRM_Database::table( 'bookings' );
		$type_counts    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT room_type, COUNT(*) AS rooms_count FROM {$rooms_table} WHERE hotel_id = %d GROUP BY room_type",
				$hotel_id
			),
			OBJECT_K
		);
		$booking_rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT room_type, COUNT(id) AS bookings_count, COALESCE(SUM(overlap_nights), 0) AS nights_sold, COALESCE(SUM(total_amount * overlap_nights / GREATEST(total_nights, 1)), 0) AS booked_revenue, COALESCE(SUM(amount_paid * overlap_nights / GREATEST(total_nights, 1)), 0) AS paid_revenue, COALESCE(SUM(balance * overlap_nights / GREATEST(total_nights, 1)), 0) AS outstanding_balance
				FROM (
					SELECT r.room_type, b.id, b.total_nights, b.total_amount, b.amount_paid, b.balance, GREATEST(0, DATEDIFF(LEAST(b.check_out, DATE_ADD(%s, INTERVAL 1 DAY)), GREATEST(b.check_in, %s))) AS overlap_nights
					FROM {$bookings_table} b
					INNER JOIN {$rooms_table} r ON r.id = b.room_id AND r.hotel_id = b.hotel_id
					WHERE b.hotel_id = %d
					AND b.status NOT IN ('cancelled','no_show')
					AND b.check_in <= %s
					AND b.check_out > %s
				) metrics
				GROUP BY room_type",
				$to,
				$from,
				$hotel_id,
				$to,
				$from
			),
			OBJECT_K
		);

		$all_types             = array_unique( array_merge( array_keys( (array) $type_counts ), array_keys( (array) $booking_rows ) ) );
		$total_bookings        = 0;
		$total_booked_revenue  = 0;
		$rows                  = array();

		foreach ( $booking_rows as $metrics ) {
			$total_bookings       += (int) $metrics->bookings_count;
			$total_booked_revenue += (float) $metrics->booked_revenue;
		}

		foreach ( $all_types as $type ) {
			$metrics             = isset( $booking_rows[ $type ] ) ? $booking_rows[ $type ] : null;
			$bookings_count      = $metrics ? (int) $metrics->bookings_count : 0;
			$nights_sold         = $metrics ? (int) $metrics->nights_sold : 0;
			$booked_revenue      = $metrics ? (float) $metrics->booked_revenue : 0;
			$paid_revenue        = $metrics ? (float) $metrics->paid_revenue : 0;
			$outstanding_balance = $metrics ? (float) $metrics->outstanding_balance : 0;
			$average_rate        = $nights_sold > 0 ? round( $booked_revenue / $nights_sold, 2 ) : 0;

			$rows[] = array(
				'room_type_slug'                => (string) $type,
				'room_type'                     => ucwords( str_replace( '_', ' ', $type ) ),
				'rooms_count'                   => isset( $type_counts[ $type ] ) ? (int) $type_counts[ $type ]->rooms_count : 0,
				'bookings_count'                => $bookings_count,
				'nights_sold'                   => $nights_sold,
				'booking_share'                 => $total_bookings > 0 ? round( ( $bookings_count / $total_bookings ) * 100, 1 ) : 0,
				'revenue_share'                 => $total_booked_revenue > 0 ? round( ( $booked_revenue / $total_booked_revenue ) * 100, 1 ) : 0,
				'average_rate'                  => $average_rate,
				'average_rate_formatted'        => HRM_Settings::money( $average_rate, $hotel_id ),
				'booked_revenue'                => $booked_revenue,
				'booked_revenue_formatted'      => HRM_Settings::money( $booked_revenue, $hotel_id ),
				'paid_revenue'                  => $paid_revenue,
				'paid_revenue_formatted'        => HRM_Settings::money( $paid_revenue, $hotel_id ),
				'outstanding_balance'           => $outstanding_balance,
				'outstanding_balance_formatted' => HRM_Settings::money( $outstanding_balance, $hotel_id ),
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				if ( $a['bookings_count'] === $b['bookings_count'] ) {
					return $b['booked_revenue'] <=> $a['booked_revenue'];
				}
				return $b['bookings_count'] <=> $a['bookings_count'];
			}
		);

		return array(
			'rows'    => $rows,
			'summary' => array(
				'types'                  => count( $rows ),
				'bookings'               => $total_bookings,
				'booked_revenue'         => $total_booked_revenue,
				'booked_revenue_formatted' => HRM_Settings::money( $total_booked_revenue, $hotel_id ),
			),
		);
	}

	/**
	 * Room performance report.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @return array
	 */
	public function room_performance( $hotel_id, $from, $to ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$range    = $this->normalize_range( $from, $to );
		$from     = $range['from'];
		$to       = $range['to'];

		if ( ! $hotel_id ) {
			return array( 'rows' => array(), 'summary' => array() );
		}

		$rooms_table    = HRM_Database::table( 'rooms' );
		$bookings_table = HRM_Database::table( 'bookings' );
		$rooms          = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$rooms_table} WHERE hotel_id = %d ORDER BY room_number ASC",
				$hotel_id
			)
		);
		$booking_rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT room_id, COUNT(id) AS bookings_count, COALESCE(SUM(overlap_nights), 0) AS nights_sold, COALESCE(SUM(total_amount * overlap_nights / GREATEST(total_nights, 1)), 0) AS booked_revenue, COALESCE(SUM(amount_paid * overlap_nights / GREATEST(total_nights, 1)), 0) AS paid_revenue, COALESCE(SUM(balance * overlap_nights / GREATEST(total_nights, 1)), 0) AS outstanding_balance
				FROM (
					SELECT room_id, id, total_nights, total_amount, amount_paid, balance, GREATEST(0, DATEDIFF(LEAST(check_out, DATE_ADD(%s, INTERVAL 1 DAY)), GREATEST(check_in, %s))) AS overlap_nights
					FROM {$bookings_table}
					WHERE hotel_id = %d
					AND status NOT IN ('cancelled','no_show')
					AND check_in <= %s
					AND check_out > %s
				) metrics
				GROUP BY room_id",
				$to,
				$from,
				$hotel_id,
				$to,
				$from
			),
			OBJECT_K
		);

		$period_days = max( 1, (int) floor( ( strtotime( $to ) - strtotime( $from ) ) / DAY_IN_SECONDS ) + 1 );
		$rows        = array();
		$summary     = array(
			'rooms'               => count( $rooms ),
			'bookings'            => 0,
			'nights_sold'         => 0,
			'booked_revenue'      => 0,
			'paid_revenue'        => 0,
			'outstanding_balance' => 0,
		);

		foreach ( $rooms as $room ) {
			$metrics             = isset( $booking_rows[ $room->id ] ) ? $booking_rows[ $room->id ] : null;
			$bookings_count      = $metrics ? (int) $metrics->bookings_count : 0;
			$nights_sold         = $metrics ? (int) $metrics->nights_sold : 0;
			$booked_revenue      = $metrics ? (float) $metrics->booked_revenue : 0;
			$paid_revenue        = $metrics ? (float) $metrics->paid_revenue : 0;
			$outstanding_balance = $metrics ? (float) $metrics->outstanding_balance : 0;
			$occupancy           = min( 100, round( ( $nights_sold / $period_days ) * 100, 1 ) );
			$average_rate        = $nights_sold > 0 ? round( $booked_revenue / $nights_sold, 2 ) : 0;

			$summary['bookings']            += $bookings_count;
			$summary['nights_sold']         += $nights_sold;
			$summary['booked_revenue']      += $booked_revenue;
			$summary['paid_revenue']        += $paid_revenue;
			$summary['outstanding_balance'] += $outstanding_balance;

			$rows[] = array(
				'room_id'                       => (int) $room->id,
				'room_number'                   => (string) $room->room_number,
				'room_type'                     => ucwords( str_replace( '_', ' ', $room->room_type ) ),
				'bookings_count'                => $bookings_count,
				'nights_sold'                   => $nights_sold,
				'occupancy'                     => $occupancy,
				'occupancy_label'               => $occupancy . '%',
				'average_rate'                  => $average_rate,
				'average_rate_formatted'        => HRM_Settings::money( $average_rate, $hotel_id ),
				'booked_revenue'                => $booked_revenue,
				'booked_revenue_formatted'      => HRM_Settings::money( $booked_revenue, $hotel_id ),
				'paid_revenue'                  => $paid_revenue,
				'paid_revenue_formatted'        => HRM_Settings::money( $paid_revenue, $hotel_id ),
				'outstanding_balance'           => $outstanding_balance,
				'outstanding_balance_formatted' => HRM_Settings::money( $outstanding_balance, $hotel_id ),
			);
		}

		$summary['booked_revenue_formatted']      = HRM_Settings::money( $summary['booked_revenue'], $hotel_id );
		$summary['paid_revenue_formatted']        = HRM_Settings::money( $summary['paid_revenue'], $hotel_id );
		$summary['outstanding_balance_formatted'] = HRM_Settings::money( $summary['outstanding_balance'], $hotel_id );

		return array(
			'rows'    => $rows,
			'summary' => $summary,
		);
	}

	/**
	 * Booking trend report grouped by check-in date.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @return array
	 */
	public function booking_trends( $hotel_id, $from, $to ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$range    = $this->normalize_range( $from, $to );
		$from     = $range['from'];
		$to       = $range['to'];

		if ( ! $hotel_id ) {
			return array();
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT check_in AS date_key, COUNT(*) AS bookings_count, COALESCE(SUM(total_nights), 0) AS nights_sold, COALESCE(SUM(total_amount), 0) AS booked_revenue, COALESCE(SUM(amount_paid), 0) AS paid_revenue
				FROM {$wpdb->prefix}hrm_bookings
				WHERE hotel_id = %d
				AND status NOT IN ('cancelled','no_show')
				AND check_in BETWEEN %s AND %s
				GROUP BY check_in
				ORDER BY check_in ASC",
				$hotel_id,
				$from,
				$to
			),
			OBJECT_K
		);

		$payload = array();
		$current = strtotime( $from );
		$end     = strtotime( $to );
		while ( $current <= $end ) {
			$date_key       = date( 'Y-m-d', $current );
			$row            = isset( $rows[ $date_key ] ) ? $rows[ $date_key ] : null;
			$booked_revenue = $row ? (float) $row->booked_revenue : 0;
			$paid_revenue   = $row ? (float) $row->paid_revenue : 0;

			$payload[] = array(
				'date'                     => $date_key,
				'date_display'             => date_i18n( 'M j', $current ),
				'bookings_count'           => $row ? (int) $row->bookings_count : 0,
				'nights_sold'              => $row ? (int) $row->nights_sold : 0,
				'booked_revenue'           => $booked_revenue,
				'booked_revenue_formatted' => HRM_Settings::money( $booked_revenue, $hotel_id ),
				'paid_revenue'             => $paid_revenue,
				'paid_revenue_formatted'   => HRM_Settings::money( $paid_revenue, $hotel_id ),
			);

			$current = strtotime( '+1 day', $current );
		}

		return $payload;
	}

	/**
	 * Booking mix by a safe predefined field.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @param string $field    Field key.
	 * @return array
	 */
	public function booking_mix( $hotel_id, $from, $to, $field ) {
		global $wpdb;

		$hotel_id       = absint( $hotel_id );
		$range          = $this->normalize_range( $from, $to );
		$field          = sanitize_key( $field );
		$allowed_fields = array(
			'booking_source' => 'booking_source',
			'status'         => 'status',
			'payment_method' => 'payment_method',
		);

		if ( ! $hotel_id || empty( $allowed_fields[ $field ] ) ) {
			return array();
		}

		$column = $allowed_fields[ $field ];
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT item_key, COUNT(id) AS bookings_count, COALESCE(SUM(total_amount * overlap_nights / GREATEST(total_nights, 1)), 0) AS booked_revenue, COALESCE(SUM(amount_paid * overlap_nights / GREATEST(total_nights, 1)), 0) AS paid_revenue
				FROM (
					SELECT {$column} AS item_key, id, total_nights, total_amount, amount_paid, GREATEST(0, DATEDIFF(LEAST(check_out, DATE_ADD(%s, INTERVAL 1 DAY)), GREATEST(check_in, %s))) AS overlap_nights
					FROM {$wpdb->prefix}hrm_bookings
					WHERE hotel_id = %d
					AND status NOT IN ('cancelled','no_show')
					AND check_in <= %s
					AND check_out > %s
				) metrics
				GROUP BY item_key
				ORDER BY bookings_count DESC",
				$range['to'],
				$range['from'],
				$hotel_id,
				$range['to'],
				$range['from']
			)
		);

		$total = 0;
		foreach ( $rows as $row ) {
			$total += (int) $row->bookings_count;
		}

		$payload = array();
		foreach ( $rows as $row ) {
			$count     = (int) $row->bookings_count;
			$revenue   = (float) $row->booked_revenue;
			$item_key  = $row->item_key ? (string) $row->item_key : __( 'Unspecified', 'hrm-pro' );
			$payload[] = array(
				'key'                       => $item_key,
				'label'                     => ucwords( str_replace( '_', ' ', $item_key ) ),
				'bookings_count'            => $count,
				'share'                     => $total > 0 ? round( ( $count / $total ) * 100, 1 ) : 0,
				'booked_revenue'            => $revenue,
				'booked_revenue_formatted'  => HRM_Settings::money( $revenue, $hotel_id ),
				'paid_revenue'              => (float) $row->paid_revenue,
				'paid_revenue_formatted'    => HRM_Settings::money( $row->paid_revenue, $hotel_id ),
			);
		}

		return $payload;
	}

	/**
	 * Return the strongest row by bookings and revenue.
	 *
	 * @param array  $rows      Rows.
	 * @param string $label_key Label key.
	 * @return array
	 */
	private function top_row( $rows, $label_key ) {
		if ( empty( $rows ) ) {
			return array(
				'label'          => __( 'No data', 'hrm-pro' ),
				'bookings_count' => 0,
				'paid_revenue'   => 0,
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				$a_bookings = isset( $a['bookings_count'] ) ? (int) $a['bookings_count'] : 0;
				$b_bookings = isset( $b['bookings_count'] ) ? (int) $b['bookings_count'] : 0;
				if ( $a_bookings === $b_bookings ) {
					$a_revenue = isset( $a['paid_revenue'] ) ? (float) $a['paid_revenue'] : 0;
					$b_revenue = isset( $b['paid_revenue'] ) ? (float) $b['paid_revenue'] : 0;
					return $b_revenue <=> $a_revenue;
				}
				return $b_bookings <=> $a_bookings;
			}
		);

		$row = $rows[0];

		return array(
			'label'          => isset( $row[ $label_key ] ) ? (string) $row[ $label_key ] : __( 'No data', 'hrm-pro' ),
			'bookings_count' => isset( $row['bookings_count'] ) ? (int) $row['bookings_count'] : 0,
			'paid_revenue'   => isset( $row['paid_revenue'] ) ? (float) $row['paid_revenue'] : 0,
		);
	}

	/**
	 * Revenue leakage report.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @return array
	 */
	public function revenue_leakage( $hotel_id, $from, $to ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$range    = $this->normalize_range( $from, $to );
		$from     = $range['from'];
		$to       = $range['to'];

		if ( ! $hotel_id ) {
			return array( 'rows' => array(), 'summary' => array() );
		}

		$bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, r.room_number, g.full_name AS guest_name, g.phone AS guest_phone
				FROM {$wpdb->prefix}hrm_bookings b
				INNER JOIN {$wpdb->prefix}hrm_rooms r ON r.id = b.room_id AND r.hotel_id = b.hotel_id
				INNER JOIN {$wpdb->prefix}hrm_guests g ON g.id = b.guest_id AND g.hotel_id = b.hotel_id
				WHERE b.hotel_id = %d
				AND b.status NOT IN ('cancelled','no_show')
				AND b.check_in <= %s
				AND b.check_out > %s
				AND (b.balance > 0 OR b.payment_status IN ('unpaid','part_paid'))
				ORDER BY b.balance DESC, b.created_at DESC
				LIMIT 200",
				$hotel_id,
				$to,
				$from
			)
		);

		$rows          = array();
		$total_leakage = 0;

		foreach ( $bookings as $booking ) {
			$amount = max( 0, (float) $booking->balance );
			$type   = 'checked_out' === $booking->status ? __( 'Checkout balance', 'hrm-pro' ) : __( 'Outstanding payment', 'hrm-pro' );

			if ( 'unpaid' === $booking->payment_status && $amount <= 0 ) {
				$amount = (float) $booking->total_amount;
				$type   = __( 'Unpaid booking', 'hrm-pro' );
			}

			$total_leakage += $amount;
			$rows[]         = array(
				'booking_id'         => (int) $booking->id,
				'booking_ref'        => '#HRM' . (int) $booking->id,
				'guest_name'         => (string) $booking->guest_name,
				'guest_phone'        => (string) $booking->guest_phone,
				'room_number'        => (string) $booking->room_number,
				'dates'              => $booking->check_in . ' - ' . $booking->check_out,
				'status'             => ucwords( str_replace( '_', ' ', $booking->status ) ),
				'payment_status'     => ucwords( str_replace( '_', ' ', $booking->payment_status ) ),
				'type'               => $type,
				'amount'             => $amount,
				'amount_formatted'   => HRM_Settings::money( $amount, $hotel_id ),
				'balance_formatted'  => HRM_Settings::money( $booking->balance, $hotel_id ),
				'total_formatted'    => HRM_Settings::money( $booking->total_amount, $hotel_id ),
				'created_at_display' => date_i18n( get_option( 'date_format' ), strtotime( $booking->created_at ) ),
			);
		}

		return array(
			'rows'    => $rows,
			'summary' => array(
				'issues'                  => count( $rows ),
				'total_leakage'           => $total_leakage,
				'total_leakage_formatted' => HRM_Settings::money( $total_leakage, $hotel_id ),
			),
		);
	}

	/**
	 * End-of-shift report.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @return array
	 */
	public function end_of_shift( $hotel_id, $from, $to ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$range    = $this->normalize_range( $from, $to );
		$from_sql = $range['from'] . ' 00:00:00';
		$to_sql   = $range['to'] . ' 23:59:59';

		if ( ! $hotel_id ) {
			return array( 'payments' => array(), 'bookings' => array(), 'summary' => array() );
		}

		$payments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT payment_method, COUNT(*) AS booking_count, COALESCE(SUM(amount_paid), 0) AS amount
				FROM {$wpdb->prefix}hrm_bookings
				WHERE hotel_id = %d AND created_at BETWEEN %s AND %s
				GROUP BY payment_method",
				$hotel_id,
				$from_sql,
				$to_sql
			)
		);
		$bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, r.room_number, g.full_name AS guest_name
				FROM {$wpdb->prefix}hrm_bookings b
				INNER JOIN {$wpdb->prefix}hrm_rooms r ON r.id = b.room_id AND r.hotel_id = b.hotel_id
				INNER JOIN {$wpdb->prefix}hrm_guests g ON g.id = b.guest_id AND g.hotel_id = b.hotel_id
				WHERE b.hotel_id = %d AND b.created_at BETWEEN %s AND %s
				ORDER BY b.created_at DESC
				LIMIT 100",
				$hotel_id,
				$from_sql,
				$to_sql
			)
		);

		$payment_rows = array();
		$total_paid   = 0;

		foreach ( array( 'cash', 'transfer', 'pos', 'paystack' ) as $method ) {
			$match = null;
			foreach ( $payments as $payment ) {
				if ( $method === $payment->payment_method ) {
					$match = $payment;
					break;
				}
			}

			$amount     = $match ? (float) $match->amount : 0;
			$total_paid += $amount;
			$payment_rows[] = array(
				'method'           => ucwords( $method ),
				'booking_count'    => $match ? (int) $match->booking_count : 0,
				'amount'           => $amount,
				'amount_formatted' => HRM_Settings::money( $amount, $hotel_id ),
			);
		}

		$booking_rows         = array();
		$outstanding_balance = 0;
		$checkins            = 0;
		$checkouts           = 0;

		foreach ( $bookings as $booking ) {
			$outstanding_balance += (float) $booking->balance;
			$checkins            += 'checked_in' === $booking->status ? 1 : 0;
			$checkouts           += 'checked_out' === $booking->status ? 1 : 0;
			$booking_rows[]       = array(
				'booking_ref'        => '#HRM' . (int) $booking->id,
				'guest_name'         => (string) $booking->guest_name,
				'room_number'        => (string) $booking->room_number,
				'status'             => ucwords( str_replace( '_', ' ', $booking->status ) ),
				'payment_method'     => ucwords( $booking->payment_method ),
				'amount_paid'        => (float) $booking->amount_paid,
				'amount_paid_label'  => HRM_Settings::money( $booking->amount_paid, $hotel_id ),
				'balance'            => (float) $booking->balance,
				'balance_label'      => HRM_Settings::money( $booking->balance, $hotel_id ),
				'created_at_display' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $booking->created_at ) ),
			);
		}

		return array(
			'payments' => $payment_rows,
			'bookings' => $booking_rows,
			'summary'  => array(
				'bookings'                      => count( $booking_rows ),
				'checkins'                      => $checkins,
				'checkouts'                     => $checkouts,
				'total_paid'                    => $total_paid,
				'total_paid_formatted'          => HRM_Settings::money( $total_paid, $hotel_id ),
				'outstanding_balance'           => $outstanding_balance,
				'outstanding_balance_formatted' => HRM_Settings::money( $outstanding_balance, $hotel_id ),
			),
		);
	}

	/**
	 * Activity log report.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @param int    $limit    Maximum rows.
	 * @return array
	 */
	public function activity( $hotel_id, $from, $to, $limit = 100 ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$range    = $this->normalize_range( $from, $to );
		$limit    = max( 1, min( 500, absint( $limit ) ) );

		if ( ! $hotel_id ) {
			return array();
		}

		$from_sql = $range['from'] . ' 00:00:00';
		$to_sql   = $range['to'] . ' 23:59:59';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, u.display_name
				FROM {$wpdb->prefix}hrm_activity_log a
				LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
				WHERE a.hotel_id = %d AND a.created_at BETWEEN %s AND %s
				ORDER BY a.created_at DESC
				LIMIT %d",
				$hotel_id,
				$from_sql,
				$to_sql,
				$limit
			)
		);

		$payload = array();
		foreach ( $rows as $row ) {
			$payload[] = array(
				'id'                 => (int) $row->id,
				'user'               => $row->display_name ? (string) $row->display_name : __( 'System', 'hrm-pro' ),
				'action'             => (string) $row->action,
				'action_label'       => ucwords( str_replace( '_', ' ', $row->action ) ),
				'entity_type'        => (string) $row->entity_type,
				'entity_id'          => (int) $row->entity_id,
				'details'            => (string) $row->details,
				'created_at'         => (string) $row->created_at,
				'created_at_display' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->created_at ) ),
			);
		}

		return $payload;
	}

	/**
	 * Return a CSV export payload.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $type     Report type.
	 * @param string $from     Start date.
	 * @param string $to       End date.
	 * @return array|WP_Error
	 */
	public function csv( $hotel_id, $type, $from = '', $to = '' ) {
		$type  = sanitize_key( $type );
		$range = $this->normalize_range( $from, $to );

		switch ( $type ) {
			case 'room_performance':
				$report  = $this->room_performance( $hotel_id, $range['from'], $range['to'] );
				$headers = array( 'Room', 'Type', 'Bookings', 'Nights Sold', 'Occupancy', 'Average Rate', 'Booked Revenue', 'Paid Revenue', 'Outstanding Balance' );
				$rows    = array_map(
					static function ( $row ) {
						return array( $row['room_number'], $row['room_type'], $row['bookings_count'], $row['nights_sold'], $row['occupancy_label'], $row['average_rate'], $row['booked_revenue'], $row['paid_revenue'], $row['outstanding_balance'] );
					},
					$report['rows']
				);
				break;
			case 'revenue_leakage':
				$report  = $this->revenue_leakage( $hotel_id, $range['from'], $range['to'] );
				$headers = array( 'Booking Ref', 'Guest', 'Phone', 'Room', 'Dates', 'Issue Type', 'Status', 'Payment Status', 'Leakage Amount', 'Balance', 'Total' );
				$rows    = array_map(
					static function ( $row ) {
						return array( $row['booking_ref'], $row['guest_name'], $row['guest_phone'], $row['room_number'], $row['dates'], $row['type'], $row['status'], $row['payment_status'], $row['amount'], $row['balance_formatted'], $row['total_formatted'] );
					},
					$report['rows']
				);
				break;
			case 'end_of_shift':
				$report  = $this->end_of_shift( $hotel_id, $range['from'], $range['to'] );
				$headers = array( 'Booking Ref', 'Guest', 'Room', 'Status', 'Payment Method', 'Amount Paid', 'Balance', 'Created At' );
				$rows    = array_map(
					static function ( $row ) {
						return array( $row['booking_ref'], $row['guest_name'], $row['room_number'], $row['status'], $row['payment_method'], $row['amount_paid'], $row['balance'], $row['created_at_display'] );
					},
					$report['bookings']
				);
				break;
			case 'activity':
				$report  = $this->activity( $hotel_id, $range['from'], $range['to'], 500 );
				$headers = array( 'Date', 'User', 'Action', 'Entity Type', 'Entity ID', 'Details' );
				$rows    = array_map(
					static function ( $row ) {
						return array( $row['created_at_display'], $row['user'], $row['action_label'], $row['entity_type'], $row['entity_id'], $row['details'] );
					},
					$report
				);
				break;
			default:
				return new WP_Error( 'hrm_unknown_report', __( 'Unknown report type.', 'hrm-pro' ) );
		}

		return array(
			'filename' => 'hrm-' . $type . '-' . $range['from'] . '-to-' . $range['to'] . '.csv',
			'content'  => $this->build_csv( $headers, $rows ),
		);
	}

	/**
	 * Normalize report date range.
	 *
	 * @param string $from Start date.
	 * @param string $to   End date.
	 * @return array
	 */
	public function normalize_range( $from = '', $to = '' ) {
		$from = $this->normalize_date( $from );
		$to   = $this->normalize_date( $to );

		if ( ! $from ) {
			$from = date( 'Y-m-01', current_time( 'timestamp' ) );
		}

		if ( ! $to ) {
			$to = current_time( 'Y-m-d' );
		}

		if ( strtotime( $to ) < strtotime( $from ) ) {
			$to = $from;
		}

		return array(
			'from'          => $from,
			'to'            => $to,
			'from_display'  => date_i18n( get_option( 'date_format' ), strtotime( $from ) ),
			'to_display'    => date_i18n( get_option( 'date_format' ), strtotime( $to ) ),
			'label'         => date_i18n( get_option( 'date_format' ), strtotime( $from ) ) . ' - ' . date_i18n( get_option( 'date_format' ), strtotime( $to ) ),
		);
	}

	/**
	 * Build CSV content.
	 *
	 * @param array $headers Header row.
	 * @param array $rows    Data rows.
	 * @return string
	 */
	private function build_csv( $headers, $rows ) {
		$handle = fopen( 'php://temp', 'r+' );
		fputcsv( $handle, $headers );

		foreach ( $rows as $row ) {
			fputcsv( $handle, $row );
		}

		rewind( $handle );
		$content = stream_get_contents( $handle );
		fclose( $handle );

		return (string) $content;
	}

	/**
	 * Normalize a date value.
	 *
	 * @param string $date Date value.
	 * @return string
	 */
	private function normalize_date( $date ) {
		$timestamp = strtotime( sanitize_text_field( $date ) );

		return $timestamp ? date( 'Y-m-d', $timestamp ) : '';
	}
}
