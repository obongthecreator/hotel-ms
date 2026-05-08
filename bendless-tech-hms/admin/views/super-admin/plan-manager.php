<?php
/**
 * Platform plan manager view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plans          = isset( $plans ) && is_array( $plans ) ? $plans : HRM_License::get_plans();
$feature_labels = isset( $feature_labels ) && is_array( $feature_labels ) ? $feature_labels : HRM_License::feature_labels();
?>

<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
	<div>
		<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Plan Manager', 'hrm-pro' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Edit pricing, room limits, staff limits, and feature gates for all HMS plans.', 'hrm-pro' ); ?></p>
	</div>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'hrm_save_plan_config' ); ?>
	<input type="hidden" name="action" value="hrm_save_plan_config">

	<div class="grid gap-6 xl:grid-cols-2">
		<?php foreach ( HRM_License::get_plan_order() as $plan_slug ) : ?>
			<?php
			if ( empty( $plans[ $plan_slug ] ) ) {
				continue;
			}
			$plan        = $plans[ $plan_slug ];
			$is_wildcard = array( '*' ) === $plan['features'];
			?>
			<div class="rounded-2xl bg-white p-6 shadow-card">
				<div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5">
					<div>
						<h3 class="text-lg font-bold text-slate-900"><?php echo esc_html( $plan['name'] ); ?></h3>
						<p class="mt-1 text-sm text-slate-500"><?php echo esc_html( sprintf( __( '%s plan configuration', 'hrm-pro' ), $plan['name'] ) ); ?></p>
					</div>
					<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?php echo esc_attr( HRM_License::plan_badge_classes( $plan_slug ) ); ?>">
						<span class="iconify" data-icon="solar:crown-star-linear"></span>
						<?php echo esc_html( $plan['name'] ); ?>
					</span>
				</div>

				<div class="mt-5 grid gap-4 md:grid-cols-3">
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Monthly Price', 'hrm-pro' ); ?></span>
						<input type="number" min="0" step="1" name="plans[<?php echo esc_attr( $plan_slug ); ?>][price_monthly]" value="<?php echo esc_attr( (int) $plan['price_monthly'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
						<p class="text-xs text-slate-400"><?php esc_html_e( 'Use 0 for annual-only plans.', 'hrm-pro' ); ?></p>
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'First Year Price', 'hrm-pro' ); ?></span>
						<input type="number" min="0" step="1" name="plans[<?php echo esc_attr( $plan_slug ); ?>][price_yearly]" value="<?php echo esc_attr( (int) $plan['price_yearly'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Renewal Price', 'hrm-pro' ); ?></span>
						<input type="number" min="0" step="1" name="plans[<?php echo esc_attr( $plan_slug ); ?>][renewal_yearly]" value="<?php echo esc_attr( isset( $plan['renewal_yearly'] ) ? (int) $plan['renewal_yearly'] : (int) $plan['price_yearly'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Room Limit', 'hrm-pro' ); ?></span>
						<input type="number" step="1" name="plans[<?php echo esc_attr( $plan_slug ); ?>][rooms_limit]" value="<?php echo esc_attr( (int) $plan['rooms_limit'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
						<p class="text-xs text-slate-400"><?php esc_html_e( 'Use -1 for unlimited.', 'hrm-pro' ); ?></p>
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Staff Limit', 'hrm-pro' ); ?></span>
						<input type="number" step="1" name="plans[<?php echo esc_attr( $plan_slug ); ?>][staff_limit]" value="<?php echo esc_attr( (int) $plan['staff_limit'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
						<p class="text-xs text-slate-400"><?php esc_html_e( 'Use -1 for unlimited.', 'hrm-pro' ); ?></p>
					</label>
				</div>

				<div class="mt-6">
					<div class="mb-3 flex items-center justify-between">
						<h4 class="text-sm font-semibold text-slate-800"><?php esc_html_e( 'Features', 'hrm-pro' ); ?></h4>
						<?php if ( 'enterprise' === $plan_slug ) : ?>
							<label class="inline-flex items-center gap-2 text-xs font-semibold text-amber-700">
								<input type="checkbox" name="plans[<?php echo esc_attr( $plan_slug ); ?>][features][]" value="*" class="h-4 w-4 rounded border-slate-300 text-primary-500 focus:ring-primary-500" <?php checked( $is_wildcard ); ?>>
								<?php esc_html_e( 'Unlock all', 'hrm-pro' ); ?>
							</label>
						<?php endif; ?>
					</div>
					<div class="grid max-h-72 gap-2 overflow-y-auto rounded-xl border border-slate-100 bg-surface-50 p-3 md:grid-cols-2">
						<?php foreach ( $feature_labels as $feature_slug => $feature_label ) : ?>
							<label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-700">
								<input type="checkbox" name="plans[<?php echo esc_attr( $plan_slug ); ?>][features][]" value="<?php echo esc_attr( $feature_slug ); ?>" class="h-4 w-4 rounded border-slate-300 text-primary-500 focus:ring-primary-500" <?php checked( $is_wildcard || in_array( $feature_slug, $plan['features'], true ) ); ?>>
								<span><?php echo esc_html( $feature_label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="sticky bottom-0 mt-6 flex justify-end border-t border-slate-200 bg-surface-50 py-4">
		<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-600">
			<span class="iconify" data-icon="solar:check-circle-linear"></span>
			<?php esc_html_e( 'Save Plan Configuration', 'hrm-pro' ); ?>
		</button>
	</div>
</form>
