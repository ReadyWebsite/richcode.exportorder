<?
namespace Richcode\Export;

class EventHandlers
{
    public static function OnSaleOrderSaved(\Bitrix\Main\Event $event)
    {
        if(!$event->getParameter("IS_NEW"))
            return;

        B24::addDealFromOrder($event);
    }
}