<?php

namespace App\Service;

use App\Exceptions\ImportException;
use App\Models\SupportedDevices;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeviceImporter
{
    /** @var integer номер строки с названиями свойств в Excel */
    private const ROW_PROPERTY_NAME = 2;

    /** @var integer c какой строки начинаются данные в Excel */
    private const ROW_START_DATA = 3;

    /** @var string буква столбца Excel в которой названия моделей */
    private const MODEL_COLUMN = 'B';

    /** @var string буква столбца Excel в которой статусы интеграции AXXON_NEXT */
    private const AN_STATUS_COLUMN = 'F';

    /** @var string буква столбца Excel в которой статусы интеграции INTELLECT */
    private const AI_STATUS_COLUMN = 'X';

    /** @var string буква столбца Excel в которой значения свойств PTZ_IN_AXXON_NEXT */
    private const AN_PTZ_STATUS = 'Q';

    /** @var string буквы столбца Excel в которой значения свойств PTZ_IN_INTELLECT */
    private const AI_PTZ_STATUS = 'AJ';

    /** @var string Литера первой колонки */
    private const FIRST_COLUMN = 'A';

    /** @var string Литера последней колонки */
    private const LAST_COLUMN = 'AV';

    /** @var string Таблица с легендой в excel-файле */
    private const LEGEND_SHEET_TITLE = 'Legend';

    /** @var array статусы интеграции в Excel при которых не добавляем устроиство */
    private const BAD_INTEGRATION_STATUSES = [
        'Не заявлять',
        'Интегрировано с критическими ошибками',
        'Не Интегрировано'
    ];

    /** @var integer номер строки с фразами в Excel */
    private const ROW_START_LEGEND_DATA = 2;

    /** @var string Буква столбца с ключом для переводов в легенде */
    private const LEGEND_KEY_COLUMN = 'A';

    /** @var string Первый столбец содержащий локали */
    private const FIRST_LOCALE = 'B';

    /** @var array Список фраз для замены */
    private array $legends = [];

    /** @var string Ошибки импорта */
    public $errors = [];

    /** @var ?string Статус импорта */
    public ?string $status = null;

    /** @var array  массив доступных локалей */
    private array  $locales;

    /**
     * Функция перевода русских сокращенных фраз в английский не сокращенный
     *
     * @param string $phrase - фраза, которую нужно перевести
     * @param string $lang - язык, на который нужно перевести
     * @param string $separator
     * @return string
     */
    private function translate(string $phrase, string $lang, string $separator = ';#'): string
    {
        $chunks = explode($separator, $phrase);

        foreach ($chunks as $i => $chunk) {
            $chunk = mb_strtolower(trim($chunk));

            if (isset($this->legends[$lang][$chunk])) {
                $chunks[$i] = $this->legends[$lang][$chunk];
            } elseif (mb_strpos($chunk, ': ')) {
                $chunks[$i] = $this->translate($chunk, $lang, ': ');
            }
        }

        return implode($separator, array_filter($chunks));
    }


    /**
     * Функция замены типа камеры в зависимости от комплектации
     *
     * @param Worksheet $sheet
     * @param int $rowIndex
     * @return string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function replaceCameraType(Worksheet $sheet, int $rowIndex): string
    {
        // получаем значение свойства PTZ_IN_AXXON_NEXT
        $ptzNext = $sheet->getCell(self::AN_PTZ_STATUS . $rowIndex)->getValue();

        // получаем значение свойства PTZ_IN_AXXON_INTELLECT
        $ptzIntellect = $sheet->getCell(self::AI_PTZ_STATUS . $rowIndex)->getValue();

        if (($ptzNext == 'ЧИ' || $ptzNext == 'И') || ($ptzIntellect == 'ЧИ' || $ptzIntellect == 'И')) {
            return 'PTZ Camera';
        }

        return 'Fixed Camera';
    }

    /**
     * Функция заменяет пробел, тире, точку на нижнее подчеркивание
     * Удаляет всё кроме a-zA-Z 0-9 и _
     * Приводит к верхнему регистру
     *
     * @param string $string
     * @return string
     */
    private function normalizeString(string $string): string
    {
        $cyrillic = [
            'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
            'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
            'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'
        ];
        $latin    = [
            'a', 'b', 'v', 'g', 'd', 'e', 'io', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p',
            'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sht', 'a', 'i', 'y', 'e', 'yu', 'ya',
            'A', 'B', 'V', 'G', 'D', 'E', 'Io', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P',
            'R', 'S', 'T', 'U', 'F', 'H', 'Ts', 'Ch', 'Sh', 'Sht', 'A', 'I', 'Y', 'e', 'Yu', 'Ya'
        ];

        $string = str_replace($cyrillic, $latin, $string);

        return strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($string)));
    }

    /**
     * Функция получения данных из excel файла в нужном формате
     *
     * @param Worksheet $sheet
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function loadDataFromXls(Worksheet $sheet): array
    {
        /** array $devices - результирующий массив с устройствами */
        $devices = [];

        /** array $propCode - */
        $propCode = [];

        /** string $tableName - название таблицы в БД */
        $tableName = SupportedDevices::query()->getModel()->getTable();

        // соберем все свойства
        foreach ($sheet->getRowIterator(self::ROW_PROPERTY_NAME, self::ROW_PROPERTY_NAME) as $row) {
            $cellIterator = $row->getCellIterator(self::FIRST_COLUMN, self::LAST_COLUMN);

            foreach ($cellIterator as $cell) {
                $cellValue = $cell->getValue() ?? '';
                $cellIndex = $cell->getColumn();

                $cellValue = $cellValue ? $cellValue : $sheet->getCell($cellIndex . '1')->getValue();

                $propName             = $this->normalizeString($cellValue);
                $propCode[$cellIndex] = $propName;

                if (!Schema::hasColumn($tableName, $propName)) {
                    Schema::table($tableName, function (Blueprint $table) use ($propName) {
                        $table->longText($propName)->nullable();
                    });
                }
            }
        }

        // соберем значения
        foreach ($sheet->getRowIterator(self::ROW_START_DATA) as $rowIndex => $row) {
            try {
                $cellIterator = $row->getCellIterator(self::FIRST_COLUMN, self::LAST_COLUMN);
                /** $modelName - название модели в таблице */
                $modelName = $sheet->getCell(self::MODEL_COLUMN . $rowIndex)->getValue();
                /** $axxonNextStatus - статус интеграции AXXON NEXT */
                $axxonNextStatus = $sheet->getCell(self::AN_STATUS_COLUMN . $rowIndex)->getValue();
                /** $intellectStatus - статус интеграции AXXON INTELLECT */
                $intellectStatus = $sheet->getCell(self::AI_STATUS_COLUMN . $rowIndex)->getValue();
                if (
                    !$modelName
                    || in_array($axxonNextStatus, self::BAD_INTEGRATION_STATUSES)
                    || in_array($intellectStatus, self::BAD_INTEGRATION_STATUSES)
                ) {
                    continue;
                }

                $devices[$rowIndex]['model'] = $modelName;
                $devices[$rowIndex]['slug']  = Str::slug($modelName, '-');

                foreach ($cellIterator as $cell) {
                    $cellValue = $cell->getValue() ?? '';
                    $cellIndex = $cell->getColumn();

                    if ($propCode[$cellIndex] == 'TYPE' && $cellValue == 'Camera') {
                        $cellValue = $this->replaceCameraType($sheet, $rowIndex);
                    }

                    if (!in_array($propCode[$cellIndex], SupportedDevices::NOT_TRANSLATABLE)) {
                        $translations = [];

                        foreach ($this->locales as $lang) {
                            $translations[$lang] = $this->translate($cellValue, $lang);
                        }

                        $cellValue = json_encode($translations, JSON_UNESCAPED_UNICODE);
                    }

                    $devices[$rowIndex][$propCode[$cellIndex]] = $cellValue;
                }
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }

        return $devices;
    }

    /**
     * Отсекает все нечитаемые символы по краям строки
     *
     * @param string $value
     * @return string
     */
    private function trim(string $value): string
    {
        return preg_replace('/(^[^\pL\pM_-]|[^\pL\pM_-]$)/u', '', $value);
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    private function initLocales(Worksheet $sheet): array
    {
        $row          = $sheet->getRowIterator(1)->current();
        $cellIterator = $row->getCellIterator(self::FIRST_LOCALE);

        $cellIterator->setIterateOnlyExistingCells(true);

        $locales = [];
        foreach ($cellIterator as $cell) {
            $column = '';
            sscanf($cell->getCoordinate(), "%[A-Z]", $column);

            if (!$cell->getValue() || !$column) {
                break;
            }

            $locales[$column] = $cell->getValue();
        }

        return $locales;
    }

    /**
     * Получаем сведения из легенды
     *
     * @param Worksheet $sheet - Excel-таблица
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function initLegends(Worksheet $sheet)
    {
        $this->locales = $this->initLocales($sheet);

        foreach ($sheet->getRowIterator(self::ROW_START_LEGEND_DATA) as $row) {
            /** integer $rowIndex - id строки в Excel */
            $rowIndex = $row->getRowIndex();

            /** string $legendKey - ключ перевода в легенде */
            $legendKey = mb_strtolower($this->trim($sheet->getCell(self::LEGEND_KEY_COLUMN . $rowIndex)->getValue()));

            // Заполняем легенду переводами на соответствующих языках
            foreach ($this->locales as $columnIndex => $lang) {
                if (!array_keys($this->legends, $legendKey)) {
                    $translation = $sheet->getCell($columnIndex . $rowIndex)->getValue();

                    if ($translation) {
                        $this->legends[$lang][$legendKey] = $this->trim($translation);
                    } else {
                        $this->legends[$lang][$legendKey] = '';
                    }
                }
            }
        }

        foreach ($this->locales as $lang) {
            uksort($this->legends[$lang], function ($a, $b) {
                return strlen($a) < strlen($b);
            });
        }
    }

    /**
     * Добавляет новые элементы
     *
     * @param array $data
     * @return void
     */
    private function addElements(array $data): void
    {
        foreach ($data as $props) {
            try {
                SupportedDevices::upsert($props, [], array_keys($props));
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
    }

    /**
     * Функция для удаления элементов с инфоблока с исключениями
     *
     * @return void
     */
    private function deleteElements(): void
    {
        SupportedDevices::query()
            ->where('updated_at', '<', Carbon::today()->startOfDay())
            ->delete();
    }

    /**
     * Импортирует устройства из Excel в базу данных
     *
     * @param string $fileName
     * @throws Exception
     */
    public function loadFromXlsFile(string $fileName)
    {
        if (!file_exists($fileName)) {
            throw new Exception(sprintf("File '%s' not found", $fileName), 400);
        }

        $excel       = IOFactory::load($fileName);
        $legendSheet = $excel->getSheetByName(self::LEGEND_SHEET_TITLE);

        if (!$legendSheet) {
            throw new Exception(
                sprintf("Worksheet '%s' not found.", self::LEGEND_SHEET_TITLE),
                400
            );
        }

        $this->initLegends($legendSheet);
        $devices = $this->loadDataFromXls($excel->getSheetByName('for_docs'));

        if ($devices) {
            $this->addElements($devices);
            $this->deleteElements();
        }

        if ($this->errors) {
            throw new ImportException(implode("\n", $this->errors));
        }
    }
}
