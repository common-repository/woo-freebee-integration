<?php
/**
 * Class Freebee_Core
 * Core function in this plugin
 *
 */

class Freebee_Il_WC_API_Connector {
    private $api;
    private $accepted_order_currency = 'PLN';

    public function __construct() {
        $this->init_core_hooks();
    }

    private function init_core_hooks() {
        add_action( 'woocommerce_thankyou', array( $this, 'check_transaction_to_add' ) );

        add_action( 'woocommerce_order_status_cancelled', array( $this, 'delete_transaction' ) );
        add_action( 'woocommerce_order_status_failed', array( $this, 'delete_transaction' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'mark_as_paid_or_add' ) );

        add_action( 'woocommerce_order_status_on-hold', array( $this, 'check_transaction_to_add' ) );
        add_action( 'woocommerce_order_status_refunded', array( $this, 'check_transaction_to_add' ) );
        add_action( 'woocommerce_order_status_pending', array( $this, 'check_transaction_to_add' ) );
        add_action( 'woocommerce_order_status_processing', array( $this, 'check_transaction_to_add' ) );

        add_action( 'woocommerce_pre_payment_complete', array( $this, 'check_transaction_to_add' ) );
        add_action( 'woocommerce_payment_complete', array( $this, 'mark_as_paid' ) );

        add_action( 'wp_insert_post', array( $this, 'prepare_edit_transaction' ), 10, 3 );
        add_action( 'before_delete_post', array( $this, 'delete_order' ) );
    }

    public function add_transaction( $order_id ) {

        $seller_id = get_option( 'freebee_il_client_id' );
        $card_number = get_post_meta( $order_id, 'freebee_il_customer_card_number', true );

        if( empty( $seller_id ) || empty( $card_number ) ){
            return;
        }

        $contractor_transaction_id = $seller_id . '_' . $order_id . '_' . time();
        $order = wc_get_order( $order_id );

        global $woocommerce;

        if( version_compare( $woocommerce->version, '3.0.0', '>=' )){
            $order_currency = $order->get_currency();
            $order_date_completed = $order->get_date_completed();
            $order_date_created = $order->get_date_created();
            $order_date_completed_iso =  current_time( 'c' );
            $order_date_created_iso = $order_date_created->date( 'c' );
            $products_data = $this->get_products_data( $order );

        } else {
            $order_currency = $order->get_order_currency();
            $order_date_completed_iso = current_time( 'c' );
            $order_date_created_iso = date( 'c', strtotime( $order->order_date ) );
            $products_data = $this->get_products_data_old_wc( $order );
        }

        if( $order_currency != $this->accepted_order_currency ) {
            return;
        }

        $total_gross_value = $this->price_format( $order->get_total() );
        $total_vat_value = $this->price_format( $order->get_total_tax() );
        $total_net_value = $total_gross_value - $total_vat_value;

        //add transaction to api
        $transaction_data = (object) array(
            //shop id
            'mpk_number' => $seller_id,
            //participant's card number
            'card_number'=> strval( $card_number ),
            'contractor_transaction_id' => $contractor_transaction_id,
            'transaction_datetime' => $order_date_completed_iso,
            'transaction_order_datetime' => $order_date_created_iso,
            'total_gross_value' => $total_gross_value,
            'total_vat_value' => $total_vat_value,
            'total_net_value' => $total_net_value,
            'policy_number' => strval( $order_id ),
            'seller_id' => '',
            'pos_id' => '',
            'products' => $products_data
        );

        $this->api = new Freebee_Il_API();
        $freebee_transaction_id = $this->api->add_transaction( $transaction_data );

        //save transaction id to database - order meta
        if( $freebee_transaction_id ) {
            add_post_meta( $order_id, 'freebee_il_transaction_id', $freebee_transaction_id['transaction_id'], true);

            if( get_post_meta( $order_id, 'freebee_il_transaction_error', true ) ) {
                delete_post_meta( $order_id, 'freebee_il_transaction_error' );
            }
        } else {
            add_post_meta( $order_id, 'freebee_il_transaction_error', __('Error in adding transaction', 'freebee-il'), true);
        }
    }

    public function check_transaction_to_add( $order_id ) {
        $transaction_id = get_post_meta( $order_id, 'freebee_il_transaction_id', true );
        if( !empty( $transaction_id ) ){
            return;
        }

        $this->add_transaction( $order_id );
    }

    public function mark_as_paid_or_add( $order_id ) {
        $transaction_id = get_post_meta( $order_id, 'freebee_il_transaction_id', true );
        if( !empty( $transaction_id ) ){
            $this->mark_as_paid( $order_id );
        } else {
            $this->add_transaction( $order_id );
            $this->mark_as_paid( $order_id );
        }
    }

    public function mark_as_paid( $order_id ) {
        $transaction_id = get_post_meta( $order_id, 'freebee_il_transaction_id', true );

        if( empty( $transaction_id ) ){
            return;
        }

        $this->api = new Freebee_Il_API();
        $this->api->mark_as_paid_transaction( $transaction_id );
    }

    public function prepare_edit_transaction( $order_id, $post, $update ) {
        $post_type = get_post_type( $order_id );

        if( $post_type == 'shop_order' ){
            $this->edit_transaction( $order_id );
        }
    }

