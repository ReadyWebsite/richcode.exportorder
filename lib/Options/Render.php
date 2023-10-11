<?
namespace Richcode\Export\Options;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

class Render
{
    /**
     * Generates a tab, specified by arOptions with values arConfigData.
     *
     * @param array $arOptions - options for input fields.
     * [
     *     [
     *         'label' => 'label for field',
     *         'name'  => 'field_name',
     *         'type'  => 'text',
     *     ]
     * ]
     * @param array $arConfigData - values of input fields.
     *
     * @return string
     */
    public static function generateTab($arOptions, $arConfigData)
    {
        $tab = '';

        foreach ($arOptions as $key => $arOption) {
            $td1 = self::pairTag('td', [
                'VALUE' => $arOption['label'] . ':',
                'ATTRS' => [
                    'style' => 'width: 45%;',
                ],
            ]);

            // Fields haven't attribute label, so remove it.
            unset($arOption['label']);

            $value = $arConfigData[$key];
            $input = self::generate($arOption, $value);

            $td2 = self::pairTag('td', [
                'VALUE' => $input,
                'ATTRS' => [],
            ]);

            $tr = self::pairTag('tr', [
                'VALUE' => $td1 . $td2,
                'ATTRS' => [],
            ]);

            $tab .= $tr;
        }

        return $tab;
    }

    /**
     * Generates an input field, specified by arOptions.
     *
     * @param array $arOption - params of input field.
     * @param string $data - input value.
     *
     * @return string
     */
    public static function generate($arOption, $data)
    {
        $html = '';
        $hint = '';

        if ($arOption['hint']) {
            $hint = ShowJSHint($arOption['hint'], ['return' => true]);
            unset($arOption['hint']);
        }

        if ($data) {
            $arOption['value'] = $data;
        }
        switch ($arOption['type']) {
            case 'text':
            case 'password':
                $html = self::input($arOption);
                break;
            case 'checkbox':
                $html = self::checkbox($arOption);
                break;
            case 'select':
                $html = self::select($arOption);
                break;
            case 'textarea':
                $html = self::textarea($arOption);
                break;
            case 'a':
                $html = self::a($arOption);
                break;
        }

        return $html . $hint;
    }

    /**
     * Генерирование тега input на основе заданных параметров.
     *
     * @param array $params - параметры input.
     * @return string - html код input-а.
     */
    public static function input($params)
    {
        return self::singleTag('input', $params);
    }

    /**
     * Генерирование тега input на основе заданных параметров.
     *
     * @param array $params - параметры input.
     * @return string - html код input-а.
     */
    public static function checkbox($params)
    {
        if ($params['value'] == 'Y') {
            $params['checked'] = true;
        }

        unset($params['value']);

        return self::singleTag('input', $params);
    }

    /**
     * Генерирование тега select на основе заданных параметров.
     *
     * @param array $params - параметры select.
     * [
     *     'name'    => 'name_of_select',
     *     'options' => [
     *         '1' => 1,
     *         '2' => 2,
     *     ],
     *     'type'    => 'select',
     *     'value'   => '1',
     * ]
     * @return string - html код select-а.
     */
    public static function select($params)
    {
        $attrs = [
            'name' => $params['name'],
        ];

        if ($params['multiple']) {
            $attrs['multiple'] = $params['multiple'];
        }

        $selParams = [
            'ATTRS' => $attrs,
            'VALUE' => '',
        ];

        foreach ($params['options'] as $name => $value) {
            $optParams = [
                'VALUE' => $value,
                'ATTRS' => [
                    'value' => $name,
                ],
            ];

            if ($params['value'] == $name || in_array($name, $params['value'])) {
                $optParams['ATTRS']['selected'] = '';
            }

            $selParams['VALUE'] .= self::pairTag('option', $optParams);
        }

        return self::pairTag('select', $selParams);
    }

    /**
     * Генерирование тега select на основе заданных параметров.
     *
     * @param array $params - параметры тега.
     * [
     *     'type'  => 'textarea',
     *     'name'  => 'listOfPages',
     *     'label' => 'Список страниц',
     *     'rows'  => 5,
     *     'cols'  => 30,
     * ]
     *
     * @return string - html код тега.
     */
    public static function textarea($params)
    {
        $value = $params['value'];
        unset($params['value'], $params['type']);

        return self::pairTag('textarea', [
            'VALUE' => $value,
            'ATTRS' => $params,
        ]);
    }

    public static function a($params)
    {
        $value = $params['value'];
        unset($params['value'], $params['type']);

        return self::pairTag('a', [
            'VALUE' => $value,
            'ATTRS' => $params,
        ]);
    }

    /**
     * Генерирование одиночного тега.
     *
     * @param $tagName - название тега.
     * @param $tagParams - параметры тега.
     *
     * @return string - html код тега.
     */
    public static function singleTag($tagName, $tagParams)
    {
        $params = [];

        foreach ($tagParams as $name => $value) {
            $params[] = $name . '="' . $value . '"';
        }

        return "<{$tagName} " . implode(" ", $params) . " />";
    }

    /**
     * Генерирование парного тега.
     *
     * @param $tagName - название тега.
     * @param $tagParams - параметры тега.
     *
     * @return string - html код тега.
     */
    public static function pairTag($tagName, $tagParams)
    {
        $params    = [];
        $innerHtml = $tagParams['VALUE'];

        foreach ($tagParams['ATTRS'] as $name => $value) {
            $params[] = $name . '="' . $value . '"';
        }

        return "<{$tagName} " . implode(" ", $params) . " >{$innerHtml}</{$tagName}>";
    }
}

?>