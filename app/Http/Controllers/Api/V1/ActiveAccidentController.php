<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\AccidentMarkerResource;
use App\Http\Resources\Api\V1\ActiveAccidentResource;
use App\Services\ActiveAccidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ActiveAccidentController
 *
 * Handles HTTP requests for retrieving active (In Progress) accidents.
 *
 * Optimized for map/heatmap usage (like Waze):
 * - Index: Returns minimal data for markers (id, coordinates, type, severity) - FAST
 * - Show: Returns full details based on role when user clicks a marker
 *
 * Role-based access for show endpoint:
 * - Role 2: Full accident data with CCTV location coordinates and media
 * - Role 3: Limited data with coordinates, title, and description only
 *
 * Relationship chain: Accidents <- cctvDevice <- Location
 */
class ActiveAccidentController extends BaseApiController
{
    protected ActiveAccidentService $activeAccidentService;

    public function __construct(ActiveAccidentService $activeAccidentService)
    {
        $this->activeAccidentService = $activeAccidentService;
    }

    /**
     * Get all accidents with "In Progress" status for map markers.
     *
     * Returns minimal data optimized for initial map load:
     * - id, latitude, longitude, accident_type, severity
     *
     * No relationships are loaded for maximum performance.
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return $this->sendUnauthorized('User not authenticated');
            }

            $roleId = $user->role_id;

            // Validate that user has role 2 or 3
            if (! in_array($roleId, [2, 3])) {
                return $this->sendForbidden('You do not have permission to access this resource');
            }

            // Get minimal data for map markers (no relationships loaded)
            $accidents = $this->activeAccidentService->getInProgressAccidentsForMarkers();

            Log::info('ActiveAccidentController: Retrieved markers for in-progress accidents', [
                'user_id' => $user->id,
                'role_id' => $roleId,
                'count' => $accidents->count(),
            ]);

            return $this->sendResponse([
                'markers' => AccidentMarkerResource::collection($accidents),
                'meta' => [
                    'total' => $accidents->count(),
                ],
            ], 'Accident markers retrieved successfully');

        } catch (\Exception $e) {
            Log::error('ActiveAccidentController: Error retrieving accident markers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving accident markers: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific accident with "In Progress" status.
     *
     * Returns full details when user clicks on a marker.
     * Data returned depends on user role:
     * - Role 2: Full data with location details and media
     * - Role 3: Limited data (coordinates, title, description)
     *
     * @param  int  $id  The accident ID
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return $this->sendUnauthorized('User not authenticated');
            }

            $roleId = $user->role_id;

            // Validate that user has role 2 or 3
            if (! in_array($roleId, [2, 3])) {
                return $this->sendForbidden('You do not have permission to access this resource');
            }

            $accident = $this->activeAccidentService->getInProgressAccidentById($id, $roleId);

            if (! $accident) {
                return $this->sendError('Active accident not found', null, 404);
            }

            Log::info('ActiveAccidentController: Retrieved active accident details', [
                'user_id' => $user->id,
                'role_id' => $roleId,
                'accident_id' => $id,
            ]);

            return $this->sendResponse([
                'accident' => new ActiveAccidentResource($accident, $roleId),
            ], 'Accident details retrieved successfully');

        } catch (\Exception $e) {
            Log::error('ActiveAccidentController: Error retrieving accident details', [
                'error' => $e->getMessage(),
                'accident_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving accident details: ' . $e->getMessage());
        }
    }
}
