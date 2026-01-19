<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispositionResource extends JsonResource
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
            'correspondence_id' => $this->correspondence_id,
            'correspondence' => $this->whenLoaded('correspondence', function () {
                return [
                    'id' => $this->correspondence->id,
                    'subject' => $this->correspondence->subject,
                    'letter_number' => $this->correspondence->letter_number,
                    'reference_number' => $this->correspondence->reference_number,
                ];
            }),
            'from_user_id' => $this->from_user_id,
            'from_user' => $this->whenLoaded('fromUser', function () {
                return [
                    'id' => $this->fromUser->id,
                    'name' => $this->fromUser->name,
                    'email' => $this->fromUser->email,
                ];
            }),
            'to_user_id' => $this->to_user_id,
            'to_user' => $this->whenLoaded('toUser', function () {
                return [
                    'id' => $this->toUser->id,
                    'name' => $this->toUser->name,
                    'email' => $this->toUser->email,
                ];
            }),
            'instruction' => $this->instruction,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
