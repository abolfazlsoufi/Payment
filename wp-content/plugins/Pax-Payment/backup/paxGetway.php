<?php
    /*
     * Plugin Name: درگاه پرداخت بیچون
     * Plugin URI:  http://Abanovin.ir/Plugins/payment-woocomerce-Plugin
     * Description: درگاه پرداخت اختصاصی
     * Version: 1.0
     * Author: Soufi.A
     * Author URI: http://Abanovin.ir
     */
    function woocommerce_pax_payment_init()
    {

        if (!class_exists('WC_Payment_Gateway'))
            return;
        add_filter('plugin_action_links', array('WC_Pax_Gateway', 'pep_wpwc_plugin_action_links'), 10, 2);

        class WC_Pax_Gateway extends WC_Payment_Gateway
        {
            public function __construct()
            {
                $this->id = 'pax_bwg';
                $this->method_title = 'درگاه پرداخت بیچون';
                $this->method_description = 'تنظیمات درگاه امن pax برای فروشگاه ووکامرس';
                $this->has_fields = false;
                $this->redirect_uri = WC()->api_request_url('WC_Pasargad_Gateway');

                $this->init_form_fields();
                $this->init_settings();

                $this -> title					= $this-> settings['title'];
                $this -> description			= $this-> settings['description'];
                $this -> merchantCode			= $this-> settings['merchantCode'];
                $this -> terminalCode			= $this-> settings['terminalCode'];
                $this -> redirect_page_id		= $this-> settings['redirect_page_id'];
                $this -> privateKey 			= $this-> settings['privateKey'];


                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('woocommerce_receipt_pax', array($this, 'receipt_page'));
                add_action('woocommerce_api_wc_pax_gateway', array($this, 'callback'));
            }

            function init_form_fields(){
                $this -> form_fields = array(
                    'enabled' => array(
                        'title' => 'فعال سازی/غیر فعال سازی :',
                        'type' => 'checkbox',
                        'label' => 'فعال سازی درگاه پرداخت بیچون',
                        'description' => 'برای امکان پرداخت کاربران از طریق این درگاه باید تیک فعال سازی زده شده باشد .',
                        'default' => 'no'),
                    'merchantCode' => array(
                        'title' => 'شماره پذیزنده :',
                        'type' => 'text',
                        'description' => 'شما میتوانید این کد را از بانک ارائه دهنده درگاه دریافت نمایید .'),
                    'terminalCode' => array(
                        'title' => 'شماره ترمینال :',
                        'type' => 'text',
                        'description' => 'شما میتوانید این کد را از بانک ارائه دهنده درگاه دریافت نمایید .'),
                    'privateKey' => array(
                        'title' => 'کد مخفی PrivateKey :',
                        'type' => 'textarea',
                        'description' => 'این کد در زمان دریافت درگاه توسط نرم‌افزاری از طرف بیچون تولید می‌شود .'),
                    'title' => array(
                        'title' => 'عنوان درگاه :',
                        'type'=> 'text',
                        'description' => 'این عنوان در سایت برای کاربر نمایش داده می شود .',
                        'default' => 'بیچون'),
                    'description' => array(
                        'title' => 'توضیحات درگاه :',
                        'type' => 'textarea',
                        'description' => 'این توضیحات در سایت، بعد از انتخاب درگاه توسط کاربر نمایش داده می شود .',
                        'default' => 'پرداخت وجه از طریق درگاه بیچون توسط تمام کارت های عضو شتاب .'),
                    'redirect_page_id' => array(
                        'title' => 'آدرس بازگشت',
                        'type' => 'select',
                        'options' => $this -> pax_bwg_get_pages('صفحه مورد نظر را انتخاب نمایید'),
                        'description' => "صفحه‌ای که در صورت پرداخت موفق نشان داده می‌شود را نشان دهید."),
                );
            }

            public function admin_options()
            {
                if ($this->enviroment == 'production' && get_option('woocommerce_force_ssl_checkout') == 'no' && $this->enabled == 'yes') {
                    echo '<div class="error"><p>' . sprintf(__('%s Pasargad Sandbox testing is disabled and can performe live transactions but the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woothemes'), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
                }

                echo '<h3>تنظیمات درگاه pax :</h3>';
                echo '<table class="form-table">';
                $this->generate_settings_html();
                echo '</table>';
            }

            function getToken($amount, $action, $invoiceNumber, $invoiceDate, $merchantCode, $terminalCode, $timestamp, $customerID, $redirectAddress, $currency, $CurrencyRegionCode, $storeId, $bankGatewayCode, $privateKey)
            {
                /** Convert To Array **/
                $data_array = array(
                    'amount' => number_format($amount, 2, '.', ''),
                    'action' => (int)$action,
                    'invoiceNumber' => $invoiceNumber,
                    'invoiceDate' => $invoiceDate,
                    'merchantCode' => (int)$merchantCode,
                    'terminalCode' => (int)$terminalCode,
                    'timestamp' => $timestamp,
                    'customerID' => strval($customerID),
                    'redirectAddress' => $redirectAddress,
                    'currency' => $currency,
                    'currencyRegionCode' => $CurrencyRegionCode,
                    'storeId' => $storeId,
                    'bankGatewayCode' => $bankGatewayCode
                );
                $data_string = json_encode($data_array);

                /** Create Sign **/
                $sign_openssl = '';
                openssl_sign($data_string, $sign_openssl, $privateKey, OPENSSL_ALGO_SHA1);
                $sign = base64_encode($sign_openssl);


                /** Get Token With Curl **/
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://paymentgateway.morsasw.local/PaymentGateway/GetToken',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($data_array),
                    CURLOPT_HTTPHEADER => array(
                        'sign:' . $sign,
                        'Content-Type:application/json'
                    ),
                ));

                $response = curl_exec($curl);
                curl_exec($curl);
                curl_close($curl);

                return $response;
            }

            function getOrder($tref = NULL)
            {
                if (isset($tref)) {
                    if (!function_exists('post2https') || !function_exists('makeXMLTree')) {
                        require_once("libraries/parser.php");
                    }

                    $fields = array('invoiceUID' => $tref);

                    $result = post2https($fields, 'https://paymentgateway.morsasw.local/PaymentGateway/CheckTransactionResult');
                    $array = makeXMLTree($result);

                    if ($array["resultObj"]["result"] == "True") {
                        return $array;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            function verifyOrder($invoiceNumber, $invoiceDate, $merchantCode, $terminalCode, $timestamp, $privateKey)
            {
                /*            {
                "amount": 1000000.12,
                "invoiceDate": "2021/01/01",
                "invoiceNumber": "123456",
                "merchantCode": 111,
                "terminalCode": 222,
                "timeStamp": "2021/11/12 12:11:10"
                }*/


                session_start();
                $amount = $_SESSION['paxAmount'];

                /** Convert To Array **/
                $data_array = array(
                    'amount' => number_format($amount, 2, '.', ''),
                    'invoiceDate' => $invoiceDate,
                    'invoiceNumber' => $invoiceNumber,
                    'merchantCode' => (int)$merchantCode,
                    'terminalCode' => (int)$terminalCode,
                    'timestamp' => $timestamp,
                );
                $data_string = json_encode($data_array);

                /** Create Sign **/
                $sign_openssl = '';
                openssl_sign($data_string, $sign_openssl, $privateKey, OPENSSL_ALGO_SHA1);
                $sign = base64_encode($sign_openssl);

                /** Get Verify With Curl **/
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://paymentgateway.morsasw.local/PaymentGateway/GetToken',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($data_array),
                    CURLOPT_HTTPHEADER => array(
                        'sign:' . $sign,
                        'Content-Type:application/json'
                    ),
                ));

                $response = curl_exec($curl);
                curl_exec($curl);
                curl_close($curl);

                echo $response;

            }

        }
        function woocommerce_add_pax_gateway_method($methods)
        {
            $methods[] = 'WC_Pasargad_Gateway';
            return $methods;
        }

        add_filter('woocommerce_payment_gateways', 'woocommerce_add_pax_gateway_method');
    }
    add_action('plugins_loaded', 'woocommerce_pax_payment_init', 0);
