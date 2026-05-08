<?php
/**
 * Hotel settings view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings        = isset( $settings ) && is_array( $settings ) ? $settings : array();
$staff_users     = isset( $staff_users ) && is_array( $staff_users ) ? $staff_users : array();
$available_users = isset( $available_users ) && is_array( $available_users ) ? $available_users : array();
$owner_user      = isset( $owner_user ) ? $owner_user : false;
$staff_roles     = isset( $staff_roles ) && is_array( $staff_roles ) ? $staff_roles : array( 'receptionist' => __( 'Receptionist', 'hrm-pro' ), 'hotel_admin' => __( 'Hotel Admin', 'hrm-pro' ) );
$weekend_days    = isset( $settings['weekend_days'] ) && is_array( $settings['weekend_days'] ) ? array_map( 'sanitize_key', $settings['weekend_days'] ) : array();
$peak_seasons    = isset( $settings['peak_season_dates'] ) && is_array( $settings['peak_season_dates'] ) ? $settings['peak_season_dates'] : array();
$peak_rows       = array_pad( array_slice( $peak_seasons, 0, 3 ), 3, array( 'from' => '', 'to' => '' ) );
$plan            = $hotel ? HRM_License::get_plan( $hotel->plan ) : null;
$staff_limit     = $plan ? (int) $plan['staff_limit'] : 0;
$brand_primary   = isset( $settings['brand_primary_color'] ) && sanitize_hex_color( $settings['brand_primary_color'] ) ? sanitize_hex_color( $settings['brand_primary_color'] ) : '#987CC0';
$brand_button    = isset( $settings['brand_button_color'] ) && sanitize_hex_color( $settings['brand_button_color'] ) ? sanitize_hex_color( $settings['brand_button_color'] ) : '#987CC0';
$brand_text      = isset( $settings['brand_text_color'] ) && sanitize_hex_color( $settings['brand_text_color'] ) ? sanitize_hex_color( $settings['brand_text_color'] ) : '#000000';
$brand_font      = isset( $settings['brand_font_family'] ) ? sanitize_text_field( $settings['brand_font_family'] ) : 'Inter, system-ui, sans-serif';
$bank_name       = isset( $settings['bank_name'] ) ? sanitize_text_field( $settings['bank_name'] ) : '';
$bank_account    = isset( $settings['bank_account_name'] ) ? sanitize_text_field( $settings['bank_account_name'] ) : '';
$bank_number     = isset( $settings['bank_account_number'] ) ? sanitize_text_field( $settings['bank_account_number'] ) : '';
?>

<?php if ( ! $hotel ) : ?>
	<div class="rounded-2xl bg-white p-10 text-center shadow-card">
		<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'No hotel assigned', 'hrm-pro' ); ?></h2>
	</div>
	<?php return; ?>
<?php endif; ?>

<form class="hrm-ajax-form hrm-settings-form space-y-6" data-action="hrm_save_settings">
	<?php wp_nonce_field( 'hrm_nonce', 'nonce' ); ?>

	<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Settings', 'hrm-pro' ); ?></h2>
			<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Hotel profile, pricing rules, notifications, and staff access.', 'hrm-pro' ); ?></p>
		</div>
		<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
			<span class="iconify" data-icon="solar:check-circle-linear"></span>
			<?php esc_html_e( 'Save Settings', 'hrm-pro' ); ?>
		</button>
	</div>

	<div class="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
		<div class="rounded-2xl bg-white shadow-card">
			<div class="border-b border-slate-100 px-6 py-4">
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Hotel Info', 'hrm-pro' ); ?></h3>
			</div>
			<div class="grid gap-4 p-6 md:grid-cols-2">
				<label class="space-y-1.5 md:col-span-2">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Hotel Name', 'hrm-pro' ); ?></span>
					<input type="text" name="hotel_name" required value="<?php echo esc_attr( $hotel->hotel_name ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Email', 'hrm-pro' ); ?></span>
					<input type="email" name="hotel_email" value="<?php echo esc_attr( $hotel->hotel_email ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Phone', 'hrm-pro' ); ?></span>
					<input type="tel" name="hotel_phone" value="<?php echo esc_attr( $hotel->hotel_phone ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5 md:col-span-2">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Address', 'hrm-pro' ); ?></span>
					<textarea name="hotel_address" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"><?php echo esc_textarea( $hotel->hotel_address ); ?></textarea>
				</label>
				<label class="space-y-1.5 md:col-span-2">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Logo URL', 'hrm-pro' ); ?></span>
					<input type="url" name="logo_url" value="<?php echo esc_attr( $hotel->logo_url ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
			</div>
		</div>

		<div class="rounded-2xl bg-white shadow-card">
			<div class="border-b border-slate-100 px-6 py-4">
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'VAT & Currency', 'hrm-pro' ); ?></h3>
			</div>
			<div class="grid gap-4 p-6 md:grid-cols-2">
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'VAT Rate (%)', 'hrm-pro' ); ?></span>
					<input type="number" name="vat_rate" min="0" step="0.01" value="<?php echo esc_attr( $settings['vat_rate'] ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Currency Symbol', 'hrm-pro' ); ?></span>
					<input type="text" name="currency_symbol" value="<?php echo esc_attr( $settings['currency_symbol'] ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
			</div>
		</div>
	</div>

	<div class="rounded-2xl bg-white shadow-card">
		<div class="border-b border-slate-100 px-6 py-4">
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Bank Transfer Details', 'hrm-pro' ); ?></h3>
			<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Shown to guests at checkout when Bank Transfer is selected. Bank transfer is available on plans with multi-payment enabled.', 'hrm-pro' ); ?></p>
		</div>
		<div class="grid gap-4 p-6 md:grid-cols-3">
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Bank Name', 'hrm-pro' ); ?></span>
				<input type="text" name="bank_name" value="<?php echo esc_attr( $bank_name ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'e.g. GTBank', 'hrm-pro' ); ?>">
			</label>
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Account Name', 'hrm-pro' ); ?></span>
				<input type="text" name="bank_account_name" value="<?php echo esc_attr( $bank_account ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'Hotel legal account name', 'hrm-pro' ); ?>">
			</label>
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Account Number', 'hrm-pro' ); ?></span>
				<input type="text" name="bank_account_number" value="<?php echo esc_attr( $bank_number ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( '0000000000', 'hrm-pro' ); ?>">
			</label>
		</div>
	</div>

	<div class="rounded-2xl bg-white shadow-card">
		<div class="border-b border-slate-100 px-6 py-4">
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Frontend Branding', 'hrm-pro' ); ?></h3>
			<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'These colors and fonts control the public rooms widget, booking page, checkout flow, and frontend staff dashboard.', 'hrm-pro' ); ?></p>
		</div>
		<div class="grid gap-4 p-6 md:grid-cols-4">
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Primary Accent', 'hrm-pro' ); ?></span>
				<input type="color" name="brand_primary_color" value="<?php echo esc_attr( $brand_primary ); ?>" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-2 py-1">
			</label>
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Button Color', 'hrm-pro' ); ?></span>
				<input type="color" name="brand_button_color" value="<?php echo esc_attr( $brand_button ); ?>" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-2 py-1">
			</label>
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Text Color', 'hrm-pro' ); ?></span>
				<input type="color" name="brand_text_color" value="<?php echo esc_attr( $brand_text ); ?>" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-2 py-1">
			</label>
			<label class="space-y-1.5">
				<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Font Family', 'hrm-pro' ); ?></span>
				<input type="text" name="brand_font_family" value="<?php echo esc_attr( $brand_font ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
			</label>
		</div>
	</div>

	<div class="grid gap-6 xl:grid-cols-2">
		<div class="rounded-2xl bg-white shadow-card">
			<div class="border-b border-slate-100 px-6 py-4">
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Weekend & Peak Pricing', 'hrm-pro' ); ?></h3>
			</div>
			<div class="space-y-5 p-6">
				<?php if ( HRM_License::can( 'weekend_pricing' ) ) : ?>
					<div>
						<p class="mb-3 text-sm font-medium text-slate-700"><?php esc_html_e( 'Weekend Days', 'hrm-pro' ); ?></p>
						<div class="grid grid-cols-2 gap-2 md:grid-cols-4">
							<?php foreach ( array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) as $day ) : ?>
								<label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
									<input type="checkbox" name="weekend_days[]" value="<?php echo esc_attr( $day ); ?>" <?php checked( in_array( $day, $weekend_days, true ) ); ?> class="h-4 w-4 rounded border-slate-300 text-primary-500 focus:ring-primary-500">
									<?php echo esc_html( ucfirst( $day ) ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				<?php else : ?>
					<?php HRM_Admin::upgrade_prompt( __( 'Weekend pricing', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'weekend_pricing' ) ); ?>
				<?php endif; ?>

				<?php if ( HRM_License::can( 'peak_season_pricing' ) ) : ?>
					<div class="space-y-3">
						<p class="text-sm font-medium text-slate-700"><?php esc_html_e( 'Peak Season Windows', 'hrm-pro' ); ?></p>
						<?php foreach ( $peak_rows as $index => $season ) : ?>
							<div class="grid gap-3 md:grid-cols-2">
								<label class="space-y-1.5">
									<span class="block text-xs font-semibold uppercase tracking-wide text-slate-400"><?php echo esc_html( sprintf( __( 'Season %d From', 'hrm-pro' ), $index + 1 ) ); ?></span>
									<input type="date" name="peak_from[]" value="<?php echo esc_attr( isset( $season['from'] ) ? $season['from'] : '' ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
								</label>
								<label class="space-y-1.5">
									<span class="block text-xs font-semibold uppercase tracking-wide text-slate-400"><?php echo esc_html( sprintf( __( 'Season %d To', 'hrm-pro' ), $index + 1 ) ); ?></span>
									<input type="date" name="peak_to[]" value="<?php echo esc_attr( isset( $season['to'] ) ? $season['to'] : '' ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
								</label>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<?php HRM_Admin::upgrade_prompt( __( 'Peak season pricing', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'peak_season_pricing' ) ); ?>
				<?php endif; ?>
			</div>
		</div>

		<div class="rounded-2xl bg-white shadow-card">
			<div class="border-b border-slate-100 px-6 py-4">
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'WhatsApp Configuration', 'hrm-pro' ); ?></h3>
			</div>
			<div class="space-y-4 p-6">
				<?php if ( HRM_License::can( 'whatsapp_notifications' ) ) : ?>
					<label class="flex items-center justify-between gap-4 rounded-xl bg-surface-50 p-4">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Enable WhatsApp', 'hrm-pro' ); ?></span>
						<select name="whatsapp_enabled" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
							<option value="no" <?php selected( $settings['whatsapp_enabled'], 'no' ); ?>><?php esc_html_e( 'Off', 'hrm-pro' ); ?></option>
							<option value="yes" <?php selected( $settings['whatsapp_enabled'], 'yes' ); ?>><?php esc_html_e( 'On', 'hrm-pro' ); ?></option>
						</select>
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Server URL', 'hrm-pro' ); ?></span>
						<input type="url" name="whatsapp_server_url" value="<?php echo esc_attr( $settings['whatsapp_server_url'] ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'API Key', 'hrm-pro' ); ?></span>
						<input type="password" name="whatsapp_api_key" value="<?php echo esc_attr( $settings['whatsapp_api_key'] ); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<div class="flex gap-2">
						<input type="tel" class="hrm-whatsapp-test-phone min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'Test phone number', 'hrm-pro' ); ?>">
						<button type="button" class="hrm-test-whatsapp inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
							<span class="iconify" data-icon="solar:chat-round-linear"></span>
							<?php esc_html_e( 'Test', 'hrm-pro' ); ?>
						</button>
					</div>
				<?php else : ?>
					<?php HRM_Admin::upgrade_prompt( __( 'WhatsApp notifications', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'whatsapp_notifications' ) ); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="rounded-2xl bg-white shadow-card">
		<div class="border-b border-slate-100 px-6 py-4">
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Staff Roles', 'hrm-pro' ); ?></h3>
		</div>
		<div class="grid gap-6 p-6 xl:grid-cols-[0.8fr_1.2fr]">
			<div class="space-y-4">
				<div class="rounded-xl bg-surface-50 p-4">
					<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Hotel Admin', 'hrm-pro' ); ?></p>
					<p class="mt-1 text-sm font-bold text-slate-900"><?php echo esc_html( $owner_user ? $owner_user->display_name : __( 'Owner user missing', 'hrm-pro' ) ); ?></p>
					<p class="text-xs text-slate-500"><?php echo esc_html( $owner_user ? $owner_user->user_email : '' ); ?></p>
				</div>
				<div class="rounded-xl border border-slate-100 bg-white p-4">
					<h4 class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Create New Staff Login', 'hrm-pro' ); ?></h4>
					<div class="mt-4 grid gap-3">
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Full Name', 'hrm-pro' ); ?></span>
							<input type="text" name="new_staff_name" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'e.g. Ada Okafor', 'hrm-pro' ); ?>">
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Email Address', 'hrm-pro' ); ?></span>
							<input type="email" name="new_staff_email" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'staff@example.com', 'hrm-pro' ); ?>">
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Temporary Password', 'hrm-pro' ); ?></span>
							<input type="text" name="new_staff_password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'Leave blank to email a generated password', 'hrm-pro' ); ?>">
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Access Level', 'hrm-pro' ); ?></span>
							<select name="new_staff_role" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
								<?php foreach ( $staff_roles as $role_key => $role_label ) : ?>
									<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					</div>
				</div>
				<div class="grid gap-3 md:grid-cols-[1fr_160px_auto]">
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Assign Existing User', 'hrm-pro' ); ?></span>
						<select name="staff_user_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
							<option value="0"><?php esc_html_e( 'Select WordPress user', 'hrm-pro' ); ?></option>
							<?php foreach ( $available_users as $user_option ) : ?>
								<option value="<?php echo esc_attr( $user_option->ID ); ?>"><?php echo esc_html( $user_option->display_name . ' - ' . $user_option->user_email ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Access', 'hrm-pro' ); ?></span>
						<select name="staff_user_role" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
							<?php foreach ( $staff_roles as $role_key => $role_label ) : ?>
								<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="flex items-end">
						<span class="rounded-lg bg-surface-50 px-3 py-2.5 text-sm font-semibold text-slate-600"><?php echo esc_html( $staff_limit < 0 ? __( 'Unlimited staff', 'hrm-pro' ) : sprintf( __( '%d staff limit', 'hrm-pro' ), $staff_limit ) ); ?></span>
					</div>
				</div>
			</div>
			<div class="space-y-3">
				<?php if ( $staff_users ) : ?>
					<?php foreach ( $staff_users as $staff ) : ?>
						<div class="rounded-xl border border-slate-100 bg-surface-50 p-4">
							<div class="grid gap-3 md:grid-cols-[1fr_1fr_160px_auto]">
								<label class="space-y-1.5">
									<span class="block text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Name', 'hrm-pro' ); ?></span>
									<input type="text" name="staff_profiles[<?php echo esc_attr( $staff['id'] ); ?>][display_name]" value="<?php echo esc_attr( $staff['name'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
								</label>
								<label class="space-y-1.5">
									<span class="block text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Email', 'hrm-pro' ); ?></span>
									<input type="email" name="staff_profiles[<?php echo esc_attr( $staff['id'] ); ?>][email]" value="<?php echo esc_attr( $staff['email'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
								</label>
								<label class="space-y-1.5">
									<span class="block text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Access', 'hrm-pro' ); ?></span>
									<select name="staff_profiles[<?php echo esc_attr( $staff['id'] ); ?>][role]" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
										<?php foreach ( $staff_roles as $role_key => $role_label ) : ?>
											<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $staff['role'], $role_key ); ?>><?php echo esc_html( $role_label ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label class="flex items-end gap-2 pb-2 text-xs font-semibold text-red-600">
									<input type="checkbox" name="remove_staff_ids[]" value="<?php echo esc_attr( $staff['id'] ); ?>" class="h-4 w-4 rounded border-slate-300 text-red-500 focus:ring-red-500">
									<?php esc_html_e( 'Remove', 'hrm-pro' ); ?>
								</label>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="rounded-xl bg-surface-50 px-4 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No receptionist users assigned yet.', 'hrm-pro' ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</form>
