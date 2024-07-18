<?php

namespace Commerce\Payments;

class Paykeeper extends Payment
{
    protected $debug = false;
    protected $vat = '';

    public function __construct($modx, array $params = [])
    {
        parent::__construct($modx, $params);
        $this->lang = $modx->commerce->getUserLanguage('paykeeper');
        $this->debug = $this->getSetting('debug') == '1';
        $this->vat = $this->getSetting('vat');
    }

    public function getMarkup()
    {
        if (empty($this->getSetting('secret_key')) || empty($this->getSetting('shop_id'))) {
            return '<span class="error" style="color: red;">' . $this->lang['paykeeper.error_empty_params'] . '</span>';
        }
    }

    public function getPaymentMarkup()
    {
        $processor = $this->modx->commerce->loadProcessor();
        $order = $processor->getOrder();
        $payment = $this->createPayment($order['id'], $order['amount']);
        $data = [
            'sum'                  => $payment['amount'],
            'clientid'             => $order['name'],
            'client_email'         => $order['email'],
            'client_phone'         => $order['phone'],
            'currency'             => $order['currency'],
            'orderid'              => $order['id'] . '-' . $payment['id'],
        ];

        $cart = $processor->getCart();
        $items = $this->prepareItems($cart);
        $products = [];
        foreach ($items as $item) {
            $products[] = [
                'name'        => $item['name'],
                'price'       => $item['price'],
                'quantity'    => $item['count'],
                'tax'         => $item['meta']['tax'] ?? $this->vat,
                'sum'         => $item['total'],
                'item_type'   => $item['meta']['item_type'] ?? 'goods',
            ];
        }

        $data['cart'] = json_encode($products);
        $data['sign'] = hash('sha256', $data['sum'] . $data['clientid'] . $data['orderid']
            . '' . $data['client_email'] . $data['client_phone'] . $this->getSetting('secret_key'));

        $view = new \Commerce\Module\Renderer($this->modx, null, [
            'path' => 'assets/plugins/commerce/templates/front/',
        ]);

        if ($this->debug) {
            $this->modx->logEvent(0, 3,
                'Payment start <pre>' . print_r($data, true) . '</pre>',
                'Commerce Paykeeper Payment');
        }

        return $view->render('payment_form.tpl', [
            'url'    => "https://{$this->getSetting('shop_id')}.server.paykeeper.ru/create/",
            'method' => 'post',
            'data'   => $data,
        ]);
    }

    public function handleCallback()
    {
        $input = $_POST;
        if ($this->debug) {
            $this->modx->logEvent(0, 3,
                'Callback start <pre>' . print_r($input, true) . '</pre>',
                'Commerce Paykeeper Payment Callback');
        }
        foreach (['id', 'sum', 'clientid', 'orderid', 'key'] as $key) {
            if(!isset($input[$key]) || !is_scalar($input[$key])) {
                return false;
            }
        }
        $sign = md5($input['id'] . $input['sum'] . $input['clientid'] . $input['orderid'] . $this->getSetting('secret_key'));
        if($sign != $input['key']) {
            return false;
        }

        $processor = $this->modx->commerce->loadProcessor();
        try {
            [$order_id, $payment_id] = explode('-', $input['orderid']);
            $payment = $processor->loadPayment($payment_id);

            if (!$payment || $payment['order_id'] != $order_id) {
                throw new Exception('Payment "' . htmlentities(print_r($payment_id, true)) . '" . not found!');
            }

            return $processor->processPayment($payment['id'], $payment['amount']);
        } catch (Exception $e) {
            if ($this->debug) {
                $this->modx->logEvent(0, 3, 'Payment process failed: ' . $e->getMessage(),
                    'Commerce Paykeeper Payment Callback');

                return false;
            }
        }

        return false;
    }
}
