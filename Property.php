<?php


namespace Axxon\SaleClient\Model;


use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Type\Date;
use CIBlockProperty;
use Exception;

class Property
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
        if ($uniqBy) {
            $filter = [];
            foreach ($uniqBy as $key) {
                if (array_key_exists($key, $fields)) {
                    $filter[$key] = $fields[$key];
                }
            }

            $row = PropertyTable::getList([
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
            PropertyTable::update($id, ['fields' => $fields]);

            if (array_key_exists('VALUES', $fields)) {
                $propertyEnumValues = [];
                $rows = PropertyEnumerationTable::getList([
                    'select' => ['ID', 'XML_ID'],
                    'filter' => ['PROPERTY_ID' => $id],
                ]);
                while ($row = $rows->fetch()) {
                    $propertyEnumValues[$row['XML_ID']] = $row['ID'];
                }

                foreach ($fields['VALUES'] as $value) {
                    if (!array_key_exists($value['XML_ID'], $propertyEnumValues)) {
                        PropertyEnumerationTable::add([
                            'PROPERTY_ID' => $id,
                            'VALUE'       => $value['VALUE'],
                            'XML_ID'      => $value['XML_ID']
                        ]);
                    } else {
                        unset($propertyEnumValues[$value['XML_ID']]);
                    }
                }
            }
        } else {
            $id = (new CIBlockProperty())->add($fields);
        }

        return $id;
    }
}