<?php


namespace Axxon\SaleClient\Model;


use Bitrix\Iblock\InheritedProperty\SectionValues;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Type\Date;
use CIBlockSection;
use Exception;

class Catalog
{
    /**
     * @param array $fields
     * @param array $uniqBy
     * @return string|null
     *
     * @throws Exception
     */
    public static function upsert(array $fields, array $uniqBy = []): ?string
    {
        $id = null;
        $sectionTable = new CIBlockSection();

        if ($uniqBy) {
            $filter = [];
            foreach ($uniqBy as $key) {
                if (array_key_exists($key, $fields)) {
                    $filter[$key] = $fields[$key];
                }
            }

            $row = SectionTable::getList([
                'select' => [
                    'ID'
                ],
                'filter' => $filter,
                'limit'  => 1
            ])->fetch();

            if ($row) {
                $id = $row['ID'];
            }
        }

        $fields['TIMESTAMP_X'] = new Date();

        if ($id) {
            $sectionTable->Update($id, $fields);
        } else {
            $fields = array_merge([
                'DATE_CREATE'   => new Date(),
                'ACTIVE'        => 'Y',
                'GLOBAL_ACTIVE' => 'Y'
            ], $fields);

            $id = $sectionTable->Add($fields);
        }

        $sectionValues = new SectionValues($fields['IBLOCK_ID'], $id);
        $sectionValues->clearValues();

        if ($sectionTable->LAST_ERROR) {
            throw new Exception($sectionTable->LAST_ERROR);
        }

        return $id;
    }
}