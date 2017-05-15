<?php

/*
* ver. 3.1.4
* PayU Payment Modules
*
* @copyright  Copyright 2016 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
*/

class ControllerPaymentPayU extends Controller
{
    const ORDER_V2_PENDING = 'PENDING';
    const ORDER_V2_CANCELED = 'CANCELED';
    const ORDER_V2_REJECTED = 'REJECTED';
    const ORDER_V2_COMPLETED = 'COMPLETED';
    const ORDER_V2_WAITING_FOR_CONFIRMATION = 'WAITING_FOR_CONFIRMATION';

    const PAY_BUTTON = 'https://static.payu.com/pl/standard/partners/buttons/payu_account_button_01.png';

    const VERSION = '3.1.4';

    private $ocr = array();
    private $totalWithoutDiscount = 0;

    //loading PayU SDK
    private function loadLibConfig()
    {
        require_once(DIR_SYSTEM . 'library/sdk_v21/openpayu.php');

        OpenPayU_Configuration::setMerchantPosId($this->config->get('payu_merchantposid'));
        OpenPayU_Configuration::setSignatureKey($this->config->get('payu_signaturekey'));
        OpenPayU_Configuration::setEnvironment();
        OpenPayU_Configuration::setApiVersion(2.1);
        OpenPayU_Configuration::setSender('OpenCart ver ' . VERSION . ' / Plugin ver ' . self::VERSION);
        $this->logger = new Log('payu.log');
    }

    public function index()
    {
        $data['payu_button'] = self::PAY_BUTTON;
        $data['action'] = $this->url->link('payment/payu/pay', '', true);

        if ($this->isVersion22()) {
            return $this->load->view('payment/payu', $data);
        } else {
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payu.tpl')) {
                return $this->load->view($this->config->get('config_template') . '/template/payment/payu.tpl', $data);
            } else {
                return $this->load->view('default/template/payment/payu.tpl', $data);
            }
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
                    $payuOrderStatus = $orderRetrive->getResponse()->orders[0]->status;
                    $order = $this->model_checkout_order->getOrder($orderInfo['order_id']);

                    if ($orderInfo['status'] != self::ORDER_V2_COMPLETED) {
                        $newstatus = $this->getPaymentStatusId($payuOrderStatus);

                        if ($newstatus && $newstatus != $order['order_status']) {
                            $this->model_payment_payu->updateSatatus($session_id, $payuOrderStatus);
                            $this->model_checkout_order->addOrderHistory($orderInfo['order_id'], $newstatus);
                        }

                    }
                }
            }

        } catch (OpenPayU_Exception $e) {
            $this->logger->write('OCR Notification: ' . $e->getMessage());
            die($e->getMessage());
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

    private function buildOrder()
    {

        $this->language->load('payment/payu');
        $this->load->model('payment/payu');
        $this->loadLibConfig();

        //get order info
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        //OCR basic data
        $this->ocr['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $this->ocr['description'] = $this->language->get('text_payu_order') . ' #' . $order_info['order_id'];
        $this->ocr['customerIp'] = $this->getIP($order_info['ip']);
        $this->ocr['notifyUrl'] = $this->url->link('payment/payu/ordernotify', '', true);
        $this->ocr['continueUrl'] = $this->url->link('checkout/success', '', true);
        $this->ocr['currencyCode'] = $order_info['currency_code'];
        $this->ocr['totalAmount'] = $this->toAmount(
            $this->currencyFormat($order_info['total'], $order_info['currency_code'])
        );
        $this->ocr['extOrderId'] = uniqid($order_info['order_id'] . '-', true);
        $this->ocr['settings']['invoiceDisabled'] = true;

        //OCR customer data
        $this->buildCustomerInOrder($order_info);

        //OCR products
        $this->buildProductsInOrder($this->cart->getProducts(), $order_info['currency_code']);

        //OCR shipping
        if ($this->cart->hasShipping()) {
            $this->buildShippingInOrder($this->session->data['shipping_method'], $order_info['currency_code']);
        }

        if ($this->ocr['totalAmount'] < $this->totalWithoutDiscount) {
            $this->buildDiscountInOrder($this->ocr['totalAmount']);
        }

        return $this->ocr;

    }

    /**
     * @param array $order_info
     */
    private function buildCustomerInOrder($order_info)
    {

        if (!empty($order_info['email'])) {
            $this->ocr['buyer'] = array(
                'email' => $order_info['email'],
                'firstName' => $order_info['firstname'],
                'lastName' => $order_info['lastname'],
                'phone' => $order_info['telephone']
            );
        }
    }

    /**
     * @param array $products
     */
    private function buildProductsInOrder($products, $currencyCode)
    {
        foreach ($products as $item) {

            $gross = $this->currencyFormat(
                $this->tax->calculate($item['price'], $item['tax_class_id'], $this->config->get('config_tax')),
                $currencyCode
            );
            $itemGross = $this->toAmount($gross);

            $this->ocr['products'][] = array(
                'quantity' => $item['quantity'],
                'name' => substr($item['name'], 0, 250),
                'unitPrice' => $itemGross
            );

            $this->totalWithoutDiscount += $itemGross;
        }
    }


    /**
     * @param int $total
     */
    private function buildDiscountInOrder($total)
    {
        $this->ocr['products'][] = array(
            'quantity' => 1,
            'name' => $this->language->get('text_payu_discount'),
            'unitPrice' => $total - $this->totalWithoutDiscount
        );

    }

    /**
     * @param array $shippingMethod
     */
    private function buildShippingInOrder($shippingMethod, $currencyCode)
    {
        $itemGross = $this->toAmount($this->currencyFormat($shippingMethod['cost'], $currencyCode));
        $this->ocr['products'][] = array(
            'quantity' => 1,
            'name' => $shippingMethod['title'],
            'unitPrice' => $itemGross
        );
        $this->totalWithoutDiscount += $itemGross;
    }

    /**
     * Convert to amount
     *
     * @param $value
     * @return int
     */
    private function toAmount($value)
    {
        return number_format($value * 100, 0, '', '');
    }

    /**
     * Currency format
     *
     * @param float $value
     * @return float
     */
    private function currencyFormat($value, $currencyCode)
    {
        return $this->currency->format($value, $currencyCode, '', false);
    }

    private function getIP($orderIP)
    {
        return $orderIP == "::1"
        || $orderIP == "::"
        || !preg_match(
            "/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m",
            $orderIP
        )
            ? '127.0.0.1' : $orderIP;
    }

    private function isVersion22()
    {
        return version_compare(VERSION, '2.2', '>=');
    }
}
