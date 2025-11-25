<?php

namespace App\Http\Controllers\Api\V1\Citizen;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Citizen\StoreConcernRequest;
use App\Http\Requests\Api\V1\Citizen\UpdateConcernRequest;
use App\Http\Resources\Api\V1\ConcernResource;
use App\Models\Citizen\Concern;
use App\Models\IncidentMedia;
use App\Models\ConcernDistribution;
use App\Models\ConcernHistory;
use App\Events\ConcernAssigned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FileUploadService;


class ConcernController extends BaseApiController
{

    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Get only concerns belonging to the authenticated user
            $concerns = Concern::where('citizen_id', auth()->id())
                ->with([
                    'media' => function ($query) {
                        $query->where('source_category', 'citizen_concern');
                    },
                    'distribution.purokLeader.officialDetails',
                    'histories.actor.officialDetails'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse([
                'concerns' => ConcernResource::collection($concerns)
            ], 'Manual concerns retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving manual concerns', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving concerns: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreConcernRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // 🔍 Step 0: Determine Concern Type & Prepare Data
            $concernType = $validated['type'];
            
            if ($concernType === 'voice') {
                $title = $validated['title'] ?? 'Voice Concern - ' . now()->format('M d, Y H:i');
                $description = $validated['description'] ?? 'Audio recording received. Transcription pending...';
            } else {
                $title = $validated['title'];
                $description = $validated['description'];
            }

            // Generate Tracking Code: CN-YYYYMMDD-XXXX
            $datePart = now()->format('Ymd');
            $randomPart = Str::upper(Str::random(4));
            $trackingCode = "CN-{$datePart}-{$randomPart}";

            // 🟢 Step 1: Create the Concern record
            $concern = Concern::create([
                'type' => $concernType,
                'citizen_id' => auth()->id(), // Use authenticated user's ID
                'title' => $title,
                'description' => $description,
                'status' => 'pending',
                'category' => $validated['category'],
                'severity' => 'low',
                'transcript_text' => $validated['transcript_text'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'tracking_code' => $trackingCode,
            ]);

            $uploadedMedia = [];

            // 🟡 Step 2: Handle file uploads
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $isMultiple = is_array($files);

                // Upload (single or multiple)
                $uploadResults = $isMultiple
                    ? $this->fileUploadService->uploadMultiple($files, 'concerns')
                    : ['successful' => [$this->fileUploadService->uploadSingle($files, 'concerns')]];

                // 🟣 Step 3: Save uploaded files in the database
                foreach ($uploadResults['successful'] as $upload) {
                    $media = IncidentMedia::create([
                        'source_type' => \App\Models\Citizen\Concern::class,
                        'source_id' => $concern->id,
                        'source_category' => 'citizen_concern',
                        'media_type' => 'image', // you can later detect type dynamically
                        'original_path' => $upload['public_url'] ?? null,
                        'blurred_path' => null,
                        'public_id' => $upload['storage_path'] ?? null,
                        'original_filename' => $upload['original_filename'] ?? null,
                        'file_size' => $upload['file_size'] ?? null,
                        'mime_type' => $upload['mime_type'] ?? null,
                        'captured_at' => now(),
                    ]);

                    $uploadedMedia[] = $media->original_path;
                }
            }

            // 🔵 Step 4: Distribute concern to purok leader
            // TODO: Replace hardcoded purok_leader_id with actual location-based logic
            // For now, all concerns are assigned to purok leader with ID = 2 (Adoracion S. Jumadiao)
            $purokLeaderId = 2;

            $distribution = ConcernDistribution::create([
                'concern_id' => $concern->id,
                'purok_leader_id' => $purokLeaderId,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            // 🟠 Step 4.5: Create Initial History (Audit Log)
            ConcernHistory::create([
                'concern_id' => $concern->id,
                'acted_by' => null, // System action
                'status' => 'pending',
                'remarks' => 'Concern submitted and automatically distributed to Purok Leader.',
            ]);

            // 🟣 Step 5: Broadcast real-time notification to purok leader
            event(new ConcernAssigned($concern, $distribution, $uploadedMedia));

            DB::commit();

            // Load relationships for resource
            $concern->load(['media', 'distribution.purokLeader.officialDetails', 'histories.actor.officialDetails']);

            // 🟢 Step 6: Return successful response
            return $this->sendResponse([
                'concern' => new ConcernResource($concern),
            ], 'Concern submitted successfully!', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating concern', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while submitting concern: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Get concern only if it belongs to the authenticated user
            $concern = Concern::where('id', $id)
                ->where('citizen_id', auth()->id())
                ->with([
                    'media' => function ($query) {
                        $query->where('source_category', 'citizen_concern');
                    },
                    'distribution.purokLeader.officialDetails',
                    'histories.actor.officialDetails'
                ])
                ->first();

            if (!$concern) {
                return $this->sendError('Manual concern not found or you do not have permission to view it', [], 404);
            }

            return $this->sendResponse([
                'concern' => new ConcernResource($concern)
            ], 'Manual concern retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving manual concern', [
                'error' => $e->getMessage(),
                'concern_id' => $id,
            ]);

            return $this->sendError('An error occurred while retrieving concern: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConcernRequest $request, string $id)
    {
        DB::beginTransaction();

        try {
            // Get concern only if it belongs to the authenticated user
            $concern = Concern::where('id', $id)
                ->where('citizen_id', auth()->id())
                ->with([
                    'media' => function ($query) {
                        $query->where('source_category', 'citizen_concern');
                    }
                ])
                ->first();

            if (!$concern) {
                return $this->sendError('Manual concern not found or you do not have permission to update it', [], 404);
            }

            // Update only title and description
            $concern->update($request->validated());

            DB::commit();

            return $this->sendResponse([
                'concern' => new ConcernResource($concern)
            ], 'Manual concern updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating manual concern', [
                'error' => $e->getMessage(),
                'concern_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while updating concern: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            // Get concern only if it belongs to the authenticated user
            $concern = Concern::where('id', $id)
                ->where('citizen_id', auth()->id())
                ->first();

            if (!$concern) {
                return $this->sendError('Manual concern not found or you do not have permission to delete it', [], 404);
            }

            // Soft delete the concern
            $concern->delete();

            DB::commit();

            return $this->sendResponse([
                'concern_id' => $id
            ], 'Manual concern deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting manual concern', [
                'error' => $e->getMessage(),
                'concern_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while deleting concern: ' . $e->getMessage());
        }
    }
}
