<?php

namespace App\Http\Controllers\Api\V1\Citizen;

use App\Events\ConcernAssigned;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Citizen\StoreConcernRequest;
use App\Http\Requests\Api\V1\Citizen\UpdateConcernRequest;
use App\Http\Resources\Api\V1\ConcernResource;
use App\Models\Citizen\Concern;
use App\Models\ConcernDistribution;
use App\Models\ConcernHistory;
use App\Models\IncidentMedia;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConcernController extends BaseApiController
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * List My Concerns
     * 
     * Retrieve all concerns submitted by the authenticated citizen user.
     * Includes media attachments, distribution status, and history.
     *
     * @group Citizen
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "concerns": [
     *       {
     *         "id": 1,
     *         "type": "manual",
     *         "tracking_code": "CN-20231227-ABCD",
     *         "title": "Broken Streetlight",
     *         "description": "The streetlight on Main St is broken",
     *         "category": "infrastructure",
     *         "severity": "low",
     *         "status": "pending",
     *         "latitude": 16.4023,
     *         "longitude": 120.5960,
     *         "address": "Main St, Baguio City",
     *         "created_at": "2023-12-27T10:00:00.000000Z",
     *         "media": [],
     *         "distribution": {
     *           "status": "assigned",
     *           "purok_leader": {
     *             "name": "Juan Dela Cruz"
     *           }
     *         },
     *         "histories": []
     *       }
     *     ]
     *   },
     *   "message": "Manual concerns retrieved successfully"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while retrieving concerns: error details"
     * }
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
                    'histories.actor.officialDetails',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse([
                'concerns' => ConcernResource::collection($concerns),
            ], 'Manual concerns retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving manual concerns', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving concerns: '.$e->getMessage());
        }
    }

    /**
     * Submit New Concern
     * 
     * Submit a new concern (manual or voice) with optional media attachments and location information.
     * Automatically generates a tracking code and assigns to Purok Leader.
     * Voice concerns will be transcribed in the background.
     *
     * @group Citizen
     * @authenticated
     * 
     * @bodyParam type string required Concern type (manual, voice). Example: manual
     * @bodyParam title string required (for manual) Concern title (max 100 chars). Example: Broken Streetlight
     * @bodyParam description string required (for manual) Detailed description. Example: The streetlight on Main St is broken and needs repair.
     * @bodyParam category string required Category (safety, security, infrastructure, environment, noise, other). Example: infrastructure
     * @bodyParam severity string optional Severity level (low, medium, high). Default: low. Example: medium
     * @bodyParam transcript_text string optional Transcribed text for voice concerns. Example: There is a broken streetlight
     * @bodyParam longitude number optional Longitude coordinate (-180 to 180). Example: 120.5960
     * @bodyParam latitude number optional Latitude coordinate (-90 to 90). Example: 16.4023
     * @bodyParam address string optional Full address. Example: Main St, Baguio City
     * @bodyParam custom_location string optional Custom location description. Example: Near the park
     * @bodyParam files file[] optional Media files (images or audio, max 3 files, 10MB each). Example: ["image1.jpg", "audio.mp3"]
     * 
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "concern": {
     *       "id": 1,
     *       "type": "manual",
     *       "tracking_code": "CN-20231227-ABCD",
     *       "title": "Broken Streetlight",
     *       "description": "The streetlight on Main St is broken",
     *       "category": "infrastructure",
     *       "severity": "medium",
     *       "status": "pending",
     *       "created_at": "2023-12-27T10:00:00.000000Z",
     *       "media": [
     *         {
     *           "id": 1,
     *           "media_type": "image",
     *           "original_path": "https://example.com/image.jpg"
     *         }
     *       ],
     *       "distribution": {
     *         "status": "assigned",
     *         "assigned_at": "2023-12-27T10:00:00.000000Z"
     *       }
     *     }
     *   },
     *   "message": "Concern submitted successfully!"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "title": ["Title is required."]
     *   }
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while submitting concern: error details"
     * }
     */
    public function store(StoreConcernRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // 🔍 Step 0: Determine Concern Type & Prepare Data
            $concernType = $validated['type'];

            if ($concernType === 'voice') {
                $title = $validated['title'] ?? 'Voice Concern - '.now()->format('M d, Y H:i');
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
                'severity' => $validated['severity'] ?? 'low',
                'transcript_text' => $validated['transcript_text'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'address' => $validated['address'] ?? null,
                'custom_location' => $validated['custom_location'] ?? null,
                'tracking_code' => $trackingCode,
            ]);

            $uploadedMedia = [];

            // 🟡 Step 2: Handle file uploads
            // Check for 'files' OR 'images' (fallback)
            $fileInput = null;
            if ($request->hasFile('files')) {
                $fileInput = $request->file('files');
                Log::info('Manual Concern: Found "files" input.');
            } elseif ($request->hasFile('images')) {
                $fileInput = $request->file('images');
                Log::info('Manual Concern: Found "images" input (fallback used).');
            } else {
                Log::warning('Manual Concern: No "files" or "images" detected in request.', [
                    'keys' => array_keys($request->all()),
                    'has_file_files' => $request->hasFile('files'),
                    'has_file_images' => $request->hasFile('images'),
                ]);
            }

            if ($fileInput) {
                $files = $fileInput;
                $isMultiple = is_array($files);

                // Upload (single or multiple)
                $uploadResults = $isMultiple
                    ? $this->fileUploadService->uploadMultiple($files, 'concerns')
                    : ['successful' => [$this->fileUploadService->uploadSingle($files, 'concerns')]];

                // 🟣 Step 3: Save uploaded files in the database
                foreach ($uploadResults['successful'] as $upload) {
                    $mimeType = $upload['mime_type'] ?? '';
                    $mediaType = str_starts_with($mimeType, 'audio/') ? 'audio' : 'image';

                    $media = IncidentMedia::create([
                        'source_type' => \App\Models\Citizen\Concern::class,
                        'source_id' => $concern->id,
                        'source_category' => 'citizen_concern',
                        'media_type' => $mediaType,
                        'original_path' => $upload['public_url'] ?? null,
                        'blurred_path' => null,
                        'public_id' => $upload['storage_path'] ?? null,
                        'original_filename' => $upload['original_filename'] ?? null,
                        'file_size' => $upload['file_size'] ?? null,
                        'mime_type' => $mimeType,
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

            // 🧠 Step 5.5: Dispatch Background Job for Voice Analysis
            if ($concernType === 'voice') {
                ProcessVoiceConcernJob::dispatch($concern->id);
            }

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

            return $this->sendError('An error occurred while submitting concern: '.$e->getMessage());
        }
    }

    /**
     * Get Concern Details
     * 
     * Retrieve detailed information about a specific concern submitted by the authenticated user.
     * Includes media, distribution status, and action history.
     *
     * @group Citizen
     * @authenticated
     * 
     * @urlParam id integer required The ID of the concern. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "concern": {
     *       "id": 1,
     *       "type": "manual",
     *       "tracking_code": "CN-20231227-ABCD",
     *       "title": "Broken Streetlight",
     *       "description": "The streetlight on Main St is broken",
     *       "category": "infrastructure",
     *       "severity": "medium",
     *       "status": "pending",
     *       "latitude": 16.4023,
     *       "longitude": 120.5960,
     *       "address": "Main St, Baguio City",
     *       "created_at": "2023-12-27T10:00:00.000000Z",
     *       "media": [
     *         {
     *           "id": 1,
     *           "media_type": "image",
     *           "original_path": "https://example.com/image.jpg"
     *         }
     *       ],
     *       "distribution": {
     *         "status": "assigned",
     *         "purok_leader": {
     *           "name": "Juan Dela Cruz",
     *           "officialDetails": {
     *             "position": "Purok Leader"
     *           }
     *         }
     *       },
     *       "histories": [
     *         {
     *           "id": 1,
     *           "status": "pending",
     *           "remarks": "Concern submitted and automatically distributed to Purok Leader.",
     *           "created_at": "2023-12-27T10:00:00.000000Z"
     *         }
     *       ]
     *     }
     *   },
     *   "message": "Manual concern retrieved successfully"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Manual concern not found or you do not have permission to view it"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while retrieving concern: error details"
     * }
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
                    'histories.actor.officialDetails',
                ])
                ->first();

            if (! $concern) {
                return $this->sendError('Manual concern not found or you do not have permission to view it', [], 404);
            }

            return $this->sendResponse([
                'concern' => new ConcernResource($concern),
            ], 'Manual concern retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving manual concern', [
                'error' => $e->getMessage(),
                'concern_id' => $id,
            ]);

            return $this->sendError('An error occurred while retrieving concern: '.$e->getMessage());
        }
    }

    /**
     * Update Concern
     * 
     * Update the title and description of a concern. Only concerns belonging to the authenticated user can be updated.
     *
     * @group Citizen
     * @authenticated
     * 
     * @urlParam id integer required The ID of the concern. Example: 1
     * @bodyParam title string optional Updated concern title. Example: Updated Streetlight Issue
     * @bodyParam description string optional Updated description. Example: Updated description text
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "concern": {
     *       "id": 1,
     *       "title": "Updated Streetlight Issue",
     *       "description": "Updated description text",
     *       "updated_at": "2023-12-27T11:00:00.000000Z"
     *     }
     *   },
     *   "message": "Manual concern updated successfully"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Manual concern not found or you do not have permission to update it"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {}
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while updating concern: error details"
     * }
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
                    },
                ])
                ->first();

            if (! $concern) {
                return $this->sendError('Manual concern not found or you do not have permission to update it', [], 404);
            }

            // Update only title and description
            $concern->update($request->validated());

            DB::commit();

            return $this->sendResponse([
                'concern' => new ConcernResource($concern),
            ], 'Manual concern updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating manual concern', [
                'error' => $e->getMessage(),
                'concern_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while updating concern: '.$e->getMessage());
        }
    }

    /**
     * Delete Concern
     * 
     * Soft delete a concern. Only concerns belonging to the authenticated user can be deleted.
     *
     * @group Citizen
     * @authenticated
     * 
     * @urlParam id integer required The ID of the concern to delete. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "concern_id": "1"
     *   },
     *   "message": "Manual concern deleted successfully"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Manual concern not found or you do not have permission to delete it"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while deleting concern: error details"
     * }
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            // Get concern only if it belongs to the authenticated user
            $concern = Concern::where('id', $id)
                ->where('citizen_id', auth()->id())
                ->first();

            if (! $concern) {
                return $this->sendError('Manual concern not found or you do not have permission to delete it', [], 404);
            }

            // Soft delete the concern
            $concern->delete();

            DB::commit();

            return $this->sendResponse([
                'concern_id' => $id,
            ], 'Manual concern deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting manual concern', [
                'error' => $e->getMessage(),
                'concern_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while deleting concern: '.$e->getMessage());
        }
    }
}
