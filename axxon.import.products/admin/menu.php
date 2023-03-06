<?php

use Axxon\Import\Products\Src\MenuBuilder;
use Bitrix\Main\Loader;

try {
    Loader::includeModule('axxon.import.products');

    $menuBuilder = new MenuBuilder();
    return $menuBuilder->build();
} catch (\Bitrix\Main\LoaderException $e) {
}

