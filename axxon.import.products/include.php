<?php

namespace Axxon\Import\Products;

use CJSCore;
use CModule;

CJSCore::Init([
    'jquery'
]);

CModule::AddAutoloadClasses('axxon.import.products', [
    'Axxon\Import\Products\Src\MenuBuilder' => 'src/MenuBuilder.php',
    'Axxon\Import\Products\Src\MdbConvert' => 'src/MdbConvert.php',
    'Axxon\Import\Products\Src\UpdateProducts' => 'src/UpdateProducts.php'
]);
