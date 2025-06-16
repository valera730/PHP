<?php
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler('', 'B24CrmContactOnBeforeUpdate', [EventsB24CrmContact::class, 'OnBeforeUpdate']);

class EventsB24CrmContact {
    function OnBeforeUpdate(\Bitrix\Main\Entity\Event $event) {

    	$result = new \Bitrix\Main\Entity\EventResult;

        $id = $event->getParameter("id");
        $id = $id["ID"];

        $entity = $event->getEntity();
        $entityDataClass = $entity->GetDataClass();

        $arFields = $event->getParameter("fields");

        $item = $entityDataClass::getByPrimary($id)->fetch();

        $initiator = 46;

        if(defined("B24_INITIATOR_B24"))
            $initiator = 47;

        if(defined("B24_INITIATOR_1С"))
            $initiator = 48;

        $rsData = \Bitrix\Highloadblock\HighloadBlockTable::getById(43);
        if ($arData = $rsData->fetch()) {
            $entityLog = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arData);
            $DataClassLog = $entityLog->getDataClass();

            foreach ($arFields as $key => $value) {
                if(in_array($key, ['UF_UPDATE', 'UF_DATE_MODIFY', 'UF_CRM_1608879221'])) continue;

                if($value != $item[$key]) {
                    $DataClassLog::add([
                        'UF_INTIATOR' => $initiator,
                        'UF_TIMESTAMP' => new \Bitrix\Main\Type\DateTime(),
                        'UF_TYPE' => 44,
                        'UF_CRM_ID' => $id,
                        'UF_B24_ID' => $item['UF_ENTITY_ID'],
                        'UF_FIELD' => $key,
                        'UF_OLD_VALUE' => $item[$key],
                        'UF_NEW_VALUE' => $value
                    ]);
                }
            }
        }

    	return $result;
    }
}