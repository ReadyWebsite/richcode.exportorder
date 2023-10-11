<?
namespace Richcode\Export\Options;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
define('RC_EXPORT_ORDER_MODULE_ID', 'richcode.exportorder');

use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

abstract class BaseConfig
{
    protected static $fields = [];
    protected static $prefix = '';
    protected static $moduleId = '';

    /**
     * Получение значения указанного свойства.
     *
     * @param $name - название свойства, значение которого необходимо получить.
     * @param string $default - значение по умолчанию, если не установлено значение.
     *
     * @return string|null
     */
    public static function getParam($name, $default = '')
    {
        return Option::get(static::$moduleId, static::getOptionName($name), $default);
    }

    /**
     * Установка нового значения для указанного свойства.
     *
     * @param string $name - название свойства.
     * @param string $value - новое значение свойства.
     */
    public static function setParam($name, $value)
    {
        Option::set(static::$moduleId, static::getOptionName($name), $value);
    }

    /**
     * В таблице b_option длинна столбца NAME равно 50 символам.
     * Поэтому обрезаем лишнюю часть, во избежание неочевидных багов.
     * @param string $name - название настройки.
     * @return string
     */
    protected static function getOptionName($name) {
        return substr(static::$prefix . $name, 0, 50);
    }

    /**
     * Сохранение настроек текущего модуля. Данные берутся из $_REQUEST.
     */
    public static function saveSettings()
    {
        static::onBeforeSaveSettings();

        $request = Context::getCurrent()->getRequest();
        $server = Context::getCurrent()->getServer();
        if ($request->isPost()) {
            foreach (static::$fields as $field) {
                if ($field == 'password' && $request[$field] == '') {
                    continue;
                }
                if (isset($request[$field])) {
                    static::setParam($field, $request[$field]);
                }
            }

            static::onAfterSaveSettings();

            LocalRedirect($server->getRequestUri());
        }
    }

    protected static function onBeforeSaveSettings() { }
    protected static function onAfterSaveSettings() { }

    /**
     * Получение сохранённых данных настроек текущего модуля:
     *
     * @return array
     * [
     *     optionName1 => optionValue1,
     *     optionName2 => optionValue2,
     *     ...,
     *     optionNameN => optionValueN,
     * ]
     */
    public static function getSettings()
    {
        $data = [];
        foreach (static::$fields as $field) {
            if ($field == 'password') {
                $data[$field] = '';
                continue;
            }
            $data[$field] = static::getDefaultValue($field);

            if ($data[$field] == '') {
                $data[$field] = static::getParam($field);
            }
        }

        return $data;
    }

    /**
     * Получение значения для указанного свойства.
     *
     * @param $name - название свойства.
     * @return string
     */
    public static function getDefaultValue($name)
    {
        return '';
    }
}

class Config
    extends BaseConfig
{
    /**
     * |------------------------------------------------------------------------------
     * |          Массив названий всех полей настроек текущего модуля.               |
     * |------------------------------------------------------------------------------
     *  |
     * |------------------------------------------------------------------------------
     */
    protected static $fields = [
        'USE_API',
        'URL',
        'AUTH_ID',
        'USER_ID',
    ];

    /**
     * @param string $prefix - префикс, добавляющийся к названию настройки при сохранении.
     */
    protected static $prefix = 'rc_api_';

    protected static $moduleId = RC_EXPORT_ORDER_MODULE_ID;

    public static function useApi()
    {
        return parent::getParam('USE_API', 'Y') == 'Y';
    }

    public static function getUrl(){
        return parent::getParam('URL','');
    }

    public static function getAuthId(){
        return parent::getParam('AUTH_ID','');
    }

    public static function getUserId(){
        return parent::getParam('USER_ID','');
    }



    public static function getDefaultValue($name)
    {
        $funcs = [
            'URL' => function() { return static::getUrl(); },
            'AUTH_ID' => function() { return static::getAuthId(); },

        ];

        $res = $funcs[$name];

        return $res ? $res() : '';
    }

    protected static function onBeforeSaveSettings()
    {
        $request = Context::getCurrent()->getRequest();
        $arRequest = $request->toArray();

        // Чекбоксы не добавляются в случае пустого значения.
        // Поэтому немного модифицируем запрос (добавляем значение N).
        $arRequest['USE_API'] = $arRequest['USE_API'] == 'on' ? 'Y' : 'N';

        $request->set($arRequest);
    }

}