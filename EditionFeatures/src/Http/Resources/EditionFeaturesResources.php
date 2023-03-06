<?php

namespace Axxon\EditionFeatures\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use \Illuminate\Http\Request;

class EditionFeaturesResources extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = [
            'id'          => $this->id,
            'title'       => $this->title,
            'code'        => $this->code,
            'link'        => $this->link,
            'order'       => $this->order,
            'description' => $this->description,
            'active'      => (bool)$this->active,
        ];

        if ($this->children()->get()) {
            $data['children'] = json_decode(self::collection($this->children()->orderBy('order')->get())->toJson(),
                true);
        }

        return $data;
    }
}
