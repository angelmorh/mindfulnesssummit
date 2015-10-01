<?php
	/*	
	*	Goodlayers Function File
	*	---------------------------------------------------------------------
	*	This file include all of important function and features of the theme
	*	---------------------------------------------------------------------
	*/	
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

/**
 * This filter will bypass the Cart page when a product is added to cart and
 * send the customer directly to checkout.
 */
add_filter ('woocommerce_add_to_cart_redirect', 'woo_redirect_to_checkout');
function woo_redirect_to_checkout() {
	$checkout_url = WC()->cart->get_checkout_url();

	return $checkout_url;
}

/**
 * Remove the Coupon form from the Checkout page
 */
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

/**
 * Remove fields from the checkout page
 */
add_filter( 'woocommerce_checkout_fields' , 'mindsummit_custom_override_checkout_fields' );
function mindsummit_custom_override_checkout_fields( $fields ) {


	unset($fields['billing']['billing_company']);
	unset($fields['billing']['billing_address_1']);
	unset($fields['billing']['billing_address_2']);
	unset($fields['billing']['billing_city']);
	unset($fields['billing']['billing_postcode']);
	unset($fields['billing']['billing_country']);
	unset($fields['billing']['billing_state']);
	unset($fields['billing']['billing_phone']);
	unset($fields['order']['order_comments']);

	return $fields;
}

/**
 * Remove the "Product has been added to cart" message
 */
add_filter( 'wc_add_to_cart_message', 'mindsummit_remove_add_to_cart_message' );
function mindsummit_remove_add_to_cart_message( $message, $product_id ){
	return '';
}

/**
 * Auto Complete all WooCommerce orders.
 */
add_action( 'woocommerce_thankyou', 'custom_woocommerce_auto_complete_order' );
function custom_woocommerce_auto_complete_order( $order_id ) {
	if ( ! $order_id ) {
		return;
	}

	$order = wc_get_order( $order_id );
	$order->update_status( 'completed' );
}

/**
 * Remove the address from the Complete Order email
 */
remove_action( 'woocommerce_email_customer_details', array( 'WC_Emails', 'email_addresses' ), 20, 3 );