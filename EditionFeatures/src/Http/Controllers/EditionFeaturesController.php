<?php

namespace Axxon\EditionFeatures\Http\Controllers;

use App\Models\EditionsFeature;
use Axxon\EditionFeatures\Http\Services\EditionFeaturesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditionFeaturesController
{
    /**
     * @param string $locale
     * @return JsonResponse
     */
    public function getTree(string $locale = 'en'): JsonResponse
    {
        app()->setLocale($locale);

        return response()->json(EditionFeaturesService::getTree(), 200);
    }

    /**
     * @param string $locale
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $locale, Request $request): JsonResponse
    {
        app()->setLocale($locale);
        EditionFeaturesService::update($request->get('id'), $request->all());

        return response()->json(['success' => true], 200);
    }

    /**
     * @param string $locale
     * @param Request $request
     * @return JsonResponse
     */
    public function createRootElement(string $locale, Request $request): JsonResponse
    {
        app()->setLocale($locale);
        EditionFeaturesService::createRootElement($request->all());

        return response()->json(['success' => true], 200);
    }

    public function createChildElement(string $locale, EditionsFeature $parent, Request $request)
    {
        app()->setLocale($locale);
        EditionFeaturesService::addChildrenElement($parent, $request->all());
    }

    public function delete($id): JsonResponse
    {
        EditionFeaturesService::delete($id);
        return response()->json(['success' => true], 204);
    }
}
