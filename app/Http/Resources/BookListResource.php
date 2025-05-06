<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookListResource extends JsonResource
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
            'ebook' => $this->ebook ?? '',
            'hasPhysical' => $this->has_physical,
            'thumbnail' => $this->thumbnail ?? '',
            'category' => $this->whenLoaded('category', fn () => $this->category->name),
        ];
    }
}
