<?php

namespace App\Observers;

use App\Factory\SearchFactory;
use App\Models\Search\AbstractSearch;
use Illuminate\Database\Eloquent\Model;
use MeiliSearch\Client;

class SearchObserver
{
    /**
     * @param Model $model
     * @return string
     */
    private function getUid(Model $model): string
    {
        $className = strtolower(class_basename($model->getMorphClass()));

        return $className . '_' . $model->id;
    }

    /**
     * @return AbstractSearch
     */
    private function getSearchRepository(): AbstractSearch
    {
        return SearchFactory::create(app()->getLocale());
    }

    /**
     * @param Model $model
     */
    private function deleteFromIndex(Model $model)
    {
        $indexName = $this->getSearchRepository()->searchableAs();

        $client = resolve(Client::class);
        $client->index($indexName)->deleteDocument($this->getUid($model));
    }

    /**
     * @param Model $model
     */
    private function saveModel(Model $model)
    {
        $active = $model->active ?? false;

        if (!$active) {
            $this->deleteFromIndex($model);
        } else {
            $searchRepository = $this->getSearchRepository();
            $searchModel = $searchRepository::where('uid', $this->getUid($model))->first();

            if ($searchModel) {
                $searchModel->save();
            }
        }
    }

    /**
     * При создании экземпляра модели создает его документ в индексе MeiliSearch
     *
     * @param Model $model - экземпляр модели, который был создан
     */
    public function created(Model $model)
    {
        $this->saveModel($model);
    }

    /**
     * При обновлении экземпляра модели обновляет его документ в индексе MeiliSearch
     *
     * @param Model $model - экземпляр модели, который был обновлен
     */
    public function updated(Model $model)
    {
        $this->saveModel($model);
    }

    /**
     * При удалении экземпляра модели удаляет его документ в индексе MeiliSearch
     *
     * @param Model $model - экземпляр модели, который был создан
     */
    public function deleted(Model $model)
    {
        $this->deleteFromIndex($model);
    }
}
