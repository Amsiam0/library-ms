<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Feedback extends JsonResource
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
            'book_id' => $this->book_id,
            'user_id' => $this->user_id,
            'rating' => $this->rating,
            'comment' => $this->review,
            'book' => new BookResource($this->whenLoaded('book')),
            'user' => new UserResource($this->whenLoaded('user')),
            'createdAt' => $this->submitted_at,
        ];
    }
}
