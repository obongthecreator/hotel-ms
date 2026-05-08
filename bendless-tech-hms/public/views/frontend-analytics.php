<?php
/**
 * Frontend hotel analytics view.
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
$range             = isset( $analytics['range'] ) && is_array( $analytics['range'] ) ? $analytics['range'] : array();
$room_performance  = isset( $analytics['room_performance']['rows'] ) && is_array( $analytics['room_performance']['rows'] ) ? $analytics['room_performance']['rows'] : array();
$type_performance  = isset( $analytics['room_type_performance']['rows'] ) && is_array( $analytics['room_type_performance']['rows'] ) ? $analytics['room_type_performance']['rows'] : array();
$booking_trends    = isset( $analytics['booking_trends'] ) && is_array( $analytics['booking_trends'] ) ? $analytics['booking_trends'] : array();
$source_mix        = isset( $analytics['source_mix'] ) && is_array( $analytics['source_mix'] ) ? $analytics['source_mix'] : array();
$status_mix        = isset( $analytics['status_mix'] ) && is_array( $analytics['status_mix'] ) ? $analytics['status_mix'] : array();
$payment_mix       = isset( $analytics['payment_mix'] ) && is_array( $analytics['payment_mix'] ) ? $analytics['payment_mix'] : array();
$checkin_details   = isset( $analytics['checkin_details'] ) && is_array( $analytics['checkin_details'] ) ? $analytics['checkin_details'] : array();
$feature_available = isset( $feature_available ) ? (bool) $feature_available : false;
$can_detailed      = isset( $can_detailed ) ? (bool) $can_detailed : $feature_available;
$dashboard_url     = ! empty( $config['dashboardUrl'] ) ? esc_url( $config['dashboardUrl'] ) : '';
$current_url       = get_permalink();
$default_from      = isset( $range['from'] ) ? $range['from'] : date( 'Y-m-01', current_time( 'timestamp' ) );
$default_to        = isset( $range['to'] ) ? $range['to'] : current_time( 'Y-m-d' );
$month_start       = date( 'Y-m-01', current_time( 'timestamp' ) );
$month_end         = date( 'Y-m-t', current_time( 'timestamp' ) );
$last_30_start     = date( 'Y-m-d', strtotime( '-29 days', current_time( 'timestamp' ) ) );
$today             = current_time( 'Y-m-d' );
$max_trend         = 1;

foreach ( $booking_trends as $trend ) {
	$max_trend = max( $max_trend, isset( $trend['bookings_count'] ) ? (int) $trend['bookings_count'] : 0 );
}

$stat_cards = array(
	array(
		'label' => __( 'Bookings', 'hrm-pro' ),
		'value' => number_format_i18n( isset( $summary['total_bookings'] ) ? (int) $summary['total_bookings'] : 0 ),
		'icon'  => 'solar:calendar-mark-linear',
		'hint'  => __( 'Completed and active stays in range', 'hrm-pro' ),
	),
	array(
		'label' => __( 'Nights Sold', 'hrm-pro' ),
		'value' => number_format_i18n( isset( $summary['nights_sold'] ) ? (int) $summary['nights_sold'] : 0 ),
		'icon'  => 'solar:moon-stars-linear',
		'hint'  => __( 'Total booked room nights', 'hrm-pro' ),
	),
	array(
		'label' => __( 'Occupancy', 'hrm-pro' ),
		'value' => number_format_i18n( isset( $summary['occupancy'] ) ? (float) $summary['occupancy'] : 0, 1 ) . '%',
		'icon'  => 'solar:buildings-2-linear',
		'hint'  => __( 'Nights sold divided by available room nights', 'hrm-pro' ),
	),
	array(
		'label' => __( 'Booked Revenue', 'hrm-pro' ),
		'value' => isset( $summary['booked_revenue_formatted'] ) ? $summary['booked_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id ),
		'icon'  => 'solar:wallet-money-linear',
		'hint'  => __( 'Total reservation value', 'hrm-pro' ),
	),
	array(
		'label' => __( 'Paid Revenue', 'hrm-pro' ),
		'value' => isset( $summary['paid_revenue_formatted'] ) ? $summary['paid_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id ),
		'icon'  => 'solar:graph-new-up-linear',
		'hint'  => __( 'Cash collected in range', 'hrm-pro' ),
	),
	array(
		'label' => __( 'Outstanding', 'hrm-pro' ),
		'value' => isset( $summary['outstanding_balance_formatted'] ) ? $summary['outstanding_balance_formatted'] : HRM_Settings::money( 0, $hotel_id ),
		'icon'  => 'solar:danger-triangle-linear',
		'hint'  => __( 'Unpaid booking balance', 'hrm-pro' ),
	),
	array(
		'label' => __( 'Average Stay', 'hrm-pro' ),
		'value' => number_format_i18n( isset( $summary['average_stay'] ) ? (float) $summary['average_stay'] : 0, 1 ) . ' ' . __( 'nights', 'hrm-pro' ),
		'icon'  => 'solar:clock-circle-linear',
		'hint'  => __( 'Average nights per booking', 'hrm-pro' ),
	),
	array(
		'label' => __( 'Daily Revenue', 'hrm-pro' ),
		'value' => isset( $summary['average_daily_revenue_formatted'] ) ? $summary['average_daily_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id ),
		'icon'  => 'solar:chart-square-linear',
		'hint'  => __( 'Booked revenue per day', 'hrm-pro' ),
	),
);

$owner_summary = sprintf(
	/* translators: 1: bookings, 2: room type, 3: room, 4: paid revenue */
	__( 'For this period, the hotel recorded %1$s bookings. The strongest room type is %2$s, the strongest room is %3$s, and the money collected is %4$s.', 'hrm-pro' ),
	number_format_i18n( isset( $summary['total_bookings'] ) ? (int) $summary['total_bookings'] : 0 ),
	isset( $summary['top_room_type']['label'] ) ? $summary['top_room_type']['label'] : __( 'not available yet', 'hrm-pro' ),
	isset( $summary['top_room']['label'] ) ? $summary['top_room']['label'] : __( 'not available yet', 'hrm-pro' ),
	isset( $summary['paid_revenue_formatted'] ) ? $summary['paid_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id )
);

