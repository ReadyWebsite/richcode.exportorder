<?
namespace RichCode\Export;

use \Bitrix\Main\Web\Json,
    \Bitrix\Main\UserTable,
    \Bitrix\Sale,
    \Bitrix\Main\SystemException,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\Web\HttpClient;

Loc::loadMessages(__FILE__);

class B24
{

    public static function query ($method, $data)
    {
        if(!Options\Config::useApi())
            return;
        $http = new HttpClient();
        $http->setTimeout(5);

        $url = Options\Config::getUrl();
        $auth_id = Options\Config::getAuthId();
        $user_id = Options\Config::getUserId();

        //временный костыль для второго сайта
        if (SITE_ID == 'rw')
        {
            $url = "readywebsite.bitrix24.ru";
            $auth_id = "35v69sjn7xdee5lc";
            $user_id = 1;
        }
        //////////////////////////////////////////

        $result = $http->post(
            'https://' . $url . '/rest/' . $user_id . '/' . $auth_id . '/' . $method,
            $data
        );
       // echo "<pre>data1 "; print_r($data); echo "</pre>";
        $result = Json::decode($result);

       // echo "<pre>data2 "; print_r($data); echo "</pre>";
      //  echo "<pre>result "; print_r($result); echo "</pre>";
      //  exit;
        
        return $result;
    }

    public static function addDeal ($arFields)
    {
        $data = http_build_query(array(
            'fields' => $arFields
        ));

        return self::query('crm.deal.add', $data);
    }

    public static function addContact ($arFields)
    {
        $data = http_build_query(array(
            'fields' => $arFields
        ));

        return self::query('crm.contact.add', $data);
    }


    public static function getContactByEmail ($arEmails)
    {
        foreach ($arEmails as &$email)
        {
            if (!check_email($email))
                unset($email);
        }

        if (count($arEmails) <= 0)
            return false;

        $data = http_build_query(array(
            'filter' => array("EMAIL" => $arEmails)
        ));

        $result = self::query('crm.contact.list', $data);

        if ($result['total'] > 0)
            return $result['result'][0];
        else
            return false;

    }

    public static function addBasketForDeal (int $dealID, array $arBasket)
    {
        $data = http_build_query(array(
            'id' => $dealID,
            'rows' => $arBasket
        ));

        $result  = self::query('crm.deal.productrows.set', $data);
      //   echo "<pre><hr>datat: "; print_r($data); echo "</pre>";
     //   echo "<pre><hr>result: "; print_r($result); echo "</pre>";
    //    exit;
        return $result;
    }

    public static function addDealFromOrder(\Bitrix\Main\Event $event)
    {

        try
        {
            $order = $event->getParameter("ENTITY");
           
            if(!$order->getId())
                throw new SystemException(Loc::getMessage('RC_B24ERR_ORDER_NOT_FOUND'));

            $arEmails = [];
            $arPhones = [];

            $propertyCollection = $order->getPropertyCollection();
           
            
            $emailPropValue = $propertyCollection->getUserEmail();
            $arEmails[] =  $emailPropValue->getValue();
          

            $phonePropValue = $propertyCollection->getPhone();
            $arPhones[] = $phonePropValue->getValue();
             
          // echo "<br>user: ". $order->getUserId();
            $res = UserTable::getList(array(
                "filter" => array("=ID" => $order->getUserId()),
                "select" => array('NAME', 'LAST_NAME', 'SECOND_NAME', "EMAIL", "PERSONAL_PHONE",  'WORK_PHONE')
            ));
           // echo "<pre>res "; print_r($res); echo "</pre>";
           // exit;
            if (!$arUser = $res->fetch())
                throw new SystemException(Loc::getMessage('RC_B24ERR_USER_NOT_FOUND'));
           // echo "<pre>arUser "; print_r($arUser); echo "</pre>";
            $arEmails[] = $arUser["EMAIL"];

            if (!empty($arUser['PERSONAL_PHONE']))
                $arPhones[] = $arUser["PERSONAL_PHONE"];

            if (!empty($arUser['WORK_PHONE']))
                $arPhones[] = $arUser["WORK_PHONE"];

            $arEmails = array_unique($arEmails);
            $arPhones = array_unique($arPhones);

            
            
            if ($arContact = self::getContactByEmail($arEmails)):
                $contactID = $arContact["ID"];
            else:

                $arB24Emails = self::getMultiVals($arEmails);
                $arB24Phones = self::getMultiVals($arPhones);

                $arContactFields = array(
                    "NAME" => $arUser["NAME"],
                    "SECOND_NAME" => $arUser["SECOND_NAME"],
                    "LAST_NAME" => $arUser["LAST_NAME"],
                    "TYPE_ID" => "CLIENT",
                    "PHONE" => $arB24Phones,
                    "EMAIL" => $arB24Emails,
                );
            
                $result = self::addContact($arContactFields);

                if (self::answerIsError($result))
                    throw new SystemException(Loc::getMessage("RC_B24ERR_ADD_CONTACT") . $result['error_description']);

                $contactID = $result["result"];

            endif;

            if (empty($contactID))
                throw new SystemException(Loc::getMessage("RC_B24ERR_NOT_CONTACT"));

            $basket = $order->getBasket();
           // echo "<pre>basket "; print_r($basket); echo "</pre>";
            $arDealFields  = array(
                "TITLE" => Loc::getMessage("RC_B24_ORDER_NUMBER", array('#ORDER_ID#' => $order->getField('ACCOUNT_NUMBER'))),
                "CONTACT_ID" => $contactID,
                'OPPORTUNITY' => $basket->getPrice(),
                'ADDITIONAL_INFO' => $order->getField('USER_DESCRIPTION'),
            );
        
            $result = self::addDeal($arDealFields);
            if (self::answerIsError($result))
            	{
            		
				throw new SystemException(Loc::getMessage("RC_B24ERR_DEAL_ADD") . $result['error_description']);	
            	}
                

            $dealID = $result["result"];
            $arBasketItems = [];

            foreach ($basket as $basketItem)
            {
                $arBasketItems[] = array(
                   // 'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
                    'PRODUCT_NAME' => $basketItem->getField('NAME'),
                    'PRICE' => $basketItem->getField('PRICE'),
                    'QUANTITY' => $basketItem->getQuantity(),
                );
            }
          // echo "<pre><hr>arBasketItems: "; print_r($arBasketItems); echo "</pre>";
           
            $result = self::addBasketForDeal($dealID, $arBasketItems);
            if (self::answerIsError($result))
                throw new SystemException(Loc::getMessage("RC_B24ERR_GOODS") .  $result['error_description']);
       
        //echo "<pre><hr>result: "; print_r($result); echo "</pre>";
        //exit;
        }
        catch (SystemException $exception)
        {
            \CEventLog::Add(array(
                "SEVERITY" => "WARNING",
                "AUDIT_TYPE_ID" => "B24_INTEGRATION_ERR",
                "MODULE_ID" => "sale",
                "ITEM_ID" => $order->getId(),
                "DESCRIPTION" => Loc::getMessage("RC_B24ERR", array('#ORDER_ID#' => $order->getId())) .  $exception->getMessage(),
            ));
        }

    }

    protected function answerIsError(array $result)
    {
        if (array_key_exists("error", $result))
            return true;
        else
            return false;
    }

    protected function getMultiVals (array $arData)
    {
        $arB24Data = [];

        foreach ($arData as $data)
        {
            $arB24Data[] = array("VALUE" => $data);
        }

        return $arB24Data;
    }
}