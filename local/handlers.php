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

EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    'updateUserAddresses'
);

EventManager::getInstance()->addEventHandler(
    'iblock',
    'OnAfterIBlockElementAdd',
    'OnAfterIBlockElementAddHandler'
);

EventManager::getInstance()->addEventHandler(
    "iblock",
    "OnAfterIBlockElementAdd",
    "ResizeElementProperty"
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

function updateUserAddresses(\Bitrix\Main\Event $event) {
    global $USER, $APPLICATION;

    $userId = $USER->GetId();

    CModule::IncludeModule('highloadblock');

    $order = $event->getParameter("ENTITY");
    $order_id = $order->getId();
    $props = $order->getPropertyCollection();

    $addressId = $_REQUEST["ADDRESS_ID"];

    $arAddressFields = [];

    if( isset($_REQUEST['DADATA_VALUE']) && is_string($_REQUEST['DADATA_VALUE']) && is_array( json_decode($_REQUEST['DADATA_VALUE'], true) ) ) {
        $arAddressFields['UF_DADATA'] = $_REQUEST['DADATA_VALUE'];
    } else {
        $arAddressFields['UF_DADATA'] = false;
    }

    foreach ($props as $prop) :
        $code = $prop->getField("CODE");
        $value = $prop->getValue();

        switch ($code) :
            case 'CONTACT_PERSON':
                $fio = trim($value);
                break;
            case 'EMAIL':
                $email = trim($value);
                break;
            case 'PHONE':
                $phone = trim($value);
                break;
            case 'COMPANY':
                $company = trim($value);
                break;
            case 'ADDRESS':
                $address = trim($value);
                break;
            case 'D_CITY':
                $arAddressFields['UF_CITY'] = trim($value);
                break;
            case 'D_STREET':
                $arAddressFields['UF_STREET'] = trim($value);
                break;
            case 'D_HOUSE':
                $arAddressFields['UF_HOUSE'] = trim($value);
                break;
            case 'FLAT':
                $arAddressFields['UF_FLAT'] = trim($value);
                break;
            case 'ENTRANCE':
                $arAddressFields['UF_ENTRANCE'] = trim($value);
                break;
            case 'INTERCOM_CODE':
                $arAddressFields['UF_INTERCOM_CODE'] = trim($value);
                break;
        endswitch;
    endforeach;

    $hldata = HL\HighloadBlockTable::getList(['filter' => ['TABLE_NAME' => 'user_addresses']])->fetch();
    $entityClass = HL\HighloadBlockTable::compileEntity($hldata)->getDataClass();
    $data = [
        "UF_ADDRESS"=>$address,
        "UF_DATE"=>date("d.m.Y H:i:s")
    ];
    if(!empty($arAddressFields)) {
        $data = array_merge($data, $arAddressFields);
    }

    if($addressId == "new"){
        $rsData = $entityClass::getList([
            "select" => ["ID"],
            "filter" => ["UF_USER_ID"=>$userId, "UF_FIO"=>$fio, "UF_EMAIL"=>$email, "UF_PHONE"=>$phone, "UF_ADDRESS"=>$address]
        ]);
        if (!$rsData->Fetch()) {
            $data["UF_USER_ID"] = $userId;

            $result = $entityClass::add($data);
        }
    } elseif((int)$addressId > 0) {
        $rsData = $entityClass::getList([
            "select" => ["UF_USER_ID","ID","UF_ADDRESS"],
            "filter" => ["ID"=>$addressId,"UF_USER_ID"=>$userId]
        ]);
        if($arData = $rsData->Fetch()){
            $arData["UF_ADDRESS"]=trim($arData["UF_ADDRESS"]);
            $result = $entityClass::update($addressId, $data);
            if ($address != $arData["UF_ADDRESS"]) {
                $arEventFields["CLIENT_NAME"]=$fio." (ID: ".$userId.")";
                $arEventFields["ORDER_ID"]=$order_id;
                $arEventFields["CLIENT_INFO"]="Старый адрес доставки: ".$arData["UF_ADDRESS"]."<br>";
                $arEventFields["CLIENT_INFO"].="<font color='red'>Новый адрес доставки: ".$address."</font><br>";
                CEvent::Send("SALE_ADDRESS_CHANGE", ['s1'], $arEventFields);
            }
        }
    }

    if ($phone && $APPLICATION->GetCurUri() == "/personal/cart/order/" && $_REQUEST["action"] == "saveOrderAjax") {
        $rsUsers = Bitrix\Main\UserTable::GetList([
            'filter' => [
                '=ACTIVE' => 'Y',
                '=ID' => $userId,
            ],
            'select' => ['ID','PERSONAL_MOBILE'],
        ]);
        if ($arUser = $rsUsers->Fetch()) {
            if(!trim($arUser['PERSONAL_MOBILE'])) {
                $user = new CUser;
                $fields = [
                    "PERSONAL_MOBILE"   => $phone,
                ];
                $user->Update($userId, $fields);
            }
        }
    }
}

function OnAfterIBlockElementAddHandler(&$arFields) {
    if ($arFields['IBLOCK_ID'] == 45 && $arFields['ID']) {
        $arSelect = [
            "ID",
            "IBLOCK_ID",
            "NAME",
            "PREVIEW_TEXT",
            "PROPERTY_DATE",
            "PROPERTY_PROFILE",
            "PROPERTY_ADDRESS",
            "PROPERTY_FIO",
            "PROPERTY_POSITION",
            "PROPERTY_PHONE",
            "PROPERTY_EMAIL",
            "PROPERTY_GOODS",
            "PROPERTY_ART_NUM",
            "PROPERTY_QUANTITY",
            "PROPERTY_FIO_CONT",
            "PROPERTY_POSITION_CONT",
            "PROPERTY_ORDER",
            "PROPERTY_STATUS",
            "PROPERTY_GOODS_VIEW",
            "PROPERTY_DOCS",

            "CREATED_BY",
            "PROPERTY_STATUS_1C",
            "PROPERTY_ORDER_NUM",
            "PROPERTY_ORDER_NUM_1C",
            "PROPERTY_RESULT_1C",
            "PROPERTY_REASON_1C",
            "PROPERTY_STATUS_PROVIDER_1C",
        ];

        $arFilter = ["IBLOCK_ID" => $arFields['IBLOCK_ID'], "ID" => $arFields['ID']];
        $res = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);

        if ($ob = $res->GetNext()) {
            \Local\B24\B24Helper::ClaimsUpdateHandler($ob, true);
        }
    }
}

