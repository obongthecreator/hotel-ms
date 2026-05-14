<?php
/**
 * Phase 1 tenant engine dashboard.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$subscription_callback = isset( $subscription_callback ) ? (bool) $subscription_callback : false;
$callback_success      = isset( $callback_success ) ? (bool) $callback_success : false;
$callback_message      = isset( $callback_message ) ? $callback_message : '';
$stats                 = isset( $stats ) && is_array( $stats ) ? $stats : array();
$activities            = isset( $activities ) && is_array( $activities ) ? $activities : array();
$feature_labels        = isset( $feature_labels ) && is_array( $feature_labels ) ? $feature_labels : HRM_License::feature_labels();
$available_plans       = isset( $available_plans ) && is_array( $available_plans ) ? $available_plans : HRM_License::get_plans();
?>

<?php if ( $subscription_callback ) : ?>
	<div class="mb-6 rounded-2xl border <?php echo $callback_success ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-red-100 bg-red-50 text-red-700'; ?> p-5">
		<div class="flex items-start gap-3">
			<span class="iconify mt-0.5 text-2xl" data-icon="<?php echo esc_attr( $callback_success ? 'solar:check-circle-linear' : 'solar:close-circle-linear' ); ?>"></span>
			<div>
				<h2 class="text-base font-semibold"><?php echo esc_html( $callback_success ? __( 'Payment Verified', 'hrm-pro' ) : __( 'Verification Failed', 'hrm-pro' ) ); ?></h2>
				<p class="mt-1 text-sm"><?php echo esc_html( $callback_message ); ?></p>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ( ! $hotel ) : ?>
	<div class="rounded-2xl bg-white p-10 text-center shadow-card">
		<div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-500">
			<span class="iconify text-3xl" data-icon="solar:city-linear"></span>
		</div>
		<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'No hotel assigned', 'hrm-pro' ); ?></h2>
		<p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500">
			<?php esc_html_e( 'A super admin needs to add this WordPress user as a hotel owner before the Hotel Manager workspace can be used.', 'hrm-pro' ); ?>
		</p>
		<?php if ( is_super_admin() ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hrm-platform-hotels' ) ); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
				<span class="iconify" data-icon="solar:add-circle-linear"></span>
				<?php esc_html_e( 'Add Hotel', 'hrm-pro' ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php return; ?>
<?php endif; ?>

<?php $expiry_context = HRM_License::get_expiry_context( $hotel ); ?>

<div class="grid gap-6 xl:grid-cols-4">
	<div class="rounded-2xl bg-white p-6 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50">
				<span class="iconify text-2xl text-primary-500" data-icon="solar:buildings-2-linear"></span>
			</div>
			<div class="min-w-0">
				<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Rooms Registered', 'hrm-pro' ); ?></p>
				<p class="mt-0.5 truncate text-2xl font-bold text-slate-900"><?php echo esc_html( number_format_i18n( isset( $stats['total_rooms'] ) ? $stats['total_rooms'] : 0 ) ); ?></p>
				<p class="mt-1 flex items-center gap-1 text-xs text-slate-400">
					<span class="iconify" data-icon="solar:city-linear"></span>
					<?php echo esc_html( $hotel->hotel_name ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="rounded-2xl bg-white p-6 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
				<span class="iconify text-2xl text-emerald-500" data-icon="solar:calendar-mark-linear"></span>
			</div>
			<div class="min-w-0">
				<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Active Bookings', 'hrm-pro' ); ?></p>
				<p class="mt-0.5 truncate text-2xl font-bold text-slate-900"><?php echo esc_html( number_format_i18n( isset( $stats['active_bookings'] ) ? $stats['active_bookings'] : 0 ) ); ?></p>
				<p class="mt-1 flex items-center gap-1 text-xs text-slate-400">
					<span class="iconify" data-icon="solar:refresh-linear"></span>
					<?php esc_html_e( 'Engine sync ready', 'hrm-pro' ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="rounded-2xl bg-white p-6 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50">
				<span class="iconify text-2xl text-amber-500" data-icon="solar:wallet-money-linear"></span>
			</div>
			<div class="min-w-0">
				<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Paid Revenue', 'hrm-pro' ); ?></p>
				<p class="mt-0.5 truncate text-2xl font-bold text-slate-900"><?php echo esc_html( HRM_License::money( isset( $stats['paid_revenue'] ) ? $stats['paid_revenue'] : 0 ) ); ?></p>
				<p class="mt-1 flex items-center gap-1 text-xs text-slate-400">
					<span class="iconify" data-icon="solar:document-text-linear"></span>
					<?php esc_html_e( 'Ledger tables online', 'hrm-pro' ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="rounded-2xl bg-white p-6 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl <?php echo $hotel->is_active() ? 'bg-emerald-50' : 'bg-red-50'; ?>">
				<span class="iconify text-2xl <?php echo $hotel->is_active() ? 'text-emerald-500' : 'text-red-500'; ?>" data-icon="<?php echo esc_attr( $hotel->is_active() ? 'solar:check-circle-linear' : 'solar:danger-triangle-linear' ); ?>"></span>
			</div>
			<div class="min-w-0">
				<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Subscription', 'hrm-pro' ); ?></p>
				<p class="mt-0.5 truncate text-2xl font-bold text-slate-900"><?php echo esc_html( $expiry_context['status'] ); ?></p>
				<p class="mt-1 flex items-center gap-1 text-xs text-slate-400">
					<span class="iconify" data-icon="solar:calendar-add-linear"></span>
					<?php echo esc_html( $expiry_context['expiry_display'] ); ?>
				</p>
			</div>
		</div>
	</div>
</div>

<div class="mt-6 rounded-2xl bg-white shadow-card">
	<div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-4 md:flex-row md:items-center md:justify-between">
		<div>
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Live Room Grid', 'hrm-pro' ); ?></h3>
			<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Auto-refreshing room state for front desk decisions.', 'hrm-pro' ); ?></p>
		</div>
		<button type="button" class="hrm-refresh-room-grid inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
			<span class="iconify" data-icon="solar:refresh-linear"></span>
			<?php esc_html_e( 'Refresh', 'hrm-pro' ); ?>
		</button>
	</div>
	<div class="p-6">
		<div class="hrm-room-grid-live grid gap-4 md:grid-cols-2 xl:grid-cols-4" data-autorefresh="1">
			<div class="col-span-full rounded-xl bg-surface-50 px-4 py-6 text-center text-sm font-medium text-slate-500">
				<?php esc_html_e( 'Loading room grid...', 'hrm-pro' ); ?>
			</div>
		</div>
	</div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
	<div class="rounded-2xl bg-white shadow-card">
		<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
			<div>
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'License & Feature Gates', 'hrm-pro' ); ?></h3>
				<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'The active plan controls which HMS modules can render for this hotel.', 'hrm-pro' ); ?></p>
			</div>
			<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?php echo esc_attr( HRM_License::plan_badge_classes( $hotel->plan ) ); ?>">
				<span class="iconify" data-icon="solar:crown-star-linear"></span>
				<?php echo esc_html( HRM_License::plan_name( $hotel->plan ) ); ?>
			</span>
		</div>
		<div class="grid gap-3 p-6 md:grid-cols-2">
			<?php foreach ( $feature_labels as $feature_slug => $feature_name ) : ?>
				<?php $allowed = HRM_License::can( $feature_slug ); ?>
				<div class="flex items-center justify-between rounded-xl border border-slate-100 bg-surface-50 px-4 py-3">
					<div class="flex min-w-0 items-center gap-3">
						<span class="iconify shrink-0 text-lg <?php echo $allowed ? 'text-emerald-500' : 'text-slate-400'; ?>" data-icon="<?php echo esc_attr( $allowed ? 'solar:check-circle-linear' : 'solar:lock-password-linear' ); ?>"></span>
						<span class="truncate text-sm font-medium text-slate-700"><?php echo esc_html( $feature_name ); ?></span>
					</div>
					<span class="ml-3 shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold <?php echo $allowed ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'; ?>">
						<?php echo esc_html( $allowed ? __( 'On', 'hrm-pro' ) : __( 'Locked', 'hrm-pro' ) ); ?>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="space-y-6">
		<div class="rounded-2xl bg-white p-6 shadow-card">
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Hotel Profile', 'hrm-pro' ); ?></h3>
			<div class="mt-4 space-y-3 text-sm">
				<div class="flex items-start gap-3">
					<span class="iconify mt-0.5 text-slate-400" data-icon="solar:city-linear"></span>
					<div>
						<p class="font-medium text-slate-800"><?php echo esc_html( $hotel->hotel_name ); ?></p>
						<p class="text-slate-500"><?php echo esc_html( $hotel->hotel_slug ); ?></p>
					</div>
				</div>
				<div class="flex items-start gap-3">
					<span class="iconify mt-0.5 text-slate-400" data-icon="solar:bell-linear"></span>
					<div>
						<p class="font-medium text-slate-800"><?php echo esc_html( $hotel->hotel_email ? $hotel->hotel_email : __( 'No email', 'hrm-pro' ) ); ?></p>
						<p class="text-slate-500"><?php echo esc_html( $hotel->hotel_phone ? $hotel->hotel_phone : __( 'No phone number', 'hrm-pro' ) ); ?></p>
					</div>
				</div>
				<div class="flex items-start gap-3">
					<span class="iconify mt-0.5 text-slate-400" data-icon="solar:calendar-add-linear"></span>
					<div>
						<p class="font-medium text-slate-800"><?php echo esc_html( $expiry_context['expiry_display'] ); ?></p>
						<p class="text-slate-500"><?php esc_html_e( 'Subscription end date', 'hrm-pro' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div class="rounded-2xl bg-white shadow-card">
			<div class="border-b border-slate-100 px-6 py-4">
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Recent Activity', 'hrm-pro' ); ?></h3>
			</div>
			<div class="divide-y divide-slate-50">
				<?php if ( $activities ) : ?>
					<?php foreach ( $activities as $activity ) : ?>
						<div class="flex items-start gap-3 px-6 py-4">
							<span class="iconify mt-0.5 text-slate-400" data-icon="solar:clock-circle-linear"></span>
							<div class="min-w-0">
								<p class="truncate text-sm font-medium text-slate-700"><?php echo esc_html( ucwords( str_replace( '_', ' ', $activity->action ) ) ); ?></p>
								<p class="mt-1 text-xs text-slate-400"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $activity->created_at ) ) ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="px-6 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No activity recorded yet.', 'hrm-pro' ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<?php if ( ! HRM_License::can( 'advanced_reports' ) ) : ?>
	<div class="mt-6">
		<?php HRM_Admin::upgrade_prompt( __( 'Advanced reports', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'advanced_reports' ) ); ?>
	</div>
<?php endif; ?>
