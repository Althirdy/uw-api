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

    /**
     * Get heatmap data for verified/resolved incidents.
     * Returns only location, severity, and type for privacy.
     * Does not include images or personal details.
     */
    public function heatmap(Request $request)
    {
        try {
            $query = Accident::query()
                ->whereIn('status', ['resolved', 'verified']);

            // Optional filter by accident type
            if ($request->filled('accident_type')) {
                $query->where('accident_type', $request->accident_type);
            }

            // Optional filter by severity
            if ($request->filled('severity')) {
                $query->where('severity', $request->severity);
            }

            // Optional date range filter
            if ($request->filled('from_date')) {
                $query->where('occurred_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->where('occurred_at', '<=', $request->to_date);
            }

            $incidents = $query->select([
                'id',
                'latitude',
                'longitude',
                'severity',
                'accident_type',
                'title',
                'occurred_at',
            ])->get();

            // Transform to heatmap-friendly format
            $heatmapData = $incidents->map(function ($incident) {
                return [
                    'id' => $incident->id,
                    'lat' => (float) $incident->latitude,
                    'lng' => (float) $incident->longitude,
                    'severity' => $incident->severity,
                    'type' => $incident->accident_type,
                    'title' => $incident->title,
                    'occurred_at' => $incident->occurred_at,
                ];
            });

            return $this->sendResponse([
                'incidents' => $heatmapData,
                'total' => $heatmapData->count(),
            ], 'Heatmap data retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving heatmap data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving heatmap data: '.$e->getMessage());
        }
    }
}
