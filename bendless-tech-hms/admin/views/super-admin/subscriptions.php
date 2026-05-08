<?php
/**
 * Platform subscription manager view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$subscriptions = isset( $subscriptions ) && is_array( $subscriptions ) ? $subscriptions : array();
$filters       = isset( $filters ) && is_array( $filters ) ? $filters : array();
$plans         = isset( $plans ) && is_array( $plans ) ? $plans : HRM_License::get_plans();
?>

<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
	<div>
		<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Subscription Manager', 'hrm-pro' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Filter payments, extend billing periods, cancel subscriptions, and record manual payments.', 'hrm-pro' ); ?></p>
	</div>
</div>

<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="mb-6 rounded-2xl bg-white p-5 shadow-card">
	<input type="hidden" name="page" value="hrm-platform-subscriptions">
	<div class="grid gap-4 md:grid-cols-5">
		<label class="space-y-1.5">
			<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Plan', 'hrm-pro' ); ?></span>
			<select name="plan" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
				<option value=""><?php esc_html_e( 'All plans', 'hrm-pro' ); ?></option>
				<?php foreach ( $plans as $slug => $plan ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( isset( $filters['plan'] ) ? $filters['plan'] : '', $slug ); ?>><?php echo esc_html( $plan['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="space-y-1.5">
			<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></span>
			<select name="status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
				<option value=""><?php esc_html_e( 'All statuses', 'hrm-pro' ); ?></option>
				<?php foreach ( array( 'active', 'cancelled', 'expired', 'pending' ) as $status_option ) : ?>
					<option value="<?php echo esc_attr( $status_option ); ?>" <?php selected( isset( $filters['status'] ) ? $filters['status'] : '', $status_option ); ?>><?php echo esc_html( ucfirst( $status_option ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="space-y-1.5">
			<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Cycle', 'hrm-pro' ); ?></span>
			<select name="billing_cycle" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
				<option value=""><?php esc_html_e( 'All cycles', 'hrm-pro' ); ?></option>
				<option value="monthly" <?php selected( isset( $filters['billing_cycle'] ) ? $filters['billing_cycle'] : '', 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'hrm-pro' ); ?></option>
				<option value="yearly" <?php selected( isset( $filters['billing_cycle'] ) ? $filters['billing_cycle'] : '', 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'hrm-pro' ); ?></option>
			</select>
		</label>
		<label class="space-y-1.5">
			<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'From', 'hrm-pro' ); ?></span>
			<input type="date" name="date_from" value="<?php echo esc_attr( isset( $filters['date_from'] ) ? $filters['date_from'] : '' ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
		</label>
		<label class="space-y-1.5">
			<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'To', 'hrm-pro' ); ?></span>
			<input type="date" name="date_to" value="<?php echo esc_attr( isset( $filters['date_to'] ) ? $filters['date_to'] : '' ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
		</label>
	</div>
	<div class="mt-4 flex justify-end gap-3">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hrm-platform-subscriptions' ) ); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
			<span class="iconify" data-icon="solar:refresh-linear"></span>
			<?php esc_html_e( 'Reset', 'hrm-pro' ); ?>
		</a>
		<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
			<span class="iconify" data-icon="solar:filter-linear"></span>
			<?php esc_html_e( 'Filter', 'hrm-pro' ); ?>
		</button>
	</div>
</form>

<div class="overflow-hidden rounded-2xl bg-white shadow-card">
	<div class="overflow-x-auto">
		<table class="w-full">
			<thead>
				<tr class="border-b border-slate-100 bg-surface-50">
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Hotel', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Plan', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Cycle', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Amount', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Ends', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Actions', 'hrm-pro' ); ?></th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-50">
				<?php if ( $subscriptions ) : ?>
					<?php foreach ( $subscriptions as $subscription ) : ?>
						<?php
						$status_class = 'bg-slate-100 text-slate-600';
						$dot_class    = 'bg-slate-400';
						if ( 'active' === $subscription->status ) {
							$status_class = 'bg-emerald-50 text-emerald-700';
							$dot_class    = 'bg-emerald-500';
						} elseif ( 'cancelled' === $subscription->status || 'expired' === $subscription->status ) {
							$status_class = 'bg-red-50 text-red-700';
							$dot_class    = 'bg-red-500';
						} elseif ( 'pending' === $subscription->status ) {
							$status_class = 'bg-amber-50 text-amber-700';
							$dot_class    = 'bg-amber-500';
						}
						?>
						<tr class="transition-colors hover:bg-surface-50">
							<td class="px-6 py-4 text-sm font-semibold text-slate-900">
								<?php echo esc_html( $subscription->hotel_name ? $subscription->hotel_name : __( 'Unknown hotel', 'hrm-pro' ) ); ?>
								<?php if ( (int) $subscription->is_test ) : ?>
									<span class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700"><?php esc_html_e( 'TEST', 'hrm-pro' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( HRM_License::plan_name( $subscription->plan ) ); ?></td>
							<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( ucfirst( $subscription->billing_cycle ) ); ?></td>
							<td class="px-6 py-4 text-sm font-semibold text-slate-900"><?php echo esc_html( HRM_License::money( $subscription->amount_paid ) ); ?></td>
							<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription->ends_at ) ) ); ?></td>
							<td class="px-6 py-4">
								<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo esc_attr( $status_class ); ?>">
									<span class="h-1.5 w-1.5 rounded-full <?php echo esc_attr( $dot_class ); ?>"></span>
									<?php echo esc_html( ucfirst( $subscription->status ) ); ?>
								</span>
							</td>
							<td class="px-6 py-4">
								<div class="flex justify-end gap-2">
									<button type="button" data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>" data-action-type="extend_30" class="hrm-subscription-action inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-surface-50">
										<span class="iconify" data-icon="solar:calendar-add-linear"></span>
										<?php esc_html_e( '+30', 'hrm-pro' ); ?>
									</button>
									<button type="button" data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>" data-action-type="extend_90" class="hrm-subscription-action inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-surface-50">
										<span class="iconify" data-icon="solar:calendar-add-linear"></span>
										<?php esc_html_e( '+90', 'hrm-pro' ); ?>
									</button>
									<button type="button" data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>" data-action-type="extend_365" class="hrm-subscription-action inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-surface-50">
										<span class="iconify" data-icon="solar:calendar-add-linear"></span>
										<?php esc_html_e( '+365', 'hrm-pro' ); ?>
									</button>
									<button type="button" data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>" data-action-type="mark_paid" class="hrm-subscription-action inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50" title="<?php esc_attr_e( 'Mark as Paid', 'hrm-pro' ); ?>">
										<span class="iconify" data-icon="solar:wallet-money-linear"></span>
									</button>
									<button type="button" data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>" data-action-type="cancel" class="hrm-subscription-action inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600 transition-colors hover:bg-red-100" title="<?php esc_attr_e( 'Cancel', 'hrm-pro' ); ?>">
										<span class="iconify" data-icon="solar:close-circle-linear"></span>
									</button>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No subscriptions match these filters.', 'hrm-pro' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
