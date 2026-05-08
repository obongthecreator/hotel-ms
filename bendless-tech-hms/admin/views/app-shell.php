<?php
/**
 * Shared admin application shell.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hotel_name        = $hotel ? $hotel->hotel_name : __( 'Hotel Manager', 'hrm-pro' );
$hotel_initials    = $hotel ? strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/', '', $hotel->hotel_name ), 0, 2 ) ) : 'HM';
$current_plan_slug = $hotel ? sanitize_key( $hotel->plan ) : '';
$current_plan_name = $current_plan ? $current_plan['name'] : __( 'No plan', 'hrm-pro' );
$user_initials     = strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/', '', $user->display_name ? $user->display_name : $user->user_login ), 0, 2 ) );
$feature_labels    = HRM_License::feature_labels();
?>
<div class="hrm-admin-shell min-h-screen bg-surface-50 font-sans text-slate-800">
	<?php if ( $test_mode ) : ?>
		<div class="hrm-test-banner fixed left-0 right-0 top-[32px] z-[70] bg-amber-500 px-4 py-2 text-center text-xs font-bold uppercase tracking-wide text-white">
			<?php esc_html_e( 'TEST MODE', 'hrm-pro' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( HRM_License::is_impersonating() && $hotel ) : ?>
		<div class="hrm-impersonation-banner fixed left-0 right-0 z-[69] bg-red-600 px-4 py-2 text-center text-sm font-semibold text-white <?php echo $test_mode ? 'top-[64px]' : 'top-[32px]'; ?>">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: hotel name */
					__( 'You are viewing as %s', 'hrm-pro' ),
					$hotel->hotel_name
				)
			);
			?>
			<button type="button" class="hrm-exit-impersonation ml-3 inline-flex items-center gap-1 rounded-md bg-white/15 px-2 py-1 text-xs hover:bg-white/25">
				<span class="iconify" data-icon="solar:exit-linear"></span>
				<?php esc_html_e( 'Exit Impersonation', 'hrm-pro' ); ?>
			</button>
			<?php if ( ! $hotel->is_active() ) : ?>
				<span class="ml-3 rounded-md bg-black/20 px-2 py-1 text-xs"><?php esc_html_e( 'EXPIRED', 'hrm-pro' ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<aside class="hrm-sidebar fixed bottom-0 left-0 top-[32px] z-50 flex w-[260px] flex-col bg-surface-900 text-white">
		<div class="flex h-24 items-center gap-3 px-6">
			<?php if ( $hotel && ! empty( $hotel->logo_url ) ) : ?>
				<img src="<?php echo esc_url( $hotel->logo_url ); ?>" alt="<?php echo esc_attr( $hotel->hotel_name ); ?>" class="h-12 w-12 rounded-xl object-cover">
			<?php else : ?>
				<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-500 text-sm font-bold text-white">
					<?php echo esc_html( $is_platform ? 'HP' : $hotel_initials ); ?>
				</div>
			<?php endif; ?>
			<div class="min-w-0">
				<p class="truncate text-sm font-semibold text-white"><?php echo esc_html( $is_platform ? __( 'HMS Platform', 'hrm-pro' ) : $hotel_name ); ?></p>
				<p class="mt-0.5 truncate text-xs text-slate-400"><?php echo esc_html( $is_platform ? __( 'Control Tower', 'hrm-pro' ) : __( 'Hotel workspace', 'hrm-pro' ) ); ?></p>
			</div>
		</div>

		<nav class="flex-1 space-y-1 px-4">
			<?php foreach ( $navigation as $item ) : ?>
				<?php
				$is_active = $active_page === $item['key'];
				$classes   = $is_active
					? 'bg-primary-500 text-white'
					: ( ! empty( $item['disabled'] ) ? 'text-slate-500 cursor-not-allowed opacity-70' : 'text-slate-400 hover:bg-white/5 hover:text-white' );
				?>
				<a href="<?php echo esc_url( $item['url'] ); ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all <?php echo esc_attr( $classes ); ?>" <?php echo ! empty( $item['disabled'] ) ? 'aria-disabled="true" onclick="return false;"' : ''; ?>>
					<span class="iconify text-xl" data-icon="<?php echo esc_attr( $item['icon'] ); ?>"></span>
					<span><?php echo esc_html( $item['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="border-t border-white/10 p-4">
			<?php if ( ! $is_platform ) : ?>
				<div class="mb-4 rounded-xl bg-white/5 p-3">
					<p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Current Plan', 'hrm-pro' ); ?></p>
					<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?php echo esc_attr( HRM_License::plan_badge_classes( $current_plan_slug ) ); ?>">
						<span class="iconify" data-icon="solar:crown-star-linear"></span>
						<?php echo esc_html( $current_plan_name ); ?>
					</span>
				</div>
			<?php endif; ?>
			<div class="flex items-center gap-3">
				<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-bold text-white">
					<?php echo esc_html( $user_initials ? $user_initials : 'U' ); ?>
				</div>
				<div class="min-w-0">
					<p class="truncate text-sm font-semibold text-white"><?php echo esc_html( $user->display_name ? $user->display_name : $user->user_login ); ?></p>
					<p class="truncate text-xs text-slate-400"><?php echo esc_html( $role_label ); ?></p>
				</div>
			</div>
		</div>
	</aside>

	<main class="hrm-main min-h-screen pl-[260px]">
		<header class="sticky top-[32px] z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-8 backdrop-blur">
			<div>
				<p class="text-xs font-medium uppercase tracking-wide text-slate-400">
					<?php echo esc_html( $is_platform ? __( 'HMS Platform', 'hrm-pro' ) : __( 'Hotel Manager', 'hrm-pro' ) ); ?>
				</p>
				<h1 class="mt-0.5 text-xl font-bold text-slate-900"><?php echo esc_html( $page_title ); ?></h1>
			</div>
			<div class="flex items-center gap-3">
				<?php if ( $is_platform ) : ?>
					<button type="button" data-enabled="<?php echo esc_attr( $test_mode ? '0' : '1' ); ?>" class="hrm-toggle-test-mode inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold transition-colors <?php echo $test_mode ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-surface-50'; ?>">
						<span class="iconify" data-icon="solar:test-tube-linear"></span>
						<?php echo esc_html( $test_mode ? __( 'Test Mode On', 'hrm-pro' ) : __( 'Test Mode Off', 'hrm-pro' ) ); ?>
					</button>
				<?php elseif ( $hotel ) : ?>
					<button type="button" onclick="openUpgradeModal()" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-600">
						<span class="iconify" data-icon="solar:crown-star-linear"></span>
						<?php esc_html_e( 'View Plans', 'hrm-pro' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</header>

		<section class="p-8">
			<?php
			if ( ! empty( $_GET['hrm_notice'] ) ) {
				$notice_key = sanitize_key( wp_unslash( $_GET['hrm_notice'] ) );
				$notice     = '';
				if ( 'plans_saved' === $notice_key ) {
					$notice = __( 'Plan configuration saved.', 'hrm-pro' );
				}
				if ( 'platform_saved' === $notice_key ) {
					$notice = __( 'Platform settings saved.', 'hrm-pro' );
				}
				if ( $notice ) :
					?>
					<div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
						<span class="iconify text-lg" data-icon="solar:check-circle-linear"></span>
						<?php echo esc_html( $notice ); ?>
					</div>
					<?php
				endif;
			}

			if ( is_readable( $content_view ) ) {
				extract( $content_data, EXTR_SKIP );
				include $content_view;
			}
			?>
		</section>
	</main>

	<div id="modal-overlay" class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm"></div>

	<?php if ( $is_blocked && $hotel ) : ?>
		<div class="fixed inset-x-0 bottom-0 top-[32px] z-[80] flex items-center justify-center bg-surface-900/75 px-6 backdrop-blur-sm">
			<div class="w-full max-w-xl rounded-2xl bg-white p-8 text-center shadow-modal">
				<div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50">
					<span class="iconify text-3xl text-red-500" data-icon="solar:lock-password-linear"></span>
				</div>
				<p class="text-sm font-semibold uppercase tracking-wide text-red-500"><?php esc_html_e( 'Subscription Expired', 'hrm-pro' ); ?></p>
				<h2 class="mt-2 text-2xl font-bold text-slate-900">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: plan name */
							__( '%s plan needs renewal', 'hrm-pro' ),
							$expiry_context['plan_name']
						)
					);
					?>
				</h2>
				<p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: expiry date, 2: days expired */
							__( 'Your plan expired on %1$s. It has been expired for %2$d day(s). Renew now to restore access to HMS admin tools.', 'hrm-pro' ),
							$expiry_context['expiry_display'],
							(int) $expiry_context['days_expired']
						)
					);
					?>
				</p>
				<div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
					<button type="button" class="hrm-plan-pay inline-flex items-center gap-2 rounded-lg bg-primary-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-600" data-hotel-id="<?php echo esc_attr( $hotel->id ); ?>" data-plan="<?php echo esc_attr( $current_plan_slug ? $current_plan_slug : 'basic' ); ?>">
						<span class="iconify" data-icon="solar:wallet-money-linear"></span>
						<?php esc_html_e( 'Renew Now', 'hrm-pro' ); ?>
					</button>
					<button type="button" onclick="openUpgradeModal()" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
						<span class="iconify" data-icon="solar:crown-star-linear"></span>
						<?php esc_html_e( 'Compare Plans', 'hrm-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! $is_platform && $hotel ) : ?>
		<div id="hrm-upgrade-modal" class="fixed inset-0 z-[90] hidden overflow-y-auto bg-surface-900/80 px-6 py-8 backdrop-blur-sm">
			<div class="mx-auto max-w-7xl rounded-2xl bg-white shadow-modal">
				<div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 md:flex-row md:items-center md:justify-between">
					<div>
						<p class="text-sm font-semibold uppercase tracking-wide text-primary-500"><?php esc_html_e( 'BENDLESS TECH HMS', 'hrm-pro' ); ?></p>
						<h2 class="mt-1 text-2xl font-bold text-slate-900"><?php esc_html_e( 'Choose Your Plan', 'hrm-pro' ); ?></h2>
					</div>
					<div class="flex items-center gap-3">
						<div class="inline-flex rounded-lg bg-surface-100 p-1">
							<button type="button" class="hrm-billing-toggle rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm" data-cycle="yearly"><?php esc_html_e( 'Annual Plans', 'hrm-pro' ); ?></button>
						</div>
						<button type="button" onclick="closeUpgradeModal()" class="text-slate-400 transition-colors hover:text-slate-600">
							<span class="iconify text-2xl" data-icon="solar:close-circle-linear"></span>
						</button>
					</div>
				</div>
				<div class="grid gap-5 p-6 lg:grid-cols-4">
					<?php foreach ( HRM_License::get_plan_order() as $plan_slug ) : ?>
						<?php
						if ( empty( $plans[ $plan_slug ] ) ) {
							continue;
						}
						$plan            = $plans[ $plan_slug ];
						$is_current      = $current_plan_slug === $plan_slug;
						$yearly          = (float) $plan['price_yearly'];
						$renewal_yearly  = isset( $plan['renewal_yearly'] ) ? (float) $plan['renewal_yearly'] : $yearly;
						$room_limit      = (int) $plan['rooms_limit'];
						$staff_limit     = (int) $plan['staff_limit'];
						$feature_slugs   = array( '*' ) === $plan['features'] ? array_keys( $feature_labels ) : $plan['features'];
						$advantages      = HRM_License::plan_advantages( $plan_slug );
						?>
						<div class="relative rounded-2xl border bg-white p-5 <?php echo $is_current ? 'border-primary-500 ring-2 ring-primary-100' : 'border-slate-200'; ?>">
							<?php if ( $is_current ) : ?>
								<span class="absolute right-4 top-4 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-600"><?php esc_html_e( 'Current', 'hrm-pro' ); ?></span>
							<?php endif; ?>
							<h3 class="pr-20 text-lg font-bold text-slate-900"><?php echo esc_html( $plan['name'] ); ?></h3>
							<div class="mt-4">
								<p class="hrm-plan-price text-3xl font-bold text-slate-900" data-monthly="<?php echo esc_attr( $yearly > 0 ? HRM_License::money( $yearly ) : __( 'Custom', 'hrm-pro' ) ); ?>" data-yearly="<?php echo esc_attr( $yearly > 0 ? HRM_License::money( $yearly ) : __( 'Custom', 'hrm-pro' ) ); ?>">
									<?php echo esc_html( $yearly > 0 ? HRM_License::money( $yearly ) : __( 'Custom', 'hrm-pro' ) ); ?>
								</p>
								<p class="hrm-plan-cycle mt-1 text-sm text-slate-500"><?php esc_html_e( 'first year', 'hrm-pro' ); ?></p>
								<p class="hrm-plan-savings mt-1 text-xs font-medium text-emerald-600" data-savings="0">
									<?php echo esc_html( sprintf( __( '%s yearly renewal after the first year', 'hrm-pro' ), HRM_License::money( $renewal_yearly ) ) ); ?>
								</p>
							</div>
							<?php if ( $advantages ) : ?>
								<div class="mt-5 rounded-xl bg-primary-50 p-3">
									<p class="text-xs font-bold uppercase tracking-wide text-primary-600"><?php esc_html_e( 'Why choose this', 'hrm-pro' ); ?></p>
									<ul class="mt-2 space-y-2 text-xs leading-5 text-slate-700">
										<?php foreach ( $advantages as $advantage ) : ?>
											<li class="flex gap-2"><span class="iconify mt-0.5 shrink-0 text-primary-600" data-icon="solar:alt-arrow-right-linear"></span><span><?php echo esc_html( $advantage ); ?></span></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<div class="mt-5 grid grid-cols-2 gap-3 text-sm">
								<div class="rounded-xl bg-surface-50 p-3">
									<p class="text-xs text-slate-500"><?php esc_html_e( 'Rooms', 'hrm-pro' ); ?></p>
									<p class="font-semibold text-slate-900"><?php echo esc_html( $room_limit < 0 ? __( 'Unlimited', 'hrm-pro' ) : number_format_i18n( $room_limit ) ); ?></p>
								</div>
								<div class="rounded-xl bg-surface-50 p-3">
									<p class="text-xs text-slate-500"><?php esc_html_e( 'Staff', 'hrm-pro' ); ?></p>
									<p class="font-semibold text-slate-900"><?php echo esc_html( $staff_limit < 0 ? __( 'Unlimited', 'hrm-pro' ) : number_format_i18n( $staff_limit ) ); ?></p>
								</div>
							</div>
							<ul class="mt-5 max-h-60 space-y-2 overflow-y-auto pr-1 text-sm text-slate-600">
								<?php foreach ( $feature_slugs as $feature_slug ) : ?>
									<li class="flex items-start gap-2">
										<span class="iconify mt-0.5 shrink-0 text-emerald-500" data-icon="solar:check-circle-linear"></span>
										<span><?php echo esc_html( isset( $feature_labels[ $feature_slug ] ) ? $feature_labels[ $feature_slug ] : $feature_slug ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
							<button type="button" class="hrm-plan-pay mt-6 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-600" data-hotel-id="<?php echo esc_attr( $hotel->id ); ?>" data-plan="<?php echo esc_attr( $plan_slug ); ?>">
								<span class="iconify" data-icon="solar:wallet-money-linear"></span>
								<?php echo esc_html( $is_current ? __( 'Renew Plan', 'hrm-pro' ) : __( 'Upgrade / Subscribe', 'hrm-pro' ) ); ?>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
