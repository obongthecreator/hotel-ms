<?php
/**
 * Admin reports and activity log view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$report_data      = isset( $report_data ) && is_array( $report_data ) ? $report_data : array();
$range            = isset( $report_data['range'] ) ? $report_data['range'] : array();
$room_report      = isset( $report_data['room_performance'] ) ? $report_data['room_performance'] : array( 'rows' => array(), 'summary' => array() );
$leakage_report   = isset( $report_data['revenue_leakage'] ) ? $report_data['revenue_leakage'] : array( 'rows' => array(), 'summary' => array() );
$shift_report     = isset( $report_data['end_of_shift'] ) ? $report_data['end_of_shift'] : array( 'payments' => array(), 'bookings' => array(), 'summary' => array() );
$activity_rows    = isset( $report_data['activity'] ) && is_array( $report_data['activity'] ) ? $report_data['activity'] : array();
$from             = isset( $range['from'] ) ? $range['from'] : '';
$to               = isset( $range['to'] ) ? $range['to'] : '';
$can_room_report  = HRM_License::can( 'advanced_reports' );
$can_leakage      = HRM_License::can( 'revenue_leakage' );
$can_shift        = HRM_License::can( 'shift_reports' );
$can_csv          = HRM_License::can( 'csv_export' );
?>

<div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
	<div>
		<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Reports', 'hrm-pro' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500"><?php echo esc_html( sprintf( __( 'Performance, leakage, shift, and activity data for %s.', 'hrm-pro' ), isset( $range['label'] ) ? $range['label'] : '' ) ); ?></p>
	</div>
	<form method="get" class="hrm-report-filter-form flex flex-col gap-2 rounded-2xl bg-white p-3 shadow-card md:flex-row md:items-center">
		<input type="hidden" name="page" value="hrm-reports">
		<label class="space-y-1">
			<span class="block text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'From', 'hrm-pro' ); ?></span>
			<input type="date" name="hrm_from" value="<?php echo esc_attr( $from ); ?>" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
		</label>
		<label class="space-y-1">
			<span class="block text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'To', 'hrm-pro' ); ?></span>
			<input type="date" name="hrm_to" value="<?php echo esc_attr( $to ); ?>" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
		</label>
		<button type="submit" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600 md:mt-5">
			<span class="iconify" data-icon="solar:filter-linear"></span>
			<?php esc_html_e( 'Apply', 'hrm-pro' ); ?>
		</button>
	</form>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-4">
	<div class="rounded-2xl bg-white p-5 shadow-card">
		<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Booked Revenue', 'hrm-pro' ); ?></p>
		<p class="mt-1 text-2xl font-bold text-slate-900"><?php echo esc_html( isset( $room_report['summary']['booked_revenue_formatted'] ) ? $room_report['summary']['booked_revenue_formatted'] : '0.00' ); ?></p>
	</div>
	<div class="rounded-2xl bg-white p-5 shadow-card">
		<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Paid Revenue', 'hrm-pro' ); ?></p>
		<p class="mt-1 text-2xl font-bold text-slate-900"><?php echo esc_html( isset( $room_report['summary']['paid_revenue_formatted'] ) ? $room_report['summary']['paid_revenue_formatted'] : '0.00' ); ?></p>
	</div>
	<div class="rounded-2xl bg-white p-5 shadow-card">
		<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Leakage Risk', 'hrm-pro' ); ?></p>
		<p class="mt-1 text-2xl font-bold text-red-600"><?php echo esc_html( isset( $leakage_report['summary']['total_leakage_formatted'] ) ? $leakage_report['summary']['total_leakage_formatted'] : '0.00' ); ?></p>
	</div>
	<div class="rounded-2xl bg-white p-5 shadow-card">
		<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Shift Collections', 'hrm-pro' ); ?></p>
		<p class="mt-1 text-2xl font-bold text-slate-900"><?php echo esc_html( isset( $shift_report['summary']['total_paid_formatted'] ) ? $shift_report['summary']['total_paid_formatted'] : '0.00' ); ?></p>
	</div>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-card">
	<div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
		<div class="flex flex-wrap gap-2">
			<button type="button" class="hrm-report-tab rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white" data-tab="room-performance"><?php esc_html_e( 'Room Performance', 'hrm-pro' ); ?></button>
			<button type="button" class="hrm-report-tab rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-surface-50" data-tab="revenue-leakage"><?php esc_html_e( 'Revenue Leakage', 'hrm-pro' ); ?></button>
			<button type="button" class="hrm-report-tab rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-surface-50" data-tab="end-of-shift"><?php esc_html_e( 'End of Shift', 'hrm-pro' ); ?></button>
		</div>
		<div class="flex flex-wrap gap-2">
			<?php foreach ( array( 'room_performance' => __( 'Rooms CSV', 'hrm-pro' ), 'revenue_leakage' => __( 'Leakage CSV', 'hrm-pro' ), 'end_of_shift' => __( 'Shift CSV', 'hrm-pro' ) ) as $type => $label ) : ?>
				<button type="button" class="hrm-export-report inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50 <?php echo esc_attr( $can_csv ? '' : 'opacity-50' ); ?>" data-report="<?php echo esc_attr( $type ); ?>" data-from="<?php echo esc_attr( $from ); ?>" data-to="<?php echo esc_attr( $to ); ?>" <?php disabled( ! $can_csv ); ?>>
					<span class="iconify" data-icon="solar:download-minimalistic-linear"></span>
					<?php echo esc_html( $label ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="hrm-report-panel p-6" data-panel="room-performance">
		<?php if ( ! $can_room_report ) : ?>
			<?php HRM_Admin::upgrade_prompt( __( 'Room performance reports', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'advanced_reports' ) ); ?>
		<?php else : ?>
			<div class="overflow-x-auto">
				<table class="w-full">
					<thead>
						<tr class="border-b border-slate-100 bg-surface-50">
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Bookings', 'hrm-pro' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Nights', 'hrm-pro' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Occupancy', 'hrm-pro' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Average Rate', 'hrm-pro' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Paid Revenue', 'hrm-pro' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-50">
						<?php foreach ( $room_report['rows'] as $row ) : ?>
							<tr class="hover:bg-surface-50">
								<td class="px-4 py-3"><p class="text-sm font-semibold text-slate-900"><?php echo esc_html( $row['room_number'] ); ?></p><p class="text-xs text-slate-500"><?php echo esc_html( $row['room_type'] ); ?></p></td>
								<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( number_format_i18n( (int) $row['bookings_count'] ) ); ?></td>
								<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( number_format_i18n( (int) $row['nights_sold'] ) ); ?></td>
								<td class="px-4 py-3"><div class="h-2 w-28 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-primary-500" style="width:<?php echo esc_attr( min( 100, (float) $row['occupancy'] ) ); ?>%"></div></div><p class="mt-1 text-xs text-slate-500"><?php echo esc_html( $row['occupancy_label'] ); ?></p></td>
								<td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( $row['average_rate_formatted'] ); ?></td>
								<td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( $row['paid_revenue_formatted'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<div class="hrm-report-panel hidden p-6" data-panel="revenue-leakage">
		<?php if ( ! $can_leakage ) : ?>
			<?php HRM_Admin::upgrade_prompt( __( 'Revenue leakage detection', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'revenue_leakage' ) ); ?>
		<?php else : ?>
			<div class="space-y-3">
				<?php if ( $leakage_report['rows'] ) : ?>
					<?php foreach ( $leakage_report['rows'] as $row ) : ?>
						<div class="flex flex-col gap-3 rounded-xl border border-red-100 bg-red-50/40 p-4 md:flex-row md:items-center md:justify-between">
							<div>
								<p class="text-sm font-bold text-slate-900"><?php echo esc_html( $row['booking_ref'] . ' - ' . $row['guest_name'] ); ?></p>
								<p class="mt-1 text-xs text-slate-500"><?php echo esc_html( $row['room_number'] . ' - ' . $row['dates'] . ' - ' . $row['type'] ); ?></p>
							</div>
							<div class="text-left md:text-right">
								<p class="text-base font-bold text-red-600"><?php echo esc_html( $row['amount_formatted'] ); ?></p>
								<p class="text-xs text-slate-500"><?php echo esc_html( $row['payment_status'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="rounded-xl bg-surface-50 px-4 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No revenue leakage detected for this range.', 'hrm-pro' ); ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="hrm-report-panel hidden p-6" data-panel="end-of-shift">
		<?php if ( ! $can_shift ) : ?>
			<?php HRM_Admin::upgrade_prompt( __( 'End-of-shift reports', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'shift_reports' ) ); ?>
		<?php else : ?>
			<div class="grid gap-4 md:grid-cols-4">
				<?php foreach ( $shift_report['payments'] as $payment ) : ?>
					<div class="rounded-xl bg-surface-50 p-4">
						<p class="text-sm font-medium text-slate-500"><?php echo esc_html( $payment['method'] ); ?></p>
						<p class="mt-1 text-xl font-bold text-slate-900"><?php echo esc_html( $payment['amount_formatted'] ); ?></p>
						<p class="mt-1 text-xs text-slate-400"><?php echo esc_html( sprintf( _n( '%d booking', '%d bookings', (int) $payment['booking_count'], 'hrm-pro' ), (int) $payment['booking_count'] ) ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="mt-5 overflow-x-auto">
				<table class="w-full">
					<thead><tr class="border-b border-slate-100 bg-surface-50"><th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Booking', 'hrm-pro' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Paid', 'hrm-pro' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Balance', 'hrm-pro' ); ?></th></tr></thead>
					<tbody class="divide-y divide-slate-50">
						<?php foreach ( $shift_report['bookings'] as $row ) : ?>
							<tr class="hover:bg-surface-50"><td class="px-4 py-3"><p class="text-sm font-semibold text-slate-900"><?php echo esc_html( $row['booking_ref'] . ' - ' . $row['guest_name'] ); ?></p><p class="text-xs text-slate-500"><?php echo esc_html( $row['room_number'] . ' - ' . $row['created_at_display'] ); ?></p></td><td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $row['status'] ); ?></td><td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( $row['amount_paid_label'] ); ?></td><td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $row['balance_label'] ); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>

<div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-card">
	<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
		<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Activity Log', 'hrm-pro' ); ?></h3>
		<button type="button" class="hrm-export-report inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50 <?php echo esc_attr( $can_csv ? '' : 'opacity-50' ); ?>" data-report="activity" data-from="<?php echo esc_attr( $from ); ?>" data-to="<?php echo esc_attr( $to ); ?>" <?php disabled( ! $can_csv ); ?>>
			<span class="iconify" data-icon="solar:download-minimalistic-linear"></span>
			<?php esc_html_e( 'Activity CSV', 'hrm-pro' ); ?>
		</button>
	</div>
	<div class="overflow-x-auto">
		<table class="w-full">
			<thead><tr class="border-b border-slate-100 bg-surface-50"><th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Date', 'hrm-pro' ); ?></th><th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'User', 'hrm-pro' ); ?></th><th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Action', 'hrm-pro' ); ?></th><th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Entity', 'hrm-pro' ); ?></th></tr></thead>
			<tbody class="divide-y divide-slate-50">
				<?php if ( $activity_rows ) : ?>
					<?php foreach ( $activity_rows as $activity ) : ?>
						<tr class="hover:bg-surface-50"><td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( $activity['created_at_display'] ); ?></td><td class="px-6 py-4 text-sm font-semibold text-slate-800"><?php echo esc_html( $activity['user'] ); ?></td><td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( $activity['action_label'] ); ?></td><td class="px-6 py-4 text-sm text-slate-500"><?php echo esc_html( trim( $activity['entity_type'] . ' #' . $activity['entity_id'] ) ); ?></td></tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No activity recorded in this range.', 'hrm-pro' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
