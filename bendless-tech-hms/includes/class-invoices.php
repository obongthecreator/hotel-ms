<?php
/**
 * Invoice and receipt generation.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds print-ready invoice and receipt documents.
 */
class HRM_Invoices {

	/**
	 * Return invoice payload for a booking.
	 *
	 * @param int $booking_id Booking ID.
	 * @param int $hotel_id   Hotel ID. Uses current hotel when omitted.
	 * @return array|WP_Error
	 */
	public function payload( $booking_id, $hotel_id = 0 ) {
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );
		$booking  = ( new HRM_Bookings() )->get_full( $booking_id, $hotel_id );

		if ( ! $booking ) {
			return new WP_Error( 'hrm_invoice_missing_booking', __( 'The booking could not be found.', 'hrm-pro' ) );
		}

		$hotel = HRM_License::get_hotel( $hotel_id );
		if ( ! $hotel ) {
			return new WP_Error( 'hrm_invoice_missing_hotel', __( 'The hotel could not be found.', 'hrm-pro' ) );
		}

		$is_receipt = 'paid' === $booking->payment_status || (float) $booking->balance <= 0;

		return array(
			'hotel'            => $hotel,
			'booking'          => $booking,
			'document_type'    => $is_receipt ? __( 'Receipt', 'hrm-pro' ) : __( 'Invoice', 'hrm-pro' ),
			'document_number'  => ( $is_receipt ? 'RCP' : 'INV' ) . '-' . str_pad( (string) $booking->id, 6, '0', STR_PAD_LEFT ),
			'booking_ref'      => '#HRM' . (int) $booking->id,
			'issue_date'       => date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ),
			'date_range'       => date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) . ' - ' . date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ),
			'subtotal'         => HRM_Settings::money( $booking->subtotal, $hotel_id ),
			'vat'              => HRM_Settings::money( $booking->vat_amount, $hotel_id ),
			'total'            => HRM_Settings::money( $booking->total_amount, $hotel_id ),
			'amount_paid'      => HRM_Settings::money( $booking->amount_paid, $hotel_id ),
			'balance'          => HRM_Settings::money( $booking->balance, $hotel_id ),
			'rate_per_night'   => HRM_Settings::money( $booking->rate_per_night, $hotel_id ),
		);
	}

	/**
	 * Render an invoice template.
	 *
	 * @param int $booking_id Booking ID.
	 * @param int $hotel_id   Hotel ID.
	 * @return string|WP_Error
	 */
	public function render( $booking_id, $hotel_id = 0 ) {
		$payload = $this->payload( $booking_id, $hotel_id );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		ob_start();
		include HRM_PLUGIN_DIR . 'templates/invoice.php';

		return ob_get_clean();
	}
}
