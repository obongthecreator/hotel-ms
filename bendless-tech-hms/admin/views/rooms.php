<?php
/**
 * Admin room management view.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rooms  = isset( $rooms ) && is_array( $rooms ) ? $rooms : array();
$counts = isset( $counts ) && is_array( $counts ) ? $counts : array();
?>

<?php if ( ! HRM_License::can( 'room_management' ) ) : ?>
	<?php HRM_Admin::upgrade_prompt( __( 'Room management', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'room_management' ) ); ?>
	<?php return; ?>
<?php endif; ?>

<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
	<div>
		<h2 class="text-2xl font-bold text-slate-900"><?php esc_html_e( 'Room Management', 'hrm-pro' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Create rooms, update rates, and change operational status inline.', 'hrm-pro' ); ?></p>
	</div>
	<button type="button" onclick="openModal('hrm-room-modal')" class="hrm-new-room inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-600">
		<span class="iconify" data-icon="solar:add-circle-linear"></span>
		<?php esc_html_e( 'Add Room', 'hrm-pro' ); ?>
	</button>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-4">
	<?php foreach ( array( 'available' => 'Room available', 'occupied' => 'Room occupied', 'cleaning' => 'Room cleaning', 'maintenance' => 'Maintenance' ) as $status_key => $label ) : ?>
		<div class="rounded-2xl bg-white p-5 shadow-card">
			<p class="text-sm font-medium text-slate-500"><?php echo esc_html( $label ); ?></p>
			<p class="mt-1 text-2xl font-bold text-slate-900"><?php echo esc_html( number_format_i18n( isset( $counts[ $status_key ] ) ? $counts[ $status_key ] : 0 ) ); ?></p>
		</div>
	<?php endforeach; ?>
</div>

<div class="mb-6 rounded-2xl bg-white p-5 shadow-card">
	<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
		<div>
			<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Room Filters', 'hrm-pro' ); ?></h3>
			<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Choose a room type first, then filter by room condition.', 'hrm-pro' ); ?></p>
		</div>
		<div class="flex flex-col gap-3 xl:flex-row xl:items-center">
			<div class="flex flex-wrap gap-2" data-hrm-room-filter-group="type">
				<?php foreach ( array( 'all' => __( 'All Types', 'hrm-pro' ), 'single' => __( 'Single', 'hrm-pro' ), 'double' => __( 'Double', 'hrm-pro' ), 'suite' => __( 'Suite', 'hrm-pro' ), 'deluxe' => __( 'Deluxe', 'hrm-pro' ), 'executive' => __( 'Executive', 'hrm-pro' ) ) as $type_key => $type_label ) : ?>
					<button type="button" class="hrm-room-filter rounded-lg border px-3 py-2 text-sm font-semibold transition-colors <?php echo 'all' === $type_key ? 'border-primary-500 bg-primary-500 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-surface-50'; ?>" data-filter-kind="type" data-filter-value="<?php echo esc_attr( $type_key ); ?>"><?php echo esc_html( $type_label ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="flex flex-wrap gap-2" data-hrm-room-filter-group="status">
				<?php foreach ( array( 'all' => __( 'All Conditions', 'hrm-pro' ), 'available' => __( 'Available', 'hrm-pro' ), 'occupied' => __( 'Occupied', 'hrm-pro' ), 'cleaning' => __( 'Cleaning', 'hrm-pro' ), 'maintenance' => __( 'Maintenance', 'hrm-pro' ) ) as $status_key => $status_label ) : ?>
					<button type="button" class="hrm-room-filter rounded-lg border px-3 py-2 text-sm font-semibold transition-colors <?php echo 'all' === $status_key ? 'border-primary-500 bg-primary-500 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-surface-50'; ?>" data-filter-kind="status" data-filter-value="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-card">
	<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
		<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Rooms', 'hrm-pro' ); ?></h3>
		<button type="button" class="hrm-refresh-room-grid inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-surface-50">
			<span class="iconify" data-icon="solar:refresh-linear"></span>
			<?php esc_html_e( 'Refresh', 'hrm-pro' ); ?>
		</button>
	</div>
	<div class="overflow-x-auto">
		<table class="w-full">
			<thead>
				<tr class="border-b border-slate-100 bg-surface-50">
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Room', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Type', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Rate', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></th>
					<th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e( 'Actions', 'hrm-pro' ); ?></th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-50">
				<?php if ( $rooms ) : ?>
					<?php foreach ( $rooms as $room ) : ?>
						<tr class="hrm-filterable-room transition-colors hover:bg-surface-50" data-room-type="<?php echo esc_attr( $room->room_type ); ?>" data-room-status="<?php echo esc_attr( $room->status ); ?>">
							<td class="px-6 py-4">
								<p class="text-sm font-semibold text-slate-900"><?php echo esc_html( $room->room_number ); ?></p>
								<p class="text-xs text-slate-500"><?php echo esc_html( sprintf( __( 'Floor %d · %d guests', 'hrm-pro' ), (int) $room->floor, (int) $room->max_guests ) ); ?></p>
							</td>
							<td class="px-6 py-4 text-sm text-slate-600"><?php echo esc_html( ucwords( str_replace( '_', ' ', $room->room_type ) ) ); ?></td>
							<td class="px-6 py-4 text-sm font-semibold text-slate-900"><?php echo esc_html( HRM_Settings::money( $room->price_per_night, $room->hotel_id ) ); ?></td>
							<td class="px-6 py-4">
								<select class="hrm-room-status-toggle rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700" data-room-id="<?php echo esc_attr( $room->id ); ?>" data-current-status="<?php echo esc_attr( $room->status ); ?>">
									<?php foreach ( array( 'available', 'occupied', 'cleaning', 'maintenance' ) as $status ) : ?>
										<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $room->status, $status ); ?>><?php echo esc_html( ucwords( $status ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td class="px-6 py-4">
								<div class="flex justify-end gap-2">
									<button type="button" class="hrm-edit-room inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-surface-50"
										data-room='<?php echo esc_attr( wp_json_encode( $room ) ); ?>'>
										<span class="iconify" data-icon="solar:pen-linear"></span>
									</button>
									<button type="button" class="hrm-delete-room inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600 transition-colors hover:bg-red-100" data-room-id="<?php echo esc_attr( $room->id ); ?>">
										<span class="iconify" data-icon="solar:trash-bin-minimalistic-linear"></span>
									</button>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500"><?php esc_html_e( 'No rooms have been added yet.', 'hrm-pro' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php if ( HRM_License::can( 'housekeeping' ) ) : ?>
<div class="mt-6 rounded-2xl bg-white shadow-card">
	<div class="border-b border-slate-100 px-6 py-4">
		<div class="flex items-center gap-3">
			<span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
				<span class="iconify text-2xl" data-icon="solar:star-rings-linear"></span>
			</span>
			<div>
				<h3 class="font-semibold text-slate-800"><?php esc_html_e( 'Housekeeping Mobile Updates', 'hrm-pro' ); ?></h3>
				<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Fast room state changes for staff working from a phone.', 'hrm-pro' ); ?></p>
			</div>
		</div>
	</div>
	<div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
		<?php if ( $rooms ) : ?>
			<?php foreach ( $rooms as $room ) : ?>
				<div class="hrm-filterable-room rounded-2xl border border-slate-100 bg-surface-50 p-4" data-room-type="<?php echo esc_attr( $room->room_type ); ?>" data-room-status="<?php echo esc_attr( $room->status ); ?>">
					<div class="flex items-center justify-between gap-3">
						<div>
							<p class="font-bold text-slate-900"><?php echo esc_html( $room->room_number ); ?></p>
							<p class="text-xs text-slate-500"><?php echo esc_html( ucwords( str_replace( '_', ' ', $room->room_type ) ) ); ?></p>
						</div>
						<span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600"><?php echo esc_html( ucwords( $room->status ) ); ?></span>
					</div>
					<div class="mt-4 grid grid-cols-3 gap-2">
						<button type="button" class="hrm-housekeeping-status inline-flex items-center justify-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-emerald-700 shadow-sm" data-room-id="<?php echo esc_attr( $room->id ); ?>" data-status="available">
							<span class="iconify mr-1" data-icon="solar:check-circle-linear"></span><?php esc_html_e( 'Clean', 'hrm-pro' ); ?>
						</button>
						<button type="button" class="hrm-housekeeping-status inline-flex items-center justify-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-amber-700 shadow-sm" data-room-id="<?php echo esc_attr( $room->id ); ?>" data-status="cleaning">
							<span class="iconify mr-1" data-icon="solar:star-rings-linear"></span><?php esc_html_e( 'Cleaning', 'hrm-pro' ); ?>
						</button>
						<button type="button" class="hrm-housekeeping-status inline-flex items-center justify-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm" data-room-id="<?php echo esc_attr( $room->id ); ?>" data-status="maintenance">
							<span class="iconify mr-1" data-icon="solar:widget-2-linear"></span><?php esc_html_e( 'Repair', 'hrm-pro' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="col-span-full rounded-xl bg-surface-50 px-4 py-8 text-center text-sm text-slate-500"><?php esc_html_e( 'No rooms are available for housekeeping updates.', 'hrm-pro' ); ?></div>
		<?php endif; ?>
	</div>
</div>
<?php else : ?>
	<div class="mt-6">
		<?php HRM_Admin::upgrade_prompt( __( 'Housekeeping updates', 'hrm-pro' ), HRM_License::minimum_plan_for_feature( 'housekeeping' ) ); ?>
	</div>
<?php endif; ?>

<div id="hrm-room-modal" class="fixed left-1/2 top-1/2 z-50 hidden w-full max-w-2xl -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-modal">
	<form class="hrm-ajax-form hrm-room-form" data-action="hrm_save_room">
		<?php wp_nonce_field( 'hrm_nonce', 'nonce' ); ?>
		<input type="hidden" name="room_id" value="">
		<div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
			<h3 class="hrm-room-modal-title text-lg font-semibold text-slate-800"><?php esc_html_e( 'Add Room', 'hrm-pro' ); ?></h3>
			<button type="button" onclick="closeModal('hrm-room-modal')" class="text-slate-400 transition-colors hover:text-slate-600"><span class="iconify text-2xl" data-icon="solar:close-circle-linear"></span></button>
		</div>
		<div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
			<div class="grid gap-4 md:grid-cols-2">
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Room Number', 'hrm-pro' ); ?></span>
					<input type="text" name="room_number" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
				</label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Room Type', 'hrm-pro' ); ?></span>
					<select name="room_type" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
						<?php foreach ( array( 'single', 'double', 'suite', 'deluxe', 'executive' ) as $type ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( ucwords( $type ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Floor', 'hrm-pro' ); ?></span><input type="number" name="floor" min="1" value="1" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
				<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Max Guests', 'hrm-pro' ); ?></span><input type="number" name="max_guests" min="1" value="2" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
				<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Nightly Rate', 'hrm-pro' ); ?></span><input type="number" name="price_per_night" min="0" step="0.01" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
				<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Weekend Rate', 'hrm-pro' ); ?></span><input type="number" name="weekend_rate" min="0" step="0.01" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
				<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Peak Rate', 'hrm-pro' ); ?></span><input type="number" name="peak_rate" min="0" step="0.01" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></label>
				<label class="space-y-1.5">
					<span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Status', 'hrm-pro' ); ?></span>
					<select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
						<?php foreach ( array( 'available', 'occupied', 'cleaning', 'maintenance' ) as $status ) : ?>
							<option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucwords( $status ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Description', 'hrm-pro' ); ?></span><textarea name="description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea></label>
			<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Amenities', 'hrm-pro' ); ?></span><textarea name="amenities" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'wifi, ac, tv', 'hrm-pro' ); ?>"></textarea></label>
			<label class="space-y-1.5"><span class="block text-sm font-medium text-slate-700"><?php esc_html_e( 'Image URLs', 'hrm-pro' ); ?></span><textarea name="image_urls" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="<?php esc_attr_e( 'One URL per line', 'hrm-pro' ); ?>"></textarea></label>
		</div>
		<div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
			<button type="button" onclick="closeModal('hrm-room-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"><?php esc_html_e( 'Cancel', 'hrm-pro' ); ?></button>
			<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-600">
				<span class="iconify" data-icon="solar:check-circle-linear"></span>
				<?php esc_html_e( 'Save Room', 'hrm-pro' ); ?>
			</button>
		</div>
	</form>
</div>
