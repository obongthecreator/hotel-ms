<?php
/**
 * Printable guest profile export.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$guest    = isset( $profile['guest'] ) && is_array( $profile['guest'] ) ? $profile['guest'] : array();
$bookings = isset( $profile['bookings'] ) && is_array( $profile['bookings'] ) ? $profile['bookings'] : array();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( sprintf( __( 'Guest Profile - %s', 'hrm-pro' ), isset( $guest['full_name'] ) ? $guest['full_name'] : '' ) ); ?></title>
	<style>
		body { margin: 0; padding: 32px; color: #111; background: #fff; font-family: Inter, Arial, sans-serif; }
		.header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 3px solid #987CC0; padding-bottom: 18px; }
		h1 { margin: 0; font-size: 28px; }
		h2 { margin: 28px 0 12px; font-size: 16px; text-transform: uppercase; letter-spacing: .08em; color: #987CC0; }
		.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 20px; }
		.card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; }
		.label { margin: 0 0 4px; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; }
		.value { margin: 0; font-weight: 700; }
		table { width: 100%; border-collapse: collapse; margin-top: 10px; }
		th, td { border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: left; font-size: 13px; }
		th { background: #f8f5fc; color: #111; font-size: 11px; text-transform: uppercase; }
		.actions { margin-top: 24px; }
		button { border: 0; border-radius: 10px; background: #987CC0; color: #fff; cursor: pointer; font-weight: 700; padding: 12px 16px; }
		@media print { .actions { display: none; } body { padding: 0; } }
	</style>
</head>
<body>
	<header class="header">
		<div>
			<h1><?php echo esc_html( isset( $guest['full_name'] ) ? $guest['full_name'] : __( 'Guest Profile', 'hrm-pro' ) ); ?></h1>
			<p><?php echo esc_html( isset( $guest['phone'] ) ? $guest['phone'] : '' ); ?><?php echo ! empty( $guest['email'] ) ? esc_html( ' - ' . $guest['email'] ) : ''; ?></p>
		</div>
		<div>
			<p class="label"><?php esc_html_e( 'Hotel', 'hrm-pro' ); ?></p>
			<p class="value"><?php echo esc_html( $hotel->hotel_name ); ?></p>
		</div>
	</header>

	<section class="grid">
		<div class="card"><p class="label"><?php esc_html_e( 'Flag', 'hrm-pro' ); ?></p><p class="value"><?php echo esc_html( isset( $guest['flag'] ) ? ucwords( $guest['flag'] ) : __( 'None', 'hrm-pro' ) ); ?></p></div>
		<div class="card"><p class="label"><?php esc_html_e( 'Total Spent', 'hrm-pro' ); ?></p><p class="value"><?php echo esc_html( isset( $guest['total_spent'] ) ? $guest['total_spent'] : HRM_Settings::money( 0, (int) $hotel->id ) ); ?></p></div>
		<div class="card"><p class="label"><?php esc_html_e( 'Last Stay', 'hrm-pro' ); ?></p><p class="value"><?php echo esc_html( isset( $guest['last_stay'] ) ? $guest['last_stay'] : __( 'No stays yet', 'hrm-pro' ) ); ?></p></div>
		<div class="card"><p class="label"><?php esc_html_e( 'ID Type', 'hrm-pro' ); ?></p><p class="value"><?php echo esc_html( isset( $guest['id_type'] ) ? $guest['id_type'] : '' ); ?></p></div>
		<div class="card"><p class="label"><?php esc_html_e( 'ID Number', 'hrm-pro' ); ?></p><p class="value"><?php echo esc_html( isset( $guest['id_number'] ) ? $guest['id_number'] : '' ); ?></p></div>
		<div class="card"><p class="label"><?php esc_html_e( 'Created', 'hrm-pro' ); ?></p><p class="value"><?php echo esc_html( isset( $guest['created_at'] ) ? $guest['created_at'] : '' ); ?></p></div>
	</section>

	<h2><?php esc_html_e( 'Notes', 'hrm-pro' ); ?></h2>
	<p><?php echo esc_html( isset( $guest['notes'] ) ? $guest['notes'] : '' ); ?></p>

	<h2><?php esc_html_e( 'Stay History', 'hrm-pro' ); ?></h2>
	<table>
		<thead>
			<tr>
				<th><?php esc_html_e( 'Ref', 'hrm-pro' ); ?></th>
				<th><?php esc_html_e( 'Room', 'hrm-pro' ); ?></th>
				<th><?php esc_html_e( 'Dates', 'hrm-pro' ); ?></th>
				<th><?php esc_html_e( 'Status', 'hrm-pro' ); ?></th>
				<th><?php esc_html_e( 'Payment', 'hrm-pro' ); ?></th>
				<th><?php esc_html_e( 'Total', 'hrm-pro' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $bookings ) : ?>
				<?php foreach ( $bookings as $booking ) : ?>
					<tr>
						<td><?php echo esc_html( $booking['ref'] ); ?></td>
						<td><?php echo esc_html( $booking['room'] ); ?></td>
						<td><?php echo esc_html( $booking['dates'] ); ?></td>
						<td><?php echo esc_html( $booking['status'] ); ?></td>
						<td><?php echo esc_html( $booking['payment_status'] ); ?></td>
						<td><?php echo esc_html( $booking['total'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No stay history yet.', 'hrm-pro' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>

	<div class="actions">
		<button type="button" onclick="window.print()"><?php esc_html_e( 'Print or Save as PDF', 'hrm-pro' ); ?></button>
	</div>
</body>
</html>
