<?php
/**
 * Print-ready invoice template.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hotel  = $payload['hotel'];
$booking = $payload['booking'];
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $payload['document_type'] . ' ' . $payload['document_number'] ); ?></title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
	<style>
		body { font-family: Inter, system-ui, sans-serif; }
		@media print {
			.hrm-print-actions { display: none !important; }
			body { background: #fff !important; }
			.hrm-invoice-page { box-shadow: none !important; margin: 0 !important; max-width: none !important; }
		}
	</style>
</head>
<body class="bg-slate-100 text-slate-900">
	<div class="hrm-print-actions sticky top-0 z-10 border-b border-slate-200 bg-white px-6 py-3">
		<div class="mx-auto flex max-w-4xl justify-end gap-3">
			<button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
				<span class="iconify" data-icon="solar:printer-linear"></span>
				<?php esc_html_e( 'Print', 'hrm-pro' ); ?>
			</button>
			<button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
				<span class="iconify" data-icon="solar:download-minimalistic-linear"></span>
				<?php esc_html_e( 'Save PDF', 'hrm-pro' ); ?>
			</button>
		</div>
	</div>
	<main class="px-4 py-8">
		<section class="hrm-invoice-page mx-auto max-w-4xl rounded-2xl bg-white p-8 shadow-2xl">
			<header class="flex flex-col gap-6 border-b border-slate-200 pb-8 md:flex-row md:items-start md:justify-between">
				<div>
					<div class="flex items-center gap-4">
						<?php if ( $hotel->logo_url ) : ?>
							<img src="<?php echo esc_url( $hotel->logo_url ); ?>" alt="<?php echo esc_attr( $hotel->hotel_name ); ?>" class="h-14 w-14 rounded-xl object-cover">
						<?php else : ?>
							<div class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white"><?php echo esc_html( strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/', '', $hotel->hotel_name ), 0, 2 ) ) ); ?></div>
						<?php endif; ?>
						<div>
							<h1 class="text-xl font-bold text-slate-900"><?php echo esc_html( $hotel->hotel_name ); ?></h1>
							<p class="mt-1 text-sm text-slate-500"><?php echo esc_html( $hotel->hotel_address ); ?></p>
						</div>
					</div>
					<div class="mt-5 space-y-1 text-sm text-slate-600">
						<?php if ( $hotel->hotel_email ) : ?><p><?php echo esc_html( $hotel->hotel_email ); ?></p><?php endif; ?>
						<?php if ( $hotel->hotel_phone ) : ?><p><?php echo esc_html( $hotel->hotel_phone ); ?></p><?php endif; ?>
					</div>
				</div>
				<div class="text-left md:text-right">
					<p class="text-sm font-semibold uppercase tracking-wide text-slate-400"><?php echo esc_html( $payload['document_type'] ); ?></p>
					<p class="mt-1 text-2xl font-bold text-slate-900"><?php echo esc_html( $payload['document_number'] ); ?></p>
					<p class="mt-2 text-sm text-slate-500"><?php echo esc_html( $payload['issue_date'] ); ?></p>
				</div>
			</header>

			<div class="grid gap-6 border-b border-slate-200 py-8 md:grid-cols-2">
				<div>
					<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Guest', 'hrm-pro' ); ?></p>
					<h2 class="mt-2 text-lg font-bold text-slate-900"><?php echo esc_html( $booking->guest_name ); ?></h2>
					<p class="mt-1 text-sm text-slate-600"><?php echo esc_html( $booking->guest_phone ); ?></p>
					<p class="mt-1 text-sm text-slate-600"><?php echo esc_html( $booking->guest_email ); ?></p>
				</div>
				<div>
					<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Booking', 'hrm-pro' ); ?></p>
					<h2 class="mt-2 text-lg font-bold text-slate-900"><?php echo esc_html( $payload['booking_ref'] ); ?></h2>
					<p class="mt-1 text-sm text-slate-600"><?php echo esc_html( $booking->room_number . ' · ' . ucwords( $booking->room_type ) ); ?></p>
					<p class="mt-1 text-sm text-slate-600"><?php echo esc_html( $payload['date_range'] ); ?></p>
				</div>
			</div>

			<div class="py-8">
				<table class="w-full">
					<thead>
						<tr class="border-b border-slate-200">
							<th class="py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Description', 'hrm-pro' ); ?></th>
							<th class="py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Qty', 'hrm-pro' ); ?></th>
							<th class="py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Rate', 'hrm-pro' ); ?></th>
							<th class="py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Amount', 'hrm-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr class="border-b border-slate-100">
							<td class="py-4 text-sm font-medium text-slate-800"><?php echo esc_html( __( 'Room accommodation', 'hrm-pro' ) . ' · ' . $booking->room_number ); ?></td>
							<td class="py-4 text-right text-sm text-slate-600"><?php echo esc_html( (int) $booking->total_nights ); ?></td>
							<td class="py-4 text-right text-sm text-slate-600"><?php echo esc_html( $payload['rate_per_night'] ); ?></td>
							<td class="py-4 text-right text-sm font-semibold text-slate-900"><?php echo esc_html( $payload['subtotal'] ); ?></td>
						</tr>
					</tbody>
				</table>
				<div class="ml-auto mt-6 w-full max-w-sm space-y-3 text-sm">
					<div class="flex justify-between gap-4"><span class="text-slate-500"><?php esc_html_e( 'Subtotal', 'hrm-pro' ); ?></span><span class="font-semibold text-slate-900"><?php echo esc_html( $payload['subtotal'] ); ?></span></div>
					<div class="flex justify-between gap-4"><span class="text-slate-500"><?php echo esc_html( sprintf( __( 'VAT (%s%%)', 'hrm-pro' ), number_format_i18n( (float) $booking->vat_rate, 2 ) ) ); ?></span><span class="font-semibold text-slate-900"><?php echo esc_html( $payload['vat'] ); ?></span></div>
					<div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base"><span class="font-bold text-slate-900"><?php esc_html_e( 'Total', 'hrm-pro' ); ?></span><span class="font-bold text-slate-900"><?php echo esc_html( $payload['total'] ); ?></span></div>
					<div class="flex justify-between gap-4"><span class="text-slate-500"><?php esc_html_e( 'Amount Paid', 'hrm-pro' ); ?></span><span class="font-semibold text-emerald-700"><?php echo esc_html( $payload['amount_paid'] ); ?></span></div>
					<div class="flex justify-between gap-4"><span class="text-slate-500"><?php esc_html_e( 'Balance', 'hrm-pro' ); ?></span><span class="font-semibold text-red-700"><?php echo esc_html( $payload['balance'] ); ?></span></div>
				</div>
			</div>
		</section>
	</main>
</body>
</html>
