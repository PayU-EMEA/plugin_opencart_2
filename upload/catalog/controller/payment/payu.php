<?php

/*
* ver. 3.0.1
* PayU Payment Modules
*
* @copyright  Copyright 2015 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/

class ControllerPaymentPayU extends Controller
{
    const ORDER_V2_PENDING = 'PENDING';
    const ORDER_V2_CANCELED = 'CANCELED';
    const ORDER_V2_REJECTED = 'REJECTED';
    const ORDER_V2_COMPLETED = 'COMPLETED';
    const ORDER_V2_WAITING_FOR_CONFIRMATION = 'WAITING_FOR_CONFIRMATION';

    const PAY_BUTTON = 'https://static.payu.com/pl/standard/partners/buttons/payu_account_button_01.png';

    const VERSION = '3.0.1';

    protected $vouchersAmount = 0.0;

    //loading PayU SDK
    private function loadLibConfig()
    {
        require_once(DIR_SYSTEM . 'library/sdk_v21/openpayu.php');

        OpenPayU_Configuration::setMerchantPosId($this->config->get('payu_merchantposid'));
        OpenPayU_Configuration::setSignatureKey($this->config->get('payu_signaturekey'));
        OpenPayU_Configuration::setEnvironment();
        OpenPayU_Configuration::setApiVersion(2.1);
        OpenPayU_Configuration::setSender('OpenCart ver ' . VERSION . ' / Plugin ver '.self::VERSION);
        $this->logger = new Log('payu.log');
    }

    public function index()
    {
        $data['payu_button'] = self::PAY_BUTTON;
        $data['action'] = $this->url->link('payment/payu/pay');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payu.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/payu.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/payu.tpl', $data);
        }
    }

    public function pay()
    {
        if ($this->session->data['payment_method']['code'] == 'payu') {
            $this->language->load('payment/payu');
            $this->load->model('checkout/order');
            $this->load->model('payment/payu');

            //OCR
            $this->loadLibConfig();
            $order = $this->buildOrder();

            try {
                $response = OpenPayU_Order::create($order);
                $status_desc = OpenPayU_Util::statusDesc($response->getStatus());

                if ($response->getStatus() == 'SUCCESS') {
                    $this->session->data['sessionId'] = $response->getResponse()->orderId;
                    $this->model_payment_payu->bindOrderIdAndSessionId(
                        $this->session->data['order_id'],
                        $this->session->data['sessionId']
                    );
                    $this->model_checkout_order->addOrderHistory(
                        $this->session->data['order_id'],
                        $this->config->get('payu_new_status')
                    );

                    $return['status'] = 'SUCCESS';
                    $return['redirectUri'] = $response->getResponse()->redirectUri . '&lang=' .
                        strtolower($this->session->data['language']);
                } else {
                    $return['status'] = 'ERROR';

                    $data['text_error'] = $this->language->get('text_error_message');
                    $this->logger->write('OCR: ' . serialize($order));
                    $this->logger->write(
                        $response->getError() . ' [request: ' . serialize($response) . ']'
                    );
                    $return['message'] = $this->language->get('text_error_message') .
                        '(' . $response->getStatus() . ': ' . $status_desc . ')';
                }
            } catch (OpenPayU_Exception $e) {
                $this->logger->write('OCR: ' . serialize($order));
                $this->logger->write('OCR Exception: ' . $e->getMessage());
                $return['status'] = 'ERROR';
                $return['message'] = $this->language->get('text_error_message');
            }
            echo json_encode($return);
            exit();
        }
    }

    //Notification
    public function ordernotify()
    {
        $this->loadLibConfig();
        $this->load->model('payment/payu');
        $this->load->model('checkout/order');

        $body = file_get_contents('php://input');
        $data = trim($body);

        try {
            if (!empty($data)) {
                $result = OpenPayU_Order::consumeNotification($data);
            }

            if ($session_id = $result->getResponse()->order->orderId) {
                $orderInfo = $this->model_payment_payu->getOrderInfoBySessionId($session_id);
                $orderRetrive = OpenPayU_Order::retrieve($session_id);

                if ($orderRetrive->getStatus() != 'SUCCESS') {
                    $this->logger->write(
                        $orderRetrive->getError() . ' [response: ' . serialize($orderRetrive->getResponse()) . ']'
                    );
                } else {

                    if ($orderInfo['status'] != self::ORDER_V2_COMPLETED) {
                        $newstatus = $this->getPaymentStatusId($orderRetrive->getResponse()->orders[0]->status);

                        if ($newstatus && $newstatus != $orderInfo['status']) {
                            $this->model_payment_payu->updateSatatus($session_id, $newstatus);
                            $this->model_checkout_order->addOrderHistory($orderInfo['order_id'], $newstatus);
                        }

                    }
                }
            }

        } catch (OpenPayU_Exception $e) {
            $this->logger->write('OCR Notification: ' . $e->getMessage());
        }

    }

    //Getting system status
    private function getPaymentStatusId($paymentStatus)
    {
        $this->load->model('payment/payu');
        if (!empty($paymentStatus)) {

            switch ($paymentStatus) {
                case self::ORDER_V2_CANCELED :
                    return $this->config->get('payu_cancelled_status');
                case self::ORDER_V2_PENDING :
                    return $this->config->get('payu_pending_status');
                case self::ORDER_V2_WAITING_FOR_CONFIRMATION :
                    return $this->config->get('payu_waiting_for_confirmation_status');
                case self::ORDER_V2_REJECTED :
                    return $this->config->get('payu_returned_status');
                case self::ORDER_V2_COMPLETED :
                    return $this->config->get('payu_complete_status');
                default:
                    return false;
            }
        }

        return false;
    }

    //building order for express checkout
    private function buildOrder()
    {

        $OCRV2 = array();

        $this->language->load('payment/payu');
        $this->load->model('payment/payu');
        $this->loadLibConfig();

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $this->load->model('localisation/country');
        $this->tax->setShippingAddress($order_info['shipping_country_id'], $order_info['shipping_zone_id']);
        $this->tax->setPaymentAddress($order_info['payment_country_id'], $order_info['payment_zone_id']);
        $this->tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
        $grandTotal = 0;
        $orderType = 'VIRTUAL';
        $shippingCostAmount = 0.0;

        $decimalPlace = $this->currency->getDecimalPlace();


        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $this->vouchersAmount += $this->currency->format($voucher['amount']);
                $OCRV2['products'] [] = array(
                    'quantity' => 1,
                    'name' => $voucher['description'],
                    'unitPrice' => $this->toAmount($voucher['amount'])
                );
            }
        }

        foreach ($this->cart->getProducts() as $item) {

            list($productOrderType, $OCRV2, $grandTotal) = $this->prepareProductsSection(
                $decimalPlace,
                $item,
                $order_info,
                $OCRV2,
                $grandTotal
            );
        }

        if ($productOrderType == 'MATERIAL') {
            $orderType = $productOrderType;
        }

        $OCReq = array(
            'ReqId' => md5(rand()),
            'CustomerIp' => (($order_info['ip'] == "::1" || $order_info['ip'] == "::" || !preg_match(
                    "/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m",
                    $order_info['ip']
                )) ? '127.0.0.1' : $order_info['ip']),
            // note, this should be real ip of customer retrieved from $_SERVER['REMOTE_ADDR']
            'NotifyUrl' => $this->url->link('payment/payu/ordernotify'),
            'OrderCompleteUrl' => $this->url->link('checkout/success')
        );

        $customer = array();


        if (!empty($order_info['email'])) {

            $customer = array(
                'email' => $order_info['email'],
                'firstName' => $order_info['firstname'],
                'lastName' => $order_info['lastname'],
                'phone' => $order_info['telephone']
            );

        } elseif (!empty($this->session->data['customer_id'])) {

            $this->load->model('account\customer');
            $custdata = $this->model_account_customer->getCustomer($this->session['customer_id']);

            if (!empty($custdata['email'])) {

                $customer = array(
                    'email' => $custdata['email'],
                    'firstName' => $custdata['firstname'],
                    'lastName' => $custdata['lastname'],
                    'phone' => $custdata['telephone']
                );
            }
        }

        if ($orderType == 'MATERIAL') {

            if (!empty($customer) && !empty($order_info['shipping_city']) && !empty($order_info['shipping_postcode']) && !empty($order_info['payment_iso_code_2'])) {

                $customer['delivery'] = array(
                    'street' => $order_info['shipping_address_1'] . " " . ($order_info['shipping_address_2'] ? $order_info['shipping_address_2'] : ''),
                    'postalCode' => $order_info['shipping_postcode'],
                    'city' => $order_info['shipping_city'],
                    'countryCode' => $order_info['payment_iso_code_2'],
                    'recipientName' => $order_info['shipping_firstname'] . " " . $order_info['shipping_lastname'],
                    'recipientPhone' => $order_info['telephone'],
                    'recipientEmail' => $order_info['email']
                );
            }

            if (!empty($order_info['shipping_method'])) {
                list($shippingCostList, $shippingCostAmount) = $this->prepareShippingMethodsSection(
                    $decimalPlace,
                    $order_info
                );
            }

        }

        if (isset($this->session->data['coupon']) || !empty($this->session->data['coupon'])) {
            $OCRV2 = $this->prepareCumulatedProductsArray(
                $OCRV2,
                $order_info,
                $shippingCostAmount,
                $this->language->get('text_payu_order')
            );
        }

        $OCRV2['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $OCRV2['orderUrl'] = $this->url->link('payment/payu/callback') . '?order=' . $this->session->data['order_id'];
        $OCRV2['description'] = $this->language->get('text_payu_order') . ' #' . $this->session->data['order_id'];
        $OCRV2['customerIp'] = $OCReq['CustomerIp'];
        $OCRV2['notifyUrl'] = $OCReq['NotifyUrl'];
        $OCRV2['continueUrl'] = $OCReq['OrderCompleteUrl'];
        $OCRV2['currencyCode'] = $order_info['currency_code'];
        $OCRV2['settings']['invoiceDisabled'] = true;

        $total = $order_info['total'];

        if (empty($decimalPlace)) {
            $total = $this->toAmount($total);
        }

        $total = str_ireplace(
            array('.', ' '),
            array('', ''),
            $this->currency->format($total - $shippingCostAmount, $order_info['currency_code'], false, false)
        );

        $OCRV2['totalAmount'] = $total;
        $OCRV2['extOrderId'] = $this->session->data['order_id'] . '-' . md5(microtime());
        if (isset($shippingCostList)) {
            $OCRV2['shippingMethods'] = $shippingCostList['shippingMethods'];
        }
        $OCRV2['buyer'] = $customer;

        return $OCRV2;

    }

    /**
     * Convert to amount
     *
     * @param $value
     * @return int
     */
    private function toAmount($value)
    {
        $val = $value * 100;
        $round = (int)round($val);

        return $round;
    }

    /**
     * @param $OCRV2
     * @param $order_info
     * @param $shippingCostAmount
     * @param $txt_prefix
     * @return mixed
     */
    private function prepareCumulatedProductsArray($OCRV2, $order_info, $shippingCostAmount, $txt_prefix)
    {
        unset($OCRV2['products']);
        $OCRV2['products'] = array();
        $totalProducts = str_ireplace(
            array('.', ' '),
            array('', ''),
            $this->currency->format(
                $this->toAmount((int)($order_info['total'])) - $shippingCostAmount,
                $order_info['currency_code'],
                false,
                false
            )
        );
        $OCRV2['products'] [] = array(
            'quantity' => 1,
            'name' => $txt_prefix . ':' . $this->session->data['order_id'],
            'unitPrice' => $totalProducts
        );

        return $OCRV2;
    }

    /**
     * @param $decimalPlace
     * @param $order_info
     * @return array
     */
    private function prepareShippingMethodsSection($decimalPlace, $order_info)
    {
        $shippingCostList = array();
        $shippingCost = $shippingCostAmount = $this->session->data['shipping_method']['cost'];

        if (empty($decimalPlace)) {
            $shippingCost *= 100;
            $shippingCostAmount = $shippingCost;
        }

        $price = $this->currency->format(
            $this->tax->calculate(
                $shippingCost,
                $this->session->data['shipping_method']['tax_class_id']
            )
        );

        $price = preg_replace("/[^0-9]/", "", $price);

        $shippingCostList ['shippingMethods'] [] = array(
            'name' => $order_info['shipping_method'],
            'country' => $order_info['payment_iso_code_2'],
            'price' => $price
        );

        return array($shippingCostList, $shippingCostAmount);
    }

    /**
     * @param $decimalPlace
     * @param $item
     * @param $order_info
     * @param $OCRV2
     * @param $grandTotal
     * @return array
     */
    private function prepareProductsSection($decimalPlace, $item, $order_info, $OCRV2, $grandTotal)
    {
        if (empty($decimalPlace)) {
            $item['price'] *= 100;
        }

        $gross = $this->tax->calculate($item['price'], $item['tax_class_id']);

        $orderType = $item['shipping'] == 1 ? 'MATERIAL' : 'VIRTUAL';

        $itemGross = str_ireplace(
            array('.', ' '),
            array('', ''),
            $this->currency->format($gross, $order_info['currency_code'], false, false)
        );

        $OCRV2['products'] [] = array(
            'quantity' => $item['quantity'],
            'name' => $item['name'],
            'unitPrice' => $itemGross
        );

        $grandTotal += $itemGross * $item['quantity'];

        return array($orderType, $OCRV2, $grandTotal);
    }
}
