<?php
/**
 * Frontend platform dashboard view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hotels = isset( $hotels ) && is_array( $hotels ) ? $hotels : array();
$stats  = isset( $stats ) && is_array( $stats ) ? $stats : array();
?>

<div class="hrm-frontend-dashboard font-sans text-black" style="--hrm-brand-primary:#987CC0;--hrm-brand-button:#987CC0;--hrm-brand-text:#000000;--hrm-brand-font:Inter, system-ui, sans-serif;">
	<header class="rounded-2xl border border-black/10 bg-white p-6 shadow-card">
		<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
			<div>
				<p class="text-xs font-bold uppercase tracking-wide text-primary-600"><?php esc_html_e( 'Bendless Tech HMS', 'hrm-pro' ); ?></p>
				<h1 class="mt-1 text-3xl font-bold text-black"><?php esc_html_e( 'Platform Dashboard', 'hrm-pro' ); ?></h1>
				<p class="mt-2 text-sm text-slate-600"><?php esc_html_e( 'Subscriber hotels, plan status, expiry dates, room counts, and platform revenue at a glance.', 'hrm-pro' ); ?></p>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hrm-platform' ) ); ?>" class="hrm-brand-button">
				<span class="iconify" data-icon="solar:settings-linear"></span>
				<?php esc_html_e( 'Open WP Platform Panel', 'hrm-pro' ); ?>
			</a>
		</div>
	</header>

	<section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
		<?php
		$cards = array(
			array( __( 'Hotels', 'hrm-pro' ), number_format_i18n( isset( $stats['total_hotels'] ) ? (int) $stats['total_hotels'] : 0 ), 'solar:city-linear' ),
			array( __( 'Active', 'hrm-pro' ), number_format_i18n( isset( $stats['active_subscriptions'] ) ? (int) $stats['active_subscriptions'] : 0 ), 'solar:check-circle-linear' ),
			array( __( 'Expired', 'hrm-pro' ), number_format_i18n( isset( $stats['expired'] ) ? (int) $stats['expired'] : 0 ), 'solar:danger-triangle-linear' ),
			array( __( 'Trial', 'hrm-pro' ), number_format_i18n( isset( $stats['trial'] ) ? (int) $stats['trial'] : 0 ), 'solar:test-tube-linear' ),
			array( __( 'Revenue', 'hrm-pro' ), '₦' . number_format( isset( $stats['total_revenue'] ) ? (float) $stats['total_revenue'] : 0, 2 ), 'solar:wallet-money-linear' ),
		);
		?>
		<?php foreach ( $cards as $card ) : ?>
			<div class="hrm-dashboard-card p-5">
				<div class="flex items-center gap-3">
					<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600"><span class="iconify text-2xl" data-icon="<?php echo esc_attr( $card[2] ); ?>"></span></span>
					<div>
						<p class="text-sm font-medium text-slate-500"><?php echo esc_html( $card[0] ); ?></p>
						<p class="text-2xl font-bold text-black"><?php echo esc_html( $card[1] ); ?></p>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</section>

	<section class="hrm-dashboard-card mt-6 overflow-hidden">
		<div class="border-b border-black/10 px-6 py-4">
			<h2 class="font-bold text-black"><?php esc_html_e( 'Subscribed Hotels', 'hrm-pro' ); ?></h2>
		</div>
		<div class="overflow-x-auto">
			<table class="hrm-dashboard-table">
				<thead>
					<tr class="bg-surface-100 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
						<th class="px-6 py-3"><?php esc_html_e( 'Hotel', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Owner', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Plan', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Expires', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Rooms', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Revenue', 'hrm-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-black/5">
					<?php if ( $hotels ) : ?>
						<?php foreach ( $hotels as $hotel_row ) : ?>
							<tr class="hover:bg-surface-100">
								<td class="px-6 py-4 text-sm">
									<p class="font-bold text-black"><?php echo esc_html( $hotel_row->hotel_name ); ?></p>
									<p class="text-xs text-slate-500"><?php echo esc_html( $hotel_row->hotel_slug ); ?></p>
								</td>
								<td class="px-6 py-4 text-sm text-slate-700">
									<p><?php echo esc_html( $hotel_row->owner_name ? $hotel_row->owner_name : __( 'Unassigned', 'hrm-pro' ) ); ?></p>
									<p class="text-xs text-slate-500"><?php echo esc_html( $hotel_row->owner_email ); ?></p>
								</td>
								<td class="px-6 py-4 text-sm font-semibold text-black"><?php echo esc_html( HRM_License::plan_name( $hotel_row->plan ) ); ?></td>
								<td class="px-6 py-4 text-sm">
									<span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-600"><?php echo esc_html( ucwords( str_replace( '_', ' ', $hotel_row->subscription_status ) ) ); ?></span>
								</td>
								<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( $hotel_row->subscription_ends_at ? date_i18n( get_option( 'date_format' ), strtotime( $hotel_row->subscription_ends_at ) ) : __( 'No expiry', 'hrm-pro' ) ); ?></td>
								<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( number_format_i18n( (int) $hotel_row->rooms_count ) ); ?></td>
								<td class="px-6 py-4 text-sm font-semibold text-black"><?php echo esc_html( '₦' . number_format( (float) $hotel_row->lifetime_revenue, 2 ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr><td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No hotels have been onboarded yet.', 'hrm-pro' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>
</div>
