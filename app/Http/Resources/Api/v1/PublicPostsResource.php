<?php

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPostsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'title' => $this->title,
            'content' => $this->content,
            'publishedAt' => $this->published_at->diffForHumans(),
            'publishedBy' => 'Barangay 176-E',
            'media' => $this->image_path,
            'category' => $this->category,
        ];
    }
}
