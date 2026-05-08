<?php
/**
 * Public booking confirmation page.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_paid             = ! empty( $payload ) && ! empty( $payload['paid'] );
$is_transfer_pending = ! empty( $payload ) && ! empty( $payload['transfer_pending'] );
$is_success          = $is_paid || $is_transfer_pending;
$page_title          = $is_paid ? __( 'Booking Confirmed', 'hrm-pro' ) : ( $is_transfer_pending ? __( 'Transfer Submitted', 'hrm-pro' ) : __( 'Booking Confirmation', 'hrm-pro' ) );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $page_title ); ?></title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script>
		tailwind.config = {
			theme: {
				extend: {
					colors: {
						primary: { 50: '#f0f4ff', 100: '#e0e9ff', 500: '#4f6ef7', 600: '#3d5af1', 700: '#2d47e0', 900: '#1a2b8a' },
						surface: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 800: '#1e293b', 900: '#0f172a' }
					},
					fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
					boxShadow: { card: '0 1px 3px 0 rgb(0 0 0 / 0.08), 0 1px 2px -1px rgb(0 0 0 / 0.08)', modal: '0 20px 60px -10px rgb(0 0 0 / 0.3)' }
				}
			}
		};
	</script>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( HRM_PLUGIN_URL . 'public/assets/public.css?ver=' . HRM_VERSION ); ?>">
	<script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-surface-50 font-sans text-slate-900' ); ?>>
	<main class="min-h-screen px-4 py-10">
		<div class="mx-auto max-w-3xl">
			<?php if ( $is_success ) : ?>
				<div class="overflow-hidden rounded-2xl bg-white shadow-modal">
					<div class="bg-surface-900 px-6 py-8 text-center text-white">
						<div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl <?php echo esc_attr( $is_paid ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-300/20' : 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-300/20' ); ?>">
							<span class="iconify text-4xl" data-icon="<?php echo esc_attr( $is_paid ? 'solar:check-circle-linear' : 'solar:wallet-money-linear' ); ?>"></span>
						</div>
						<h1 class="text-3xl font-bold"><?php echo esc_html( $is_paid ? __( 'Booking confirmed', 'hrm-pro' ) : __( 'Transfer submitted', 'hrm-pro' ) ); ?></h1>
						<p class="mt-2 text-sm text-slate-300"><?php echo esc_html( $is_paid ? $payload['hotel']->hotel_name : __( 'Your room is awaiting hotel payment confirmation.', 'hrm-pro' ) ); ?></p>
					</div>
					<div class="p-6">
						<div class="grid gap-4 md:grid-cols-2">
							<div class="rounded-2xl bg-surface-50 p-4">
								<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Booking Reference', 'hrm-pro' ); ?></p>
								<p class="mt-1 text-xl font-bold text-slate-900"><?php echo esc_html( $payload['ref'] ); ?></p>
							</div>
							<div class="rounded-2xl bg-surface-50 p-4">
								<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php echo esc_html( $is_paid ? __( 'Total Paid', 'hrm-pro' ) : __( 'Amount Due', 'hrm-pro' ) ); ?></p>
								<p class="mt-1 text-xl font-bold text-slate-900"><?php echo esc_html( $is_paid ? $payload['paid_amount'] : $payload['total'] ); ?></p>
							</div>
							<div class="rounded-2xl bg-surface-50 p-4">
								<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></p>
								<p class="mt-1 text-base font-bold text-slate-900"><?php echo esc_html( $payload['room_label'] ); ?></p>
							</div>
							<div class="rounded-2xl bg-surface-50 p-4">
								<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Dates', 'hrm-pro' ); ?></p>
								<p class="mt-1 text-base font-bold text-slate-900"><?php echo esc_html( $payload['date_label'] ); ?></p>
							</div>
						</div>

						<?php if ( $is_transfer_pending ) : ?>
							<div class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
								<span class="iconify mr-1 align-[-2px]" data-icon="solar:danger-triangle-linear"></span>
								<?php esc_html_e( 'The hotel will verify your transfer and confirm the booking. Keep your transfer reference for follow-up.', 'hrm-pro' ); ?>
								<?php if ( ! empty( $payload['transfer_ref'] ) ) : ?>
									<span class="block pt-1 text-amber-900"><?php echo esc_html( sprintf( __( 'Submitted reference: %s', 'hrm-pro' ), $payload['transfer_ref'] ) ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<div class="mt-6 rounded-2xl border border-slate-100 p-5">
							<h2 class="font-bold text-slate-900"><?php esc_html_e( 'Hotel Contact', 'hrm-pro' ); ?></h2>
							<div class="mt-3 grid gap-3 text-sm text-slate-600">
								<?php if ( $payload['hotel']->hotel_phone ) : ?>
									<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $payload['hotel']->hotel_phone ) ); ?>" class="inline-flex items-center gap-2 font-semibold text-slate-700">
										<span class="iconify text-primary-500" data-icon="solar:bell-linear"></span>
										<?php echo esc_html( $payload['hotel']->hotel_phone ); ?>
									</a>
								<?php endif; ?>
								<?php if ( $payload['hotel']->hotel_email ) : ?>
									<a href="mailto:<?php echo esc_attr( $payload['hotel']->hotel_email ); ?>" class="inline-flex items-center gap-2 font-semibold text-slate-700">
										<span class="iconify text-primary-500" data-icon="solar:letter-linear"></span>
										<?php echo esc_html( $payload['hotel']->hotel_email ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>

						<?php if ( $is_paid && 'yes' === HRM_Settings::get( 'whatsapp_enabled', 'no', (int) $payload['booking']->hotel_id ) ) : ?>
							<div class="mt-5 flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
								<span class="iconify text-lg" data-icon="solar:chat-round-linear"></span>
								<?php esc_html_e( 'A confirmation has been sent to your WhatsApp.', 'hrm-pro' ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php else : ?>
				<div class="rounded-2xl bg-white p-8 text-center shadow-modal">
					<div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500">
						<span class="iconify text-4xl" data-icon="solar:close-circle-linear"></span>
					</div>
					<h1 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Payment not confirmed', 'hrm-pro' ); ?></h1>
					<p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500">
						<?php echo esc_html( $error_message ? $error_message : __( 'We could not verify this booking payment. Please contact the hotel if your account was debited.', 'hrm-pro' ) ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
