<?php

namespace Axxon\Import\Products\Src;
use Bitrix\Main\Localization\Loc;
/**
 * Class MenuBuilder
 * @package Axxon\Import\Products\Src
 */
class MenuBuilder
{
    /**
     * @return array
     */
    public function build()
    {
        return [
            'parent_menu' => 'global_menu_settings',
            'section'     => 'axxon.import.products',
            'sort'        => 50,
            'text'        => 'Axxon',
            'icon'        => 'iblock_menu_icon_settings',
            'page_icon'   => 'iblock_menu_icon_settings',
            'items_id'    => 'axxon_import_products_items',
            'items'       => $this->makeMenuItems(),
        ];
    }

    /**
     * @return array
     */
    private function makeMenuItems()
    {
        return [
            [
                'text'      => Loc::getMessage('AXXON_IMPORT_PAGE_NAME'),
                'title'     => Loc::getMessage('AXXON_IMPORT_PAGE_NAME'),
                'module_id' => 'axxon.import.products',
                'items_id'  => 'axxon_import_products_items',
                'url'       => sprintf('axxon_import_products.php')
            ]
        ];
    }
}