<?php

    class PaxBank_GateWay
    {
        /*
         * SEND ORDER TO BANK
         *
         * @param $invoiceNumber int
         * @param invoiceDate date()
         * @param amount int/Rial
         *
         * Return False if there is a error,
         * redirect to gateway if everything OK.
         */

        /** Original Code **/
        /*function sendOrder($invoiceNumber = NULL, $invoiceDate = NULL, $amount = NULL, $merchantCode, $terminalCode, $redirectAddress, $privateKey)
        {
            if (isset($invoiceNumber) and isset($invoiceDate) and isset($amount)) {
                if (!class_exists('RSAProcessor')) {
                    require_once("libraries/RSAProcessor.class.php");
                }
                $processor = new RSAProcessor($privateKey, RSAKeyType::XMLString);
                date_default_timezone_set('Asia/Tehran');
                $timeStamp = date("Y/m/d H:i:s");
                $action = "1003";
                session_start();

                $_SESSION['paxAmount'] = $amount;

                $data = "#" . $merchantCode . "#" . $terminalCode . "#" . $invoiceNumber . "#" . $invoiceDate . "#" . $amount . "#" . $redirectAddress . "#" . $action . "#" . $timeStamp . "#";
                $data = sha1($data, true);
                $data = $processor->sign($data);
                $result = base64_encode($data);






                echo " 
                    <!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
                    <html>
                        <body>
                            <div id='WaitToSend' style='margin:0 auto; width: 600px; text-align: center;'>درحال انتقال به درگاه بانک<br>لطفا منتظر بمانید .</div>
                            <form Id='GateWayForm' Method='post' Action='https://pep.shaparak.ir/gateway.aspx' style='display: none;'>
                                invoiceNumber<input type='text' name='invoiceNumber' value='$invoiceNumber' />
                                invoiceDate<input type='text' name='invoiceDate' value='$invoiceDate' />
                                amount<input type='text' name='amount' value='$amount' />
                                terminalCode<input type='text' name='terminalCode' value='$terminalCode' />
                                merchantCode<input type='text' name='merchantCode' value='$merchantCode' />
                                redirectAddress<input type='text' name='redirectAddress' value='$redirectAddress' />
                                timeStamp<input type='text' name='timeStamp' value='$timeStamp' />
                                action<input type='text' name='action' value='$action' />
                                sign<input type='text' name='sign' value='$result' />
                            </form>
                            <script language='javascript'>document.forms['GateWayForm'].submit();</script>
                        </body>
                    </html> ";
                // include('../../pg/index.php');

            }
            else {
                return FALSE;
            }
        }*/

        /** My Code **/
        function sendOrder($amount,$action,$invoiceNumber,$invoiceDate,$merchantCode,$terminalCode,$timestamp,$customerID,$redirectAddress,$currency,$CurrencyRegionCode,$storeId,$bankGatewayCode,$privateKey)
        {

            if (!class_exists('RSAProcessor')) {
                require_once("libraries/RSAProcessor.class.php");
            }


            $processor = new RSAProcessor($privateKey, RSAKeyType::XMLString);
            date_default_timezone_set('Asia/Tehran');
            session_start();
            $_SESSION['paxAmount'] = $amount;


            /** Convert String To Decimal & integer **/
            $amount         = number_format($amount, 2, '.', '');
            $merchantCode   = (int)$merchantCode;
            $terminalCode   = (int)$terminalCode;
            $action         = (int)$action;



            $Data_String = "#" . $merchantCode . "#" . $terminalCode . "#" . $invoiceNumber . "#" . $invoiceDate .
                "#" . $amount . "#" . $redirectAddress . "#" . $action . "#" . $timestamp . "#" . $bankGatewayCode .
                "#" . $currency . "#" . $CurrencyRegionCode . "#" . $customerID . "#" . $storeId . "#";

            //$Data_String = '#111#222#2447#2021/12/19#56600.00#https://pax.test/my-account/?wc-api=pax_bwg_full_payment#1003#2021/12/19 13:46:10#5555#Rial#1331#093935455356#63110db4-b13a-4199-bab4-90ccf6fc0d8c#';

            echo $Data_String . "\n";

            /** Create Sign **/
            $data   = sha1($Data_String,true);
            $data   = $processor->sign($data);
            $result = base64_encode($data);

            //echo $result . "\n"; exit();

            /** Convert To Array **/
            $data_array = array(
                'amount'                => number_format($amount, 2, '.', ''),
                'action'                => (int)$action,
                'invoiceNumber'         => $invoiceNumber,
                'invoiceDate'           => $invoiceDate,
                'merchantCode'          => (int)$merchantCode,
                'terminalCode'          => (int)$terminalCode,
                'timestamp'             => $timestamp,
                'customerID'            => strval($customerID),
                'redirectAddress'       => $redirectAddress,
                'currency'              => $currency,
                'currencyRegionCode'    => $CurrencyRegionCode,
                'storeId'               => $storeId,
                'bankGatewayCode'       => $bankGatewayCode
            );

            //echo $data_array; exit();


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
                    'sign:' . $result,
                    'Content-Type:application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_exec($curl);
            curl_close($curl);

            echo $response;

            exit();


            /** Send Data With Form **/
            /*echo "
            <!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
            <html>
                <body>
                    <div id='WaitToSend' style='margin:0 auto; width: 600px; text-align: center;'>درحال انتقال به درگاه بانک<br>لطفا منتظر بمانید .</div>
                    <form Id='GateWayForm' Method='post' Action='https://paymentgateway.morsasw.local/PaymentGateway/GetToken'>
                        <input type='text' name='amount' value='$amount' />
                        <input type='text' name='action' value='$action' />
                        <input type='text' name='invoiceNumber' value='$invoiceNumber' />
                        <input type='text' name='invoiceDate' value='$invoiceDate' /> 
                        <input type='text' name='merchantCode' value='$merchantCode' /> 
                        <input type='text' name='terminalCode' value='$terminalCode' />
                        <input type='text' name='timestamp' value='$timestamp' />
                        <input type='text' name='customerID' value='$customerID' />                                                       
                        <input type='text' name='redirectAddress' value='$redirectAddress' /> 
                        <input type='text' name='currency' value='$currency' /> 
                        <input type='text' name='currencyRigionCode' value='$CurrencyRegionCode' /> 
                        <input type='text' name='storeId' value='$storeId' /> 
                        <input type='text' name='bankGatewayCode' value='$bankGatewayCode' />
                        <input type='text' name='sign' value='$result' />
                    </form>
                    <script language='javascript'>document.forms['GateWayForm'].submit();</script>
                </body>
            </html> ";*/
        }



        /*
         * Check Order If Exist iN Shaparak Co.
         *
         * @param $tref int
         *
         * Return False if there is a error,
         * send an array to output if not.
         */
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

        /*
         * Verify Order iN Shaparak Co.
         *
         * @param $amount int/Rials
         *
         * Return False if there is a error,
         * return True if everything OK.
         */
        function verifyOrder($merchantCode, $terminalCode, $privateKey)
        {
            if (!class_exists('RSAProcessor')) {
                require_once("libraries/RSAProcessor.class.php");
            }

            if (!function_exists('post2https') || !function_exists('makeXMLTree')) {
                require_once("libraries/parser.php");
            }
            session_start();
            $amount = $_SESSION['paxAmount'];

            $fields = array(
                'MerchantCode' => $merchantCode,
                'TerminalCode' => $terminalCode,
                'InvoiceNumber' => $_GET['iN'],
                'InvoiceDate' => $_GET['iD'],
                'amount' => $amount,
                'TimeStamp' => date("Y/m/d H:i:s"),
                'sign' => ''
            );

            $processor = new RSAProcessor($privateKey, RSAKeyType::XMLString);

            $data = "#" . $fields['MerchantCode'] . "#" . $fields['TerminalCode'] . "#" . $fields['InvoiceNumber'] . "#" . $fields['InvoiceDate'] . "#" . $fields['amount'] . "#" . $fields['TimeStamp'] . "#";
            $data = sha1($data, true);
            $data = $processor->sign($data);
            $fields['sign'] = base64_encode($data);

            $verifyresult = post2https($fields, 'https://paymentgateway.morsasw.local/PaymentGateway/VerifyPayment');
            $array = makeXMLTree($verifyresult);

            if ($array['actionResult']['result'] == "True") {
                return true;
            } else {
                return false;
            }
        }
    }
