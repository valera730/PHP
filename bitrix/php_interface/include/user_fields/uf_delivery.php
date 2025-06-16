<?php
AddEventHandler("main", "OnAdminTabControlBegin", array("UfFieldsDelivery", "OnAdminTabEdit"));
AddEventHandler("main", "OnProlog", array("UfFieldsDelivery", "OnEventForUpdate"));
AddEventHandler("main", "OnBeforeLocalRedirect", array("UfFieldsDelivery", "OnEventForUpdate"));

class UfFieldsDelivery {
    const UF_OBJECT = "DELIVERY";
    const CURRENT_PAGE_URL = "/bitrix/admin/sale_delivery_service_edit.php";

    public static function GetValues($_id) {
        global $USER_FIELD_MANAGER;

        $_id = intval($_id);

        $arUserFields = $USER_FIELD_MANAGER->GetUserFields(self::UF_OBJECT, $_id);

        return $arUserFields;
    }

    /**
     * Событие для добавления вкладки в админку
     *
     * @param CAdminTabControl $form
     */
    public static function OnAdminTabEdit(&$form) {
        if($GLOBALS["APPLICATION"]->GetCurPage() == self::CURRENT_PAGE_URL && intval($_REQUEST['ID'])>0) {

            $_id = intval($_REQUEST['ID']);
            $arUserFields = self::GetValues($_id);

            self::AddTab($form, $arUserFields, $_id);
        }
    }

    /**
     * Добавляет вкладку в админку
     *
     * @param CAdminTabControl $form
     * @param string           $url
     */
    protected static function AddTab(&$form, $arValues, $ID) {
        global $USER_FIELD_MANAGER;

    	$tab =  $USER_FIELD_MANAGER->EditFormTab(self::UF_OBJECT);

    	ob_start();
    	$USER_FIELD_MANAGER->EditFormShowTab(self::UF_OBJECT, false, $ID);
    	$tab['CONTENT'] = ob_get_contents();
    	ob_end_clean();

    	$form->tabs[] = $tab;
    }
}