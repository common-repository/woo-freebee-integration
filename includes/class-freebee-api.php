<?php
/**
 * Class Freebee_Api
 * Use for communication with Freebee API
 *
 */

class Freebee_Il_API {

    private $api_server  = 'https://api.jamam.pl';
    private $api_key;
    private $authorization_token;

    public function __construct() {
        $this->api_key = get_option( 'freebee_il_auth_code' );
        $this->get_authorization_token_from_api();
    }

    public function api_request( $end_point = array(), $data = array(), $header_authorization = '', $request_type = 'POST' ) {
        $curl_url = $this->api_server . '/' . ( !empty( $end_point ) ? implode('/', $end_point ) : '' );
        $header = array(
            'Content-Type: application/vnd.api+json',
            $header_authorization
        );

        $ch = curl_init( $curl_url );

        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_type );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );

        $result = curl_exec( $ch );
        $result = json_decode($result, true);
        $curl_info = curl_getinfo($ch);

        curl_close($ch);

        if ( !in_array( $curl_info['http_code'],  array( 200, 201, 204 ) ) ) {
            //errors
            $log = array(
                'date' => date('Y-m-d H:i:s'),
                'curl_url' => $curl_url,
                'request_type' => $request_type,
                'curl_info' => $curl_info,
                'result' => $result
            );

            $this->log_request( $log );
            return false;
        }

        if ( in_array( $curl_info['http_code'],  array( 201, 204 ) ) ) {
            //ok
            $log = array(
                'date' => date('Y-m-d H:i:s'),
                'curl_url' => $curl_url,
                'request_type' => $request_type,
                'curl_info' => $curl_info,
                'result' => $result
            );

            $this->log_request( $log, 'ok' );
        }

        return $result;
    }

    private function get_authorization_token_from_api() {
        $api_key = array( "api_key"=> $this->api_key );
        $request = $this->api_request( array( 'transactions_receiver', 'auth' ), $api_key );

        if( $request ) {
            $this->authorization_token = $request['token'];
        }
    }

    public function get_authorization_token() {
        return $this->authorization_token;
    }

    public function add_transaction( $transaction_data ) {
        $header_authorization = 'Authorization:Bearer ' . $this->authorization_token;
        $request = $this->api_request( array( 'transactions_receiver', 'transactions' ), $transaction_data, $header_authorization );

        return $request;
    }

    public function mark_as_paid_transaction( $transaction_id ) {
        $header_authorization = 'Authorization:Bearer ' . $this->authorization_token;
        $transaction_data = array();
        $request = $this->api_request( array( 'transactions_receiver', 'transactions', $transaction_id, 'mark_as_paid' ), $transaction_data, $header_authorization );

        return $request;
    }

    public function modify_transaction( $transaction_data, $transaction_id, $request_type = 'PATCH' ) {
        $header_authorization = 'Authorization:Bearer ' . $this->authorization_token;
        $request = $this->api_request( array( 'transactions_receiver', 'transactions', $transaction_id ), $transaction_data, $header_authorization, $request_type );

        return $request;
    }

    public function log_request($log, $log_type = '') {
        if( $log_type == 'ok' ){
            $file_log_name = 'freebee_il_ok.log';
        } else {
            $file_log_name = 'freebee_il_error.log';
        }

        $uploads_dir = wp_upload_dir();
        $log_dir = $uploads_dir['basedir'] .'/woocommerce-freebee-integration/';
        $log_file = $log_dir . $file_log_name;

        if ( file_exists( $log_dir ) ) {
            if ( file_exists( $log_file ) ) {
                $open_mode = 'a';
            } else {
                $open_mode = 'w+';
            }

            $fp = fopen( $log_file, $open_mode );
            fwrite( $fp, json_encode($log). PHP_EOL );
            fclose( $fp );
        }
    }
}
?>