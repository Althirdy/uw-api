<?php

namespace App\Http\Controllers\Api\V1\Operator;

use App\Events\AccidentStatusUpdated;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\AccidentResource;
use App\Models\Accident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccidentController extends BaseApiController
{
    /**
     * Display a listing of the accidents/reports.
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
     * Display the specified accident.
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
     * Update the status of the specified accident.
     */
    public function updateStatus(Request $request, Accident $accident)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:Pending,In Progress,Resolved',
            ]);

            // Store previous status for broadcasting
            $previousStatus = $accident->status;

            $accident->update($validated);

            // Broadcast the status change for real-time map updates
            broadcast(new AccidentStatusUpdated($accident, $previousStatus));

            Log::info('Accident status updated and broadcasted', [
                'accident_id' => $accident->id,
                'previous_status' => $previousStatus,
                'new_status' => $accident->status,
            ]);

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
