<?php
/**
 * Public checkout view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bank_transfer_enabled = ! empty( $config['bankTransferEnabled'] );
?>

<section class="hrm-view hrm-view-checkout mt-8 hidden" data-view="checkout">
	<button type="button" class="hrm-back-detail mb-5 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
		<span class="iconify" data-icon="solar:alt-arrow-right-linear"></span>
		<?php esc_html_e( 'Back to room', 'hrm-pro' ); ?>
	</button>

	<div class="grid gap-6 lg:grid-cols-[1fr_420px]">
		<form class="hrm-checkout-form rounded-2xl bg-white p-6 shadow-card">
			<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Guest Details', 'hrm-pro' ); ?></h2>
			<div class="hrm-returning-guest mt-4 hidden rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
				<span class="iconify mr-1 align-[-2px]" data-icon="solar:check-circle-linear"></span>
				<?php esc_html_e( 'Returning Guest', 'hrm-pro' ); ?>
			</div>
			<div class="hrm-blacklist-warning mt-4 hidden rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
				<span class="iconify mr-1 align-[-2px]" data-icon="solar:shield-warning-linear"></span>
				<?php esc_html_e( 'We are unable to complete this reservation. Please contact the hotel directly.', 'hrm-pro' ); ?>
			</div>

			<div class="mt-5 grid gap-4 md:grid-cols-2">
				<label class="space-y-1.5 md:col-span-2">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Full Name', 'hrm-pro' ); ?></span>
					<input type="text" name="full_name" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500" autocomplete="name">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Phone', 'hrm-pro' ); ?></span>
					<input type="tel" name="phone" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500" autocomplete="tel">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Email', 'hrm-pro' ); ?></span>
					<input type="email" name="email" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500" autocomplete="email">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'ID Type', 'hrm-pro' ); ?></span>
					<select name="id_type" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500">
						<option value=""><?php esc_html_e( 'Select ID type', 'hrm-pro' ); ?></option>
						<option value="national_id"><?php esc_html_e( 'National ID', 'hrm-pro' ); ?></option>
						<option value="passport"><?php esc_html_e( 'Passport', 'hrm-pro' ); ?></option>
						<option value="drivers_license"><?php esc_html_e( 'Driver License', 'hrm-pro' ); ?></option>
						<option value="voters_card"><?php esc_html_e( 'Voter Card', 'hrm-pro' ); ?></option>
					</select>
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'ID Number', 'hrm-pro' ); ?></span>
					<input type="text" name="id_number" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5 md:col-span-2">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Special Requests', 'hrm-pro' ); ?></span>
					<textarea name="notes" rows="4" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
				</label>
			</div>

			<div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
				<p class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Payment Method', 'hrm-pro' ); ?></p>
				<div class="mt-3 grid gap-3 md:grid-cols-2">
					<label class="hrm-payment-option flex cursor-pointer items-center gap-3 rounded-xl border border-primary-500 bg-primary-50 px-4 py-3 text-sm font-semibold text-slate-900">
						<input type="radio" name="payment_method" value="paystack" checked class="h-4 w-4 border-slate-300 text-primary-500 focus:ring-primary-500">
						<span class="iconify text-primary-500" data-icon="solar:wallet-money-linear"></span>
						<span><?php esc_html_e( 'Paystack Card/Transfer', 'hrm-pro' ); ?></span>
					</label>
					<?php if ( $bank_transfer_enabled ) : ?>
						<label class="hrm-payment-option flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
							<input type="radio" name="payment_method" value="transfer" class="h-4 w-4 border-slate-300 text-primary-500 focus:ring-primary-500">
							<span class="iconify text-primary-500" data-icon="solar:wallet-money-linear"></span>
							<span><?php esc_html_e( 'Bank Transfer', 'hrm-pro' ); ?></span>
						</label>
					<?php endif; ?>
				</div>

				<?php if ( $bank_transfer_enabled ) : ?>
					<div class="hrm-bank-transfer-panel mt-4 hidden rounded-xl bg-surface-50 p-4 text-sm text-slate-700">
						<div class="grid gap-3 md:grid-cols-3">
							<div>
								<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Bank', 'hrm-pro' ); ?></p>
								<p class="mt-1 font-bold text-slate-900"><?php echo esc_html( $config['bankName'] ); ?></p>
							</div>
							<div>
								<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Account Name', 'hrm-pro' ); ?></p>
								<p class="mt-1 font-bold text-slate-900"><?php echo esc_html( $config['bankAccountName'] ); ?></p>
							</div>
							<div>
								<p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php esc_html_e( 'Account Number', 'hrm-pro' ); ?></p>
								<p class="mt-1 font-bold text-slate-900"><?php echo esc_html( $config['bankAccountNumber'] ); ?></p>
							</div>
						</div>
						<label class="mt-4 block space-y-1.5">
							<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Transfer Reference or Depositor Name', 'hrm-pro' ); ?></span>
							<input type="text" name="transfer_ref" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'e.g. Ada Okafor / bank reference', 'hrm-pro' ); ?>">
						</label>
						<p class="mt-3 text-xs leading-5 text-slate-500"><?php esc_html_e( 'Submit after making the transfer. Hotel staff will verify payment before final confirmation.', 'hrm-pro' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<label class="mt-5 flex items-start gap-3 rounded-xl bg-surface-50 p-4 text-sm text-slate-600">
				<input type="checkbox" name="terms" value="1" required class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary-500 focus:ring-primary-500">
				<span><?php esc_html_e( 'I agree to the hotel booking terms and confirm that the details above are accurate.', 'hrm-pro' ); ?></span>
			</label>

			<button type="submit" class="hrm-pay-button mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-primary-600">
				<span class="iconify" data-icon="solar:wallet-money-linear"></span>
				<span class="hrm-pay-button-text"><?php esc_html_e( 'Pay with Paystack', 'hrm-pro' ); ?></span>
			</button>

			<div class="hrm-payment-security mt-4 flex items-center justify-center gap-2 text-xs font-semibold text-slate-500">
				<span class="iconify" data-icon="solar:lock-password-linear"></span>
				<span class="hrm-payment-security-text"><?php esc_html_e( 'Secured by Paystack', 'hrm-pro' ); ?></span>
			</div>
		</form>

		<aside class="rounded-2xl bg-white p-6 shadow-card lg:self-start">
			<h3 class="text-lg font-bold text-slate-900"><?php esc_html_e( 'Booking Summary', 'hrm-pro' ); ?></h3>
			<div class="mt-5 overflow-hidden rounded-2xl bg-surface-50">
				<div class="hrm-summary-image aspect-[4/3] bg-surface-900"></div>
				<div class="p-4">
					<p class="hrm-summary-room text-lg font-bold text-slate-900"></p>
					<p class="hrm-summary-dates mt-1 text-sm text-slate-500"></p>
				</div>
			</div>

			<div class="mt-5 space-y-3 text-sm">
				<div class="flex justify-between gap-4">
					<span class="text-slate-500"><?php esc_html_e( 'Nights', 'hrm-pro' ); ?></span>
					<span class="hrm-summary-nights font-semibold text-slate-900">0</span>
				</div>
				<div class="flex justify-between gap-4">
					<span class="text-slate-500"><?php esc_html_e( 'Subtotal', 'hrm-pro' ); ?></span>
					<span class="hrm-summary-subtotal font-semibold text-slate-900">₦0.00</span>
				</div>
				<div class="flex justify-between gap-4">
					<span class="hrm-summary-vat-label text-slate-500"><?php esc_html_e( 'VAT', 'hrm-pro' ); ?></span>
					<span class="hrm-summary-vat font-semibold text-slate-900">₦0.00</span>
				</div>
				<div class="border-t border-slate-200 pt-3">
					<div class="flex justify-between gap-4">
						<span class="text-base font-bold text-slate-900"><?php esc_html_e( 'Total', 'hrm-pro' ); ?></span>
						<span class="hrm-summary-total text-base font-bold text-slate-900">₦0.00</span>
					</div>
				</div>
			</div>

			<div class="hrm-summary-breakdown mt-5 rounded-xl bg-surface-50 p-4 text-xs text-slate-500"></div>
		</aside>
	</div>
</section>
