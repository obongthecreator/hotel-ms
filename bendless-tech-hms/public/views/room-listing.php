<?php
/**
 * Public room listing view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$room_types = array( 'all', 'single', 'double', 'suite', 'deluxe', 'executive' );
?>

<section class="hrm-view hrm-view-listing mt-8" data-view="listing">
	<div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Choose a room', 'hrm-pro' ); ?></h2>
			<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Live availability and pricing are checked for your selected dates.', 'hrm-pro' ); ?></p>
		</div>
		<div class="hrm-filter-pills flex gap-2 overflow-x-auto pb-1">
			<?php foreach ( $room_types as $type ) : ?>
				<button type="button" data-filter="<?php echo esc_attr( $type ); ?>" class="hrm-filter-pill inline-flex shrink-0 items-center rounded-full border px-4 py-2 text-sm font-semibold transition-colors <?php echo 'all' === $type ? 'border-primary-500 bg-primary-50 text-primary-600' : 'border-slate-200 bg-white text-slate-600 hover:bg-surface-50'; ?>">
					<?php echo esc_html( 'all' === $type ? __( 'All', 'hrm-pro' ) : ucwords( $type ) ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="hrm-room-grid grid gap-5 md:grid-cols-2 xl:grid-cols-3">
		<?php if ( $rooms ) : ?>
			<?php foreach ( $rooms as $room ) : ?>
				<?php
				$has_image = ! empty( $room['images'][0] );
				$type_name = ucwords( str_replace( '_', ' ', $room['room_type'] ) );
				?>
				<article class="hrm-room-card overflow-hidden rounded-2xl bg-white shadow-card transition-all hover:-translate-y-0.5 hover:shadow-modal" data-room-id="<?php echo esc_attr( $room['id'] ); ?>" data-room-type="<?php echo esc_attr( $room['room_type'] ); ?>" data-available="1">
					<div class="relative aspect-[4/3] overflow-hidden <?php echo $has_image ? '' : 'bg-gradient-to-br ' . esc_attr( $room['placeholder'] ); ?>">
						<?php if ( $has_image ) : ?>
							<img src="<?php echo esc_url( $room['images'][0] ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Room %s', 'hrm-pro' ), $room['room_number'] ) ); ?>" class="h-full w-full object-cover transition-transform duration-500 hover:scale-105">
						<?php else : ?>
							<div class="absolute inset-0 flex items-center justify-center text-white/80">
								<span class="iconify text-6xl" data-icon="solar:bed-linear"></span>
							</div>
						<?php endif; ?>
						<div class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-slate-800 shadow-sm">
							<?php echo esc_html( $type_name ); ?>
						</div>
						<div class="hrm-availability-badge absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
							<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
							<?php esc_html_e( 'Available', 'hrm-pro' ); ?>
						</div>
					</div>
					<div class="p-5">
						<div class="flex items-start justify-between gap-4">
							<div>
								<h3 class="text-lg font-bold text-slate-900">
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: room number */
											__( 'Room %s', 'hrm-pro' ),
											$room['room_number']
										)
									);
									?>
								</h3>
								<p class="mt-1 text-sm text-slate-500">
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: floor, 2: max guests */
											__( 'Floor %1$d · Up to %2$d guests', 'hrm-pro' ),
											$room['floor'],
											$room['max_guests']
										)
									);
									?>
								</p>
							</div>
							<p class="text-right text-lg font-bold text-slate-900">
								<span class="hrm-room-price"><?php echo esc_html( $room['price_formatted'] ); ?></span>
								<span class="block text-xs font-medium text-slate-400"><?php esc_html_e( 'per night', 'hrm-pro' ); ?></span>
							</p>
						</div>

						<div class="mt-4 flex flex-wrap gap-2">
							<?php foreach ( array_slice( $room['amenities'], 0, 4 ) as $amenity ) : ?>
								<?php $icon = isset( $amenity_icons[ strtolower( $amenity ) ] ) ? $amenity_icons[ strtolower( $amenity ) ] : 'solar:check-circle-linear'; ?>
								<span class="inline-flex items-center gap-1 rounded-full bg-surface-50 px-2.5 py-1 text-xs font-medium text-slate-600">
									<span class="iconify" data-icon="<?php echo esc_attr( $icon ); ?>"></span>
									<?php echo esc_html( $amenity ); ?>
								</span>
							<?php endforeach; ?>
						</div>

						<button type="button" class="hrm-view-room mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-600" data-room-id="<?php echo esc_attr( $room['id'] ); ?>">
							<?php esc_html_e( 'View Details', 'hrm-pro' ); ?>
							<span class="iconify" data-icon="solar:alt-arrow-right-linear"></span>
						</button>
					</div>
				</article>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="col-span-full rounded-2xl bg-white p-10 text-center shadow-card">
				<div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-surface-100 text-slate-500">
					<span class="iconify text-3xl" data-icon="solar:buildings-2-linear"></span>
				</div>
				<h3 class="text-xl font-bold text-slate-900"><?php esc_html_e( 'No rooms listed yet', 'hrm-pro' ); ?></h3>
				<p class="mx-auto mt-2 max-w-md text-sm text-slate-500"><?php esc_html_e( 'Please contact the hotel directly for room availability.', 'hrm-pro' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</section>
