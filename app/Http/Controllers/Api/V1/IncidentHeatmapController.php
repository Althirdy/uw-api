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
                // Map severity: Low -> low, Medium -> medium, High -> high
                $severityMap = [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                ];
                $severity = $severityMap[$accident->severity] ?? strtolower($accident->severity ?? 'low');

                // Handle occurred_at - ensure it's a Carbon instance or convert it
                $occurredAt = $accident->occurred_at;
                if ($occurredAt && !($occurredAt instanceof \Carbon\Carbon)) {
                    try {
                        $occurredAt = \Carbon\Carbon::parse($occurredAt);
                    } catch (\Exception $e) {
                        $occurredAt = now();
                    }
                } elseif (!$occurredAt) {
                    $occurredAt = now();
                }

                return [
                    'id' => (int) $accident->id,
                    'lat' => $accident->lat !== null ? (string) $accident->lat : null,
                    'lng' => $accident->lng !== null ? (string) $accident->lng : null,
                    'severity' => $severity,
                    'type' => strtolower($accident->type ?? 'accident'),
                    'title' => $accident->title ?? 'Untitled Incident',
                    'occurred_at' => $occurredAt->toISOString(),
                ];
            })->filter(function ($accident) {
                // Filter out incidents without valid coordinates
                return $accident['lat'] !== null && $accident['lng'] !== null &&
                       !empty($accident['lat']) && !empty($accident['lng']);
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
                // Handle created_at - ensure it's a Carbon instance
                $createdAt = $concern->created_at;
                if (!$createdAt || !($createdAt instanceof \Carbon\Carbon)) {
                    $createdAt = now();
                }

                return [
                    'id' => (int) $concern->id,
                    'lat' => $concern->latitude !== null ? (string) $concern->latitude : null,
                    'lng' => $concern->longitude !== null ? (string) $concern->longitude : null,
                    'severity' => strtolower($concern->severity ?? 'low'),
                    'type' => strtolower($concern->category ?? 'other'), // Use category as type for concerns
                    'title' => $concern->title ?? 'Untitled Concern',
                    'occurred_at' => $createdAt->toISOString(),
                ];
            })->filter(function ($concern) {
                // Filter out incidents without valid coordinates
                return $concern['lat'] !== null && $concern['lng'] !== null &&
                       !empty($concern['lat']) && !empty($concern['lng']);
            });

            // Combine both sources and reset keys
            // Convert to arrays first to avoid Eloquent Collection merge issues (getKey() error)
            $incidents = collect(array_merge(
                $accidents->toArray(),
                $concerns->toArray()
            ))->values();

            return $this->sendResponse([
                'incidents' => $incidents,
                'total' => $incidents->count(),
            ], 'Heatmap data retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving heatmap incidents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving incidents: '.$e->getMessage());
        }
    }
}

