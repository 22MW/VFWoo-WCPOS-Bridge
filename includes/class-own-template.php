<?php
/**
 * Ticket template owned by the bridge.
 *
 * With the option on, the POS script replaces the Webkul template with this one when it prints.
 * Every shop datum comes from VFWoo, so the ticket matches the PDF. With the option off nothing
 * changes: the Webkul templates keep working and may use the ${vfwoo_*} variables.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Own_Template {
	public const OPTION = 'vfwoo_webkul_own_template';

	/**
	 * The template needs VFWoo: it is made of VFWoo variables.
	 */
	public static function enabled(): bool {
		return '1' === get_option( self::OPTION, '0' ) && Requirements::vfwoo_active();
	}

	/**
	 * Template HTML. Webkul evaluates it as a JavaScript template literal, so the text we insert
	 * must not carry backslashes, backticks or ${ sequences.
	 */
	public static function html(): string {
		$labels = array(
			'order'    => __( 'Pedido', 'vfwoo-webkul-pos-bridge' ),
			'date'     => __( 'Fecha', 'vfwoo-webkul-pos-bridge' ),
			'cashier'  => __( 'Cajero', 'vfwoo-webkul-pos-bridge' ),
			'invoice'  => __( 'Factura', 'vfwoo-webkul-pos-bridge' ),
			'issued'   => __( 'Expedida', 'vfwoo-webkul-pos-bridge' ),
			'customer' => __( 'Cliente', 'vfwoo-webkul-pos-bridge' ),
			'nif'      => __( 'NIF', 'vfwoo-webkul-pos-bridge' ),
			'address'  => __( 'Domicilio', 'vfwoo-webkul-pos-bridge' ),
			'tel'      => __( 'Tel.', 'vfwoo-webkul-pos-bridge' ),
			'product'  => __( 'Producto', 'vfwoo-webkul-pos-bridge' ),
			'price'    => __( 'Precio', 'vfwoo-webkul-pos-bridge' ),
			'qty'      => __( 'Cant.', 'vfwoo-webkul-pos-bridge' ),
			'total'    => __( 'Total', 'vfwoo-webkul-pos-bridge' ),
			'subtotal' => __( 'Subtotal', 'vfwoo-webkul-pos-bridge' ),
			'discount' => __( 'Descuento', 'vfwoo-webkul-pos-bridge' ),
			'cash'     => __( 'Efectivo', 'vfwoo-webkul-pos-bridge' ),
			'change'   => __( 'Cambio', 'vfwoo-webkul-pos-bridge' ),
			'note'     => __( 'Nota', 'vfwoo-webkul-pos-bridge' ),
		);

		$replace = array();
		foreach ( $labels as $key => $label ) {
			$replace[ '{{' . $key . '}}' ] = self::literal_safe( esc_html( $label ) );
		}

		return strtr( self::markup(), $replace );
	}

	private static function literal_safe( string $value ): string {
		return str_replace( array( '\\', '`', '${' ), array( '\\\\', '\\`', '\\${' ), $value );
	}

	/**
	 * Same structure and CSS classes as the Webkul default template, so the order lines and the
	 * totals print as usual. The CSS travels inside the template, as Webkul's does: the ticket is
	 * rendered in a print context that does not load the admin or POS stylesheets.
	 */
	private static function markup(): string {
		return <<<'HTML'
<style>
.wkwcpos-invoice-wrapper { padding: 10px; background-color: #fff; border-radius: 2px; grid-area: second; }
.wkwcpos-invoice-wrapper * { padding: 0; margin: 0; }
.wkwcpos-invoice-wrapper .invoice-header, .wkwcpos-invoice-wrapper .invoice-footer .footer-details { text-align: center; }
.wkwcpos-invoice-wrapper .invoice-header img { margin: 6px 0; }
.wkwcpos-invoice-wrapper .invoice-header h3 { margin: 4px 0; }
.wkwcpos-invoice-wrapper .invoice-details { margin: 10px 0 0; padding-top: 6px; }
.wkwcpos-invoice-wrapper .product-details { margin: 12px 0; }
.wkwcpos-invoice-wrapper .product-details table { border-collapse: collapse; width: 100%; table-layout: fixed; }
.wkwcpos-invoice-wrapper .product-details table th, .wkwcpos-invoice-wrapper .product-details table td { padding: 3px 0; vertical-align: middle; }
.wkwcpos-invoice-wrapper .product-details table th, .wkwcpos-invoice-wrapper .product-details table td p { padding: 3px 0; vertical-align: middle; }
.wkwcpos-invoice-wrapper .product-details .wkwcpos-invoice-totals { display: block; width: 100%; }
.wkwcpos-invoice-wrapper .product-details .wkwcpos-invoice-totals tbody { display: block; }
.wkwcpos-invoice-wrapper .product-details .wkwcpos-invoice-totals tr { display: flex; width: 100%; }
.wkwcpos-invoice-wrapper .product-details .wkwcpos-invoice-totals td { display: block; box-sizing: border-box; min-width: 0; overflow-wrap: break-word; word-break: break-word; }
.wkwcpos-invoice-wrapper .product-details .wkwcpos-invoice-totals td:nth-child(1) { width: 25%; }
.wkwcpos-invoice-wrapper .product-details .wkwcpos-invoice-totals td:nth-child(2) { width: 20%; }
.wkwcpos-invoice-wrapper .product-details .wkwcpos-invoice-totals td:nth-child(3) { width: 30%; }
.wkwcpos-invoice-wrapper .product-details .wkwcpos-invoice-totals td:nth-child(4) { width: 25%; }
.dashed-wrap { border-top: 3px dashed #ddd; border-bottom: 3px dashed #ddd; }
.dashed-top { border-top: 3px dashed #ddd; }
.dashed-bottom { border-bottom: 3px dashed #ddd; }
.wkwcpos-invoice-wrapper hr { width: 35%; margin: 10px auto 7px; border-style: dashed; border-width: 3px 0; border-top-color: #ddd; border-bottom-color: #fafafa; }
</style>

<div class="wkwcpos-invoice-wrapper">

	<div class="invoice-header">
		<div>${vfwoo_company_logo}</div>
		<h3>${vfwoo_company_name}</h3>
		<p class="vfwoo-hide-empty">${vfwoo_company_address}</p>
		<p class="vfwoo-hide-empty">{{nif}}: ${vfwoo_company_nif}</p>
		<p class="vfwoo-hide-empty">{{tel}} ${vfwoo_company_phone}</p>
		<p class="vfwoo-hide-empty">${vfwoo_company_email}</p>
	</div>

	<div class="invoice-details dashed-top">
		<p class="vfwoo-hide-empty"><strong>{{invoice}} ${vfwoo_invoice_number}</strong> (${vfwoo_invoice_type})</p>
		<p class="vfwoo-hide-empty">{{issued}}: ${vfwoo_invoice_date}</p>
		<p>{{order}} - ${order_id}</p>
		<p>{{date}}: ${order_date}</p>
		<p>{{cashier}}: ${cashier_name}</p>
		<p class="vfwoo-hide-empty">{{customer}}: ${vfwoo_customer_name}</p>
		<p class="vfwoo-hide-empty">{{nif}}: ${vfwoo_customer_nif}</p>
		<p class="vfwoo-hide-empty">{{address}}: ${vfwoo_customer_address}</p>
	</div>

	<div class="product-details">
		<table>
			<tbody class="products-thead">
				<tr>
					<th class="dashed-wrap" style="width:40%;text-align:left;">{{product}}</th>
					<th class="dashed-wrap" style="width:20%;text-align:center;">{{price}}</th>
					<th class="dashed-wrap" style="width:20%;text-align:center;">{{qty}}</th>
					<th class="dashed-wrap" style="width:20%;text-align:right;">{{total}}</th>
				</tr>
			</tbody>
			<tbody>
				<tr>
					<td colspan="4" class="order_products_data dashed-bottom">${order_products_data}</td>
				</tr>
			</tbody>
			<tbody>
				<tr>
					<td colspan="4" class="order_totals_data" style="padding:0;">
						<table class="wkwcpos-invoice-totals">
							<tbody>
								<tr>
									<td></td>
									<td></td>
									<td>{{subtotal}}</td>
									<td>${sub_total}</td>
								</tr>
								<tr>
									<td></td>
									<td></td>
									<td>${coupon_name}</td>
									<td>${coupon_amount}</td>
								</tr>
								<tr>
									<td></td>
									<td></td>
									<td>${tax_title}</td>
									<td>${order_tax}</td>
								</tr>
								<tr>
									<td></td>
									<td></td>
									<td>{{discount}}</td>
									<td>${order_discount}</td>
								</tr>
								${fee_title ? `<tr>
									<td></td>
									<td></td>
									<td>${fee_title}</td>
									<td>${fee_amount}</td>
								</tr>` : ''}
							</tbody>
							<tbody>
								<tr>
									<td></td>
									<td></td>
									<td><strong>{{total}}</strong></td>
									<td><strong>${order_total}</strong></td>
								</tr>
								<tr>
									<td></td>
									<td></td>
									<td>{{cash}}</td>
									<td>${cashpay_amount}</td>
								</tr>
								<tr>
									<td></td>
									<td></td>
									<td>${other_payment_text}</td>
									<td>${otherpay_amount}</td>
								</tr>
								<tr>
									<td></td>
									<td></td>
									<td>{{change}}</td>
									<td>${order_change}</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="invoice-footer dashed-top">
		<p>{{note}}: ${cashier_note}</p>
		<div class="footer-details">
			<div>${vfwoo_qr}</div>
			<p class="vfwoo-hide-empty" style="font-size:10px;">${vfwoo_legal_legend}</p>
			<p class="vfwoo-hide-empty">${vfwoo_pdf_footer}</p>
		</div>
	</div>
</div>
HTML;
	}
}
