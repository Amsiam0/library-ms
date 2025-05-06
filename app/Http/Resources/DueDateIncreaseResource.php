<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DueDateIncreaseResource extends JsonResource
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
            'newDueDate' => $this->new_due_date,
            'reason' => $this->reason,
            'status' => $this->status,
            'bookLoan' => new BookLoanResource($this->bookLoan),
            'user' => new UserResource($this->user),
            'createdAt' => $this->created_at,
        ];
    }
}
