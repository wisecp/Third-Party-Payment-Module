<?php
    class SamplePaymentModule extends PaymentGatewayModule
    {
        function __construct()
        {
            $this->name             = __CLASS__;

            parent::__construct();
        }

        public function config_fields()
        {
            return [
                'example1'          => [
                    'name'              => "Text",
                    'description'       => "Description for Text",
                    'type'              => "text",
                    'value'             => $this->config["settings"]["example1"] ?? '',
                    'placeholder'       => "Example Placeholder",
                ],
                'example2'          => [
                    'name'              => "Password",
                    'description'       => "Description for Password",
                    'type'              => "password",
                    'value'             => $this->config["settings"]["example2"] ?? '',
                    'placeholder'       => "Example Placeholder",
                ],
                'example3'          => [
                    'name'              => "Approval Button",
                    'description'       => "Description for Approval Button",
                    'type'              => "approval",
                    'value'             => 1,
                    'checked'           => (boolean) (int) ($this->config["settings"]["example3"] ?? 0),
                ],
                'example4'          => [
                    'name'              => "Dropdown Menu 1",
                    'description'       => "Description for Dropdown Menu 1",
                    'type'              => "dropdown",
                    'options'           => "Option 1,Option 2,Option 3,Option 4",
                    'value'             => $this->config["settings"]["example4"] ?? '',
                ],
                'example5'          => [
                    'name'              => "Dropdown Menu 2",
                    'description'       => "Description for Dropdown Menu 2",
                    'type'              => "dropdown",
                    'options'           => [
                        'opt1'     => "Option 1",
                        'opt2'     => "Option 2",
                        'opt3'     => "Option 3",
                        'opt4'     => "Option 4",
                    ],
                    'value'         => $this->config["settings"]["example5"] ?? '',
                ],
                'example6'          => [
                    'name'              => "Radio Button 1",
                    'description'       => "Description for Radio Button 1",
                    'width'             => 40,
                    'description_pos'   => 'L',
                    'is_tooltip'        => true,
                    'type'              => "radio",
                    'options'           => "Option 1,Option 2,Option 3,Option 4",
                    'value'             => $this->config["settings"]["example6"] ?? '',
                ],
                'example7'          => [
                    'name'              => "Radio Button 2",
                    'description'       => "Description for Radio Button 2",
                    'description_pos'   => 'L',
                    'is_tooltip'        => true,
                    'type'              => "radio",
                    'options'           => [
                        'opt1'     => "Option 1",
                        'opt2'     => "Option 2",
                        'opt3'     => "Option 3",
                        'opt4'     => "Option 4",
                    ],
                    'value'             => $this->config["settings"]["example7"] ?? '',
                ],
                'example8'          => [
                    'name'              => "Text Area",
                    'description'       => "Description for text area",
                    'rows'              => "3",
                    'type'              => "textarea",
                    'value'             => $this->config["settings"]["example8"] ?? '',
                    'placeholder'       => "Example placeholder",
                ]
            ];
        }

        public function area($params=[])
        {
            // Config Variable
            $merchant_id    = $this->config["settings"]["example1"] ?? "none";
            $secret_key     = $this->config["settings"]["example2"] ?? "none";

            // For more variables: https://dev.wisecp.com/en/kb/third-party-method

            // Sample return output
            return
                '<form action="https://www.sample.com/checkout" method="POST">
                    <input type="hidden" name="merchant_id" value="'.$merchant_id.'">
                    <input type="hidden" name="amount" value="'.$params["amount"].'">
                    <input type="hidden" name="currency" value="'.$this->currency($params["currency"]).'">
                    <input type="hidden" name="custom_id" value="'.$this->checkout_id.'">
                    <input type="hidden" name="description" value="Invoice Payment">
                    <input type="submit" value="'.$this->lang["pay-button"].'">
                </form>';
        }

        public function callback()
        {
            // Obtain the custom id that you forwarded to the payment provider.
            $custom_id      = (int) Filter::init("POST/custom_id","numbers");

            if(!$custom_id){
                $this->error = 'Custom id not found.';
                return false;
            }

            // Let's get the checkout information.
            $checkout       = $this->get_checkout($custom_id);

            // Checkout invalid error
            if(!$checkout)
            {
                $this->error = 'Checkout ID unknown';
                return false;
            }

            // You introduce checkout to the system
            $this->set_checkout($checkout);

            // You decide the status of the payment.

            return [
                /* You can define it as 'successful' or 'pending'.
                    * 'successful' : Write if the payment is complete.
                    * 'pending' : Write if the payment is pending confirmation.
                    */
                'status'            => 'successful',

                /*
                    * You can host any id number for any information you need to report to the administrator after payment or for later use in returning the invoice. This information will be stored as JSON type in the invoices.pmethod_msg field in the database.
                    * Acceptable value : 'array' and 'string'
                    */
                'message'        => [
                    'Merchant Transaction ID' => '123X456@23',
                ],
                /*
                    * When requests are sent to the callback address regarding the status of the payment, you can use this field to provide a specific response. If you do not use this field, the response will direct to the 'Payment Completed' page if the payment is successful, or to the 'Payment Failed' page if it is unsuccessful.
                */
                'callback_message'        => 'Transaction Successful',
                /*
                    * This index is optional and is used to indicate the total amount paid on the side of the payment intermediary. If the paid amount exceeds the total amount of the invoice, the excess is added to the invoice as a payment commission. This index contains two sub-indices: The first sub-index, 'amount', should be of the float type. 'Currency' refers to the currency code, which should be specified according to ISO 4217 standards.
                */
                'paid'                    => [
                    'amount'        => 15,
                    'currency'      => "USD",
                ],
            ];
        }

        // If your payment service provider does not support the refund feature, you can remove the functionality.
        public function refundInvoice($invoice=[])
        {
            $api_key        = $this->config["settings"]["example1"] ?? 'N/A';
            $secret_key     = $this->config["settings"]["example2"] ?? 'N/A';
            $amount         = $invoice["total"];
            $currency       = $this->currency($invoice["currency"]);

            // In callback or capture functions, the data transmitted as 'message' in the return value is retrieved.
            $method_msg         = Utility::jdecode($invoice["pmethod_msg"] ?? [],true);
            $transaction_id     = $method_msg["Merchant Transaction ID"] ?? false;



            $force_curr     = $this->config["settings"]["force_convert_to"] ?? 0;
            if($force_curr > 0)
            {
                $amount         = Money::exChange($amount,$currency,$force_curr);
                $currency       = $this->currency($force_curr);
            }

            // Here we are making an API call.
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "api.sample.com/refund/".$transaction_id);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'APIKEY: '.$api_key,
                'SECRET: '.$secret_key,
                'Content-Type: application/json',
            ));
            $result = curl_exec($curl);
            if(curl_errno($curl))
            {
                $result      = false;
                $this->error = curl_error($curl);
            }
            $result             = json_decode($result,true);

            if($result && $result['status'] == 'OK') $result = true;
            else
            {
                $this->error = $result['message'] ?? 'something went wrong';
                $result = false;
            }

            return $result;
        }

    }