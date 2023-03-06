<?php

namespace Axxon\EditionFeatures\Http\Services;

use App\Models\EditionsFeature;
use Axxon\EditionFeatures\Http\Resources\EditionFeaturesResources;

class EditionFeaturesService
{
    /**
     * @return mixed
     */
    public static function getTree()
    {
        return json_decode(EditionFeaturesResources::collection(EditionsFeature::orderBy('order')->get()->toTree())->toJson(), true);
    }

    /**
     * @param array $data
     */
    public static function createRootElement(array $data)
    {
        EditionsFeature::create($data);
    }

    /**
     * @param EditionsFeature $parent
     * @param $data
     */
    public static function addChildrenElement(EditionsFeature $parent, $data)
    {
        $parent->children()->create($data);
    }

    /**
     * @param $id
     * @param $data
     */
    public static function update($id, $data)
    {
        $feature = EditionsFeature::query()->find($id);
        $feature->update($data);
    }

    public static function delete($id)
    {
        if ($feature = EditionsFeature::query()->find($id)) {
            $feature->delete();
        }
    }
}
