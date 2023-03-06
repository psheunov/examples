<?php

namespace App\Console\Commands;

use App\Models\Localizable;
use App\Models\Page;
use App\Models\SitemapModels;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    const DOMAIN_TEMPLATE = 'https://%s.axxonsoft.com';

    private $pathTemplate = '%s/sitemap-%s.xml';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Создает папку если она не существует
     * @param $directory
     */
    private function createDirectory($directory)
    {
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true, true);
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $locales        = array_keys(config('multilingual.locales'));
        $fallbackLocale = config('app.fallback_locale');

        $models = SitemapModels::query()
            ->where('enabled', true)
            ->orderBy('order')
            ->get();

        File::deleteDirectory(public_path() . '/sitemap');

        foreach ($locales as $locale) {
            $sitemapDirectory  = sprintf('/sitemap/%s', $locale);
            $absoluteDirectory = public_path() . $sitemapDirectory;
            $domain            = sprintf(self::DOMAIN_TEMPLATE, ($locale != $fallbackLocale) ? $locale : 'www');

            $this->createDirectory($absoluteDirectory);

            $sitemapPaths = [];

            foreach ($models as $model) {
                $sitemap   = Sitemap::create();
                $className = $model->model_name;

                // Получаем записи из модели
                $query = $className::query()
                    ->where('active', true)
                    ->where('published_at', '<=', date('Y-m-d H:m:s'));

                if ($className == Page::class) {
                    // Исключаем запись с символьным кодом "home"
                    $query = $query->where('slug', '!=', 'home');
                    $home  = $className::where('slug', 'home')->first();

                    // Для главной страницы добавляем соответствующий путь
                    $sitemap->add(Url::create($domain . '/')
                        ->setLastModificationDate($home->updated_at)
                        ->setPriority(0)
                        ->setChangeFrequency(0)
                    );
                }

                $items = $query->get();

                foreach ($items as $item) {
                    if (
                        $item instanceof Localizable
                        && !$item->localized($locale)
                    ) {
                        continue;
                    }

                    $sitemap->add(Url::create($domain . $item->path())
                        ->setLastModificationDate($item->updated_at)
                        ->setPriority(0)
                        ->setChangeFrequency(0)
                    );
                }

                $filename = strtolower($className);
                $filename = substr($filename, strrpos($className, '\\') + 1);

                $sitemap->writeToFile(sprintf($this->pathTemplate, $absoluteDirectory, $filename));
                $sitemapPaths[] = sprintf($this->pathTemplate, $sitemapDirectory, $filename);
            }

            $sitemap = SitemapIndex::create();
            foreach ($sitemapPaths as $path) {
                $sitemap->add($domain . $path);
            }
            $sitemap->writeToFile(sprintf('%s/sitemap-%s.xml', public_path(), $locale));
        }

        return 0;
    }
}
