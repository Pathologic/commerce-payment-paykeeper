//<?php
/**
 * Payment Paykeeper
 *
 * Paykeeper payments processing
 *
 * @category    plugin
 * @version     1.0.0
 * @author      Pathologic
 * @internal    @events OnRegisterPayments,OnBeforeOrderSending,OnManagerBeforeOrderRender
 * @internal    @properties &title=Название;text; &shop_id=ID магазина;text; &secret_key=Секретный ключ;text; &vat=Налог;list;НДС не облагается==none||НДС 0%==vat0||НДС 10%==vat10||НДС 20%==vat20||НДС 10/110==vat110||НДС 20/120==vat120;none &debug=Отладка запросов;list;Нет==0||Да==1;1
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (empty($modx->commerce) && !defined('COMMERCE_INITIALIZED')) {
    return;
}

$isSelectedPayment = !empty($order['fields']['payment_method']) && $order['fields']['payment_method'] == 'paykeeper';
$commerce = ci()->commerce;
$lang = $commerce->getUserLanguage('paykeeper');

switch ($modx->event->name) {
    case 'OnRegisterPayments': {
        $class = new \Commerce\Payments\Paykeeper($modx, $params);
        if (empty($params['title'])) {
            $params['title'] = $lang['paykeeper.caption'];
        }

        $commerce->registerPayment('paykeeper', $params['title'], $class);
        break;
    }

    case 'OnBeforeOrderSending': {
        if ($isSelectedPayment) {
            $FL->setPlaceholder('extra', $FL->getPlaceholder('extra', '') . $commerce->loadProcessor()->populateOrderPaymentLink());
        }

        break;
    }

    case 'OnManagerBeforeOrderRender': {
        if (isset($params['groups']['payment_delivery']) && $isSelectedPayment) {
            $params['groups']['payment_delivery']['fields']['payment_link'] = [
                'title'   => $lang['paykeeper.link_caption'],
                'content' => function($data) use ($commerce) {
                    return $commerce->loadProcessor()->populateOrderPaymentLink('@CODE:<a href="[+link+]" target="_blank">[+link+]</a>');
                },
                'sort' => 50,
            ];
        }

        break;
    }
}
