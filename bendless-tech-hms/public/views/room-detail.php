<?php
/**
 * Public room detail view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="hrm-view hrm-view-detail mt-8 hidden" data-view="detail">
	<button type="button" class="hrm-back-listing mb-5 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
		<span class="iconify" data-icon="solar:alt-arrow-right-linear"></span>
		<?php esc_html_e( 'Back to rooms', 'hrm-pro' ); ?>
	</button>

	<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
		<div class="min-w-0 space-y-6">
			<div class="hrm-detail-gallery grid gap-3 md:grid-cols-[1.2fr_0.8fr]">
				<div class="hrm-detail-main-image relative aspect-[4/3] overflow-hidden rounded-2xl bg-surface-900"></div>
				<div class="hrm-detail-thumbs grid grid-cols-2 gap-3"></div>
			</div>

			<div class="rounded-2xl bg-white p-6 shadow-card">
				<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
					<div>
						<span class="hrm-detail-type inline-flex rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-600"></span>
						<h2 class="hrm-detail-title mt-3 text-3xl font-bold text-slate-900"></h2>
						<p class="hrm-detail-meta mt-2 text-sm text-slate-500"></p>
					</div>
					<div class="rounded-2xl bg-surface-50 px-4 py-3 text-right">
						<p class="hrm-detail-price text-2xl font-bold text-slate-900"></p>
						<p class="text-xs font-medium text-slate-400"><?php esc_html_e( 'per night', 'hrm-pro' ); ?></p>
					</div>
				</div>
				<p class="hrm-detail-description mt-5 text-sm leading-7 text-slate-600"></p>
			</div>

			<div class="rounded-2xl bg-white p-6 shadow-card">
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Amenities', 'hrm-pro' ); ?></h3>
				<div class="hrm-detail-amenities mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"></div>
			</div>
		</div>

		<aside class="lg:sticky lg:top-24 lg:self-start">
			<div class="rounded-2xl bg-white p-5 shadow-card">
				<h3 class="text-lg font-bold text-slate-900"><?php esc_html_e( 'Book this room', 'hrm-pro' ); ?></h3>
				<form class="hrm-detail-booking-form mt-4 space-y-4">
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Check-In', 'hrm-pro' ); ?></span>
						<input type="date" name="detail_check_in" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Check-Out', 'hrm-pro' ); ?></span>
						<input type="date" name="detail_check_out" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<label class="space-y-1.5">
						<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Guests', 'hrm-pro' ); ?></span>
						<input type="number" name="detail_guests" min="1" value="1" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500">
					</label>
					<div class="hrm-price-breakdown rounded-xl bg-surface-50 p-4">
						<div class="space-y-2 text-sm">
							<div class="flex justify-between gap-4">
								<span class="text-slate-500"><?php esc_html_e( 'Nights', 'hrm-pro' ); ?></span>
								<span class="hrm-breakdown-nights font-semibold text-slate-800">0</span>
							</div>
							<div class="flex justify-between gap-4">
								<span class="text-slate-500"><?php esc_html_e( 'Subtotal', 'hrm-pro' ); ?></span>
								<span class="hrm-breakdown-subtotal font-semibold text-slate-800">₦0.00</span>
							</div>
							<div class="flex justify-between gap-4">
								<span class="hrm-breakdown-vat-label text-slate-500"><?php esc_html_e( 'VAT', 'hrm-pro' ); ?></span>
								<span class="hrm-breakdown-vat font-semibold text-slate-800">₦0.00</span>
							</div>
							<div class="border-t border-slate-200 pt-2">
								<div class="flex justify-between gap-4">
									<span class="font-semibold text-slate-900"><?php esc_html_e( 'Total', 'hrm-pro' ); ?></span>
									<span class="hrm-breakdown-total font-bold text-slate-900">₦0.00</span>
								</div>
							</div>
						</div>
						<div class="hrm-nightly-breakdown mt-3 hidden rounded-lg bg-white p-3 text-xs text-slate-500"></div>
					</div>
					<button type="submit" class="hrm-book-now inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
						<span class="iconify" data-icon="solar:calendar-mark-linear"></span>
						<?php esc_html_e( 'Book Now', 'hrm-pro' ); ?>
					</button>
				</form>
			</div>
		</aside>
	</div>

	<div class="hrm-gallery-modal fixed inset-0 z-[90] hidden items-center justify-center bg-surface-900/90 p-4">
		<button type="button" class="hrm-gallery-close absolute right-5 top-5 text-white/80 transition-colors hover:text-white">
			<span class="iconify text-3xl" data-icon="solar:close-circle-linear"></span>
		</button>
		<button type="button" class="hrm-gallery-prev absolute left-5 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white transition-colors hover:bg-white/20">
			<span class="iconify text-2xl" data-icon="solar:alt-arrow-right-linear"></span>
		</button>
		<img class="hrm-gallery-image max-h-[85vh] max-w-[90vw] rounded-2xl object-contain shadow-modal" alt="">
		<button type="button" class="hrm-gallery-next absolute right-5 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white transition-colors hover:bg-white/20">
			<span class="iconify text-2xl" data-icon="solar:alt-arrow-right-linear"></span>
		</button>
	</div>
</section>
