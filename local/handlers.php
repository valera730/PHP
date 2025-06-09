<?
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Main\Mail\Event;
use Bitrix\Sale;
use Bitrix\Sale\EntityMarker;
use Bitrix\Main\Type\DateTime;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;
use Bitrix\Main\Application;
use Bitrix\Highloadblock\HighloadBlockTable;

EventManager::getInstance()->addEventHandler('', 'KitchenFillingFilterOnBeforeUpdate', 'FilterOnBeforeAddUpdate');
EventManager::getInstance()->addEventHandler('', 'KitchenFillingFilterOnBeforeAdd', 'FilterOnBeforeAddUpdate');
EventManager::getInstance()->addEventHandler('', 'CabinetFillingFilterOnBeforeUpdate', 'FilterOnBeforeAddUpdate');
EventManager::getInstance()->addEventHandler('', 'CabinetFillingFilterOnBeforeAdd', 'FilterOnBeforeAddUpdate');

EventManager::getInstance()->addEventHandler(
    '',
    'PartneryNaSaytOnBeforeAdd',
    'OnBeforeAdd'
);

function FilterOnBeforeAddUpdate(\Bitrix\Main\Entity\Event $event) {
    $arFields = $event->getParameter('fields');

    $baseProps = [];
    if ($arFields['UF_BASE_PROPS'] && is_array($arFields['UF_BASE_PROPS'])) {
        foreach ($arFields['UF_BASE_PROPS'] as $key => $val) {
            TrimArr($val);

            if(preg_match('/VALS_([0-9]+)/is', $key, $m)) {
                if(!in_array($m[1], $val['PROPS'])) {
                    unset($arFields['UF_BASE_PROPS'][$key]);
                }
            }

            if(empty($val)) {
                unset($arFields['UF_BASE_PROPS'][$key]);
            }
        }

        $arFields['UF_BASE_PROPS'] = serialize($arFields['UF_BASE_PROPS']);
    }

    if ($arFields['UF_USER_PROPS'] && is_array($arFields['UF_USER_PROPS'])) {
        foreach ($arFields['UF_USER_PROPS'] as $key => $val) {
            TrimArr($val);

            if(preg_match('/VALS_([0-9]+)/is', $key, $m)) {
                if(!in_array($m[1], $val['PROPS'])) {
                    unset($arFields['UF_USER_PROPS'][$key]);
                }
            }

            if ($val == 'N') {
                unset($arFields['UF_USER_PROPS'][$key]);
            }

            if(empty($val)) {
                unset($arFields['UF_USER_PROPS'][$key]);
            }
        }

        $arFields['UF_USER_PROPS'] = serialize($arFields['UF_USER_PROPS']);
    }

    if ($arFields['UF_USER_PROPS'] == "N") {
        $arFields['UF_USER_PROPS'] = "";
    }

    $result = new \Bitrix\Main\Entity\EventResult();

    $event->setParameter("fields", $arFields);

    $result->modifyFields($arFields);

    return $result;
}

function OnBeforeAdd(\Bitrix\Main\Entity\Event $event) {
    $entity = $event->getEntity();
    $arFields = $event->getParameter("fields");
    $result = new \Bitrix\Main\Entity\EventResult();
    if (empty($arFields['UF_DATE'])) {
        $arFields['UF_DATE'] = new \Bitrix\Main\Type\DateTime();
        $result->modifyFields($arFields);
    }

    return $result;
}