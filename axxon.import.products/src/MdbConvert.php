<?php

namespace Axxon\Import\Products\Src;

use CCSVData;

class  MdbConvert
{
    /**
     * @var array
     */
    private $products;
    private $props;
    private $icons;
    private $langFields = ['sprache0', 'sprache1', 'sprache2', 'sprache3', 'sprache7'];
    private $langFieldsDesc = ['DESCRIPTION_L_0', 'DESCRIPTION_L_1', 'DESCRIPTION_L_2', 'DESCRIPTION_L_3', 'DESCRIPTION_L_7'];
    private $dirTables = 'tables';
    private $sectionFilters = [];

    /**
     * MdbConvert constructor.
     * @param $filepath
     */
    public function __construct($filepath)
    {
        exec('sh mdb_convert.sh -f ' . $filepath . ' -o ' . $this->dirTables);

        $this->products = $this->getTable('artikel', ["ART_ID", "KZ_BEZ", "SORT", "ANL_DAT", "ART_STATUS"]);
        $this->props = $this->getTable('aa_techcat_d', array_merge(['cat_type', 'sort'], $this->langFields));
        $this->icons = $this->getTable('aa_icon_d', ['id', 'icon', 'bild1']);

        $h0 = $this->getTable('aa_headline0_d', array_merge(['ID_PC', 'Sortierung'], $this->langFields));
        $h1 = $this->getTable('aa_headline1_d', array_merge(['ID_PC', 'Sortierung'], $this->langFields));
        $h2 = $this->getTable('aa_headline2_d', array_merge(['ID_PC', 'Sortierung'], $this->langFields));

        $relatedProducts = $this->getTable('artikel', ["ART_ID", "KZ_BEZ"]);
        $description = $this->getTable('aa_features_d', array_merge(['id'], $this->langFields));
        $propValues = $this->getTable('aa_techdata_d', array_merge(["id", "catid"], $this->langFields));
        $propValueTypes = $this->getTable('aa_techcat_d', ["id", "cat_type"]);

        $this->assignMultiPropToProduct('artikel', ["ART_ID", "HL_0"], 'ART_ID', $h0, 'HL_0', 'H0');
        $this->assignMultiPropToProduct('artikel', ["ART_ID", "HL_1"], 'ART_ID', $h1, 'HL_1', 'H1');
        $this->assignMultiPropToProduct('artikel', ["ART_ID", "HL_2"], 'ART_ID', $h2, 'HL_2', 'H2');
        $this->assignMultiPropToProduct('aa_p2ac_d', ['id', 'productid', 'accid'], 'productid', $relatedProducts,
            'accid', 'accid');
        $this->assignMultiPropToProduct('aa_p2icon_d', ['aa_p2icon_d', 'productid', 'iconid', 'sortierung'], 'productid',
            $this->icons, 'iconid', 'icons');
        $this->assignMultiPropToProduct('aa_p2fe_d', ['id', 'productid', 'featureid', 'sortierung'], 'productid', $description,
            'featureid', 'featureids');

        $this->assignSinglePropToProduct('aa_assets',
            ['ID', 'assets_id', 'as_type', 'as_file', 'as_language', 'as_product_id', 'as_publish'], 'as_product_id',
            'docs');
        $this->assignSinglePropToProduct('aa_description_sh_d',
            array_merge(['ID', "PRODUCT_ID"], $this->langFieldsDesc), 'PRODUCT_ID', 'name');

        $this->addPropsProducts('aa_p2tctd_d', ["productid", "techdataid", 'sortierung', 'ca', 'status'], $propValues,
            $propValueTypes);

        $this->setSectionFilter($h0, $h1);

    }


    /**
     * Функция получает из бд массив данный с указанных полей
     *
     * @param $tableName
     * @param array $fields
     * @return array
     */
    public function getTable($tableName, $fields = [])
    {
        $keys = [];
        $result = [];
        $fileName = $this->dirTables . '/' . $tableName . '.csv';

        if (($handle = fopen($fileName, 'r')) !== false) {
            while (($row = $this->fgetcsv($handle, 10000, ",", '"')) !== false) {
                if (!$keys) {
                    $keys = $row;
                    continue;
                }

                if (
                    $fields
                    && ($row = array_combine($keys, $row))
                    && array_key_exists($fields[0], $row)
                ) {
                    $result[$row[$fields[0]]] = array_intersect_key($row, array_flip($fields));
                } else {
                    $result[] = array_combine($keys, $row);
                }
            }
            fclose($handle);
        }

        return $result;
    }

    /**
     * Функия расширяет  массив
     *
     * @param string $tableName Имя таблицы с данными
     * @param array $fields массив полей
     * @param string $productId ключ по которому связаны данные
     * @param string $propName имя свойства в который будут записываться новые данные
     */
    private function assignSinglePropToProduct($tableName, $fields, $productId, $propName)
    {
        $keys = [];
        $fileName = $this->dirTables . '/' . $tableName . '.csv';

        if (($handle = fopen($fileName, 'r')) !== false) {
            while (($row = $this->fgetcsv($handle, 1000, ",")) !== false) {
                if (!$keys) {
                    $keys = $row;
                    continue;
                }

                if (
                    $fields
                    && ($row = array_combine($keys, $row))
                    && ($row = array_intersect_key($row, array_flip($fields)))
                    && array_key_exists($productId, $row)
                    && array_key_exists($row[$productId], $this->products)
                ) {
                    $this->products[$row[$productId]][$propName][] = $row;
                }
            }
            fclose($handle);
        }
    }

