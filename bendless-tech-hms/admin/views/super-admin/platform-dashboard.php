<?php
/**
 * Platform dashboard view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats                = isset( $stats ) && is_array( $stats ) ? $stats : array();
$monthly_revenue      = isset( $monthly_revenue ) && is_array( $monthly_revenue ) ? $monthly_revenue : array();
$recent_subscriptions = isset( $recent_subscriptions ) && is_array( $recent_subscriptions ) ? $recent_subscriptions : array();
$expired_hotels       = isset( $expired_hotels ) && is_array( $expired_hotels ) ? $expired_hotels : array();
$activity             = isset( $activity ) && is_array( $activity ) ? $activity : array();
$max_revenue          = 1;
foreach ( $monthly_revenue as $month ) {
	$max_revenue = max( $max_revenue, (float) $month['total'] );
}
?>

<div class="grid gap-6 xl:grid-cols-5">
	<div class="rounded-2xl bg-white p-6 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50">
				<span class="iconify text-2xl text-primary-500" data-icon="solar:city-linear"></span>
			</div>
			<div class="min-w-0">
				<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Total Hotels', 'hrm-pro' ); ?></p>
				<p class="mt-0.5 text-2xl font-bold text-slate-900"><?php echo esc_html( number_format_i18n( isset( $stats['total_hotels'] ) ? $stats['total_hotels'] : 0 ) ); ?></p>
			</div>
		</div>
	</div>
	<div class="rounded-2xl bg-white p-6 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
				<span class="iconify text-2xl text-emerald-500" data-icon="solar:check-circle-linear"></span>
			</div>
			<div class="min-w-0">
				<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Active', 'hrm-pro' ); ?></p>
				<p class="mt-0.5 text-2xl font-bold text-slate-900"><?php echo esc_html( number_format_i18n( isset( $stats['active_subscriptions'] ) ? $stats['active_subscriptions'] : 0 ) ); ?></p>
			</div>
		</div>
	</div>
	<div class="rounded-2xl bg-white p-6 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50">
				<span class="iconify text-2xl text-red-500" data-icon="solar:danger-triangle-linear"></span>
			</div>
			<div class="min-w-0">
				<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Expired', 'hrm-pro' ); ?></p>
				<p class="mt-0.5 text-2xl font-bold text-slate-900"><?php echo esc_html( number_format_i18n( isset( $stats['expired'] ) ? $stats['expired'] : 0 ) ); ?></p>
			</div>
		</div>
	</div>
	<div class="rounded-2xl bg-white p-6 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50">
				<span class="iconify text-2xl text-blue-500" data-icon="solar:test-tube-linear"></span>
			</div>
			<div class="min-w-0">
				<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Trials', 'hrm-pro' ); ?></p>
				<p class="mt-0.5 text-2xl font-bold text-slate-900"><?php echo esc_html( number_format_i18n( isset( $stats['trial'] ) ? $stats['trial'] : 0 ) ); ?></p>
			</div>
		</div>
	</div>
	<div class="rounded-2xl bg-white p-6 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50">
				<span class="iconify text-2xl text-amber-500" data-icon="solar:wallet-money-linear"></span>
			</div>
			<div class="min-w-0">
				<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Total Revenue', 'hrm-pro' ); ?></p>
				<p class="mt-0.5 truncate text-2xl font-bold text-slate-900"><?php echo esc_html( HRM_License::money( isset( $stats['total_revenue'] ) ? $stats['total_revenue'] : 0 ) ); ?></p>
			</div>
		</div>
	</div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
	<div class="rounded-2xl bg-white shadow-card">
		<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Monthly Revenue', 'hrm-pro' ); ?></h3>
			<span class="inline-flex items-center gap-1 rounded-full bg-surface-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
				<span class="iconify" data-icon="solar:chart-square-linear"></span>
				<?php esc_html_e( 'Last 6 months', 'hrm-pro' ); ?>
			</span>
		</div>
		<div class="flex h-72 items-end gap-4 px-6 py-6">
			<?php foreach ( $monthly_revenue as $month ) : ?>
				<?php $height = max( 4, round( ( (float) $month['total'] / $max_revenue ) * 100 ) ); ?>
				<div class="flex h-full flex-1 flex-col justify-end gap-3">
					<div class="group relative flex flex-1 items-end justify-center">
						<div class="w-full rounded-t-xl bg-primary-500 transition-all group-hover:bg-primary-600" style="height: <?php echo esc_attr( $height ); ?>%"></div>
						<div class="pointer-events-none absolute -top-8 hidden rounded-lg bg-surface-900 px-2 py-1 text-xs font-semibold text-white group-hover:block">
							<?php echo esc_html( HRM_License::money( $month['total'] ) ); ?>
						</div>
					</div>
					<p class="text-center text-xs font-semibold text-slate-500"><?php echo esc_html( $month['label'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="space-y-6">
		<div class="rounded-2xl bg-white p-6 shadow-card">
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Quick Links', 'hrm-pro' ); ?></h3>
			<div class="mt-4 grid gap-3">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hrm-platform-hotels' ) ); ?>" class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
					<span class="flex items-center gap-2"><span class="iconify" data-icon="solar:add-circle-linear"></span><?php esc_html_e( 'Add Hotel', 'hrm-pro' ); ?></span>
					<span class="iconify" data-icon="solar:alt-arrow-right-linear"></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hrm-platform-hotels' ) ); ?>" class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
					<span class="flex items-center gap-2"><span class="iconify" data-icon="solar:city-linear"></span><?php esc_html_e( 'All Hotels', 'hrm-pro' ); ?></span>
					<span class="iconify" data-icon="solar:alt-arrow-right-linear"></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hrm-platform-subscriptions' ) ); ?>" class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
					<span class="flex items-center gap-2"><span class="iconify" data-icon="solar:wallet-money-linear"></span><?php esc_html_e( 'Subscription Manager', 'hrm-pro' ); ?></span>
					<span class="iconify" data-icon="solar:alt-arrow-right-linear"></span>
				</a>
			</div>
		</div>

		<div class="rounded-2xl bg-white shadow-card">
			<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Expired Hotels', 'hrm-pro' ); ?></h3>
				<span class="iconify text-red-500" data-icon="solar:danger-triangle-linear"></span>
			</div>
			<div class="divide-y divide-slate-50">
				<?php if ( $expired_hotels ) : ?>
					<?php foreach ( $expired_hotels as $expired_hotel ) : ?>
						<div class="flex items-center justify-between gap-4 px-6 py-4">
							<div class="min-w-0">
								<p class="truncate text-sm font-semibold text-slate-800"><?php echo esc_html( $expired_hotel->hotel_name ); ?></p>
								<p class="mt-1 text-xs text-slate-500"><?php echo esc_html( HRM_License::plan_name( $expired_hotel->plan ) ); ?></p>
							</div>
							<button type="button" data-hotel-id="<?php echo esc_attr( $expired_hotel->id ); ?>" data-mode="enable" class="hrm-toggle-subscription inline-flex shrink-0 items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100">
								<span class="iconify" data-icon="solar:check-circle-linear"></span>
								<?php esc_html_e( 'Enable', 'hrm-pro' ); ?>
							</button>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="px-6 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No expired hotels need attention.', 'hrm-pro' ); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rounded-2xl bg-white p-6 shadow-card">
			<?php wp_nonce_field( 'hrm_save_platform_settings' ); ?>
			<input type="hidden" name="action" value="hrm_save_platform_settings">
			<div class="flex items-center justify-between">
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Paystack Settings', 'hrm-pro' ); ?></h3>
				<label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
					<input type="checkbox" name="hrm_test_mode" value="1" class="h-4 w-4 rounded border-slate-300 text-primary-500 focus:ring-primary-500" <?php checked( get_option( 'hrm_test_mode' ), 1 ); ?>>
					<?php esc_html_e( 'Test mode', 'hrm-pro' ); ?>
				</label>
			</div>
			<div class="mt-4 grid gap-3">
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Live Public Key', 'hrm-pro' ); ?></span>
					<input type="text" name="hrm_paystack_public_key" value="<?php echo esc_attr( $paystack_public ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Live Secret Key', 'hrm-pro' ); ?></span>
					<input type="password" name="hrm_paystack_secret_key" value="<?php echo esc_attr( $paystack_secret ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Test Public Key', 'hrm-pro' ); ?></span>
					<input type="text" name="hrm_paystack_test_public_key" value="<?php echo esc_attr( $paystack_test_public ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Test Secret Key', 'hrm-pro' ); ?></span>
					<input type="password" name="hrm_paystack_test_secret_key" value="<?php echo esc_attr( $paystack_test_secret ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
			</div>
			<button type="submit" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
				<span class="iconify" data-icon="solar:check-circle-linear"></span>
				<?php esc_html_e( 'Save Settings', 'hrm-pro' ); ?>
			</button>
		</form>
	</div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
	<div class="overflow-hidden rounded-2xl bg-white shadow-card">
		<div class="border-b border-slate-100 px-6 py-4">
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Recent Subscription Payments', 'hrm-pro' ); ?></h3>
		</div>
		<div class="overflow-x-auto">
			<table class="w-full">
				<thead>
					<tr class="border-b border-slate-100 bg-surface-50">
						<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Hotel', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Plan', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Amount', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-50">
					<?php if ( $recent_subscriptions ) : ?>
						<?php foreach ( $recent_subscriptions as $subscription ) : ?>
							<tr class="transition-colors hover:bg-surface-50">
								<td class="px-6 py-4 text-sm font-medium text-slate-800"><?php echo esc_html( $subscription->hotel_name ? $subscription->hotel_name : __( 'Unknown hotel', 'hrm-pro' ) ); ?></td>
								<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( HRM_License::plan_name( $subscription->plan ) ); ?></td>
								<td class="px-6 py-4 text-sm font-semibold text-slate-900"><?php echo esc_html( HRM_License::money( $subscription->amount_paid ) ); ?></td>
								<td class="px-6 py-4 text-sm">
									<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
										<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
										<?php echo esc_html( ucfirst( $subscription->status ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No subscription payments yet.', 'hrm-pro' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="overflow-hidden rounded-2xl bg-white shadow-card">
		<div class="border-b border-slate-100 px-6 py-4">
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Platform Activity', 'hrm-pro' ); ?></h3>
		</div>
		<div class="divide-y divide-slate-50">
			<?php if ( $activity ) : ?>
				<?php foreach ( $activity as $entry ) : ?>
					<div class="flex items-start gap-3 px-6 py-4">
						<span class="iconify mt-0.5 text-slate-400" data-icon="solar:clock-circle-linear"></span>
						<div class="min-w-0">
							<p class="truncate text-sm font-medium text-slate-700"><?php echo esc_html( ucwords( str_replace( '_', ' ', $entry->action ) ) ); ?></p>
							<p class="mt-1 text-xs text-slate-400"><?php echo esc_html( ( $entry->hotel_name ? $entry->hotel_name . ' · ' : '' ) . date_i18n( get_option( 'date_format' ), strtotime( $entry->created_at ) ) ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="px-6 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No platform activity recorded yet.', 'hrm-pro' ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</div>
