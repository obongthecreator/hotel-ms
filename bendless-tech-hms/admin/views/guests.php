<?php
/**
 * Admin guest directory view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$guests         = isset( $guests ) && is_array( $guests ) ? $guests : array();
$guest_profiles = isset( $guest_profiles ) && is_array( $guest_profiles ) ? $guest_profiles : array();
$stats          = isset( $stats ) && is_array( $stats ) ? $stats : array();
$search         = isset( $search ) ? (string) $search : '';
$flag           = isset( $flag ) ? (string) $flag : 'all';
$can_flag       = current_user_can( 'manage_options' );
$can_blacklist  = HRM_License::can( 'guest_blacklist' );
?>

<?php if ( ! HRM_License::can( 'guest_management' ) ) : ?>
	<?php HRM_Admin::upgrade_prompt( __( 'Guest management', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'guest_management' ) ); ?>
	<?php return; ?>
<?php endif; ?>

<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
	<div>
		<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Guest Records', 'hrm-pro' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Profiles, stay history, VIP recognition, and blacklist controls.', 'hrm-pro' ); ?></p>
	</div>
	<form method="get" class="flex flex-col gap-2 md:flex-row md:items-center">
		<input type="hidden" name="page" value="hrm-guests">
		<label class="relative">
			<span class="iconify absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" data-icon="solar:magnifer-linear"></span>
			<input type="search" name="hrm_guest_search" value="<?php echo esc_attr( $search ); ?>" class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500 md:w-72" placeholder="<?php esc_attr_e( 'Search guests', 'hrm-pro' ); ?>">
		</label>
		<select name="hrm_guest_flag" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
			<?php foreach ( array( 'all' => __( 'All flags', 'hrm-pro' ), 'none' => __( 'No flag', 'hrm-pro' ), 'vip' => __( 'VIP', 'hrm-pro' ), 'blacklist' => __( 'Blacklist', 'hrm-pro' ) ) as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $flag, $value ); ?> <?php disabled( 'blacklist' === $value && ! $can_blacklist ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
			<span class="iconify" data-icon="solar:filter-linear"></span>
			<?php esc_html_e( 'Filter', 'hrm-pro' ); ?>
		</button>
	</form>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-4">
	<?php
	$stat_cards = array(
		array( 'label' => __( 'Total Guests', 'hrm-pro' ), 'value' => isset( $stats['total'] ) ? $stats['total'] : 0, 'icon' => 'solar:users-group-linear', 'color' => 'bg-primary-50 text-primary-500' ),
		array( 'label' => __( 'VIP Guests', 'hrm-pro' ), 'value' => isset( $stats['vip'] ) ? $stats['vip'] : 0, 'icon' => 'solar:crown-linear', 'color' => 'bg-amber-50 text-amber-600' ),
		array( 'label' => __( 'Blacklisted', 'hrm-pro' ), 'value' => isset( $stats['blacklist'] ) ? $stats['blacklist'] : 0, 'icon' => 'solar:shield-warning-linear', 'color' => 'bg-red-50 text-red-600' ),
		array( 'label' => __( 'Repeat Guests', 'hrm-pro' ), 'value' => isset( $stats['repeat'] ) ? $stats['repeat'] : 0, 'icon' => 'solar:refresh-linear', 'color' => 'bg-emerald-50 text-emerald-600' ),
	);
	?>
	<?php foreach ( $stat_cards as $card ) : ?>
		<div class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-card">
			<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl <?php echo esc_attr( $card['color'] ); ?>">
				<span class="iconify text-2xl" data-icon="<?php echo esc_attr( $card['icon'] ); ?>"></span>
			</div>
			<div>
				<p class="text-sm font-medium text-slate-500"><?php echo esc_html( $card['label'] ); ?></p>
				<p class="mt-0.5 text-2xl font-bold text-slate-900"><?php echo esc_html( number_format_i18n( (int) $card['value'] ) ); ?></p>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-card">
	<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
		<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Guest Directory', 'hrm-pro' ); ?></h3>
		<span class="text-sm text-slate-500"><?php echo esc_html( sprintf( _n( '%d record', '%d records', count( $guests ), 'hrm-pro' ), count( $guests ) ) ); ?></span>
	</div>
	<div class="overflow-x-auto">
		<table class="w-full">
			<thead>
				<tr class="border-b border-slate-100 bg-surface-50">
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Guest', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Flag', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Stays', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Total Spent', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Actions', 'hrm-pro' ); ?></th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-50">
				<?php if ( $guests ) : ?>
					<?php foreach ( $guests as $guest ) : ?>
						<?php
						$guest_flag = sanitize_key( $guest->flag );
						$badge      = 'vip' === $guest_flag ? 'bg-amber-50 text-amber-700' : ( 'blacklist' === $guest_flag ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600' );
						$icon       = 'vip' === $guest_flag ? 'solar:crown-linear' : ( 'blacklist' === $guest_flag ? 'solar:shield-warning-linear' : 'solar:user-id-linear' );
						$profile    = isset( $guest_profiles[ (int) $guest->id ] ) ? $guest_profiles[ (int) $guest->id ] : array();
						?>
						<tr class="transition-colors hover:bg-surface-50">
							<td class="px-6 py-4">
								<p class="text-sm font-semibold text-slate-900"><?php echo esc_html( $guest->full_name ); ?></p>
								<p class="text-xs text-slate-500"><?php echo esc_html( $guest->phone ); ?><?php echo $guest->email ? esc_html( ' - ' . $guest->email ) : ''; ?></p>
							</td>
							<td class="px-6 py-4">
								<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?php echo esc_attr( $badge ); ?>">
									<span class="iconify" data-icon="<?php echo esc_attr( $icon ); ?>"></span>
									<?php echo esc_html( 'none' === $guest_flag ? __( 'No flag', 'hrm-pro' ) : ucwords( $guest_flag ) ); ?>
								</span>
							</td>
							<td class="px-6 py-4">
								<p class="text-sm font-semibold text-slate-900"><?php echo esc_html( number_format_i18n( (int) $guest->bookings_count ) ); ?></p>
								<p class="text-xs text-slate-500"><?php echo esc_html( $guest->last_stay_display ); ?></p>
							</td>
							<td class="px-6 py-4 text-sm font-semibold text-slate-900"><?php echo esc_html( $guest->total_spent_formatted ); ?></td>
							<td class="px-6 py-4">
								<div class="flex justify-end gap-2">
									<button type="button" class="hrm-view-guest inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50" data-profile="<?php echo esc_attr( wp_json_encode( $profile ) ); ?>">
										<span class="iconify" data-icon="solar:eye-linear"></span>
										<?php esc_html_e( 'Profile', 'hrm-pro' ); ?>
									</button>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=hrm-guest-profile&guest_id=' . (int) $guest->id ) ); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
										<span class="iconify" data-icon="solar:download-minimalistic-linear"></span>
										<?php esc_html_e( 'PDF', 'hrm-pro' ); ?>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No guest records match your filters.', 'hrm-pro' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div id="hrm-guest-modal" class="fixed left-1/2 top-1/2 z-50 hidden w-full max-w-3xl -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-modal">
	<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
		<div>
			<h3 class="hrm-guest-profile-name text-lg font-semibold text-slate-800"><?php esc_html_e( 'Guest Profile', 'hrm-pro' ); ?></h3>
			<p class="hrm-guest-profile-contact mt-0.5 text-sm text-slate-500"></p>
		</div>
		<button type="button" onclick="closeModal('hrm-guest-modal')" class="text-slate-400 transition-colors hover:text-slate-600"><span class="iconify text-2xl" data-icon="solar:close-circle-linear"></span></button>
	</div>
	<div class="max-h-[72vh] overflow-y-auto px-6 py-5">
		<div class="grid gap-4 md:grid-cols-3">
			<div class="rounded-xl bg-surface-50 p-4">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Flag', 'hrm-pro' ); ?></p>
				<p class="hrm-guest-profile-flag mt-1 text-sm font-bold text-slate-900"></p>
			</div>
			<div class="rounded-xl bg-surface-50 p-4">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Total Spent', 'hrm-pro' ); ?></p>
				<p class="hrm-guest-profile-spent mt-1 text-sm font-bold text-slate-900"></p>
			</div>
			<div class="rounded-xl bg-surface-50 p-4">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Last Stay', 'hrm-pro' ); ?></p>
				<p class="hrm-guest-profile-last mt-1 text-sm font-bold text-slate-900"></p>
			</div>
		</div>

		<div class="mt-5 grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
			<div class="rounded-2xl border border-slate-100 p-4">
				<h4 class="font-semibold text-slate-800"><?php esc_html_e( 'Guest Notes', 'hrm-pro' ); ?></h4>
				<p class="hrm-guest-profile-id mt-3 text-sm text-slate-600"></p>
				<p class="hrm-guest-profile-reason mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700"></p>
				<p class="hrm-guest-profile-notes mt-3 whitespace-pre-line text-sm leading-6 text-slate-600"></p>

				<?php if ( $can_flag ) : ?>
					<form class="hrm-ajax-form hrm-guest-flag-form mt-5 space-y-4 border-t border-slate-100 pt-5" data-action="hrm_save_guest_flag">
						<?php wp_nonce_field( 'hrm_nonce', 'nonce' ); ?>
						<input type="hidden" name="guest_id" value="">
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Flag', 'hrm-pro' ); ?></span>
							<select name="flag" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
								<option value="none"><?php esc_html_e( 'No flag', 'hrm-pro' ); ?></option>
								<option value="vip"><?php esc_html_e( 'VIP', 'hrm-pro' ); ?></option>
								<option value="blacklist" <?php disabled( ! $can_blacklist ); ?>><?php esc_html_e( 'Blacklist', 'hrm-pro' ); ?></option>
							</select>
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Flag Reason', 'hrm-pro' ); ?></span>
							<textarea name="flag_reason" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Internal Notes', 'hrm-pro' ); ?></span>
							<textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
						</label>
						<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
							<span class="iconify" data-icon="solar:check-circle-linear"></span>
							<?php esc_html_e( 'Save Profile', 'hrm-pro' ); ?>
						</button>
					</form>
				<?php endif; ?>
			</div>

			<div class="rounded-2xl border border-slate-100 p-4">
				<h4 class="font-semibold text-slate-800"><?php esc_html_e( 'Stay History', 'hrm-pro' ); ?></h4>
				<div class="hrm-guest-bookings mt-4 space-y-3"></div>
			</div>
		</div>
	</div>
</div>