    public function edit_transaction( $order_id ) {
        $transaction_id = get_post_meta( $order_id, 'freebee_il_transaction_id', true );
        if( empty( $transaction_id ) ){
            return;
        }

        $order = wc_get_order( $order_id );
        $order_status = $order->get_status();

        $seller_id = get_option( 'freebee_il_client_id' );
        $card_number = get_post_meta( $order_id, 'freebee_il_customer_card_number', true );

        if( empty( $seller_id ) || empty( $card_number ) ){
            return;
        }

        $contractor_transaction_id = $seller_id . '_' . $order_id;

        global $woocommerce;

        if( version_compare( $woocommerce->version, '3.0.0', '>=' )){
            $order_currency = $order->get_currency();
            $order_date_created = $order->get_date_created();
            $order_date_created_iso = $order_date_created->date( 'c' );

            if( $order_status == 'completed' ){
                $order_date_completed = $order->get_date_completed();
                $order_date_completed_iso = $order_date_completed->date( 'c' );
            } else {
                $order_date_completed = $order_date_created;
                $order_date_completed_iso = $order_date_created_iso;
            }

            $products_data = $this->get_products_data( $order );
        } else {
            $order_currency = $order->get_order_currency();
            $order_date_created_iso = date( 'c', strtotime( $order->order_date ) );
            if( $order_status == 'completed' ){
                $order_date_completed_iso = date( 'c', strtotime( get_post_meta( $order_id, '_completed_date', true ) ) );
            } else {
                $order_date_completed_iso = $order_date_created_iso;
            }
            $products_data = $this->get_products_data_old_wc( $order );
        }

        if( $order_currency != $this->accepted_order_currency ) {
            return;
        }

        $total_gross_value = $this->price_format( $order->get_total() );
        $total_vat_value = $this->price_format( $order->get_total_tax() );
        $total_net_value = $total_gross_value - $total_vat_value;

        //checked order refund
        $total_refunded = $this->price_format( $order->get_total_refunded() );
        if( !empty( $total_refunded ) ){
            $total_gross_value = $total_gross_value - $total_refunded;
            $total_net_value = $total_gross_value - $total_vat_value;
        }

        //add transaction to api
        $transaction_data = (object) array(
            //shop id
            'mpk_number' => $seller_id,
            //participant's card number
            'card_number'=> strval( $card_number ),
            'contractor_transaction_id' => $contractor_transaction_id,
            'transaction_datetime' => $order_date_completed_iso,
            'transaction_order_datetime' => $order_date_created_iso,
            'total_gross_value' => $total_gross_value,
            'total_vat_value' => $total_vat_value,
            'total_net_value' => $total_net_value,
            'policy_number' => strval( $order_id ),
            'seller_id' => '',
            'pos_id' => '',
            'products' => $products_data
        );

        $this->api = new Freebee_Il_API();
        $this->api->modify_transaction( $transaction_data, $transaction_id );
        $this->api->log_request( $transaction_data, 'ok' );
    }

    public function delete_transaction( $order_id ) {
        $transaction_id = get_post_meta( $order_id, 'freebee_il_transaction_id', true );

        if( empty( $transaction_id ) ) {
            return;
        }

        $this->api = new Freebee_Il_API();
        $transaction_data = array();
        $results = $this->api->modify_transaction( $transaction_data, $transaction_id, 'DELETE' );

        if( $results !== false ) {
            //delete transaction id from database - order meta
            delete_post_meta( $order_id, 'freebee_il_transaction_id' );
        }
    }

    public function delete_order( $order_id ) {
        global $post_type;

        if( $post_type !== 'shop_order' ) {
            return;
        }

        $this->delete_transaction( $order_id );
    }

    private function get_products_data ( $order ) {
        $products_data = array();

        foreach( $order->get_items() as $item_key => $item_values ) {
            $item_data = $item_values->get_data();

            $products_data[] = (object) array (
                'product_number' => strval( $item_key ),
                'quantity' => $item_data['quantity'],
                'gross_value' => 0,
                'unit' => 'pcs',
                'excluded' => false
            );
        }

        $order_data = $order->get_data();
        $shipping_lines = $order_data['shipping_lines'];

        reset($shipping_lines);
        $shipping_id = key($shipping_lines);
        $shipping_total = $order->get_total_shipping();

        //shipping
        $products_data[] = (object) array (
            'product_number' => strval( $shipping_id ),
            'quantity' => 1,
            'gross_value' => 0,
            'unit' => 'pcs',
            'excluded' => false
        );

        return $products_data;
    }

    private function get_products_data_old_wc ( $order ) {
        $products_data = array();
        $product_number = 0;

        foreach( $order->get_items() as $item_key => $item_values ) {

            $products_data[] = (object) array (
                //product_number = order_item_id
                'product_number' => strval( $item_key ),
                'quantity' => intval( $item_values['qty'] ),
                'gross_value' => 0,
                'unit' => 'pcs',
                'excluded' => false
            );

            $product_number =  $item_key;
        }

        //shipping
        $products_data[] = (object) array (
            'product_number' => strval( $product_number + 1 ),
            'quantity' => 1,
            'gross_value' => 0,
            'unit' => 'pcs',
            'excluded' => false
        );

        return $products_data;
    }

    private function price_format ( $price ) {
        return $price * 100;
    }
}