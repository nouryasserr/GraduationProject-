<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'user'      => new UserResource($this->whenLoaded('user')),
            'rating'    => $this->rating,
            'product'   => new ProductResource($this->whenLoaded('product')),
            'comments'  => $this->comments,
            'created_at' => $this->created_at,
            'time_ago' => optional($this->created_at)->diffForHumans(),
        ];
    }
}
