<?php
    /*
     * Plugin Name: درگاه پرداخت بیچون
     * Plugin URI:  http://Abanovin.ir/Plugins/payment-woocomerce-Plugin
     * Description: درگاه پرداخت اختصاصی
     * Version: 1.0
     * Author: Soufi.A
     * Author URI: http://Abanovin.ir
     */

    //add_action('plugins_loaded', 'pax_bwg', 0);

    function pax_bwg() {
        if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

        class pax_bwg_full_payment extends WC_Payment_Gateway {

            public function __construct(){

                $this -> id 			 	 = 'pax_bwg';
                $this -> method_title 	  	 = 'درگاه پرداخت بیچون';
                //$this->icon 				 = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.png';
                $this -> has_fields 	   	 = false;
                $this -> init_form_fields();
                $this -> init_settings();

                $this -> title					= $this-> settings['title'];
                $this -> description			= $this-> settings['description'];
                $this -> merchantCode			= $this-> settings['merchantCode'];
                $this -> terminalCode			= $this-> settings['terminalCode'];
                $this -> redirect_page_id		= $this-> settings['redirect_page_id'];
                $this -> privateKey 			= $this-> settings['privateKey'];

                $this -> msg['pax_bwg_message'] = "";
                $this -> msg['pax_bwg_class'] = "";

                add_action('woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'pax_bwg_check_response' ) );

                if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                    add_action( 'woocommerce_update_options_payment_gateways_pax_bwg', array( &$this, 'process_admin_options' ) );
                } else {
                    add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
                }

                add_action('woocommerce_receipt_pax_bwg', array(&$this, 'pax_bwg_receipt_page'));
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

            public function admin_options(){
                echo '<h3>درگاه پرداخت بیچون</h3>';
                echo '<table class="form-table">';
                echo
                    // IRR
                    // IRT
                $this -> generate_settings_html();
                echo '</table>';
                echo '                                 
                    <script>
                        // Set Key To Text For Human To Understand
                        key = document.getElementById("woocommerce_pax_bwg_privateKey").value;
                        document.getElementById("woocommerce_pax_bwg_privateKey").value = decodeURI(key);				
                
                        // Perform URL endoce to WP wont filter XML characters
                        var ele = document.getElementById("mainform");
                        if(ele.addEventListener){
                            ele.addEventListener("submit", keyMaker, false);  //Modern browsers
                        }else if(ele.attachEvent){
                            ele.attachEvent("onsubmit", keyMaker);            //Old IE
                        }
                
                        function keyMaker()
                        {
                            key = document.getElementById("woocommerce_pax_bwg_privateKey").value;
                            document.getElementById("woocommerce_pax_bwg_privateKey").value = encodeURI(key);
                        }
                    </script>
                ';
            }

            //Original Code
            function pax_bwg_receipt_page($order_id){
                if (!class_exists('PaxBank_GateWay')) {
                    require_once("paxGetway.php"); // Add Pax class To Plugin
                }

                global $woocommerce;

                $order                  = new WC_Order($order_id);
                $Invoice_Number         = $order->get_order_number();
                $Invoice_Date           = $order->get_date_modified()->date('Y/m/d');
                $Customer_Id            = $order->get_billing_phone();
                $TimeStamp              = $order->get_date_created()->date('Y/m/d H:i:s');
                //$IdCode                 = $order->get_billing_phone();


                $callback 				= ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
                $callback 				= add_query_arg( 'wc-api', get_class( $this ), $callback );

                $merchantCode			= $this->merchantCode;
                $terminalCode			= $this->terminalCode;
                $privateKey	            = str_replace("[Soufi.A]", "+", urldecode( str_replace("+", "[Soufi.A]", $this->privateKey )));
                $order_total			= round($order -> order_total);

                if(get_woocommerce_currency() == "IRT")
                {
                    $order_total = $order_total*10;
                }


                $gateWay =  new PaxBank_GateWay();
                //$gateWay -> SendOrder($order_id,date("Y/m/d H:i:s"),$order_total, $merchantCode, $terminalCode, $callback, $privateKey);
                $gateWay -> SendOrder($order_total,'1003',$Invoice_Number,$Invoice_Date,$merchantCode,$terminalCode,$TimeStamp,$Customer_Id,$callback,'Rial','1331','63110db4-b13a-4199-bab4-90ccf6fc0d8c','5555',$privateKey);


            }

            function process_payment($order_id){
                $order = new WC_Order($order_id);
                return array('result' => 'success', 'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, $this->get_return_url($this->order)))
                );
            }

            function pax_bwg_check_response(){
                global $woocommerce;

                if (!class_exists('PaxBank_GateWay')) {
                    require_once ("paxGatewayClass.php");
                }

                session_start();
                $order_id 				= $_GET['iN'];
                $tref 					= $_GET['tref'];
                $order 					= new WC_Order($order_id);

                $merchantCode			= $this -> merchantCode;
                $terminalCode			= $this -> terminalCode;
                $privateKey	            = str_replace("[Soufi.A]", "+", urldecode( str_replace("+", "[Soufi.A]", $this->privateKey )));
                //$privateKey			= $this->privateKey;
                $OrderStatus 			= new PaxBank_GateWay();

                $order_total			= round($order -> order_total);

                if(get_woocommerce_currency() == "IRT")
                {
                    $order_total = $order_total*10;
                }

                $result = $OrderStatus->getOrder($_GET['tref']);

                if(($_SESSION['paxAmount']) == $order_total){

                    if($result['resultObj']['result'] == "True"){ // Check the result.

                        if($OrderStatus->verifyOrder($merchantCode, $terminalCode, $privateKey)){
                            if($order->status !=='completed'){
                                $this -> msg['pax_bwg_class'] = 'woocommerce_message';
                                $this -> msg['pax_bwg_message'] = "پرداخت شما با موفقیت انجام شد.";

                                $order->payment_complete();
                                $order->add_order_note('پرداخت موفق، کد پرداخت: '.$tref);
                                $woocommerce->cart->empty_cart();
                            }
                        }else{
                            $this -> msg['pax_bwg_class'] = 'woocommerce_error';
                            $this -> msg['pax_bwg_message'] = "پرداخت شما تایید نشد.";

                            $order -> add_order_note('پرداخت تایید نشد.');
                        }

                    }else{
                        $this -> msg['pax_bwg_class'] = 'woocommerce_error';
                        $this -> msg['pax_bwg_message'] = "پرداخت ناموفق بود.";

                        $order -> add_order_note('پرداخت ناموفق بود.');
                    }

                }else{
                    $this -> msg['pax_bwg_class'] = 'woocommerce_error';
                    $this -> msg['pax_bwg_message'] = "پرداخت نامعتبر.";

                    $order -> add_order_note('پرداخت نا معتبر.');
                }

                unset($_SESSION['paxAmount']);

                $redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0)?get_site_url() . "/":get_permalink($this->redirect_page_id);
                $redirect_url = add_query_arg( array('pax_bwg_message'=> urlencode($this->msg['pax_bwg_message']), 'pax_bwg_class'=>$this->msg['pax_bwg_class'], 'tref' => $tref), $redirect_url );
                wp_redirect( $redirect_url );
                exit;
            }

            // get all pages
            public function pax_bwg_get_pages($title = false, $indent = true) {
                $wp_pages = get_pages('sort_column=menu_order');
                $page_list = array();
                if ($title) $page_list[] = $title;
                foreach ($wp_pages as $page) {
                    $prefix = '';
                    // show indented child pages?
                    if ($indent) {
                        $has_parent = $page->post_parent;
                        while($has_parent) {
                            $prefix .=  ' - ';
                            $next_page = get_page($has_parent);
                            $has_parent = $next_page->post_parent;
                        }
                    }
                    // add to page list array array
                    $page_list[$page->ID] = $prefix . $page->post_title;
                }
                return $page_list;
            }

        }

        function woocommerce_add_pax_bwg_gateway($methods) {
            $methods[] = 'pax_bwg_full_payment';
            return $methods;
        }

        add_filter('woocommerce_payment_gateways', 'woocommerce_add_pax_bwg_gateway' );
    }

    if( isset($_GET['pax_bwg_message']) )
    {
        add_action('the_content', 'pax_bwg_show_message');

        function pax_bwg_show_message($content)
        {
            return '<div class="'.htmlentities($_GET['pax_bwg_class']).'">'.urldecode($_GET['pax_bwg_message']).'<br />شماره پیگیری پرداخت: ' . urldecode($_GET['tref']) . '</div>'.$content;
        }
    }

