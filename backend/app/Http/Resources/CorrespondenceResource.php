<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CorrespondenceCategory;

class CorrespondenceResource extends JsonResource
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
            'institution_id' => $this->institution_id,
            'institution' => $this->when($this->institution, function () {
                return [
                    'id' => $this->institution->id ?? null,
                    'name' => $this->institution->name ?? null,
                    'npsn' => $this->institution->npsn ?? null,
                ];
            }),
            'type' => $this->type,
            'letter_type_code' => $this->letter_type_code,
            'letter_type_abbr' => $this->letter_type_abbr,
            'letter_type_name' => $this->letter_type_name,
            'letter_number' => $this->letter_number,
            'reference_number' => $this->reference_number,
            'subject' => $this->subject,
            'from' => $this->from,
            'to' => $this->to,
            'date' => $this->date?->format('Y-m-d'),
            'received_date' => $this->received_date?->format('Y-m-d'),
            'priority' => $this->priority,
            'status' => $this->status,
            'category_id' => $this->category_id,
            'category' => $this->when($this->category_id && $this->category && $this->category instanceof CorrespondenceCategory && $this->category->exists, function () {
                return [
                    'id' => $this->category->id ?? null,
                    'name' => $this->category->name ?? null,
                    'type' => $this->category->type ?? null,
                ];
            }),
            'created_by' => $this->created_by,
            'creator' => $this->when($this->creator, function () {
                return [
                    'id' => $this->creator->id ?? null,
                    'name' => $this->creator->name ?? 'Unknown',
                    'email' => $this->creator->email ?? null,
                ];
            }),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'approver' => $this->when($this->approver, function () {
                return [
                    'id' => $this->approver->id ?? null,
                    'name' => $this->approver->name ?? null,
                    'email' => $this->approver->email ?? null,
                ];
            }),
            'description' => $this->description,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'file_url' => $this->file_url,
            'dispositions' => $this->whenLoaded('dispositions', function () {
                return $this->dispositions->map(function ($disposition) {
                    return [
                        'id' => $disposition->id,
                        'from_user' => [
                            'id' => $disposition->fromUser->id,
                            'name' => $disposition->fromUser->name,
                        ],
                        'to_user' => [
                            'id' => $disposition->toUser->id,
                            'name' => $disposition->toUser->name,
                        ],
                        'instruction' => $disposition->instruction,
                        'status' => $disposition->status,
                        'completed_at' => $disposition->completed_at?->toISOString(),
                    ];
                });
            }),
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_size' => $attachment->file_size,
                        'file_size_human' => $attachment->file_size_human,
                        'file_url' => $attachment->file_url,
                        'description' => $attachment->description,
                    ];
                });
            }),
            'histories' => $this->whenLoaded('histories', function () {
                return $this->histories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'user' => [
                            'id' => $history->user->id,
                            'name' => $history->user->name,
                        ],
                        'action' => $history->action,
                        'notes' => $history->notes,
                        'changes' => $history->changes,
                        'created_at' => $history->created_at?->toISOString(),
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
