<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'description' => $this->description,
            'ebook' => $this->ebook ?? '',
            'hasPhysical' => $this->has_physical,
            'category' => $this->whenLoaded('category', fn () => $this->category->name),
            'createdAt' => $this->created_at->toDateTimeString(),
        ];
    }
}
