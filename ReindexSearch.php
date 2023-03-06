<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\News;
use App\Models\Page;
use App\Models\CaseStudy;
use App\Models\Partner;
use App\Models\SolutionPartner;
use Exception;
use Illuminate\Console\Command;
use MeiliSearch\Client;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;

class ReindexSearch extends Command
{
    const MODEL_NAME = 'Search%s';

    const MODELS     = [
        News::class,
        Page::class,
        Event::class,
        Partner::class,
        SolutionPartner::class
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'axxon:reindex-search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reaindex search models for locales';

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
     * Execute the console command.
     *
     * @param Client $searchClient
     * @return int
     */
    public function handle(Client $searchClient): int
    {
        $app = $this->getApplication();

        $locales       = array_keys(config('multilingual.locales'));
        $deleteCommand = $app->find('scout:delete-index');
        $indexCommand  = $app->find('scout:index');
        $importCommand = $app->find('scout:import');

        foreach ($locales as $locale) {
            $indexName = sprintf('Search%s', ucfirst($locale));

            try {
                $deleteCommand->run(new ArrayInput(['name' => $indexName]), $this->getOutput());
                $indexCommand->run(new ArrayInput(['name' => $indexName]), $this->getOutput());

                $index = $searchClient->index($indexName);
                $index->updateSearchableAttributes(['title', 'content']);
                $index->updateFilterableAttributes(['locales', 'model_name']);
                $index->updateRankingRules([
                    'sort',
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'exactness'
                ]);
                $index->updateSortableAttributes(['title', 'updated_at']);

                foreach (self::MODELS as $model) {
                    $this->importDocuments($model, $locale);
                }
            } catch (Exception|ExceptionInterface $e) {
            }
        }

        try {
            $indexName = (new CaseStudy())->searchableAs();
            $deleteCommand->run(new ArrayInput(['name' => $indexName]), $this->getOutput());

            $index = $searchClient->index($indexName);

            $index->updateSearchableAttributes(['slug']);
            $index->updateFilterableAttributes(['active', 'tags']);
            $index->updateSortableAttributes(['order', 'date']);

            $importCommand->run(new ArrayInput(['model' => CaseStudy::class]), $this->getOutput());
        } catch (Exception|ExceptionInterface $e) {
        }


        return 0;
    }

    /**
     * @param string $class
     * @param string $locale
     * @return void
     */
    private function importDocuments(string $class, string $locale)
    {
        $indexName = sprintf('Search%s', ucfirst($locale));

        /** @var Client $client */
        $client = resolve(Client::class);

        $documents = $class::where('active', '=', true)->get();

        $documents = $documents->map(function ($document) use ($locale) {
            return $document->toSearchableArray($locale);
        });

        $client->index($indexName)->addDocuments($documents->toArray());
    }
}
