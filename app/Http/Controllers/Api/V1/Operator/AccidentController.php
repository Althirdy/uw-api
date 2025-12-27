<?php

namespace App\Http\Controllers\Api\V1\Operator;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\AccidentResource;
use App\Models\Accident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccidentController extends BaseApiController
{
    /**
     * List Accidents
     * 
     * Retrieve a paginated list of accidents/reports detected by YOLO AI system.
     * Supports searching, filtering by accident type, and status.
     *
     * @group Operator
     * @authenticated
     * 
     * @queryParam search string optional Search term for title, description, or accident type. Example: fire
     * @queryParam accident_type string optional Filter by accident type. Example: fire
     * @queryParam status string optional Filter by status (pending, ongoing, resolved, archived). Example: pending
     * @queryParam per_page integer optional Number of results per page (default: 10). Example: 20
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "accidents": [
     *       {
     *         "id": 1,
     *         "title": "Fire Incident",
     *         "description": "Fire detected at location",
     *         "accident_type": "fire",
     *         "status": "pending",
     *         "created_at": "2023-12-27T10:00:00.000000Z",
     *         "media": []
     *       }
     *     ],
     *     "meta": {
     *       "current_page": 1,
     *       "last_page": 5,
     *       "per_page": 10,
     *       "total": 50
     *     }
     *   },
     *   "message": "Accidents retrieved successfully"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while retrieving accidents: error details"
     * }
     */
    public function index(Request $request)
    {
        try {
            $query = Accident::with(['media']);

            // Search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                        ->orWhere('description', 'like', "%{$searchTerm}%")
                        ->orWhere('accident_type', 'like', "%{$searchTerm}%");
                });
            }

            // Filter by accident type
            if ($request->filled('accident_type')) {
                $query->where('accident_type', $request->accident_type);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $accidents = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            return $this->sendResponse([
                'accidents' => AccidentResource::collection($accidents),
                'meta' => [
                    'current_page' => $accidents->currentPage(),
                    'last_page' => $accidents->lastPage(),
                    'per_page' => $accidents->perPage(),
                    'total' => $accidents->total(),
                ],
            ], 'Accidents retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving accidents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving accidents: '.$e->getMessage());
        }
    }

    /**
     * Get Accident Details
     * 
     * Retrieve detailed information about a specific accident including associated media.
     *
     * @group Operator
     * @authenticated
     * 
     * @urlParam accident integer required The ID of the accident. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "accident": {
     *       "id": 1,
     *       "title": "Fire Incident",
     *       "description": "Fire detected at location",
     *       "accident_type": "fire",
     *       "status": "pending",
     *       "latitude": 16.4023,
     *       "longitude": 120.5960,
     *       "created_at": "2023-12-27T10:00:00.000000Z",
     *       "media": [
     *         {
     *           "id": 1,
     *           "media_type": "image",
     *           "original_path": "https://example.com/image.jpg"
     *         }
     *       ]
     *     }
     *   },
     *   "message": "Accident retrieved successfully"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Accident not found"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while retrieving the accident: error details"
     * }
     */
    public function show(Accident $accident)
    {
        try {
            $accident->load('media');

            return $this->sendResponse([
                'accident' => new AccidentResource($accident),
            ], 'Accident retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving accident', [
                'error' => $e->getMessage(),
                'accident_id' => $accident->id,
            ]);

            return $this->sendError('An error occurred while retrieving the accident: '.$e->getMessage());
        }
    }

    /**
     * Update Accident Status
     * 
     * Update the status of an accident (e.g., pending, ongoing, resolved, archived).
     *
     * @group Operator
     * @authenticated
     * 
     * @urlParam accident integer required The ID of the accident. Example: 1
     * @bodyParam status string required New status (pending, ongoing, resolved, archived). Example: resolved
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "accident": {
     *       "id": 1,
     *       "title": "Fire Incident",
     *       "status": "resolved",
     *       "updated_at": "2023-12-27T11:00:00.000000Z"
     *     }
     *   },
     *   "message": "Accident status updated successfully"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "status": ["The selected status is invalid."]
     *   }
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while updating the accident status: error details"
     * }
     */
    public function updateStatus(Request $request, Accident $accident)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,ongoing,resolved,archived',
            ]);

            $accident->update($validated);

            return $this->sendResponse([
                'accident' => new AccidentResource($accident),
            ], 'Accident status updated successfully');

        } catch (\Exception $e) {
            Log::error('Error updating accident status', [
                'error' => $e->getMessage(),
                'accident_id' => $accident->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while updating the accident status: '.$e->getMessage());
        }
    }
}
