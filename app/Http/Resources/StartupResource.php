<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StartupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'name' => $this->name,
            'description' => $this->description,
            'email' => $this->email,
            'logo' => $this->logo,
            'social_media_links' => $this->social_media_links,
            'phone' => $this->phone,
            'status' => $this->status,
            'package_id' => $this->package_id,
            'categories_id' => $this->categories_id,
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'payment_method' => $this->payment_method,
            'payment_account' => $this->payment_method,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'trial_ends_at' => $this->trial_ends_at,
            'trial_days_left' => intval(now()->diffInDays($this->trial_ends_at, false)),

        ];
    }
}