$ai_insights = array();
if ( $can_detailed ) {
	$occupancy = isset( $summary['occupancy'] ) ? (float) $summary['occupancy'] : 0;
	if ( $occupancy < 35 ) {
		$ai_insights[] = __( 'Occupancy is low for the selected period. Push weekday offers, direct WhatsApp follow-ups, and bundle breakfast or late checkout for the room types with the lowest booking share.', 'hrm-pro' );
	} elseif ( $occupancy > 75 ) {
		$ai_insights[] = __( 'Occupancy is strong. Review prices on the most booked room types and test a controlled rate increase on high-demand dates.', 'hrm-pro' );
	}
	if ( ! empty( $summary['outstanding_balance'] ) && (float) $summary['outstanding_balance'] > 0 ) {
		$ai_insights[] = __( 'There is unpaid balance in this period. Require deposits before check-in and make checkout receipt sending part of the staff workflow.', 'hrm-pro' );
	}
	if ( ! empty( $type_performance[0]['room_type'] ) ) {
		$ai_insights[] = sprintf(
			/* translators: %s: room type. */
			__( '%s is leading demand. Feature it more prominently on the booking page and use its amenities as the headline in adverts.', 'hrm-pro' ),
			$type_performance[0]['room_type']
		);
	}
}

$render_mix = static function ( $title, $rows, $empty_label ) {
	?>
	<div class="hrm-dashboard-card p-6">
		<h3 class="text-base font-bold text-black"><?php echo esc_html( $title ); ?></h3>
		<div class="mt-4 space-y-4">
			<?php if ( $rows ) : ?>
				<?php foreach ( $rows as $row ) : ?>
					<?php $share = isset( $row['share'] ) ? max( 0, min( 100, (float) $row['share'] ) ) : 0; ?>
					<div>
						<div class="flex items-center justify-between gap-3 text-sm">
							<span class="font-semibold text-black"><?php echo esc_html( isset( $row['label'] ) ? $row['label'] : __( 'Unknown', 'hrm-pro' ) ); ?></span>
							<span class="text-slate-500"><?php echo esc_html( number_format_i18n( isset( $row['bookings_count'] ) ? (int) $row['bookings_count'] : 0 ) . ' - ' . number_format_i18n( $share, 1 ) . '%' ); ?></span>
						</div>
						<div class="mt-2 h-2 overflow-hidden rounded-full bg-black/10">
							<div class="h-full rounded-full bg-primary-500" style="width: <?php echo esc_attr( $share ); ?>%;"></div>
						</div>
						<p class="mt-1 text-xs text-slate-500"><?php echo esc_html( isset( $row['booked_revenue_formatted'] ) ? $row['booked_revenue_formatted'] : '' ); ?></p>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="rounded-xl border border-dashed border-black/10 bg-white p-4 text-sm text-slate-500"><?php echo esc_html( $empty_label ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
};
?>

<div id="hrm-frontend-analytics-<?php echo esc_attr( $hotel_id ); ?>" class="hrm-frontend-dashboard font-sans text-black" data-config="<?php echo esc_attr( $config_json ); ?>" style="<?php echo esc_attr( $brand_style ); ?>">
	<?php if ( ! $hotel ) : ?>
		<section class="hrm-dashboard-card p-8 text-center">
			<h2 class="text-2xl font-bold text-black"><?php esc_html_e( 'No hotel assigned', 'hrm-pro' ); ?></h2>
			<p class="mx-auto mt-2 max-w-xl text-sm text-slate-600"><?php esc_html_e( 'Ask the platform administrator to assign this user to a hotel before using frontend analytics.', 'hrm-pro' ); ?></p>
		</section>
		<?php return; ?>
	<?php endif; ?>

	<header class="rounded-2xl border border-black/10 bg-white p-6 shadow-card">
		<div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
			<div>
				<p class="text-xs font-semibold uppercase tracking-wide text-primary-600"><?php echo esc_html( $hotel->hotel_name ); ?></p>
				<h1 class="mt-1 text-3xl font-bold text-black"><?php esc_html_e( 'Hotel Analytics', 'hrm-pro' ); ?></h1>
				<p class="mt-2 text-sm text-slate-600"><?php esc_html_e( 'Room performance, demand trends, payment mix, and revenue signals for the selected period.', 'hrm-pro' ); ?></p>
			</div>
			<div class="flex flex-wrap gap-2">
				<?php if ( $dashboard_url ) : ?>
					<a href="<?php echo esc_url( $dashboard_url ); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-black/10 bg-white px-4 py-3 text-sm font-bold text-black transition-colors hover:bg-surface-100">
						<span class="iconify" data-icon="solar:home-2-linear"></span>
						<?php esc_html_e( 'Dashboard', 'hrm-pro' ); ?>
					</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'hrm_from' => $month_start, 'hrm_to' => $month_end ), $current_url ) ); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-black/10 bg-white px-4 py-3 text-sm font-bold text-black transition-colors hover:bg-surface-100">
					<span class="iconify" data-icon="solar:calendar-mark-linear"></span>
					<?php esc_html_e( 'This Month', 'hrm-pro' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( array( 'hrm_from' => $last_30_start, 'hrm_to' => $today ), $current_url ) ); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-black/10 bg-white px-4 py-3 text-sm font-bold text-black transition-colors hover:bg-surface-100">
					<span class="iconify" data-icon="solar:refresh-linear"></span>
					<?php esc_html_e( 'Last 30 Days', 'hrm-pro' ); ?>
				</a>
			</div>
		</div>
		<form method="get" class="mt-6 grid gap-3 rounded-xl bg-surface-100 p-4 md:grid-cols-[1fr_1fr_auto]">
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'From', 'hrm-pro' ); ?></span>
				<input type="date" name="hrm_from" value="<?php echo esc_attr( $default_from ); ?>" class="w-full rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
			</label>
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'To', 'hrm-pro' ); ?></span>
				<input type="date" name="hrm_to" value="<?php echo esc_attr( $default_to ); ?>" class="w-full rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
			</label>
			<button type="submit" class="hrm-brand-button self-end">
				<span class="iconify" data-icon="solar:filter-linear"></span>
				<?php esc_html_e( 'Apply Filter', 'hrm-pro' ); ?>
			</button>
		</form>
	</header>

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

	<section class="hrm-dashboard-card mt-6 p-6">
		<div class="flex items-start gap-4">
			<span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
				<span class="iconify text-2xl" data-icon="solar:chart-square-linear"></span>
			</span>
			<div>
				<h2 class="text-lg font-bold text-black"><?php esc_html_e( 'Owner Summary', 'hrm-pro' ); ?></h2>
				<p class="mt-2 text-sm leading-6 text-slate-600"><?php echo esc_html( $owner_summary ); ?></p>
			</div>
		</div>
	</section>

	<section class="mt-6 grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
		<div class="hrm-dashboard-card p-6">
			<h2 class="text-lg font-bold text-black"><?php esc_html_e( 'Top Performers', 'hrm-pro' ); ?></h2>
			<div class="mt-4 grid gap-4">
				<div class="rounded-xl bg-primary-50 p-4">
					<p class="text-xs font-semibold uppercase tracking-wide text-primary-600"><?php esc_html_e( 'Top Room', 'hrm-pro' ); ?></p>
					<p class="mt-1 text-2xl font-bold text-black"><?php echo esc_html( isset( $summary['top_room']['label'] ) ? $summary['top_room']['label'] : __( 'No data', 'hrm-pro' ) ); ?></p>
					<p class="mt-1 text-sm text-slate-600"><?php echo esc_html( number_format_i18n( isset( $summary['top_room']['bookings_count'] ) ? (int) $summary['top_room']['bookings_count'] : 0 ) . ' ' . __( 'bookings', 'hrm-pro' ) ); ?></p>
				</div>
				<div class="rounded-xl border border-black/10 bg-white p-4">
					<p class="text-xs font-semibold uppercase tracking-wide text-primary-600"><?php esc_html_e( 'Top Room Type', 'hrm-pro' ); ?></p>
					<p class="mt-1 text-2xl font-bold text-black"><?php echo esc_html( isset( $summary['top_room_type']['label'] ) ? $summary['top_room_type']['label'] : __( 'No data', 'hrm-pro' ) ); ?></p>
					<p class="mt-1 text-sm text-slate-600"><?php echo esc_html( number_format_i18n( isset( $summary['top_room_type']['bookings_count'] ) ? (int) $summary['top_room_type']['bookings_count'] : 0 ) . ' ' . __( 'bookings', 'hrm-pro' ) ); ?></p>
				</div>
			</div>
		</div>

		<div class="hrm-dashboard-card p-6">
			<div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
				<div>
					<h2 class="text-lg font-bold text-black"><?php esc_html_e( 'Booking Timeline', 'hrm-pro' ); ?></h2>
					<p class="text-sm text-slate-500"><?php esc_html_e( 'Daily check-in volume for the selected period.', 'hrm-pro' ); ?></p>
				</div>
				<span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-600"><?php echo esc_html( $default_from . ' - ' . $default_to ); ?></span>
			</div>
			<div class="mt-5 flex min-h-48 items-end gap-1 overflow-x-auto rounded-xl border border-black/10 bg-white p-4">
				<?php if ( $booking_trends ) : ?>
					<?php foreach ( $booking_trends as $trend ) : ?>
						<?php
						$count  = isset( $trend['bookings_count'] ) ? (int) $trend['bookings_count'] : 0;
						$height = max( 10, round( ( $count / $max_trend ) * 150 ) );
						?>
						<div class="group flex min-w-8 flex-1 flex-col items-center justify-end gap-2">
							<div class="w-full rounded-t-lg bg-primary-500 transition-all group-hover:opacity-80" style="height: <?php echo esc_attr( $height ); ?>px;" title="<?php echo esc_attr( sprintf( __( '%1$s: %2$d bookings', 'hrm-pro' ), isset( $trend['date'] ) ? $trend['date'] : '', $count ) ); ?>"></div>
							<span class="max-w-12 truncate text-[11px] font-medium text-slate-500"><?php echo esc_html( isset( $trend['date_display'] ) ? $trend['date_display'] : '' ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="w-full self-center text-center text-sm text-slate-500"><?php esc_html_e( 'No booking trend data yet.', 'hrm-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( $can_detailed && $ai_insights ) : ?>
		<section class="hrm-dashboard-card mt-6 p-6">
			<div class="flex items-start gap-4">
				<span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
					<span class="iconify text-2xl" data-icon="solar:danger-triangle-linear"></span>
				</span>
				<div>
					<p class="text-xs font-bold uppercase tracking-wide text-primary-600"><?php esc_html_e( 'Control Pro AI Insight', 'hrm-pro' ); ?></p>
					<h2 class="mt-1 text-lg font-bold text-black"><?php esc_html_e( 'What to improve next', 'hrm-pro' ); ?></h2>
					<div class="mt-4 grid gap-3 md:grid-cols-3">
						<?php foreach ( $ai_insights as $insight ) : ?>
							<p class="rounded-xl border border-black/10 bg-white p-4 text-sm leading-6 text-slate-700"><?php echo esc_html( $insight ); ?></p>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! $can_detailed ) : ?>
		<section class="mt-6 flex flex-col items-center justify-center rounded-2xl border border-dashed border-black/15 bg-white px-8 py-12 text-center shadow-card">
			<div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50">
				<span class="iconify text-3xl text-primary-600" data-icon="solar:crown-linear"></span>
			</div>
			<h2 class="text-xl font-bold text-black"><?php esc_html_e( 'Upgrade for detailed revenue effects', 'hrm-pro' ); ?></h2>
			<p class="mt-2 max-w-xl text-sm text-slate-600"><?php esc_html_e( 'Basic and Standard plans see the simple owner summary. Control Pro and Enterprise unlock room-by-room revenue effects, mix analysis, and AI improvement insights.', 'hrm-pro' ); ?></p>
		</section>
		<?php return; ?>
	<?php endif; ?>

	<section class="mt-6">
		<div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
			<div>
				<h2 class="text-xl font-bold text-black"><?php esc_html_e( 'Room Type Performance', 'hrm-pro' ); ?></h2>
				<p class="text-sm text-slate-500"><?php esc_html_e( 'Compare demand and revenue by room category.', 'hrm-pro' ); ?></p>
			</div>
		</div>
		<div class="grid gap-4 lg:grid-cols-3">
			<?php if ( $type_performance ) : ?>
				<?php foreach ( $type_performance as $type_row ) : ?>
					<?php
					$booking_share = isset( $type_row['booking_share'] ) ? max( 0, min( 100, (float) $type_row['booking_share'] ) ) : 0;
					$revenue_share = isset( $type_row['revenue_share'] ) ? max( 0, min( 100, (float) $type_row['revenue_share'] ) ) : 0;
					?>
					<article class="hrm-dashboard-card p-5">
						<div class="flex items-start justify-between gap-4">
							<div>
								<p class="text-lg font-bold text-black"><?php echo esc_html( isset( $type_row['room_type'] ) ? $type_row['room_type'] : __( 'Room Type', 'hrm-pro' ) ); ?></p>
								<p class="mt-1 text-sm text-slate-500"><?php echo esc_html( sprintf( __( '%1$d rooms - %2$d bookings', 'hrm-pro' ), isset( $type_row['rooms_count'] ) ? (int) $type_row['rooms_count'] : 0, isset( $type_row['bookings_count'] ) ? (int) $type_row['bookings_count'] : 0 ) ); ?></p>
							</div>
							<span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-600"><?php echo esc_html( number_format_i18n( $booking_share, 1 ) . '%' ); ?></span>
						</div>
						<div class="mt-5 space-y-4">
							<div>
								<div class="flex justify-between text-xs font-semibold text-slate-500">
									<span><?php esc_html_e( 'Booking Share', 'hrm-pro' ); ?></span>
									<span><?php echo esc_html( number_format_i18n( $booking_share, 1 ) . '%' ); ?></span>
								</div>
								<div class="mt-2 h-2 overflow-hidden rounded-full bg-black/10"><div class="h-full rounded-full bg-primary-500" style="width: <?php echo esc_attr( $booking_share ); ?>%;"></div></div>
							</div>
							<div>
								<div class="flex justify-between text-xs font-semibold text-slate-500">
									<span><?php esc_html_e( 'Revenue Share', 'hrm-pro' ); ?></span>
									<span><?php echo esc_html( number_format_i18n( $revenue_share, 1 ) . '%' ); ?></span>
								</div>
								<div class="mt-2 h-2 overflow-hidden rounded-full bg-black/10"><div class="h-full rounded-full bg-black" style="width: <?php echo esc_attr( $revenue_share ); ?>%;"></div></div>
							</div>
						</div>
						<div class="mt-5 grid grid-cols-2 gap-3 text-sm">
							<div class="rounded-xl bg-surface-100 p-3">
								<p class="text-xs text-slate-500"><?php esc_html_e( 'Nights', 'hrm-pro' ); ?></p>
								<p class="mt-1 font-bold text-black"><?php echo esc_html( number_format_i18n( isset( $type_row['nights_sold'] ) ? (int) $type_row['nights_sold'] : 0 ) ); ?></p>
							</div>
							<div class="rounded-xl bg-surface-100 p-3">
								<p class="text-xs text-slate-500"><?php esc_html_e( 'Avg Rate', 'hrm-pro' ); ?></p>
								<p class="mt-1 font-bold text-black"><?php echo esc_html( isset( $type_row['average_rate_formatted'] ) ? $type_row['average_rate_formatted'] : HRM_Settings::money( 0, $hotel_id ) ); ?></p>
							</div>
							<div class="rounded-xl bg-surface-100 p-3">
								<p class="text-xs text-slate-500"><?php esc_html_e( 'Booked', 'hrm-pro' ); ?></p>
								<p class="mt-1 font-bold text-black"><?php echo esc_html( isset( $type_row['booked_revenue_formatted'] ) ? $type_row['booked_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id ) ); ?></p>
							</div>
							<div class="rounded-xl bg-surface-100 p-3">
								<p class="text-xs text-slate-500"><?php esc_html_e( 'Paid', 'hrm-pro' ); ?></p>
								<p class="mt-1 font-bold text-black"><?php echo esc_html( isset( $type_row['paid_revenue_formatted'] ) ? $type_row['paid_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id ) ); ?></p>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="hrm-dashboard-card p-8 text-center text-sm text-slate-500 lg:col-span-3"><?php esc_html_e( 'No room type performance data for this period.', 'hrm-pro' ); ?></div>
			<?php endif; ?>
		</div>
	</section>

	<section class="hrm-dashboard-card mt-6 overflow-hidden">
		<div class="flex flex-col gap-1 border-b border-black/10 px-6 py-4 sm:flex-row sm:items-end sm:justify-between">
			<div>
				<h2 class="text-xl font-bold text-black"><?php esc_html_e( 'Room Performance', 'hrm-pro' ); ?></h2>
				<p class="text-sm text-slate-500"><?php esc_html_e( 'Bookings, nights sold, occupancy, revenue, and outstanding balance by room.', 'hrm-pro' ); ?></p>
			</div>
			<span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-600"><?php echo esc_html( number_format_i18n( count( $room_performance ) ) . ' ' . __( 'rooms', 'hrm-pro' ) ); ?></span>
		</div>
		<div class="overflow-x-auto">
			<table class="hrm-dashboard-table">
				<thead>
					<tr class="bg-surface-100 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
						<th class="px-6 py-3"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Bookings', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Nights', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Occupancy', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Avg Rate', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Booked', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Paid', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Outstanding', 'hrm-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-black/5">
					<?php if ( $room_performance ) : ?>
						<?php foreach ( $room_performance as $row ) : ?>
							<?php $occupancy = isset( $row['occupancy'] ) ? max( 0, min( 100, (float) $row['occupancy'] ) ) : 0; ?>
							<tr class="hover:bg-surface-100">
								<td class="px-6 py-4 text-sm">
									<p class="font-bold text-black"><?php echo esc_html( sprintf( __( 'Room %s', 'hrm-pro' ), isset( $row['room_number'] ) ? $row['room_number'] : '' ) ); ?></p>
									<p class="text-xs text-slate-500"><?php echo esc_html( isset( $row['room_type'] ) ? $row['room_type'] : '' ); ?></p>
								</td>
								<td class="px-6 py-4 text-sm font-semibold text-black"><?php echo esc_html( number_format_i18n( isset( $row['bookings_count'] ) ? (int) $row['bookings_count'] : 0 ) ); ?></td>
								<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( number_format_i18n( isset( $row['nights_sold'] ) ? (int) $row['nights_sold'] : 0 ) ); ?></td>
								<td class="px-6 py-4 text-sm">
									<div class="min-w-32">
										<div class="flex justify-between text-xs text-slate-500"><span><?php echo esc_html( number_format_i18n( $occupancy, 1 ) . '%' ); ?></span></div>
										<div class="mt-2 h-2 overflow-hidden rounded-full bg-black/10"><div class="h-full rounded-full bg-primary-500" style="width: <?php echo esc_attr( $occupancy ); ?>%;"></div></div>
									</div>
								</td>
								<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( isset( $row['average_rate_formatted'] ) ? $row['average_rate_formatted'] : HRM_Settings::money( 0, $hotel_id ) ); ?></td>
								<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( isset( $row['booked_revenue_formatted'] ) ? $row['booked_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id ) ); ?></td>
								<td class="px-6 py-4 text-sm font-semibold text-black"><?php echo esc_html( isset( $row['paid_revenue_formatted'] ) ? $row['paid_revenue_formatted'] : HRM_Settings::money( 0, $hotel_id ) ); ?></td>
								<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( isset( $row['outstanding_balance_formatted'] ) ? $row['outstanding_balance_formatted'] : HRM_Settings::money( 0, $hotel_id ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No room performance data for this period.', 'hrm-pro' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>

	<section class="hrm-dashboard-card mt-6 overflow-hidden">
		<div class="flex flex-col gap-1 border-b border-black/10 px-6 py-4 sm:flex-row sm:items-end sm:justify-between">
			<div>
				<h2 class="text-xl font-bold text-black"><?php esc_html_e( 'Check-In Detail', 'hrm-pro' ); ?></h2>
				<p class="text-sm text-slate-500"><?php esc_html_e( 'Who handled the check-in, the room booked, and the check-in record time.', 'hrm-pro' ); ?></p>
			</div>
			<span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-600"><?php echo esc_html( number_format_i18n( count( $checkin_details ) ) . ' ' . __( 'records', 'hrm-pro' ) ); ?></span>
		</div>
		<div class="overflow-x-auto">
			<table class="hrm-dashboard-table">
				<thead>
					<tr class="bg-surface-100 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
						<th class="px-6 py-3"><?php esc_html_e( 'Guest', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Check-In', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Handled By', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Revenue', 'hrm-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-black/5">
					<?php if ( $checkin_details ) : ?>
						<?php foreach ( $checkin_details as $detail ) : ?>
							<tr class="hover:bg-surface-100">
								<td class="px-6 py-4 text-sm">
									<p class="font-bold text-black"><?php echo esc_html( $detail['guest_name'] ); ?></p>
									<p class="text-xs text-slate-500">#<?php echo esc_html( $detail['booking_ref'] ); ?></p>
								</td>
								<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( sprintf( __( 'Room %1$s - %2$s', 'hrm-pro' ), $detail['room_number'], $detail['room_type'] ) ); ?></td>
								<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( $detail['checked_in_display'] ); ?></td>
								<td class="px-6 py-4 text-sm font-semibold text-black"><?php echo esc_html( $detail['checked_in_by'] ); ?></td>
								<td class="px-6 py-4 text-sm">
									<p class="font-semibold text-black"><?php echo esc_html( $detail['paid_formatted'] ); ?></p>
									<p class="text-xs text-slate-500"><?php echo esc_html( $detail['total_formatted'] ); ?></p>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No check-in records for this period.', 'hrm-pro' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>

	<section class="mt-6 grid gap-6 lg:grid-cols-3">
		<?php $render_mix( __( 'Booking Sources', 'hrm-pro' ), $source_mix, __( 'No booking source data yet.', 'hrm-pro' ) ); ?>
		<?php $render_mix( __( 'Booking Statuses', 'hrm-pro' ), $status_mix, __( 'No booking status data yet.', 'hrm-pro' ) ); ?>
		<?php $render_mix( __( 'Payment Methods', 'hrm-pro' ), $payment_mix, __( 'No payment method data yet.', 'hrm-pro' ) ); ?>
	</section>
</div>
