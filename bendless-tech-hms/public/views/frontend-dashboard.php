<?php
/**
 * Frontend staff dashboard view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config_json        = wp_json_encode( $config );
$brand_primary      = ! empty( $config['brandPrimary'] ) ? sanitize_hex_color( $config['brandPrimary'] ) : '';
$brand_button       = ! empty( $config['brandButton'] ) ? sanitize_hex_color( $config['brandButton'] ) : '';
$brand_text         = ! empty( $config['brandText'] ) ? sanitize_hex_color( $config['brandText'] ) : '';
$brand_font         = ! empty( $config['brandFont'] ) ? sanitize_text_field( $config['brandFont'] ) : 'Inter, system-ui, sans-serif';
$brand_primary      = $brand_primary ? $brand_primary : '#987CC0';
$brand_button       = $brand_button ? $brand_button : '#987CC0';
$brand_text         = $brand_text ? $brand_text : '#000000';
$brand_style        = '--hrm-brand-primary:' . esc_attr( $brand_primary ) . ';--hrm-brand-button:' . esc_attr( $brand_button ) . ';--hrm-brand-text:' . esc_attr( $brand_text ) . ';--hrm-brand-font:' . esc_attr( $brand_font ) . ';';
$currency_symbol    = isset( $config['currencySymbol'] ) ? (string) $config['currencySymbol'] : '₦';
$can_admin          = ! empty( $config['canAdmin'] );
$rooms              = isset( $rooms ) && is_array( $rooms ) ? $rooms : array();
$bookings           = isset( $bookings ) && is_array( $bookings ) ? $bookings : array();
$stats              = isset( $stats ) && is_array( $stats ) ? $stats : array();
$expiry_context     = isset( $expiry_context ) && is_array( $expiry_context ) ? $expiry_context : array();
$dashboard_settings = isset( $dashboard_settings ) && is_array( $dashboard_settings ) ? $dashboard_settings : array();
$page_links         = isset( $page_links ) && is_array( $page_links ) ? $page_links : array();
$staff_users        = isset( $staff_users ) && is_array( $staff_users ) ? $staff_users : array();
$available_users    = isset( $available_users ) && is_array( $available_users ) ? $available_users : array();
$owner_user         = isset( $owner_user ) ? $owner_user : false;
$staff_roles        = isset( $staff_roles ) && is_array( $staff_roles ) ? $staff_roles : array( 'receptionist' => __( 'Receptionist', 'hrm-pro' ), 'hotel_admin' => __( 'Hotel Admin', 'hrm-pro' ) );
$staff_limit        = isset( $staff_limit ) ? (int) $staff_limit : 0;
$room_statuses      = array(
	'available'   => __( 'Available', 'hrm-pro' ),
	'occupied'    => __( 'Occupied', 'hrm-pro' ),
	'cleaning'    => __( 'Cleaning', 'hrm-pro' ),
	'maintenance' => __( 'Maintenance', 'hrm-pro' ),
);
$booking_statuses   = array(
	'pending'     => __( 'Pending', 'hrm-pro' ),
	'confirmed'   => __( 'Confirmed', 'hrm-pro' ),
	'checked_in'  => __( 'Checked In', 'hrm-pro' ),
	'checked_out' => __( 'Checked Out', 'hrm-pro' ),
	'cancelled'   => __( 'Cancelled', 'hrm-pro' ),
	'no_show'     => __( 'No Show', 'hrm-pro' ),
);
?>

<div id="hrm-frontend-dashboard-<?php echo esc_attr( $hotel_id ); ?>" class="hrm-frontend-dashboard font-sans text-black" data-config="<?php echo esc_attr( $config_json ); ?>" style="<?php echo esc_attr( $brand_style ); ?>">
	<?php if ( ! $hotel ) : ?>
		<section class="hrm-dashboard-card p-8 text-center">
			<h2 class="text-2xl font-bold text-black"><?php esc_html_e( 'No hotel assigned', 'hrm-pro' ); ?></h2>
			<p class="mx-auto mt-2 max-w-xl text-sm text-slate-600"><?php esc_html_e( 'Ask the platform administrator to assign this user to a hotel before using the frontend dashboard.', 'hrm-pro' ); ?></p>
		</section>
		<?php return; ?>
	<?php endif; ?>

	<header class="rounded-2xl border border-black/10 bg-white p-6 shadow-card">
		<div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
			<div class="flex items-center gap-4">
				<?php if ( $hotel->logo_url ) : ?>
					<img src="<?php echo esc_url( $hotel->logo_url ); ?>" alt="<?php echo esc_attr( $hotel->hotel_name ); ?>" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-black/10">
				<?php else : ?>
					<div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-lg font-bold text-primary-700 ring-1 ring-primary-500/20">
						<?php echo esc_html( strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/', '', $hotel->hotel_name ), 0, 2 ) ) ); ?>
					</div>
				<?php endif; ?>
				<div>
					<p class="text-xs font-semibold uppercase tracking-wide text-primary-600"><?php esc_html_e( 'Hotel Staff Dashboard', 'hrm-pro' ); ?></p>
					<h1 class="mt-1 text-3xl font-bold text-black"><?php echo esc_html( $hotel->hotel_name ); ?></h1>
					<p class="mt-1 text-sm text-slate-600">
						<?php echo esc_html( sprintf( __( '%1$s plan - %2$s', 'hrm-pro' ), isset( $expiry_context['plan_name'] ) ? $expiry_context['plan_name'] : HRM_License::plan_name( $hotel->plan ), isset( $expiry_context['expiry_display'] ) ? $expiry_context['expiry_display'] : __( 'No expiry date', 'hrm-pro' ) ) ); ?>
					</p>
				</div>
			</div>
			<div class="flex flex-wrap gap-2">
				<?php if ( ! empty( $page_links['booking'] ) ) : ?>
					<a href="<?php echo esc_url( $page_links['booking'] ); ?>" class="hrm-brand-button">
						<span class="iconify" data-icon="solar:calendar-mark-linear"></span>
						<?php esc_html_e( 'Open Booking Page', 'hrm-pro' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( $can_admin ) : ?>
					<?php if ( ! empty( $page_links['analytics'] ) ) : ?>
						<a href="<?php echo esc_url( $page_links['analytics'] ); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-black/10 bg-white px-4 py-3 text-sm font-bold text-black transition-colors hover:bg-surface-100">
							<span class="iconify" data-icon="solar:chart-square-linear"></span>
							<?php esc_html_e( 'Analytics', 'hrm-pro' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( ! empty( $page_links['room_analytics'] ) ) : ?>
						<a href="<?php echo esc_url( $page_links['room_analytics'] ); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-black/10 bg-white px-4 py-3 text-sm font-bold text-black transition-colors hover:bg-surface-100">
							<span class="iconify" data-icon="solar:buildings-2-linear"></span>
							<?php esc_html_e( 'Room Analytics', 'hrm-pro' ); ?>
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hrm-settings' ) ); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-black/10 bg-white px-4 py-3 text-sm font-bold text-black transition-colors hover:bg-surface-100">
						<span class="iconify" data-icon="solar:settings-linear"></span>
						<?php esc_html_e( 'Backend Settings', 'hrm-pro' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
		<div class="hrm-dashboard-card p-5">
			<div class="flex items-center gap-3">
				<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-500"><span class="iconify text-2xl" data-icon="solar:buildings-2-linear"></span></span>
				<div>
					<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Rooms', 'hrm-pro' ); ?></p>
					<p class="text-2xl font-bold text-black"><?php echo esc_html( number_format_i18n( isset( $stats['total_rooms'] ) ? (int) $stats['total_rooms'] : 0 ) ); ?></p>
				</div>
			</div>
		</div>
		<div class="hrm-dashboard-card p-5">
			<div class="flex items-center gap-3">
				<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-500"><span class="iconify text-2xl" data-icon="solar:calendar-mark-linear"></span></span>
				<div>
					<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Active Bookings', 'hrm-pro' ); ?></p>
					<p class="text-2xl font-bold text-black"><?php echo esc_html( number_format_i18n( isset( $stats['active_bookings'] ) ? (int) $stats['active_bookings'] : 0 ) ); ?></p>
				</div>
			</div>
		</div>
		<div class="hrm-dashboard-card p-5">
			<div class="flex items-center gap-3">
				<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-500"><span class="iconify text-2xl" data-icon="solar:wallet-money-linear"></span></span>
				<div>
					<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Revenue', 'hrm-pro' ); ?></p>
					<p class="text-2xl font-bold text-black"><?php echo esc_html( $currency_symbol . number_format( isset( $stats['paid_revenue'] ) ? (float) $stats['paid_revenue'] : 0, 2 ) ); ?></p>
				</div>
			</div>
		</div>
		<div class="hrm-dashboard-card p-5">
			<div class="flex items-center gap-3">
				<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-500"><span class="iconify text-2xl" data-icon="solar:bell-linear"></span></span>
				<div>
					<p class="text-sm font-medium text-slate-500"><?php esc_html_e( 'Outstanding', 'hrm-pro' ); ?></p>
					<p class="text-2xl font-bold text-black"><?php echo esc_html( $currency_symbol . number_format( isset( $stats['outstanding'] ) ? (float) $stats['outstanding'] : 0, 2 ) ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<section class="mt-6 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
		<div class="hrm-dashboard-card overflow-hidden">
			<div class="flex items-center justify-between gap-4 border-b border-black/10 px-6 py-4">
				<h2 class="font-bold text-black"><?php esc_html_e( 'Rooms', 'hrm-pro' ); ?></h2>
				<span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-600"><?php echo esc_html( $can_admin ? __( 'Admin controls enabled', 'hrm-pro' ) : __( 'Staff controls', 'hrm-pro' ) ); ?></span>
			</div>
			<div class="space-y-3 border-b border-black/10 px-6 py-4">
				<div class="flex flex-wrap gap-2" data-hrm-front-room-filter-group="type">
					<?php foreach ( array( 'all' => __( 'All Types', 'hrm-pro' ), 'single' => __( 'Single', 'hrm-pro' ), 'double' => __( 'Double', 'hrm-pro' ), 'suite' => __( 'Suite', 'hrm-pro' ), 'deluxe' => __( 'Deluxe', 'hrm-pro' ), 'executive' => __( 'Executive', 'hrm-pro' ) ) as $type_key => $type_label ) : ?>
						<button type="button" class="hrm-front-room-filter rounded-lg border px-3 py-2 text-sm font-bold transition-colors <?php echo 'all' === $type_key ? 'border-primary-500 bg-primary-500 text-white' : 'border-slate-200 bg-white text-black hover:bg-primary-50'; ?>" data-filter-kind="type" data-filter-value="<?php echo esc_attr( $type_key ); ?>"><?php echo esc_html( $type_label ); ?></button>
					<?php endforeach; ?>
				</div>
				<div class="flex flex-wrap gap-2" data-hrm-front-room-filter-group="status">
					<button type="button" class="hrm-front-room-filter rounded-lg border border-primary-500 bg-primary-500 px-3 py-2 text-sm font-bold text-white transition-colors" data-filter-kind="status" data-filter-value="all"><?php esc_html_e( 'All Conditions', 'hrm-pro' ); ?></button>
					<?php foreach ( $room_statuses as $status_key => $status_label ) : ?>
						<button type="button" class="hrm-front-room-filter rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black transition-colors hover:bg-primary-50" data-filter-kind="status" data-filter-value="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="divide-y divide-black/5">
				<?php if ( $rooms ) : ?>
					<?php foreach ( $rooms as $room ) : ?>
						<?php
						$room_data = array(
							'id'              => (int) $room->id,
							'room_number'     => (string) $room->room_number,
							'room_type'       => (string) $room->room_type,
							'floor'           => (int) $room->floor,
							'price_per_night' => (float) $room->price_per_night,
							'weekend_rate'    => null === $room->weekend_rate ? '' : (float) $room->weekend_rate,
							'peak_rate'       => null === $room->peak_rate ? '' : (float) $room->peak_rate,
							'max_guests'      => (int) $room->max_guests,
							'status'          => (string) $room->status,
							'description'     => (string) $room->description,
							'amenities'       => (string) $room->amenities,
							'image_urls'      => (string) $room->image_urls,
						);
						?>
						<article class="hrm-front-filterable-room grid gap-4 px-6 py-4 lg:grid-cols-[1fr_auto]" data-room="<?php echo esc_attr( wp_json_encode( $room_data ) ); ?>" data-room-type="<?php echo esc_attr( $room->room_type ); ?>" data-room-status="<?php echo esc_attr( $room->status ); ?>">
							<div>
								<p class="text-base font-bold text-black"><?php echo esc_html( sprintf( __( 'Room %s', 'hrm-pro' ), $room->room_number ) ); ?></p>
								<p class="mt-1 text-sm text-slate-600"><?php echo esc_html( ucwords( str_replace( '_', ' ', $room->room_type ) ) . ' - ' . sprintf( __( 'Floor %1$d - %2$d guests', 'hrm-pro' ), (int) $room->floor, (int) $room->max_guests ) ); ?></p>
								<p class="mt-1 text-sm font-semibold text-black"><?php echo esc_html( $currency_symbol . number_format( (float) $room->price_per_night, 2 ) ); ?></p>
							</div>
							<div class="flex flex-wrap items-center gap-2">
								<select class="hrm-front-room-status rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500" data-room-id="<?php echo esc_attr( $room->id ); ?>">
									<?php foreach ( $room_statuses as $status_key => $status_label ) : ?>
										<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $room->status, $status_key ); ?>><?php echo esc_html( $status_label ); ?></option>
									<?php endforeach; ?>
								</select>
								<?php if ( $can_admin ) : ?>
									<input type="number" min="0" step="0.01" class="hrm-front-room-price w-32 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500" value="<?php echo esc_attr( $room->price_per_night ); ?>" aria-label="<?php esc_attr_e( 'Room price', 'hrm-pro' ); ?>">
									<button type="button" class="hrm-front-save-room-price inline-flex items-center gap-2 rounded-lg bg-primary-500 px-3 py-2 text-sm font-bold text-white hover:bg-primary-600" data-room-id="<?php echo esc_attr( $room->id ); ?>">
										<span class="iconify" data-icon="solar:check-circle-linear"></span>
										<?php esc_html_e( 'Save', 'hrm-pro' ); ?>
									</button>
									<button type="button" class="hrm-front-delete-room inline-flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-100" data-room-id="<?php echo esc_attr( $room->id ); ?>">
										<span class="iconify" data-icon="solar:trash-bin-minimalistic-linear"></span>
										<?php esc_html_e( 'Delete', 'hrm-pro' ); ?>
									</button>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No rooms have been added yet.', 'hrm-pro' ); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<div class="space-y-6">
			<div class="hrm-dashboard-card p-6">
				<h2 class="font-bold text-black"><?php esc_html_e( 'Subscription', 'hrm-pro' ); ?></h2>
				<div class="mt-4 rounded-xl bg-primary-50 p-4">
					<p class="text-xs font-semibold uppercase tracking-wide text-primary-600"><?php echo esc_html( isset( $expiry_context['status'] ) ? $expiry_context['status'] : ucfirst( $hotel->subscription_status ) ); ?></p>
					<p class="mt-1 text-lg font-bold text-black"><?php echo esc_html( isset( $expiry_context['plan_name'] ) ? $expiry_context['plan_name'] : HRM_License::plan_name( $hotel->plan ) ); ?></p>
					<p class="mt-1 text-sm text-slate-600"><?php echo esc_html( isset( $expiry_context['expiry_display'] ) ? $expiry_context['expiry_display'] : __( 'No expiry date', 'hrm-pro' ) ); ?></p>
				</div>
				<div class="mt-4 space-y-2 text-sm text-slate-600">
					<p><strong class="text-black"><?php esc_html_e( 'Rooms shortcode:', 'hrm-pro' ); ?></strong> <code>[hrm_rooms_widget hotel_id="<?php echo esc_html( (string) $hotel_id ); ?>"]</code></p>
					<p><strong class="text-black"><?php esc_html_e( 'Booking shortcode:', 'hrm-pro' ); ?></strong> <code>[hrm_booking_page hotel_id="<?php echo esc_html( (string) $hotel_id ); ?>"]</code></p>
					<p><strong class="text-black"><?php esc_html_e( 'Room analytics shortcode:', 'hrm-pro' ); ?></strong> <code>[hrm_room_analytics]</code></p>
				</div>
			</div>

			<?php if ( $can_admin ) : ?>
				<form class="hrm-dashboard-card hrm-frontend-settings-form p-6">
					<h2 class="font-bold text-black"><?php esc_html_e( 'Frontend Branding', 'hrm-pro' ); ?></h2>
					<input type="hidden" name="hotel_name" value="<?php echo esc_attr( $hotel->hotel_name ); ?>">
					<input type="hidden" name="hotel_email" value="<?php echo esc_attr( $hotel->hotel_email ); ?>">
					<input type="hidden" name="hotel_phone" value="<?php echo esc_attr( $hotel->hotel_phone ); ?>">
					<input type="hidden" name="hotel_address" value="<?php echo esc_attr( $hotel->hotel_address ); ?>">
					<input type="hidden" name="logo_url" value="<?php echo esc_attr( $hotel->logo_url ); ?>">
					<div class="mt-4 grid gap-4 sm:grid-cols-2">
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Primary Color', 'hrm-pro' ); ?></span>
							<input type="color" name="brand_primary_color" value="<?php echo esc_attr( $dashboard_settings['brand_primary_color'] ); ?>" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-2 py-1">
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Button Color', 'hrm-pro' ); ?></span>
							<input type="color" name="brand_button_color" value="<?php echo esc_attr( $dashboard_settings['brand_button_color'] ); ?>" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-2 py-1">
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Text Color', 'hrm-pro' ); ?></span>
							<input type="color" name="brand_text_color" value="<?php echo esc_attr( $dashboard_settings['brand_text_color'] ); ?>" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-2 py-1">
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Font Stack', 'hrm-pro' ); ?></span>
							<input type="text" name="brand_font_family" value="<?php echo esc_attr( $dashboard_settings['brand_font_family'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'VAT Rate', 'hrm-pro' ); ?></span>
							<input type="number" name="vat_rate" min="0" step="0.01" value="<?php echo esc_attr( $dashboard_settings['vat_rate'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
						</label>
						<label class="space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Currency Symbol', 'hrm-pro' ); ?></span>
							<input type="text" name="currency_symbol" value="<?php echo esc_attr( $dashboard_settings['currency_symbol'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
						</label>
					</div>
					<div class="mt-6 rounded-xl border border-black/10 bg-white p-4">
						<h3 class="font-bold text-black"><?php esc_html_e( 'Bank Transfer Details', 'hrm-pro' ); ?></h3>
						<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'These details appear on checkout when guests choose bank transfer.', 'hrm-pro' ); ?></p>
						<div class="mt-4 grid gap-4 md:grid-cols-3">
							<label class="space-y-1.5">
								<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Bank Name', 'hrm-pro' ); ?></span>
								<input type="text" name="bank_name" value="<?php echo esc_attr( $dashboard_settings['bank_name'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
							</label>
							<label class="space-y-1.5">
								<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Account Name', 'hrm-pro' ); ?></span>
								<input type="text" name="bank_account_name" value="<?php echo esc_attr( $dashboard_settings['bank_account_name'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
							</label>
							<label class="space-y-1.5">
								<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Account Number', 'hrm-pro' ); ?></span>
								<input type="text" name="bank_account_number" value="<?php echo esc_attr( $dashboard_settings['bank_account_number'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
							</label>
						</div>
					</div>
					<div class="mt-6 rounded-xl border border-black/10 bg-white p-4">
						<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
							<div>
								<h3 class="font-bold text-black"><?php esc_html_e( 'Staff Access', 'hrm-pro' ); ?></h3>
								<p class="text-sm text-slate-500"><?php esc_html_e( 'Create staff logins, assign existing users, edit access, or remove staff from this hotel.', 'hrm-pro' ); ?></p>
							</div>
							<span class="rounded-lg bg-primary-50 px-3 py-2 text-xs font-bold text-primary-600"><?php echo esc_html( $staff_limit < 0 ? __( 'Unlimited staff', 'hrm-pro' ) : sprintf( __( '%d staff limit', 'hrm-pro' ), $staff_limit ) ); ?></span>
						</div>
						<div class="mt-4 rounded-xl bg-surface-100 p-4">
							<p class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Owner Admin', 'hrm-pro' ); ?></p>
							<p class="mt-1 text-sm font-bold text-black"><?php echo esc_html( $owner_user ? $owner_user->display_name : __( 'Owner user missing', 'hrm-pro' ) ); ?></p>
							<p class="text-xs text-slate-500"><?php echo esc_html( $owner_user ? $owner_user->user_email : '' ); ?></p>
						</div>
						<div class="mt-4 grid gap-3 lg:grid-cols-[1fr_1fr_1fr_150px]">
							<label class="space-y-1.5">
								<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'New Staff Name', 'hrm-pro' ); ?></span>
								<input type="text" name="new_staff_name" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'Full name', 'hrm-pro' ); ?>">
							</label>
							<label class="space-y-1.5">
								<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'New Staff Email', 'hrm-pro' ); ?></span>
								<input type="email" name="new_staff_email" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'staff@example.com', 'hrm-pro' ); ?>">
							</label>
							<label class="space-y-1.5">
								<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Password', 'hrm-pro' ); ?></span>
								<input type="text" name="new_staff_password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'Auto-generate', 'hrm-pro' ); ?>">
							</label>
							<label class="space-y-1.5">
								<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Access', 'hrm-pro' ); ?></span>
								<select name="new_staff_role" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
									<?php foreach ( $staff_roles as $role_key => $role_label ) : ?>
										<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>
						<div class="mt-4 grid gap-3 md:grid-cols-[1fr_160px]">
							<label class="space-y-1.5">
								<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Assign Existing WordPress User', 'hrm-pro' ); ?></span>
								<select name="staff_user_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
									<option value="0"><?php esc_html_e( 'Select user', 'hrm-pro' ); ?></option>
									<?php foreach ( $available_users as $user_option ) : ?>
										<option value="<?php echo esc_attr( $user_option->ID ); ?>"><?php echo esc_html( $user_option->display_name . ' - ' . $user_option->user_email ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="space-y-1.5">
								<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Access', 'hrm-pro' ); ?></span>
								<select name="staff_user_role" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
									<?php foreach ( $staff_roles as $role_key => $role_label ) : ?>
										<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>
						<div class="mt-4 space-y-3">
							<?php if ( $staff_users ) : ?>
								<?php foreach ( $staff_users as $staff ) : ?>
									<div class="rounded-xl bg-surface-100 p-3">
										<div class="grid gap-3 lg:grid-cols-[1fr_1fr_150px_auto]">
											<label class="space-y-1.5">
												<span class="block text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Name', 'hrm-pro' ); ?></span>
												<input type="text" name="staff_profiles[<?php echo esc_attr( $staff['id'] ); ?>][display_name]" value="<?php echo esc_attr( $staff['name'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
											</label>
											<label class="space-y-1.5">
												<span class="block text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Email', 'hrm-pro' ); ?></span>
												<input type="email" name="staff_profiles[<?php echo esc_attr( $staff['id'] ); ?>][email]" value="<?php echo esc_attr( $staff['email'] ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
											</label>
											<label class="space-y-1.5">
												<span class="block text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Access', 'hrm-pro' ); ?></span>
												<select name="staff_profiles[<?php echo esc_attr( $staff['id'] ); ?>][role]" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
													<?php foreach ( $staff_roles as $role_key => $role_label ) : ?>
														<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $staff['role'], $role_key ); ?>><?php echo esc_html( $role_label ); ?></option>
													<?php endforeach; ?>
												</select>
											</label>
											<label class="flex items-end gap-2 pb-2 text-xs font-bold text-red-600">
												<input type="checkbox" name="remove_staff_ids[]" value="<?php echo esc_attr( $staff['id'] ); ?>" class="h-4 w-4 rounded border-slate-300 text-red-500 focus:ring-red-500">
												<?php esc_html_e( 'Remove', 'hrm-pro' ); ?>
											</label>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<div class="rounded-xl bg-surface-100 px-4 py-6 text-center text-sm text-slate-500"><?php esc_html_e( 'No staff users assigned yet.', 'hrm-pro' ); ?></div>
							<?php endif; ?>
						</div>
					</div>
					<button type="submit" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-3 text-sm font-bold text-white hover:bg-primary-600">
						<span class="iconify" data-icon="solar:check-circle-linear"></span>
						<?php esc_html_e( 'Save Settings & Staff', 'hrm-pro' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>
	</section>

	<section class="hrm-dashboard-card mt-6 overflow-hidden">
		<div class="flex items-center justify-between gap-4 border-b border-black/10 px-6 py-4">
			<h2 class="font-bold text-black"><?php esc_html_e( 'Bookings', 'hrm-pro' ); ?></h2>
			<span class="text-sm text-slate-500"><?php esc_html_e( 'Latest 200 records', 'hrm-pro' ); ?></span>
		</div>
		<div class="overflow-x-auto">
			<table class="hrm-dashboard-table">
				<thead>
					<tr class="bg-surface-100 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
						<th class="px-6 py-3"><?php esc_html_e( 'Guest', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Dates', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Payment', 'hrm-pro' ); ?></th>
						<th class="px-6 py-3"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-black/5">
					<?php if ( $bookings ) : ?>
						<?php foreach ( $bookings as $booking ) : ?>
							<?php
							$booking_data = array(
								'booking_id'     => (int) $booking->id,
								'check_in'       => (string) $booking->check_in,
								'check_out'      => (string) $booking->check_out,
								'guest_name'     => (string) $booking->guest_name,
								'guest_phone'    => (string) $booking->guest_phone,
								'guest_email'    => (string) $booking->guest_email,
								'id_type'        => isset( $booking->guest_id_type ) ? (string) $booking->guest_id_type : '',
								'id_number'      => isset( $booking->guest_id_number ) ? (string) $booking->guest_id_number : '',
								'payment_method' => (string) $booking->payment_method,
								'amount_paid'    => (float) $booking->amount_paid,
								'transfer_ref'   => (string) $booking->transfer_ref,
								'status'         => (string) $booking->status,
								'notes'          => (string) $booking->notes,
							);
							?>
							<tr>
								<td class="px-6 py-4 text-sm">
									<p class="font-bold text-black"><?php echo esc_html( $booking->guest_name ); ?></p>
									<p class="text-xs text-slate-500"><?php echo esc_html( $booking->guest_phone ); ?></p>
								</td>
								<td class="px-6 py-4 text-sm text-slate-700">
									<p><?php echo esc_html( sprintf( __( 'Room %s', 'hrm-pro' ), $booking->room_number ) ); ?></p>
									<?php if ( $can_admin ) : ?>
										<div class="mt-2 flex flex-wrap gap-2" data-booking="<?php echo esc_attr( wp_json_encode( $booking_data ) ); ?>">
											<select class="hrm-front-switch-room-select rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-black focus:outline-none focus:ring-2 focus:ring-primary-500">
												<?php foreach ( $rooms as $switch_room ) : ?>
													<option value="<?php echo esc_attr( $switch_room->id ); ?>" <?php selected( $switch_room->id, $booking->room_id ); ?>><?php echo esc_html( $switch_room->room_number . ' - ' . ucwords( str_replace( '_', ' ', $switch_room->room_type ) ) ); ?></option>
												<?php endforeach; ?>
											</select>
											<button type="button" class="hrm-front-switch-booking-room rounded-lg bg-primary-500 px-3 py-2 text-xs font-bold text-white hover:bg-primary-600"><?php esc_html_e( 'Switch', 'hrm-pro' ); ?></button>
										</div>
									<?php endif; ?>
								</td>
								<td class="px-6 py-4 text-sm text-slate-700"><?php echo esc_html( $booking->check_in . ' - ' . $booking->check_out ); ?></td>
								<td class="px-6 py-4 text-sm">
									<p class="font-bold text-black"><?php echo esc_html( $currency_symbol . number_format( (float) $booking->total_amount, 2 ) ); ?></p>
									<p class="text-xs text-slate-500"><?php echo esc_html( ucwords( str_replace( '_', ' ', $booking->payment_status ) ) ); ?></p>
								</td>
								<td class="px-6 py-4 text-sm">
									<select class="hrm-front-booking-status rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary-500" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">
										<?php foreach ( $booking_statuses as $status_key => $status_label ) : ?>
											<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $booking->status, $status_key ); ?>><?php echo esc_html( $status_label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No bookings yet.', 'hrm-pro' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>
</div>
