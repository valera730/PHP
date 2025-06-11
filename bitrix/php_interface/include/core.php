<?
use Bitrix\Main\Loader;
use Bitrix\Main\IO;
use Bitrix\Sale\Order;
class DuslarCore {
	const HL_LINKED_PRODUCTS = 'KHarakteristikiTovarov';
	const PRICE_SALE_RETAIL = 7;

    function deleteUserFull($ID) {
        if (!$ID) {
            return false;
        }

        Loader::includeModule('catalog');
        Loader::includeModule('sale');

        $filter = [
            "ID" => $ID
        ];
        $rsUsers = CUser::GetList(($by = "ID"), ($order = "desc"), $filter, ['FIELDS' => ['ID']]);
        if ($arUser = $rsUsers->Fetch()) {
            $filterOrder = [
                "USER_ID" => $arUser['ID'],
            ];
            $rsOrders = CSaleOrder::GetList([], $filterOrder, false, false, ['ID']);
            while ($arOrder = $rsOrders->Fetch()) {
                $order = Order::load($arOrder['ID']);
                $paymentCollection = $order->getPaymentCollection();
                foreach ($paymentCollection as $payment) {
                    $rP = $payment->setField('PAID', 'N');
                    $payment->save();
                    $rP = $payment->delete();
                }
                $shipmentCollection = $order->getShipmentCollection();

                foreach ($shipmentCollection as $shipment) {
                    $r = $shipment->getFields();
                    if (!$shipment->isSystem()) {
                        $r = $shipment->setField('DEDUCTED', 'N');
                        $shipment->save();
                    }
                }

                $r = Order::delete($arOrder['ID']);

                if (!$r->isSuccess()) {
                    print_r($r->getErrorMessages());
                }
            }

            if ($arAccount = CSaleUserAccount::GetByUserID($arUser['ID'], "RUB")) {
                CSaleUserAccount::delete($arAccount['ID']);
            }

            if (!CUser::Delete($arUser['ID'])) {
                $r = $APPLICATION->getException();

                print_r($r);
            }
        }
    }
}