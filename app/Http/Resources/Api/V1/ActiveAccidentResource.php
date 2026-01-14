<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveAccidentResource extends JsonResource
{
    /**
     * The user's role ID for conditional data formatting.
     */
    protected int $roleId;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @param  int  $roleId
     */
    public function __construct($resource, int $roleId = 3)
    {
        parent::__construct($resource);
        $this->roleId = $roleId;
    }

    /**
     * Transform the resource into an array.
     *
     * Role 2: Returns full accident data with coordinates from CCTV location and media
     * Role 3: Returns only coordinates, title, and description
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get coordinates from CCTV device's location relationship
        $coordinates = $this->getCoordinatesFromCctvLocation();

        // Base data for both Role 3 (Citizen) and Role 2 (Purok Leader)
        $baseData = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'status' => $this->status,
            'severity' => $this->severity,
            'accident_type' => $this->accident_type,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'location' => $this->getLocationDetails(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];

        // Both roles now get the same data including media/images
        return $baseData;
    }

    /**
     * Get coordinates from the CCTV device's location.
     *
     * @return array{latitude: string|null, longitude: string|null}
     */
    protected function getCoordinatesFromCctvLocation(): array
    {
        $cctvDevice = $this->whenLoaded('cctvDevice');

        if ($cctvDevice && $cctvDevice->location) {
            return [
                'latitude' => $cctvDevice->location->latitude,
                'longitude' => $cctvDevice->location->longitude,
            ];
        }

        // Fallback to accident's own coordinates if CCTV location not available
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * Get detailed location information from CCTV device.
     *
     * @return array|null
     */
    protected function getLocationDetails(): ?array
    {
        $cctvDevice = $this->whenLoaded('cctvDevice');

        if (! $cctvDevice || ! $cctvDevice->location) {
            return null;
        }

        $location = $cctvDevice->location;

        return [
            'location_name' => $location->location_name,
            'barangay' => $location->barangay,
            'landmark' => $location->landmark,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
        ];
    }

    /**
     * Create a collection with role context.
     *
     * @param  mixed  $resource
     * @param  int  $roleId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public static function collectionWithRole($resource, int $roleId)
    {
        return $resource->map(function ($item) use ($roleId) {
            return new static($item, $roleId);
        });
    }
}