function ResizeElementProperty(&$arFields) {
    if ($arFields['IBLOCK_ID'] == 45 && $arFields['ID']) {
        $arProp = [];
        $PROPERTY_CODES = ["FILES", "PHOTO_1", "PHOTO_2", "PHOTO_3", "PHOTO_4", "PHOTO_5", "PHOTO_6"];
        $imageMaxWidth = 2048;
        $imageMaxHeight = 2048;
        foreach ($PROPERTY_CODES as $PROPERTY_CODE) {
            $arProp = [];
            $dbRes = CIBlockElement::GetProperty($arFields["IBLOCK_ID"], $arFields["ID"], "sort", "asc",
                ["CODE" => $PROPERTY_CODE]);
            while ($arMorePhoto = $dbRes->GetNext(true, false)) {
                $arFile = CFile::GetFileArray($arMorePhoto["VALUE"]);
                if (!CFile::IsImage($arFile["ORIGINAL_NAME"])) {
                    continue;
                }
                if ($arFile["WIDTH"] > $imageMaxWidth || $arFile["HEIGHT"] > $imageMaxHeight) {
                    $tmpFilePath = $_SERVER['DOCUMENT_ROOT'] . "/upload/tmp/" . $arFile["ORIGINAL_NAME"];
                    if (!file_exists($tmpFilePath)) {
                        continue;
                    }

                    $resizeRez = CFile::ResizeImageFile(
                        $_SERVER['DOCUMENT_ROOT'] . $arFile["SRC"],
                        $tmpFilePath,
                        ['width' => $imageMaxWidth, 'height' => $imageMaxHeight],
                        BX_RESIZE_IMAGE_PROPORTIONAL,
                        [],
                        95
                    );
                    if ($resizeRez) {
                        if ($PROPERTY_CODE == 'FILES') {
                            $arProp += [
                                $arMorePhoto["PROPERTY_VALUE_ID"] => [
                                    "VALUE" => CFile::MakeFileArray($tmpFilePath),
                                    "DESCRIPTION" => $arMorePhoto["DESCRIPTION"]
                                ]
                            ];
                        } else {
                            $arProp = CFile::MakeFileArray($tmpFilePath);
                        }
                    }
                }
            }
            if (!empty($arProp)) {
                CIBlockElement::SetPropertyValueCode($arFields["ID"], $PROPERTY_CODE, $arProp);
            }
        }

        $_WFILE = glob($_SERVER['DOCUMENT_ROOT'] . "/upload/tmp/*.*");
        
        foreach ($_WFILE as $_file) {
            unlink($_file);
        }

        DeleteDirFilesEx('/upload/claims/files/');
    }
}