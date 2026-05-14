<?php
/**
 * Admin booking management view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rooms    = isset( $rooms ) && is_array( $rooms ) ? $rooms : array();
$bookings = isset( $bookings ) && is_array( $bookings ) ? $bookings : array();
$can_edit_bookings = current_user_can( 'hrm_manage_settings' ) || current_user_can( 'manage_options' );
?>

<?php if ( ! HRM_License::can( 'basic_bookings' ) ) : ?>
	<?php HRM_Admin::upgrade_prompt( __( 'Booking management', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'basic_bookings' ) ); ?>
	<?php return; ?>
<?php endif; ?>

<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
	<div>
		<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Booking Management', 'hrm-pro' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Create reservations, manage check-ins, and issue invoices from one workflow.', 'hrm-pro' ); ?></p>
	</div>
	<button type="button" onclick="openSlideOver('hrm-booking-panel')" class="hrm-new-booking inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-600">
		<span class="iconify" data-icon="solar:add-circle-linear"></span>
		<?php esc_html_e( 'New Booking', 'hrm-pro' ); ?>
	</button>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-card">
	<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
		<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Bookings', 'hrm-pro' ); ?></h3>
		<div class="flex items-center gap-2 text-sm text-slate-500">
			<span class="iconify" data-icon="solar:refresh-linear"></span>
			<?php esc_html_e( 'Synced with storefront bookings', 'hrm-pro' ); ?>
		</div>
	</div>
	<div class="overflow-x-auto">
		<table class="w-full">
			<thead>
				<tr class="border-b border-slate-100 bg-surface-50">
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Guest', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Dates', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Payment', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Actions', 'hrm-pro' ); ?></th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-50">
				<?php if ( $bookings ) : ?>
					<?php foreach ( $bookings as $booking ) : ?>
						<?php
						$booking_payload = array(
							'booking_id'     => (int) $booking->id,
							'room_id'        => (int) $booking->room_id,
							'check_in'       => (string) $booking->check_in,
							'check_out'      => (string) $booking->check_out,
							'guest_name'     => (string) $booking->guest_name,
							'guest_phone'    => (string) $booking->guest_phone,
							'guest_email'    => (string) $booking->guest_email,
							'id_type'        => isset( $booking->guest_id_type ) ? (string) $booking->guest_id_type : '',
							'id_number'      => isset( $booking->guest_id_number ) ? (string) $booking->guest_id_number : '',
							'payment_method' => (string) $booking->payment_method,
							'amount_paid'    => (float) $booking->amount_paid,
							'transfer_ref'   => (string) $booking->transfer_ref,
							'status'         => (string) $booking->status,
							'notes'          => (string) $booking->notes,
						);
						?>
						<tr class="transition-colors hover:bg-surface-50">
							<td class="px-6 py-4">
								<p class="text-sm font-semibold text-slate-900"><?php echo esc_html( $booking->guest_name ); ?></p>
								<p class="text-xs text-slate-500"><?php echo esc_html( $booking->guest_phone ); ?></p>
								<?php if ( 'frontend' === $booking->booking_source ) : ?>
									<span class="mt-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700"><?php esc_html_e( 'Online', 'hrm-pro' ); ?></span>
								<?php endif; ?>
								<?php if ( 'vip' === $booking->guest_flag || 'blacklist' === $booking->guest_flag ) : ?>
									<span class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold <?php echo esc_attr( 'vip' === $booking->guest_flag ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700' ); ?>">
										<span class="iconify" data-icon="<?php echo esc_attr( 'vip' === $booking->guest_flag ? 'solar:crown-linear' : 'solar:shield-warning-linear' ); ?>"></span>
										<?php echo esc_html( 'vip' === $booking->guest_flag ? __( 'VIP', 'hrm-pro' ) : __( 'Blacklist', 'hrm-pro' ) ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( $booking->room_number . ' · ' . ucwords( $booking->room_type ) ); ?></td>
							<td class="px-6 py-4 text-sm text-slate-600">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) . ' - ' . date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ) ); ?>
								<p class="text-xs text-slate-400"><?php echo esc_html( sprintf( _n( '%d night', '%d nights', (int) $booking->total_nights, 'hrm-pro' ), (int) $booking->total_nights ) ); ?></p>
							</td>
							<td class="px-6 py-4">
								<p class="text-sm font-semibold text-slate-900"><?php echo esc_html( HRM_Settings::money( $booking->total_amount, $booking->hotel_id ) ); ?></p>
								<p class="text-xs text-slate-500"><?php echo esc_html( ucwords( str_replace( '_', ' ', $booking->payment_status ) ) ); ?></p>
							</td>
							<td class="px-6 py-4">
								<select class="hrm-booking-status-toggle rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700" data-booking-id="<?php echo esc_attr( $booking->id ); ?>" data-current-status="<?php echo esc_attr( $booking->status ); ?>">
									<?php foreach ( array( 'pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show' ) as $status ) : ?>
										<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $booking->status, $status ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $status ) ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td class="px-6 py-4">
								<div class="flex justify-end gap-2">
									<?php if ( $can_edit_bookings ) : ?>
										<button type="button" class="hrm-edit-booking inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50" data-booking="<?php echo esc_attr( wp_json_encode( $booking_payload ) ); ?>" title="<?php esc_attr_e( 'Edit or switch room', 'hrm-pro' ); ?>">
											<span class="iconify" data-icon="solar:pen-linear"></span>
										</button>
									<?php endif; ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=hrm-invoice&booking_id=' . (int) $booking->id ) ); ?>" target="_blank" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50">
										<span class="iconify" data-icon="solar:document-text-linear"></span>
									</a>
									<button type="button" class="hrm-send-whatsapp inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50" data-booking-id="<?php echo esc_attr( $booking->id ); ?>" data-message-type="confirmation" title="<?php esc_attr_e( 'Send confirmation', 'hrm-pro' ); ?>">
										<span class="iconify" data-icon="solar:chat-round-linear"></span>
									</button>
									<?php if ( 'checked_in' === $booking->status ) : ?>
										<button type="button" class="hrm-send-whatsapp inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50" data-booking-id="<?php echo esc_attr( $booking->id ); ?>" data-message-type="checkout" title="<?php esc_attr_e( 'Send checkout message', 'hrm-pro' ); ?>">
											<span class="iconify" data-icon="solar:logout-2-linear"></span>
										</button>
									<?php endif; ?>
									<?php if ( 'paid' === $booking->payment_status ) : ?>
										<button type="button" class="hrm-send-whatsapp inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50" data-booking-id="<?php echo esc_attr( $booking->id ); ?>" data-message-type="receipt" title="<?php esc_attr_e( 'Send receipt', 'hrm-pro' ); ?>">
											<span class="iconify" data-icon="solar:document-text-linear"></span>
										</button>
									<?php endif; ?>
									<?php if ( 'checked_out' === $booking->status ) : ?>
										<button type="button" class="hrm-mark-room-clean inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 transition-colors hover:bg-emerald-100" data-room-id="<?php echo esc_attr( $booking->room_id ); ?>">
											<span class="iconify" data-icon="solar:check-circle-linear"></span>
										</button>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No bookings yet.', 'hrm-pro' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div id="slideover-overlay" class="fixed inset-0 z-40 hidden bg-black/30 backdrop-blur-sm" onclick="closeSlideOver('hrm-booking-panel')"></div>
<div id="hrm-booking-panel" class="fixed right-0 top-0 z-50 flex h-full w-full max-w-xl translate-x-full flex-col bg-white shadow-modal transition-transform duration-300 ease-out">
	<form class="hrm-ajax-form hrm-booking-form flex h-full flex-col" data-action="hrm_save_booking">
		<?php wp_nonce_field( 'hrm_nonce', 'nonce' ); ?>
		<input type="hidden" name="booking_id" value="">
		<div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-6 py-4">
			<h2 class="text-lg font-semibold text-slate-800"><?php esc_html_e( 'New Booking', 'hrm-pro' ); ?></h2>
			<button type="button" onclick="closeSlideOver('hrm-booking-panel')" class="text-slate-400 hover:text-slate-600"><span class="iconify text-2xl" data-icon="solar:close-circle-linear"></span></button>
		</div>
		<div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
			<div class="grid gap-4 md:grid-cols-2">
				<label class="space-y-1.5 md:col-span-2">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></span>
					<select name="room_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
						<option value=""><?php esc_html_e( 'Select room', 'hrm-pro' ); ?></option>
						<?php foreach ( $rooms as $room ) : ?>
							<option value="<?php echo esc_attr( $room->id ); ?>"><?php echo esc_html( $room->room_number . ' · ' . ucwords( $room->room_type ) . ' · ' . HRM_Settings::money( $room->price_per_night, $room->hotel_id ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Check-In', 'hrm-pro' ); ?></span><input type="date" name="check_in" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
				<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Check-Out', 'hrm-pro' ); ?></span><input type="date" name="check_out" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
			</div>

			<div class="rounded-2xl border border-slate-100 p-4">
				<h3 class="mb-4 font-semibold text-slate-800"><?php esc_html_e( 'Guest', 'hrm-pro' ); ?></h3>
				<div class="grid gap-4 md:grid-cols-2">
					<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Phone', 'hrm-pro' ); ?></span><input type="tel" name="guest_phone" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
					<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Full Name', 'hrm-pro' ); ?></span><input type="text" name="guest_name" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
					<label class="space-y-1.5 md:col-span-2"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Email', 'hrm-pro' ); ?></span><input type="email" name="guest_email" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
					<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'ID Type', 'hrm-pro' ); ?></span><input type="text" name="id_type" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
					<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'ID Number', 'hrm-pro' ); ?></span><input type="text" name="id_number" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
				</div>
				<div class="hrm-guest-warning mt-3 hidden rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700"></div>
			</div>

			<div class="rounded-2xl border border-slate-100 p-4">
				<h3 class="mb-4 font-semibold text-slate-800"><?php esc_html_e( 'Payment & Workflow', 'hrm-pro' ); ?></h3>
				<div class="grid gap-4 md:grid-cols-2">
					<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Payment Method', 'hrm-pro' ); ?></span><select name="payment_method" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"><option value="cash"><?php esc_html_e( 'Cash', 'hrm-pro' ); ?></option><option value="transfer"><?php esc_html_e( 'Transfer', 'hrm-pro' ); ?></option><option value="pos"><?php esc_html_e( 'POS', 'hrm-pro' ); ?></option><option value="paystack"><?php esc_html_e( 'Paystack', 'hrm-pro' ); ?></option></select></label>
					<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Amount Paid', 'hrm-pro' ); ?></span><input type="number" name="amount_paid" min="0" step="0.01" value="0" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
					<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Transfer Ref', 'hrm-pro' ); ?></span><input type="text" name="transfer_ref" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
					<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></span><select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"><option value="confirmed"><?php esc_html_e( 'Confirmed', 'hrm-pro' ); ?></option><option value="pending"><?php esc_html_e( 'Pending', 'hrm-pro' ); ?></option></select></label>
				</div>
				<label class="mt-4 flex items-center gap-3 rounded-xl bg-surface-50 p-3 text-sm font-semibold text-slate-700"><input type="checkbox" name="quick_checkin" value="1" class="h-4 w-4 rounded border-slate-300 text-primary-500 focus:ring-primary-500"><?php esc_html_e( 'Quick check-in after saving', 'hrm-pro' ); ?></label>
				<div class="hrm-admin-rate-preview mt-4 rounded-xl bg-surface-50 p-4 text-sm text-slate-600"><?php esc_html_e( 'Select a room and dates to calculate the rate.', 'hrm-pro' ); ?></div>
			</div>

			<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Notes', 'hrm-pro' ); ?></span><textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea></label>
		</div>
		<div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-white px-6 py-4">
			<button type="button" onclick="closeSlideOver('hrm-booking-panel')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"><?php esc_html_e( 'Cancel', 'hrm-pro' ); ?></button>
			<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
				<span class="iconify" data-icon="solar:check-circle-linear"></span>
				<?php esc_html_e( 'Save Booking', 'hrm-pro' ); ?>
			</button>
		</div>
	</form>
</div>
