<?php

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CCTVResource extends JsonResource
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
            'deviceName' => $this->device_name,
            'primaryRtspUrl' => $this->primary_rtsp_url,
            'backupRtspUrl' => $this->secondary_rtsp_url,
        ];
    }
}
