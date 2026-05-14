<?php
/**
 * Dedicated frontend room analytics view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config_json   = wp_json_encode( $config );
$brand_primary = ! empty( $config['brandPrimary'] ) ? sanitize_hex_color( $config['brandPrimary'] ) : '';
$brand_button  = ! empty( $config['brandButton'] ) ? sanitize_hex_color( $config['brandButton'] ) : '';
$brand_text    = ! empty( $config['brandText'] ) ? sanitize_hex_color( $config['brandText'] ) : '';
$brand_font    = ! empty( $config['brandFont'] ) ? sanitize_text_field( $config['brandFont'] ) : 'Inter, system-ui, sans-serif';
$brand_primary = $brand_primary ? $brand_primary : '#987CC0';
$brand_button  = $brand_button ? $brand_button : '#987CC0';
$brand_text    = $brand_text ? $brand_text : '#000000';
$brand_style   = '--hrm-brand-primary:' . esc_attr( $brand_primary ) . ';--hrm-brand-button:' . esc_attr( $brand_button ) . ';--hrm-brand-text:' . esc_attr( $brand_text ) . ';--hrm-brand-font:' . esc_attr( $brand_font ) . ';';

$analytics         = isset( $analytics ) && is_array( $analytics ) ? $analytics : array();
$summary           = isset( $analytics['summary'] ) && is_array( $analytics['summary'] ) ? $analytics['summary'] : array();
$range             = isset( $analytics['range'] ) && is_array( $analytics['range'] ) ? $analytics['range'] : array( 'from' => current_time( 'Y-m-d' ), 'to' => current_time( 'Y-m-d' ), 'period' => 'today' );
$room_report       = isset( $analytics['room_performance'] ) && is_array( $analytics['room_performance'] ) ? $analytics['room_performance'] : array( 'rows' => array(), 'summary' => array() );
$type_report       = isset( $analytics['room_type_performance'] ) && is_array( $analytics['room_type_performance'] ) ? $analytics['room_type_performance'] : array( 'rows' => array(), 'summary' => array() );
$room_rows         = isset( $room_report['rows'] ) && is_array( $room_report['rows'] ) ? $room_report['rows'] : array();
$type_rows         = isset( $type_report['rows'] ) && is_array( $type_report['rows'] ) ? $type_report['rows'] : array();
$checkin_rows      = isset( $analytics['checkin_details'] ) && is_array( $analytics['checkin_details'] ) ? $analytics['checkin_details'] : array();
$conditions        = isset( $room_conditions ) && is_array( $room_conditions ) ? $room_conditions : array();
$by_status         = isset( $conditions['by_status'] ) && is_array( $conditions['by_status'] ) ? $conditions['by_status'] : array();
$by_type           = isset( $conditions['by_type'] ) && is_array( $conditions['by_type'] ) ? $conditions['by_type'] : array();
$feature_available = isset( $feature_available ) ? (bool) $feature_available : false;
$can_explain       = isset( $can_explain ) ? (bool) $can_explain : false;
$current_url       = get_permalink();
$dashboard_url     = ! empty( $config['dashboardUrl'] ) ? esc_url( $config['dashboardUrl'] ) : '';
$analytics_url     = ! empty( $config['analyticsUrl'] ) ? esc_url( $config['analyticsUrl'] ) : '';
$from              = isset( $range['from'] ) ? $range['from'] : current_time( 'Y-m-d' );
$to                = isset( $range['to'] ) ? $range['to'] : current_time( 'Y-m-d' );
$period            = isset( $range['period'] ) ? $range['period'] : 'today';
$top_room          = isset( $summary['top_room'] ) && is_array( $summary['top_room'] ) ? $summary['top_room'] : array();
$top_type          = isset( $summary['top_room_type'] ) && is_array( $summary['top_room_type'] ) ? $summary['top_room_type'] : array();
$available_rooms   = isset( $by_status['available'] ) ? (int) $by_status['available'] : 0;
$occupied_rooms    = isset( $by_status['occupied'] ) ? (int) $by_status['occupied'] : 0;
$cleaning_rooms    = isset( $by_status['cleaning'] ) ? (int) $by_status['cleaning'] : 0;
$maintenance_rooms = isset( $by_status['maintenance'] ) ? (int) $by_status['maintenance'] : 0;
$total_rooms       = isset( $conditions['total'] ) ? (int) $conditions['total'] : ( isset( $summary['total_rooms'] ) ? (int) $summary['total_rooms'] : 0 );

$quick_periods = array(
	'today' => __( 'Today', 'hrm-pro' ),
	'week'  => __( '7 Days', 'hrm-pro' ),
	'month' => __( 'This Month', 'hrm-pro' ),
	'year'  => __( 'This Year', 'hrm-pro' ),
);

$stat_cards = array(
	array(
		'label' => __( 'Total Rooms', 'hrm-pro' ),
		'value' => number_format_i18n( $total_rooms ),
		'hint'  => sprintf( __( '%1$s available, %2$s occupied', 'hrm-pro' ), number_format_i18n( $available_rooms ), number_format_i18n( $occupied_rooms ) ),
		'icon'  => 'solar:buildings-2-linear',
	),
	array(
		'label' => __( 'Room Types', 'hrm-pro' ),
		'value' => number_format_i18n( count( $by_type ) ),
		'hint'  => __( 'Configured room categories', 'hrm-pro' ),
		'icon'  => 'solar:filter-linear',
	),
	array(
		'label' => __( 'Bookings In Period', 'hrm-pro' ),
		'value' => number_format_i18n( isset( $summary['total_bookings'] ) ? (int) $summary['total_bookings'] : 0 ),
		'hint'  => __( 'Active room sales in the selected range', 'hrm-pro' ),
		'icon'  => 'solar:calendar-mark-linear',
	),
	array(
		'label' => __( 'Money Received', 'hrm-pro' ),
		'value' => isset( $summary['paid_revenue_formatted'] ) ? $summary['paid_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id ),
		'hint'  => __( 'Total money collected from bookings', 'hrm-pro' ),
		'icon'  => 'solar:wallet-money-linear',
	),
	array(
		'label' => __( 'Rooms Sold Nights', 'hrm-pro' ),
		'value' => number_format_i18n( isset( $summary['nights_sold'] ) ? (int) $summary['nights_sold'] : 0 ),
		'hint'  => __( 'Total occupied nights sold in range', 'hrm-pro' ),
		'icon'  => 'solar:moon-stars-linear',
	),
	array(
		'label' => __( 'Occupancy', 'hrm-pro' ),
		'value' => number_format_i18n( isset( $summary['occupancy'] ) ? (float) $summary['occupancy'] : 0, 1 ) . '%',
		'hint'  => __( 'Sold nights divided by room capacity', 'hrm-pro' ),
		'icon'  => 'solar:chart-square-linear',
	),
	array(
		'label' => __( 'Top Room Type', 'hrm-pro' ),
		'value' => isset( $top_type['label'] ) ? $top_type['label'] : __( 'No data', 'hrm-pro' ),
		'hint'  => sprintf( __( '%s bookings', 'hrm-pro' ), number_format_i18n( isset( $top_type['bookings_count'] ) ? (int) $top_type['bookings_count'] : 0 ) ),
		'icon'  => 'solar:crown-linear',
	),
	array(
		'label' => __( 'Top Room', 'hrm-pro' ),
		'value' => isset( $top_room['label'] ) ? $top_room['label'] : __( 'No data', 'hrm-pro' ),
		'hint'  => sprintf( __( '%s bookings', 'hrm-pro' ), number_format_i18n( isset( $top_room['bookings_count'] ) ? (int) $top_room['bookings_count'] : 0 ) ),
		'icon'  => 'solar:star-rings-linear',
	),
);
?>

<div id="hrm-room-analytics-<?php echo esc_attr( $hotel_id ); ?>" class="hrm-frontend-dashboard font-sans text-black" data-config="<?php echo esc_attr( $config_json ); ?>" style="<?php echo esc_attr( $brand_style ); ?>">
	<header class="rounded-2xl border border-black/10 bg-white p-6 shadow-card">
		<div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
			<div>
				<p class="text-xs font-semibold uppercase tracking-wide text-primary-600"><?php echo esc_html( $hotel->hotel_name ); ?></p>
				<h1 class="mt-1 text-3xl font-bold text-black"><?php esc_html_e( 'Room Analytics', 'hrm-pro' ); ?></h1>
				<p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600"><?php esc_html_e( 'A simple owner-friendly view of room availability, room type demand, total money received, room sales, and check-in activity.', 'hrm-pro' ); ?></p>
			</div>
			<div class="flex flex-wrap gap-2">
				<?php if ( $dashboard_url ) : ?>
					<a href="<?php echo esc_url( $dashboard_url ); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-black/10 bg-white px-4 py-3 text-sm font-bold text-black transition-colors hover:bg-surface-100">
						<span class="iconify" data-icon="solar:home-2-linear"></span>
						<?php esc_html_e( 'Dashboard', 'hrm-pro' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( $analytics_url ) : ?>
					<a href="<?php echo esc_url( $analytics_url ); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-black/10 bg-white px-4 py-3 text-sm font-bold text-black transition-colors hover:bg-surface-100">
						<span class="iconify" data-icon="solar:chart-square-linear"></span>
						<?php esc_html_e( 'Full Analytics', 'hrm-pro' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="mt-6 flex flex-wrap gap-2">
			<?php foreach ( $quick_periods as $period_key => $period_label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'hrm_period' => $period_key ), $current_url ) ); ?>" class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-bold transition-colors <?php echo esc_attr( $period === $period_key ? 'border-primary-500 bg-primary-500 text-white' : 'border-black/10 bg-white text-black hover:bg-primary-50' ); ?>">
					<?php echo esc_html( $period_label ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<form method="get" class="mt-4 grid gap-3 rounded-xl bg-surface-100 p-4 md:grid-cols-[1fr_1fr_auto]">
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Custom From', 'hrm-pro' ); ?></span>
				<input type="date" name="hrm_from" value="<?php echo esc_attr( $from ); ?>" class="w-full rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
			</label>
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Custom To', 'hrm-pro' ); ?></span>
				<input type="date" name="hrm_to" value="<?php echo esc_attr( $to ); ?>" class="w-full rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
			</label>
			<button type="submit" class="hrm-brand-button self-end">
				<span class="iconify" data-icon="solar:filter-linear"></span>
				<?php esc_html_e( 'Filter', 'hrm-pro' ); ?>
			</button>
		</form>
	</header>

	<?php if ( ! $feature_available ) : ?>
		<section class="mt-6 rounded-2xl border border-dashed border-primary-500/40 bg-white p-10 text-center shadow-card">
			<div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
				<span class="iconify text-3xl" data-icon="solar:crown-linear"></span>
			</div>
			<h2 class="text-2xl font-bold text-black"><?php esc_html_e( 'Upgrade to unlock Room Analytics', 'hrm-pro' ); ?></h2>
			<p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600"><?php esc_html_e( 'Room Analytics is available from the Standard plan upward. It gives owners a clean room sales summary without opening the WordPress backend.', 'hrm-pro' ); ?></p>
			<button type="button" onclick="window.openUpgradeModal && window.openUpgradeModal()" class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-500 px-5 py-3 text-sm font-bold text-white hover:bg-primary-600">
				<span class="iconify" data-icon="solar:crown-linear"></span>
				<?php esc_html_e( 'View Plans', 'hrm-pro' ); ?>
			</button>
		</section>
		<?php return; ?>
	<?php endif; ?>

	<section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
		<?php foreach ( $stat_cards as $card ) : ?>
			<div class="hrm-dashboard-card p-5">
				<div class="flex items-start gap-3">
					<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-500">
						<span class="iconify text-2xl" data-icon="<?php echo esc_attr( $card['icon'] ); ?>"></span>
					</span>
					<div class="min-w-0">
						<p class="text-sm font-medium text-slate-500"><?php echo esc_html( $card['label'] ); ?></p>
						<p class="mt-1 truncate text-2xl font-bold text-black"><?php echo esc_html( $card['value'] ); ?></p>
						<p class="mt-1 text-xs text-slate-500"><?php echo esc_html( $card['hint'] ); ?></p>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</section>

	<section class="mt-6 grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
		<div class="hrm-dashboard-card p-6">
			<h2 class="text-lg font-bold text-black"><?php esc_html_e( 'Current Room Conditions', 'hrm-pro' ); ?></h2>
			<div class="mt-4 grid gap-3 sm:grid-cols-2">
				<?php foreach ( array( 'available' => __( 'Available', 'hrm-pro' ), 'occupied' => __( 'Occupied', 'hrm-pro' ), 'cleaning' => __( 'Cleaning', 'hrm-pro' ), 'maintenance' => __( 'Maintenance', 'hrm-pro' ) ) as $status_key => $status_label ) : ?>
					<?php $count = isset( $by_status[ $status_key ] ) ? (int) $by_status[ $status_key ] : 0; ?>
					<div class="rounded-xl bg-surface-100 p-4">
						<div class="flex items-center justify-between gap-3">
							<span class="font-bold text-black"><?php echo esc_html( $status_label ); ?></span>
							<span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-primary-600"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
						</div>
						<div class="mt-3 h-2 overflow-hidden rounded-full bg-black/10">
							<div class="h-full rounded-full bg-primary-500" style="width: <?php echo esc_attr( $total_rooms > 0 ? min( 100, round( ( $count / $total_rooms ) * 100, 1 ) ) : 0 ); ?>%;"></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="hrm-dashboard-card p-6">
			<h2 class="text-lg font-bold text-black"><?php esc_html_e( 'Room Types At A Glance', 'hrm-pro' ); ?></h2>
			<div class="mt-4 grid gap-3 md:grid-cols-2">
				<?php if ( $by_type ) : ?>
					<?php foreach ( $by_type as $type_row ) : ?>
						<div class="rounded-xl border border-black/10 bg-white p-4">
							<p class="text-xs font-semibold uppercase tracking-wide text-primary-600"><?php esc_html_e( 'Room Type', 'hrm-pro' ); ?></p>
							<p class="mt-1 text-xl font-bold text-black"><?php echo esc_html( $type_row['type'] ); ?></p>
							<p class="mt-1 text-sm text-slate-500"><?php echo esc_html( sprintf( __( '%s rooms configured', 'hrm-pro' ), number_format_i18n( (int) $type_row['count'] ) ) ); ?></p>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="rounded-xl border border-dashed border-black/10 p-4 text-sm text-slate-500"><?php esc_html_e( 'No rooms have been configured yet.', 'hrm-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="mt-6 grid gap-6 xl:grid-cols-2">
		<div class="hrm-dashboard-card overflow-hidden">
			<div class="border-b border-black/10 px-6 py-4">
				<h2 class="text-lg font-bold text-black"><?php esc_html_e( 'Booked Room Types', 'hrm-pro' ); ?></h2>
				<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'How each room type performed in the selected period.', 'hrm-pro' ); ?></p>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full">
					<thead>
						<tr class="bg-surface-100 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
							<th class="px-6 py-3"><?php esc_html_e( 'Type', 'hrm-pro' ); ?></th>
							<th class="px-6 py-3"><?php esc_html_e( 'Bookings', 'hrm-pro' ); ?></th>
							<th class="px-6 py-3"><?php esc_html_e( 'Money In', 'hrm-pro' ); ?></th>
							<th class="px-6 py-3"><?php esc_html_e( 'Share', 'hrm-pro' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-black/5">
						<?php if ( $type_rows ) : ?>
							<?php foreach ( $type_rows as $row ) : ?>
								<tr>
									<td class="px-6 py-4 text-sm font-bold text-black"><?php echo esc_html( $row['room_type'] ); ?></td>
									<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( number_format_i18n( (int) $row['bookings_count'] ) ); ?></td>
									<td class="px-6 py-4 text-sm font-semibold text-black"><?php echo esc_html( $row['paid_revenue_formatted'] ); ?></td>
									<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( number_format_i18n( (float) $row['booking_share'], 1 ) . '%' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No room type bookings in this period.', 'hrm-pro' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="hrm-dashboard-card overflow-hidden">
			<div class="border-b border-black/10 px-6 py-4">
				<h2 class="text-lg font-bold text-black"><?php esc_html_e( 'Rooms Booked', 'hrm-pro' ); ?></h2>
				<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Number of times each room was booked and the money received.', 'hrm-pro' ); ?></p>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full">
					<thead>
						<tr class="bg-surface-100 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
							<th class="px-6 py-3"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></th>
							<th class="px-6 py-3"><?php esc_html_e( 'Type', 'hrm-pro' ); ?></th>
							<th class="px-6 py-3"><?php esc_html_e( 'Booked', 'hrm-pro' ); ?></th>
							<th class="px-6 py-3"><?php esc_html_e( 'Money In', 'hrm-pro' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-black/5">
						<?php if ( $room_rows ) : ?>
							<?php foreach ( $room_rows as $row ) : ?>
								<tr>
									<td class="px-6 py-4 text-sm font-bold text-black"><?php echo esc_html( $row['room_number'] ); ?></td>
									<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( $row['room_type'] ); ?></td>
									<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( number_format_i18n( (int) $row['bookings_count'] ) ); ?></td>
									<td class="px-6 py-4 text-sm font-semibold text-black"><?php echo esc_html( $row['paid_revenue_formatted'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No room bookings in this period.', 'hrm-pro' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<section class="mt-6 grid gap-6 xl:grid-cols-[1fr_0.9fr]">
		<div class="hrm-dashboard-card overflow-hidden">
			<div class="border-b border-black/10 px-6 py-4">
				<h2 class="text-lg font-bold text-black"><?php esc_html_e( 'Check-In Activity', 'hrm-pro' ); ?></h2>
				<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Who checked guests in, with room, date, time, and booking value.', 'hrm-pro' ); ?></p>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full">
					<thead>
						<tr class="bg-surface-100 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
							<th class="px-6 py-3"><?php esc_html_e( 'Guest', 'hrm-pro' ); ?></th>
							<th class="px-6 py-3"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></th>
							<th class="px-6 py-3"><?php esc_html_e( 'Checked In By', 'hrm-pro' ); ?></th>
							<th class="px-6 py-3"><?php esc_html_e( 'Date/Time', 'hrm-pro' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-black/5">
						<?php if ( $checkin_rows ) : ?>
							<?php foreach ( array_slice( $checkin_rows, 0, 12 ) as $row ) : ?>
								<tr>
									<td class="px-6 py-4 text-sm font-bold text-black"><?php echo esc_html( $row['guest_name'] ); ?></td>
									<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( $row['room_number'] . ' - ' . $row['room_type'] ); ?></td>
									<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( $row['checked_in_by'] ); ?></td>
									<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( $row['checked_in_display'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No check-ins recorded in this period.', 'hrm-pro' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="hrm-dashboard-card p-6">
			<div class="flex items-start gap-4">
				<span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
					<span class="iconify text-2xl" data-icon="<?php echo esc_attr( $can_explain ? 'solar:graph-new-up-linear' : 'solar:lock-password-linear' ); ?>"></span>
				</span>
				<div>
					<h2 class="text-lg font-bold text-black"><?php echo esc_html( $can_explain ? __( 'Control Pro Explanation', 'hrm-pro' ) : __( 'Explanation Locked', 'hrm-pro' ) ); ?></h2>
					<?php if ( $can_explain ) : ?>
						<div class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
							<p><?php echo esc_html( sprintf( __( 'In this period, the hotel collected %1$s from %2$s bookings and sold %3$s room nights. This is the clean money-in number owners can read at a glance.', 'hrm-pro' ), isset( $summary['paid_revenue_formatted'] ) ? $summary['paid_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id ), number_format_i18n( isset( $summary['total_bookings'] ) ? (int) $summary['total_bookings'] : 0 ), number_format_i18n( isset( $summary['nights_sold'] ) ? (int) $summary['nights_sold'] : 0 ) ) ); ?></p>
							<p><?php echo esc_html( sprintf( __( '%1$s is currently the strongest room type, while room %2$s is the strongest individual room. Use this to decide which room type to promote first and which room needs pricing attention.', 'hrm-pro' ), isset( $top_type['label'] ) ? $top_type['label'] : __( 'No room type', 'hrm-pro' ), isset( $top_room['label'] ) ? $top_room['label'] : __( 'No room', 'hrm-pro' ) ) ); ?></p>
							<p><?php echo esc_html( sprintf( __( 'There are %1$s available rooms, %2$s occupied rooms, %3$s rooms under cleaning, and %4$s under maintenance right now. If availability is high while money received is low, push promotions for the least-booked room types.', 'hrm-pro' ), number_format_i18n( $available_rooms ), number_format_i18n( $occupied_rooms ), number_format_i18n( $cleaning_rooms ), number_format_i18n( $maintenance_rooms ) ) ); ?></p>
							<?php if ( ! empty( $summary['outstanding_balance'] ) ) : ?>
								<p><?php echo esc_html( sprintf( __( 'Outstanding balance is %s. Tighten deposit rules and confirm transfer payments before check-in to reduce leakage.', 'hrm-pro' ), $summary['outstanding_balance_formatted'] ) ); ?></p>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<p class="mt-3 text-sm leading-6 text-slate-600"><?php esc_html_e( 'Standard plan shows the room analytics numbers. The written explanation, revenue effect interpretation, and owner action suggestions are available on Control Pro and Enterprise.', 'hrm-pro' ); ?></p>
						<button type="button" onclick="window.openUpgradeModal && window.openUpgradeModal()" class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-bold text-white hover:bg-primary-600">
							<span class="iconify" data-icon="solar:crown-linear"></span>
							<?php esc_html_e( 'Upgrade for Explanation', 'hrm-pro' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
</div>
