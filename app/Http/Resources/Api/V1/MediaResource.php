<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'source_category' => $this->source_category,
            'media_type' => $this->media_type,
            'original_path' => $this->original_path,
            'blurred_path' => $this->blurred_path,
            'public_id' => $this->public_id,
            'original_filename' => $this->original_filename,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'captured_at' => $this->captured_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
