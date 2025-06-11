<?php

use Bitrix\Main\Application;
use Lkduslar\Core\Wsdl1c;
use Lkduslar\Core\Log;

define("STOP_STATISTICS", true);
define("NO_AGENT_CHECK", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$orderId = $request->get('order');
$type = $request->get('type');
$opt = $request->get('opt');

if ($type && $orderId) {
    if ($type == 'OOO') {
        $arEventFields = [
            'ORDER_ID' => $request->get('order'),
            'SUM' => $request->get('amount'),
            'PAYMENT' => 'Оплата без НДС (ООО "Дуслар", УСН) / Юкасса',
            'SALE_EMAIL' => COption::GetOptionString("sale", "order_email", "order@".$_SERVER["SERVER_NAME"])
        ];

        CEvent::Send("SALE_PAID_CARD_EXT", ['s1'], $arEventFields);

        $orderId = preg_replace("#([^\s(]+?)\s(\(.*?\))#is", "$1", $request->get('order'));

        $wsdl = new Wsdl1c;
        $params = [
            "Number" => $orderId,
            "Number1S" => $orderId,
            "Amount" => roundEx($request->get('amount'),2),
            "PaymentSystem" => "Онлайн оплата банковской картой",
            "PayID" => $opt ? 27 : 20,
        ];
        Log::getLogger("payment/pay_online","log","txt","30")->log(Wsdl1c::getAccess()['url'], "START PAYMENT - URL 1C");
        $result1с = $wsdl->CreatePaymentDocument($params);
        Log::getLogger("payment/pay_online","log","txt","30")->log($params, "1C REQUEST");
        Log::getLogger("payment/pay_online","log","txt","30")->log($result1с, "1C RESPONSE");

        if($result1с['Status'] != 1) {
            $arEventFields = [
                'STATUS' => $result1с['Status'],
                '1C_RESPONSE' => print_r($result1с, true),
                'ORDER_NUMBER' => $orderId,
                'SUM' => $params['Amount'],
                'LOGIN' => $USER->GetLogin(),
                'USER_ID' => $USER->GetId(),
                'FIO' => $USER->GetFullName(),
                'PAYMENT' => $params['PaymentSystem'],
                'DATE_PAYED' => (new \Bitrix\Main\Type\DateTime())->format("d.m.Y H:i:s"),
            ];
            CEvent::Send("SALE_PAID_CARD_1C_PAYMENT", ['s1'], $arEventFields);
        }
    } elseif($type == 'TK') {
        $arEventFields = [
            'ORDER_ID' => $request->get('order'),
            'SUM' => $request->get('amount'),
            'PAYMENT' => 'Оплата без НДС (ТК "Дуслар", УСН) / Юкасса',
            'SALE_EMAIL' => COption::GetOptionString("sale", "order_email", "order@".$_SERVER["SERVER_NAME"])
        ];

        CEvent::Send("SALE_PAID_CARD_EXT", ['s1'], $arEventFields);

        $orderId = preg_replace("#([^\s(]+?)\s(\(.*?\))#is", "$1", $request->get('order'));
        $wsdl = new \Lkduslar\Core\Wsdl1c;
        $params = [
            "Number" => $orderId,
            "Number1S" => $orderId,
            "Amount" => roundEx($request->get('amount'),2),
            "PaymentSystem" => "Онлайн оплата банковской картой на ТК Дуслар",
            "PayID" => $opt ? 28 : 20,
        ];
        Log::getLogger("payment/pay_online","log","txt","30")->log(Wsdl1c::getAccess()['url'], "START PAYMENT - URL 1C");
        $result1с = $wsdl->CreatePaymentDocument($params);
        Log::getLogger("payment/pay_online","log","txt","30")->log($params, "1C REQUEST");
        Log::getLogger("payment/pay_online","log","txt","30")->log($result1с, "1C RESPONSE");

        if($result1с['Status'] != 1) {
            $arEventFields = [
                'STATUS' => $result1с['Status'],
                '1C_RESPONSE' => print_r($result1с, true),
                'ORDER_NUMBER' => $orderId,
                'SUM' => $params['Amount'],
                'LOGIN' => $USER->GetLogin(),
                'USER_ID' => $USER->GetId(),
                'FIO' => $USER->GetFullName(),
                'PAYMENT' => $params['PaymentSystem'],
                'DATE_PAYED' => (new \Bitrix\Main\Type\DateTime())->format("d.m.Y H:i:s"),
            ];

            CEvent::Send("SALE_PAID_CARD_1C_PAYMENT", ['s1'], $arEventFields);
        }
    }
}