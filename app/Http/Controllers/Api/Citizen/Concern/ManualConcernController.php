<?php

namespace App\Http\Controllers\Api\Citizen\Concern;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Citizen\ConcernRequest;
use App\Http\Requests\Citizen\UpdateConcernRequest;
use App\Models\Citizen\Concern;
use App\Models\IncidentMedia;
use App\Models\ConcernDistribution;
use App\Events\ConcernAssigned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\FileUploadService;


class ManualConcernController extends BaseApiController
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
            $concerns = Concern::where('type', 'manual')
                ->where('citizen_id', auth()->id())
                ->with([
                    'media' => function ($query) {
                        $query->where('source_category', 'citizen_concern');
                    }
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedConcerns = $concerns->map(function ($concern) {
                return [
                    'id' => $concern->id,
                    'title' => $concern->title,
                    'description' => $concern->description,
                    'category' => $concern->category,
                    'severity' => $concern->severity,
                    'latitude' => $concern->latitude,
                    'longitude' => $concern->longitude,
                    'status' => $concern->status,
                    'created_at' => $concern->created_at,
                    'images' => $concern->media->pluck('original_path')->toArray()
                ];
            });

            return $this->sendResponse([
                'concerns' => $formattedConcerns
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
    public function store(ConcernRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // 🔍 Step 0: Determine Concern Type & Prepare Data
            $concernType = $validated['type'];
            
            if ($concernType === 'voice') {
                $title = 'Voice Concern - ' . now()->format('M d, Y H:i');
                $description = 'Audio recording received. Transcription pending...';
            } else {
                $title = $validated['title'];
                $description = $validated['description'];
            }

            // 🟢 Step 1: Create the Concern record
            $concern = Concern::create([
                'type' => $concernType,
                'citizen_id' => auth()->id(),
                'title' => $title,
                'description' => $description,
                'status' => 'pending',
                'category' => $validated['category'],
                'severity' => 'low',
                'transcript_text' => $validated['transcript_text'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
            ]);

            $uploadedImages = [];
            $audioUrl = null;

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
                    $mimeType = $upload['mime_type'] ?? '';
                    $isAudio = str_starts_with($mimeType, 'audio/');
                    $mediaType = $isAudio ? 'audio' : 'image';
                    
                    if ($isAudio) {
                        $audioUrl = $upload['public_url'];
                    } else {
                        $uploadedImages[] = $upload['public_url'];
                    }

                    IncidentMedia::create([
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
                }
            }

            // 🔵 Step 4: Distribute concern to purok leader
            // TODO: Replace hardcoded purok_leader_id with actual location-based logic
            $purokLeaderId = 2;

            $distribution = ConcernDistribution::create([
                'concern_id' => $concern->id,
                'purok_leader_id' => $purokLeaderId,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            DB::commit();

            // 🟣 Step 5: Broadcast real-time notification to purok leader
            event(new ConcernAssigned($concern, $distribution, $uploadedImages, $audioUrl));

            // 🟢 Step 6: Return successful response
            return $this->sendResponse([
                'concern' => [
                    'id' => $concern->id,
                    'title' => $concern->title,
                    'description' => $concern->description,
                    'category' => $concern->category,
                    'severity' => $concern->severity,
                    'status' => $concern->status,
                    'created_at' => $concern->created_at,
                    'images' => $uploadedImages,
                    'audio' => $audioUrl,
                    'summary' => null,
                ],
            ], 'Concern submitted successfully!');
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
            $concern = Concern::where('type', 'manual')
                ->where('id', $id)
                ->where('citizen_id', auth()->id())
                ->with([
                    'media' => function ($query) {
                        $query->where('source_category', 'citizen_concern');
                    }
                ])
                ->first();

            if (!$concern) {
                return $this->sendError('Manual concern not found or you do not have permission to view it', [], 404);
            }

            $formattedConcern = [
                'id' => $concern->id,
                'title' => $concern->title,
                'description' => $concern->description,
                'category' => $concern->category,
                'severity' => $concern->severity,
                'status' => $concern->status,
                'latitude' => $concern->latitude,
                'longitude' => $concern->longitude,
                'created_at' => $concern->created_at,
                'images' => $concern->media->pluck('original_path')->toArray()
            ];

            return $this->sendResponse([
                'concern' => $formattedConcern
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
            $concern = Concern::where('type', 'manual')
                ->where('id', $id)
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
            $concern->update([
                'title' => $request->title,
                'description' => $request->description,
            ]);

            DB::commit();

            $formattedConcern = [
                'id' => $concern->id,
                'title' => $concern->title,
                'description' => $concern->description,
                'category' => $concern->category,
                'severity' => $concern->severity,
                'status' => $concern->status,
                'latitude' => $concern->latitude,
                'longitude' => $concern->longitude,
                'created_at' => $concern->created_at,
                'updated_at' => $concern->updated_at,
                'images' => $concern->media->pluck('original_path')->toArray()
            ];

            return $this->sendResponse([
                'concern' => $formattedConcern
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
            $concern = Concern::where('type', 'manual')
                ->where('id', $id)
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
