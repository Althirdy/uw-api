<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Accident;
use App\Models\Citizen\Concern;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * IncidentHeatmapController
 *
 * Returns verified/resolved incidents for heatmap visualization (identify "Red Zones" / High Risk Areas)
 *
 * Privacy Requirements:
 * - NO images/photos in response
 * - NO personal details (no names, no citizen info)
 * - Only returns: id, lat, lng, severity, type, title, occurred_at
 *
 * Data Sources:
 * - accidents table (CCTV accidents) - status IN ('In Progress', 'Resolved')
 * - concerns table (citizen reports) - status IN ('ongoing', 'resolved') AND distribution.status IN ('in_progress', 'resolved')
 */
class IncidentHeatmapController extends BaseApiController
{
    /**
     * Get verified/resolved incidents for heatmap visualization.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $incidents = collect();

            // 1. Get verified/resolved accidents (CCTV incidents)
            // Status 'In Progress' = verified/acknowledged by admin
            // Status 'Resolved' = resolved
            $accidentsQuery = Accident::whereIn('status', ['In Progress', 'Resolved'])
                ->select([
                    'id',
                    'latitude as lat',
                    'longitude as lng',
                    'severity',
                    'accident_type as type',
                    'title',
                    'occurred_at',
                ]);

            // Apply optional filters for accidents
            if ($request->filled('accident_type')) {
                $accidentsQuery->where('accident_type', $request->accident_type);
            }

            if ($request->filled('severity')) {
                $accidentsQuery->where('severity', $request->severity);
            }

            if ($request->filled('from_date')) {
                $accidentsQuery->where('occurred_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $accidentsQuery->where('occurred_at', '<=', $request->to_date);
            }

            $accidents = $accidentsQuery->get()->map(function ($accident) {
                return [
                    'id' => $accident->id,
                    'lat' => (float) $accident->lat,
                    'lng' => (float) $accident->lng,
                    'severity' => strtolower($accident->severity),
                    'type' => strtolower($accident->type),
                    'title' => $accident->title,
                    'occurred_at' => $accident->occurred_at->toISOString(),
                ];
            });

            // 2. Get verified/resolved concerns (citizen reports)
            // Status 'ongoing' = verified/acknowledged by purok leader
            // Status 'resolved' = resolved
            // Must have distribution with status 'in_progress' or 'resolved' (acknowledged)
            $concernsQuery = Concern::whereIn('status', ['ongoing', 'resolved'])
                ->whereHas('distribution', function ($query) {
                    $query->whereIn('status', ['in_progress', 'resolved']);
                })
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select([
                    'id',
                    'latitude',
                    'longitude',
                    'severity',
                    'category',
                    'title',
                    'created_at',
                ]);

            // Apply optional filters for concerns
            if ($request->filled('accident_type')) {
                // Map accident_type to concern category if needed
                // For now, skip if filter is set (only applies to accidents)
            }

            if ($request->filled('severity')) {
                $concernsQuery->where('severity', $request->severity);
            }

            if ($request->filled('from_date')) {
                $concernsQuery->where('created_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $concernsQuery->where('created_at', '<=', $request->to_date);
            }

            $concerns = $concernsQuery->get()->map(function ($concern) {
                return [
                    'id' => $concern->id,
                    'lat' => (float) $concern->latitude,
                    'lng' => (float) $concern->longitude,
                    'severity' => $concern->severity,
                    'type' => $concern->category, // Use category as type for concerns
                    'title' => $concern->title,
                    'occurred_at' => $concern->created_at->toISOString(),
                ];
            });

            // Combine both sources
            $incidents = $accidents->merge($concerns);

            // Filter out any incidents without valid coordinates (safety check)
            $incidents = $incidents->filter(function ($incident) {
                return isset($incident['lat']) && isset($incident['lng']) &&
                       ! is_nan($incident['lat']) && ! is_nan($incident['lng']);
            })->values();

            return $this->sendResponse([
                'incidents' => $incidents,
                'total' => $incidents->count(),
            ], 'Incidents retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving heatmap incidents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving incidents: '.$e->getMessage());
        }
    }
}

