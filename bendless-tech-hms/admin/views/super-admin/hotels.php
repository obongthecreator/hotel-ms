<?php
/**
 * Platform hotels view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hotels = isset( $hotels ) && is_array( $hotels ) ? $hotels : array();
$users  = isset( $users ) && is_array( $users ) ? $users : array();
$plans  = isset( $plans ) && is_array( $plans ) ? $plans : HRM_License::get_plans();
?>

<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
	<div>
		<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Hotel Management', 'hrm-pro' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Add hotels, assign owners, control plans, and manage tenant access.', 'hrm-pro' ); ?></p>
	</div>
	<button type="button" onclick="openModal('hrm-add-hotel-modal')" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-600">
		<span class="iconify" data-icon="solar:add-circle-linear"></span>
		<?php esc_html_e( 'Add Hotel', 'hrm-pro' ); ?>
	</button>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-card">
	<div class="overflow-x-auto">
		<table class="w-full">
			<thead>
				<tr class="border-b border-slate-100 bg-surface-50">
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Hotel Name', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Owner Email', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Plan', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Rooms', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Ends', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Actions', 'hrm-pro' ); ?></th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-50">
				<?php if ( $hotels ) : ?>
					<?php foreach ( $hotels as $hotel_row ) : ?>
						<?php
						$status       = sanitize_key( $hotel_row->subscription_status );
						$status_class = 'bg-slate-100 text-slate-600';
						$dot_class    = 'bg-slate-400';
						if ( 'active' === $status ) {
							$status_class = 'bg-emerald-50 text-emerald-700';
							$dot_class    = 'bg-emerald-500';
						} elseif ( 'expired' === $status ) {
							$status_class = 'bg-red-50 text-red-700';
							$dot_class    = 'bg-red-500';
						} elseif ( 'past_due' === $status ) {
							$status_class = 'bg-amber-50 text-amber-700';
							$dot_class    = 'bg-amber-500';
						} elseif ( 'trial' === $status ) {
							$status_class = 'bg-blue-50 text-blue-700';
							$dot_class    = 'bg-blue-500';
						}
						?>
						<tr class="transition-colors hover:bg-surface-50">
							<td class="px-6 py-4">
								<p class="text-sm font-semibold text-slate-900"><?php echo esc_html( $hotel_row->hotel_name ); ?></p>
								<p class="mt-0.5 text-xs text-slate-500"><?php echo esc_html( $hotel_row->hotel_slug ); ?></p>
							</td>
							<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( $hotel_row->owner_email ? $hotel_row->owner_email : $hotel_row->hotel_email ); ?></td>
							<td class="px-6 py-4">
								<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?php echo esc_attr( HRM_License::plan_badge_classes( $hotel_row->plan ) ); ?>">
									<span class="iconify" data-icon="solar:crown-star-linear"></span>
									<?php echo esc_html( HRM_License::plan_name( $hotel_row->plan ) ); ?>
								</span>
							</td>
							<td class="px-6 py-4">
								<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo esc_attr( $status_class ); ?>">
									<span class="h-1.5 w-1.5 rounded-full <?php echo esc_attr( $dot_class ); ?>"></span>
									<?php echo esc_html( ucwords( str_replace( '_', ' ', $status ) ) ); ?>
								</span>
							</td>
							<td class="px-6 py-4 text-sm font-semibold text-slate-800"><?php echo esc_html( number_format_i18n( (int) $hotel_row->rooms_count ) ); ?></td>
							<td class="px-6 py-4 text-sm text-slate-600">
								<?php echo esc_html( $hotel_row->subscription_ends_at ? date_i18n( get_option( 'date_format' ), strtotime( $hotel_row->subscription_ends_at ) ) : __( 'No expiry', 'hrm-pro' ) ); ?>
							</td>
							<td class="px-6 py-4">
								<div class="flex justify-end gap-2">
									<button type="button" onclick="openModal('hrm-edit-hotel-<?php echo esc_attr( $hotel_row->id ); ?>')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50" title="<?php esc_attr_e( 'Edit', 'hrm-pro' ); ?>">
										<span class="iconify" data-icon="solar:pen-linear"></span>
									</button>
									<button type="button" data-hotel-id="<?php echo esc_attr( $hotel_row->id ); ?>" data-mode="<?php echo esc_attr( 'active' === $status ? 'disable' : 'enable' ); ?>" class="hrm-toggle-subscription inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50" title="<?php echo esc_attr( 'active' === $status ? __( 'Disable Subscription', 'hrm-pro' ) : __( 'Enable Subscription', 'hrm-pro' ) ); ?>">
										<span class="iconify" data-icon="<?php echo esc_attr( 'active' === $status ? 'solar:lock-password-linear' : 'solar:check-circle-linear' ); ?>"></span>
									</button>
									<button type="button" onclick="openModal('hrm-extend-hotel-<?php echo esc_attr( $hotel_row->id ); ?>')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50" title="<?php esc_attr_e( 'Extend', 'hrm-pro' ); ?>">
										<span class="iconify" data-icon="solar:calendar-add-linear"></span>
									</button>
									<button type="button" data-hotel-id="<?php echo esc_attr( $hotel_row->id ); ?>" class="hrm-impersonate inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50" title="<?php esc_attr_e( 'Impersonate', 'hrm-pro' ); ?>">
										<span class="iconify" data-icon="solar:incognito-linear"></span>
									</button>
									<button type="button" data-hotel-id="<?php echo esc_attr( $hotel_row->id ); ?>" class="hrm-delete-hotel inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600 transition-colors hover:bg-red-100" title="<?php esc_attr_e( 'Delete', 'hrm-pro' ); ?>">
										<span class="iconify" data-icon="solar:trash-bin-minimalistic-linear"></span>
									</button>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No hotels have been added yet.', 'hrm-pro' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div id="hrm-add-hotel-modal" class="fixed left-1/2 top-1/2 z-50 hidden w-full max-w-2xl -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-modal">
	<form id="hrm-add-hotel-form" class="hrm-ajax-form" data-action="hrm_super_add_hotel">
		<?php wp_nonce_field( 'hrm_nonce', 'nonce' ); ?>
		<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
			<h3 class="text-lg font-semibold text-slate-800"><?php esc_html_e( 'Add Hotel', 'hrm-pro' ); ?></h3>
			<button type="button" onclick="closeModal('hrm-add-hotel-modal')" class="text-slate-400 transition-colors hover:text-slate-600"><span class="iconify text-2xl" data-icon="solar:close-circle-linear"></span></button>
		</div>
		<div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
			<div class="grid gap-4 md:grid-cols-2">
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Hotel Name', 'hrm-pro' ); ?></span>
					<input type="text" name="hotel_name" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'e.g. Lagos Grand Suites', 'hrm-pro' ); ?>">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Owner', 'hrm-pro' ); ?></span>
					<select name="user_id" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
						<option value=""><?php esc_html_e( 'Select owner', 'hrm-pro' ); ?></option>
						<?php foreach ( $users as $user_option ) : ?>
							<option value="<?php echo esc_attr( $user_option->ID ); ?>"><?php echo esc_html( $user_option->display_name . ' · ' . $user_option->user_email ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Plan', 'hrm-pro' ); ?></span>
					<select name="plan" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
						<?php foreach ( $plans as $slug => $plan ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $plan['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Billing Cycle', 'hrm-pro' ); ?></span>
					<select name="billing_cycle" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
						<option value="monthly"><?php esc_html_e( 'Monthly', 'hrm-pro' ); ?></option>
						<option value="yearly"><?php esc_html_e( 'Yearly', 'hrm-pro' ); ?></option>
					</select>
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Subscription End Date', 'hrm-pro' ); ?></span>
					<input type="date" name="subscription_ends_at" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Hotel Email', 'hrm-pro' ); ?></span>
					<input type="email" name="hotel_email" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Phone', 'hrm-pro' ); ?></span>
					<input type="text" name="hotel_phone" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
			</div>
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Address', 'hrm-pro' ); ?></span>
				<textarea name="hotel_address" rows="3" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
			</label>
		</div>
		<div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
			<button type="button" onclick="closeModal('hrm-add-hotel-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"><?php esc_html_e( 'Cancel', 'hrm-pro' ); ?></button>
			<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
				<span class="iconify" data-icon="solar:check-circle-linear"></span>
				<?php esc_html_e( 'Save Hotel', 'hrm-pro' ); ?>
			</button>
		</div>
	</form>
</div>

<?php foreach ( $hotels as $hotel_row ) : ?>
	<div id="hrm-edit-hotel-<?php echo esc_attr( $hotel_row->id ); ?>" class="fixed left-1/2 top-1/2 z-50 hidden w-full max-w-2xl -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-modal">
		<form class="hrm-ajax-form" data-action="hrm_super_update_hotel">
			<?php wp_nonce_field( 'hrm_nonce', 'nonce' ); ?>
			<input type="hidden" name="hotel_id" value="<?php echo esc_attr( $hotel_row->id ); ?>">
			<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
				<h3 class="text-lg font-semibold text-slate-800"><?php esc_html_e( 'Edit Hotel', 'hrm-pro' ); ?></h3>
				<button type="button" onclick="closeModal('hrm-edit-hotel-<?php echo esc_attr( $hotel_row->id ); ?>')" class="text-slate-400 transition-colors hover:text-slate-600"><span class="iconify text-2xl" data-icon="solar:close-circle-linear"></span></button>
			</div>
			<div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
				<div class="grid gap-4 md:grid-cols-2">
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Hotel Name', 'hrm-pro' ); ?></span>
						<input type="text" name="hotel_name" required value="<?php echo esc_attr( $hotel_row->hotel_name ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Owner', 'hrm-pro' ); ?></span>
						<select name="user_id" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
							<?php foreach ( $users as $user_option ) : ?>
								<option value="<?php echo esc_attr( $user_option->ID ); ?>" <?php selected( (int) $hotel_row->user_id, (int) $user_option->ID ); ?>><?php echo esc_html( $user_option->display_name . ' · ' . $user_option->user_email ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Plan', 'hrm-pro' ); ?></span>
						<select name="plan" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
							<?php foreach ( $plans as $slug => $plan ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $hotel_row->plan, $slug ); ?>><?php echo esc_html( $plan['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></span>
						<select name="subscription_status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
							<?php foreach ( array( 'active', 'trial', 'expired', 'past_due' ) as $status_option ) : ?>
								<option value="<?php echo esc_attr( $status_option ); ?>" <?php selected( $hotel_row->subscription_status, $status_option ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $status_option ) ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Subscription End Date', 'hrm-pro' ); ?></span>
						<input type="date" name="subscription_ends_at" value="<?php echo esc_attr( $hotel_row->subscription_ends_at ? date( 'Y-m-d', strtotime( $hotel_row->subscription_ends_at ) ) : '' ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Hotel Email', 'hrm-pro' ); ?></span>
						<input type="email" name="hotel_email" value="<?php echo esc_attr( $hotel_row->hotel_email ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Phone', 'hrm-pro' ); ?></span>
						<input type="text" name="hotel_phone" value="<?php echo esc_attr( $hotel_row->hotel_phone ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
				</div>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Address', 'hrm-pro' ); ?></span>
					<textarea name="hotel_address" rows="3" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500"><?php echo esc_textarea( $hotel_row->hotel_address ); ?></textarea>
				</label>
			</div>
			<div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
				<button type="button" onclick="closeModal('hrm-edit-hotel-<?php echo esc_attr( $hotel_row->id ); ?>')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"><?php esc_html_e( 'Cancel', 'hrm-pro' ); ?></button>
				<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
					<span class="iconify" data-icon="solar:check-circle-linear"></span>
					<?php esc_html_e( 'Save Changes', 'hrm-pro' ); ?>
				</button>
			</div>
		</form>
	</div>

	<div id="hrm-extend-hotel-<?php echo esc_attr( $hotel_row->id ); ?>" class="fixed left-1/2 top-1/2 z-50 hidden w-full max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-modal">
		<form class="hrm-ajax-form" data-action="hrm_super_extend_subscription">
			<?php wp_nonce_field( 'hrm_nonce', 'nonce' ); ?>
			<input type="hidden" name="hotel_id" value="<?php echo esc_attr( $hotel_row->id ); ?>">
			<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
				<h3 class="text-lg font-semibold text-slate-800"><?php esc_html_e( 'Extend Subscription', 'hrm-pro' ); ?></h3>
				<button type="button" onclick="closeModal('hrm-extend-hotel-<?php echo esc_attr( $hotel_row->id ); ?>')" class="text-slate-400 transition-colors hover:text-slate-600"><span class="iconify text-2xl" data-icon="solar:close-circle-linear"></span></button>
			</div>
			<div class="space-y-4 px-6 py-5">
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'New End Date', 'hrm-pro' ); ?></span>
					<input type="date" name="ends_at" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
			</div>
			<div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
				<button type="button" onclick="closeModal('hrm-extend-hotel-<?php echo esc_attr( $hotel_row->id ); ?>')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"><?php esc_html_e( 'Cancel', 'hrm-pro' ); ?></button>
				<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
					<span class="iconify" data-icon="solar:calendar-add-linear"></span>
					<?php esc_html_e( 'Extend', 'hrm-pro' ); ?>
				</button>
			</div>
		</form>
	</div>
<?php endforeach; ?>
