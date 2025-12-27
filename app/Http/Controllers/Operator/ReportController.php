<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Accident;
use App\Models\Locations;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        // Get accidents with media instead of reports
        $query = Accident::with(['media']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('accident_type', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by accident type
        if ($request->has('report_type') && $request->report_type) {
            $query->where('accident_type', $request->report_type);
        }

        // Filter by status
        if ($request->has('acknowledged') && $request->acknowledged !== '') {
            $statusFilter = $request->acknowledged === 'true' ? 'resolved' : 'pending';
            $query->where('status', $statusFilter);
        }

        $accidents = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Fetch all locations for nearest neighbor search
        $locations = Locations::all(['location_name', 'latitude', 'longitude']);

        // Transform accidents data to match reports structure
        $accidents->getCollection()->transform(function ($accident) use ($locations) {
            // Find nearest location
            $nearestLocationName = null;
            $shortestDistance = PHP_FLOAT_MAX;

            foreach ($locations as $location) {
                $distance = $this->calculateDistance(
                    $accident->latitude,
                    $accident->longitude,
                    $location->latitude,
                    $location->longitude
                );

                if ($distance < $shortestDistance) {
                    $shortestDistance = $distance;
                    $nearestLocationName = $location->location_name;
                }
            }

            // Only assign location name if within a reasonable distance (e.g., 500 meters)
            // If distance is too far, we might just keep it as coordinates or null.
            // For now, let's just use the nearest one if it's within 1km (1000m)
            $displayLocation = ($shortestDistance <= 1000) ? $nearestLocationName : null;

            return [
                'id' => $accident->id,
                'report_type' => ucfirst($accident->accident_type),
                'transcript' => $accident->title,
                'description' => $accident->description,
                'latitute' => $accident->latitude,
                'longtitude' => $accident->longitude,
                'location_name' => $displayLocation, // Added field
                'is_acknowledge' => $accident->status !== 'pending',
                'status' => ucfirst($accident->status),
                'created_at' => $accident->created_at,
                'updated_at' => $accident->updated_at,
                'user' => null, // Accidents don't have users (YOLO detected)
                'acknowledgedBy' => null,
                'media' => $accident->media->map(function ($media) {
                    return $media->original_path;
                })->toArray(),
            ];
        });

        return Inertia::render('reports', [
            'reports' => $accidents,
            'filters' => $request->only(['search', 'report_type', 'acknowledged']),
            'reportTypes' => ['Accident', 'Fire', 'Flood'], // Accident types
            'statusOptions' => ['Pending', 'Ongoing', 'Resolved', 'Archived'],
        ]);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     * Returns distance in meters.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $lat1 = deg2rad((float) $lat1);
        $lon1 = deg2rad((float) $lon1);
        $lat2 = deg2rad((float) $lat2);
        $lon2 = deg2rad((float) $lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function create(): Response
    {
        $users = User::all();

        return Inertia::render('Reports/Create', [
            'users' => $users,
            'reportTypes' => Report::getReportTypes(),
            'statusOptions' => Report::getStatusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'report_type' => 'required|string|in:'.implode(',', Report::getReportTypes()),
                'transcript' => 'required|string|max:500',
                'description' => 'required|string|max:1000',
                'latitute' => 'required|numeric|between:-90,90',
                'longtitude' => 'required|numeric|between:-180,180',
                'user_id' => 'nullable|exists:users,id',
                'status' => 'nullable|string|in:Pending,Ongoing,Resolved,Archived',
            ]);

            // Set user_id to current user if not provided
            if (! isset($validated['user_id'])) {
                $validated['user_id'] = auth()->id();
            }

            // Set default status if not provided
            if (! isset($validated['status'])) {
                $validated['status'] = 'Pending';
            }

            $validated['is_acknowledge'] = false;

            DB::beginTransaction();
            try {
                $report = Report::create($validated);

                DB::commit();

                return redirect()->route('reports')
                    ->with('success', 'Report created successfully.');

            } catch (\Exception $e) {
                DB::rollBack();

                return back()
                    ->with('error', 'Failed to create report. Please try again.')
                    ->withInput();
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    public function show(Report $report): Response
    {
        $report->load(['user', 'acknowledgedBy']);

        return Inertia::render('Reports/Show', [
            'report' => $report,
            'statusOptions' => Report::getStatusOptions(),
        ]);
    }

    public function edit(Report $report): Response
    {
        $users = User::all();

        return Inertia::render('Reports/Edit', [
            'report' => $report,
            'users' => $users,
            'reportTypes' => Report::getReportTypes(),
            'statusOptions' => Report::getStatusOptions(),
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            // Find accident instead of report
            $accident = Accident::findOrFail($id);

            $validated = $request->validate([
                'report_type' => 'required|string',
                'transcript' => 'nullable|string|max:500',
                'description' => 'required|string|max:1000',
                'latitute' => 'required|numeric|between:-90,90',
                'longtitude' => 'required|numeric|between:-180,180',
                'status' => 'nullable|string|in:pending,ongoing,resolved,archived',
            ]);

            DB::beginTransaction();
            try {
                // Map report fields to accident fields
                $accident->update([
                    'title' => $validated['transcript'] ?? $accident->title,
                    'description' => $validated['description'],
                    'latitude' => $validated['latitute'],
                    'longitude' => $validated['longtitude'],
                    'accident_type' => strtolower($validated['report_type']),
                    'status' => $validated['status'] ?? $accident->status,
                ]);

                DB::commit();

                return redirect()->route('reports')
                    ->with('success', 'Accident report updated successfully.');
            } catch (\Exception $e) {
                DB::rollBack();

                return back()
                    ->with('error', 'Failed to update report. Please try again.')
                    ->withInput();
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    public function destroy($id)
    {
        try {
            $accident = Accident::findOrFail($id);

            DB::beginTransaction();
            try {
                // Set status to 'archived' before deleting
                $accident->update(['status' => 'archived']);
                $accident->delete();
                DB::commit();

                return redirect()->route('reports')
                    ->with('success', 'Accident report archived successfully.');
            } catch (\Exception $e) {
                DB::rollBack();

                return back()
                    ->with('error', 'Failed to archive report. Please try again.');
            }
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to archive report. Please try again.');
        }
    }

    public function acknowledge($id)
    {
        try {
            $accident = Accident::findOrFail($id);

            // Check if already acknowledged (not pending)
            if ($accident->status !== 'pending') {
                return back()->with('error', 'Report is already acknowledged.');
            }

            DB::beginTransaction();
            try {
                // Update status to ongoing when acknowledged
                $accident->update([
                    'status' => 'ongoing',
                ]);

                DB::commit();

                return redirect()->route('reports')
                    ->with('success', 'Accident report acknowledged successfully.')
                    ->with('refresh', true);
            } catch (\Exception $e) {
                DB::rollBack();

                return back()
                    ->with('error', 'Failed to acknowledge report. Please try again.');
            }
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to acknowledge report. Please try again.');
        }
    }

    public function resolve(Report $report)
    {
        try {
            if ($report->status === 'Resolved') {
                return back()->with('error', 'Report is already resolved.');
            }

            if ($report->status !== 'Ongoing') {
                return back()->with('error', 'Only ongoing reports can be resolved.');
            }

            DB::beginTransaction();
            try {
                $report->resolve();
                DB::commit();

                return redirect()->route('reports')
                    ->with('success', 'Report resolved successfully.');
            } catch (\Exception $e) {
                DB::rollBack();

                return back()
                    ->with('error', 'Failed to resolve report. Please try again.');
            }
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to resolve report. Please try again.');
        }
    }
}