    /**
     * @param string $tableName
     * @param array $fields
     * @param string $productId
     * @param array $propValues
     * @param string $propValueId
     * @param string $propName
     */
    private function assignMultiPropToProduct($tableName, $fields, $productId, $propValues, $propValueId, $propName)
    {
        $keys = [];
        $productId = trim($productId);
        $propValueId = trim($propValueId);
        $fileName = $this->dirTables . '/' . $tableName . '.csv';
        if (($handle = fopen($fileName, 'r')) !== false) {
            while (($row = $this->fgetcsv($handle, 1000, ",")) !== false) {
                if (!$keys) {
                    $keys = $row;
                    continue;
                }
                if (
                    $fields
                    && ($row = array_combine($keys, $row))
                    && ($row = array_intersect_key($row, array_flip($fields)))
                    && array_key_exists((int)$row[$productId], $this->products)
                    && array_key_exists((int)$row[$propValueId], $propValues)
                ) {
                    $this->products[$row[$productId]][$propName][] = array_merge($propValues[$row[$propValueId]], $row);
                }
            }
            fclose($handle);
        }
    }

    /**
     * @param string $tableName
     * @param array $fields
     * @param array $propsValue
     * @param array $propsType
     */
    private function addPropsProducts($tableName, $fields, $propsValue, $propsType)
    {
        $keys = [];
        $fileName = $this->dirTables . '/' . $tableName . '.csv';

        if (($handle = fopen($fileName, 'r')) !== false) {
            while (($row = $this->fgetcsv($handle, 1000, ",")) !== false) {
                if (!$keys) {
                    $keys = $row;
                    continue;
                }

                if (
                    $fields
                    && ($row = array_combine($keys, $row))
                    && ($row = array_intersect_key($row, array_flip($fields)))
                    && array_key_exists($row['productid'], $this->products)
                    && array_key_exists((int)$row['techdataid'], $propsValue)
                    && array_key_exists((int)$propsValue[$row['techdataid']]['catid'], $propsType)
                    && (int)$row['status'] > 0
                ) {
                    if (
                        (int)$row['ca'] != 0 &&
                        !array_key_exists(
                            'filter',
                            $this->props[$propsType[(int)$propsValue[$row['techdataid']]['catid']]['cat_type']]
                        )
                    ) {
                        $this->props[$propsType[(int)$propsValue[$row['techdataid']]['catid']]['cat_type']]['filter'] = 'Y';
                    }

                    $this->products[$row['productid']]['props'][$propsType[(int)$propsValue[$row['techdataid']]['catid']]['cat_type']] = array_merge($propsValue[$row['techdataid']],
                        ['sort' => $row['sortierung']]);
                }
            }
            fclose($handle);
        }
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @return array
     */
    public function getProps()
    {
        return $this->props;
    }

    /**
     * @return array
     */
    public function getIcons()
    {
        return $this->icons;
    }

    /**
     * Формируем массив разделов с свойствами для вывода в умном фильтре
     * @param $h0 Список разделов 1 уровня
     * @param $h1 Список разделов 2 уровня
     */
    private function setSectionFilter($h0, $h1)
    {
        $properties = $this->getTable('aa_techcat_d', ['id', 'cat_type', 'sort']);
        $filterTable = $this->getTable('aa_techcat_filter_d', ['id', 'cat_id', 'sort', 'status', 'HL_0', 'HL_1']);
        foreach ($filterTable as $key => $filter) {
            if ($filter['status'] == '1') {
                $section = mb_strtolower(preg_replace('/[^\da-z-_]+/miu', '-', trim($h1[$filter['HL_1']]['sprache0'])));
                $parentSection = mb_strtolower(preg_replace('/[^\da-z-_]+/miu', '-',
                    trim($h0[$filter['HL_0']]['sprache0'])));
                $this->sectionFilters[$parentSection][$section][] = $properties[$filter['cat_id']]['cat_type'];
            }
        }
    }

    /**
     * возращает список разделв с свойствами для умного фильтра
     * @return array
     */
    public function getSectionFilters()
    {
        return $this->sectionFilters;
    }

    /**
     * @param $f
     * @param $length
     * @param string $d
     * @param string $q
     * @return array|false
     */
    private function fgetcsv($f, $length, $d = ",", $q = '"')
    {
        $list = [];
        $st = fgets($f, $length);

        if ($st === false || trim($st) === "") {
            return false;
        }
        while ($st !== "" && $st !== false) {
            if ($st[0] !== $q) {
                # Non-quoted.
                list ($field) = explode($d, $st, 2);
                $st = substr($st, strlen($field) + strlen($d));
            } else {
                # Quoted field.
                $st = substr($st, 1);
                $field = "";
                while (true) {
                    # Find until finishing quote (EXCLUDING) or eol (including)
                    preg_match("/^((?:[^$q]+|$q$q)*)/sx", $st, $p);
                    $part = $p[1];
                    $partlen = strlen($part);
                    $st = substr($st, strlen($p[0]));
                    $field .= str_replace($q . $q, $q, $part);
                    if (strlen($st) && $st[0] === $q) {
                        # Found finishing quote.
                        list ($dummy) = explode($d, $st, 2);
                        $st = substr($st, strlen($dummy) + strlen($d));
                        break;
                    } else {
                        # No finishing quote - newline.
                        $st = fgets($f, $length);
                    }
                }

            }
            $list[] = trim($field);
        }
        return $list;
    }
}