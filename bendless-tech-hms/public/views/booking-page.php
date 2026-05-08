<?php
/**
 * Public booking page shell.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brand_primary = ! empty( $config['brandPrimary'] ) ? sanitize_hex_color( $config['brandPrimary'] ) : '';
$brand_button  = ! empty( $config['brandButton'] ) ? sanitize_hex_color( $config['brandButton'] ) : '';
$brand_text    = ! empty( $config['brandText'] ) ? sanitize_hex_color( $config['brandText'] ) : '';
$brand_font    = ! empty( $config['brandFont'] ) ? sanitize_text_field( $config['brandFont'] ) : 'Inter, system-ui, sans-serif';
$brand_primary = $brand_primary ? $brand_primary : '#987CC0';
$brand_button  = $brand_button ? $brand_button : '#987CC0';
$brand_text    = $brand_text ? $brand_text : '#000000';
$brand_style   = '--hrm-brand-primary:' . esc_attr( $brand_primary ) . ';--hrm-brand-button:' . esc_attr( $brand_button ) . ';--hrm-brand-text:' . esc_attr( $brand_text ) . ';--hrm-brand-font:' . esc_attr( $brand_font ) . ';';
$config_json = wp_json_encode( $config );
?>

<div id="hrm-booking-app-<?php echo esc_attr( $hotel_id ); ?>" class="hrm-public-booking font-sans text-slate-900" data-config="<?php echo esc_attr( $config_json ); ?>" style="<?php echo esc_attr( $brand_style ); ?>">
	<?php if ( ! $hotel_available ) : ?>
		<section class="mx-auto max-w-5xl rounded-2xl border border-black/10 bg-white p-8 text-center shadow-card">
			<div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-500">
				<span class="iconify text-3xl" data-icon="solar:widget-2-linear"></span>
			</div>
			<h2 class="text-2xl font-bold text-black"><?php esc_html_e( 'Reservations temporarily unavailable', 'hrm-pro' ); ?></h2>
			<p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600"><?php echo esc_html( $maintenance ); ?></p>
			<?php if ( $hotel && ( $hotel->hotel_phone || $hotel->hotel_email ) ) : ?>
				<div class="mt-6 flex flex-wrap items-center justify-center gap-3 text-sm font-semibold text-black">
					<?php if ( $hotel->hotel_phone ) : ?>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $hotel->hotel_phone ) ); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 transition-colors hover:bg-surface-50">
							<span class="iconify" data-icon="solar:bell-linear"></span>
							<?php echo esc_html( $hotel->hotel_phone ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $hotel->hotel_email ) : ?>
						<a href="mailto:<?php echo esc_attr( $hotel->hotel_email ); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 transition-colors hover:bg-surface-50">
							<span class="iconify" data-icon="solar:letter-linear"></span>
							<?php echo esc_html( $hotel->hotel_email ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
	</div>
		<?php return; ?>
	<?php endif; ?>

	<section class="hrm-booking-hero relative overflow-hidden rounded-2xl border border-black/10 bg-white text-black shadow-card">
		<div class="absolute inset-x-0 top-0 h-1 bg-primary-500"></div>
		<div class="relative px-5 py-8 md:px-8 md:py-10">
			<div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
				<div class="flex items-center gap-4">
					<?php if ( $hotel && $hotel->logo_url ) : ?>
						<img src="<?php echo esc_url( $hotel->logo_url ); ?>" alt="<?php echo esc_attr( $hotel->hotel_name ); ?>" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-black/10">
					<?php else : ?>
						<div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-lg font-bold text-primary-700 ring-1 ring-primary-500/20">
							<?php echo esc_html( $hotel ? strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/', '', $hotel->hotel_name ), 0, 2 ) ) : 'HM' ); ?>
						</div>
					<?php endif; ?>
					<div>
						<h1 class="text-3xl font-bold tracking-normal text-black md:text-4xl"><?php echo esc_html( $hotel ? $hotel->hotel_name : __( 'Hotel', 'hrm-pro' ) ); ?></h1>
						<p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600"><?php echo esc_html( $config['tagline'] ); ?></p>
					</div>
				</div>
				<div class="flex items-center gap-3 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-black ring-1 ring-primary-500/20">
					<span class="iconify text-xl text-primary-500" data-icon="solar:shield-check-linear"></span>
					<span><?php esc_html_e( 'Secured by Paystack', 'hrm-pro' ); ?></span>
				</div>
			</div>
			<div class="mt-8 rounded-2xl border border-black/10 bg-white p-4 text-slate-900 shadow-card">
				<form class="hrm-search-form grid gap-3 md:grid-cols-[1fr_1fr_0.8fr_auto]">
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Check-In', 'hrm-pro' ); ?></span>
						<input type="date" name="check_in" value="<?php echo esc_attr( $today ); ?>" min="<?php echo esc_attr( $today ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Check-Out', 'hrm-pro' ); ?></span>
						<input type="date" name="check_out" value="<?php echo esc_attr( $tomorrow ); ?>" min="<?php echo esc_attr( $tomorrow ); ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Guests', 'hrm-pro' ); ?></span>
						<input type="number" name="guests" value="1" min="1" max="20" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<button type="submit" class="mt-auto inline-flex items-center justify-center gap-2 rounded-lg bg-primary-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
						<span class="iconify" data-icon="solar:magnifer-linear"></span>
						<?php esc_html_e( 'Search', 'hrm-pro' ); ?>
					</button>
				</form>
			</div>
		</div>
	</section>

	<?php include HRM_PLUGIN_DIR . 'public/views/room-listing.php'; ?>
	<?php include HRM_PLUGIN_DIR . 'public/views/room-detail.php'; ?>
	<?php include HRM_PLUGIN_DIR . 'public/views/checkout.php'; ?>
</div>
