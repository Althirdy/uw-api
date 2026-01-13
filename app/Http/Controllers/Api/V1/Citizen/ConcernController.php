<?php

namespace App\Http\Controllers\Api\V1\Citizen;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Citizen\StoreConcernRequest;
use App\Http\Requests\Api\V1\Citizen\UpdateConcernRequest;
use App\Http\Resources\Api\V1\ConcernResource;
use App\Services\ConcernService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConcernController extends BaseApiController
{
    protected $concernService;

    public function __construct(ConcernService $concernService)
    {
        $this->concernService = $concernService;
    }

    /**
     * Display a listing of the resource.
     * Optimized for FlashList with cursor pagination and simplified data.
     * Supports filtering by status, category, and severity.
     * 
     * NOTE: Suspended users CAN view their concerns (read-only access).
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 4);
        
        // Build filters array from request
        $filters = [];
        if ($request->filled('status')) {
            $filters['status'] = $request->input('status');
        }
        if ($request->filled('category')) {
            $filters['category'] = $request->input('category');
        }
        if ($request->filled('severity')) {
            $filters['severity'] = $request->input('severity');
        }

        $concerns = $this->concernService->getUserConcerns(auth()->id(), $perPage, $filters);
        $concernsCount = $this->concernService->getConcernsCount(auth()->id(), $filters);

        return $this->sendResponse([
            'concerns' => ConcernResource::collection($concerns),
            'concerns_count' => $concernsCount,
            // Manually extract the cursor string
            'next_cursor' => $concerns->nextCursor()?->encode(),
            'prev_cursor' => $concerns->previousCursor()?->encode(),
        ], 'Concerns retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     * 
     * SUSPENSION CHECK: Suspended users are blocked from creating concerns.
     * This allows them to view announcements/accidents but prevents misuse.
     */
    public function store(StoreConcernRequest $request)
    {
        $validated = $request->validated();

        // Check if user is suspended
        if (\App\Models\UserSuspension::isUserSuspended(auth()->id())) {
            $activeSuspension = \App\Models\UserSuspension::getActiveSuspension(auth()->id());
            
            $message = 'Your account is currently suspended and cannot create concerns.';
            if ($activeSuspension) {
                if ($activeSuspension->punishment_type === 'suspension') {
                    $message = 'Your account has been permanently suspended and cannot create concerns.';
                } else {
                    $expiresAt = $activeSuspension->expires_at->format('F j, Y \\a\\t g:i A');
                    $message = "Your account is suspended until {$expiresAt} and cannot create concerns.";
                }
                
                if ($activeSuspension->reason) {
                    $message .= " Reason: {$activeSuspension->reason}";
                }
            }
            
            return $this->sendError($message, [], 403);
        }

        try {
            // Determine file input (files or images fallback)
            $files = $request->file('files') ?? $request->file('images');

            $concern = $this->concernService->createConcern($validated, auth()->id(), $files);

            return $this->sendResponse([
                'concern' => new ConcernResource($concern),
            ], 'Concern submitted successfully!', 201);
        } catch (\Exception $e) {
            Log::error('Error creating concern', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while submitting concern: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     * Returns full details for when the user selects an item from the list.
     * 
     * NOTE: Suspended users CAN view concern details (read-only access).
     */
    public function show(string $id)
    {

        $concern = $this->concernService->getConcernDetails($id, auth()->id());

        return $this->sendResponse([
            'concern' => new ConcernResource($concern),
        ], 'Concern details retrieved successfully');

        // try {
        //     $concern = $this->concernService->getConcernDetails($id, auth()->id());

        //     if (!$concern) {
        //         return $this->sendError('Concern not found or you do not have permission to view it', [], 404);
        //     }

        //     return $this->sendResponse([
        //         'concern' => new ConcernResource($concern),
        //     ], 'Concern details retrieved successfully');
        // } catch (\Exception $e) {
        //     Log::error('Error retrieving concern details', [
        //         'error' => $e->getMessage(),
        //         'concern_id' => $id,
        //     ]);

        //     return $this->sendError('An error occurred while retrieving concern: ' . $e->getMessage());
        // }
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(UpdateConcernRequest $request, string $id)
    // {

    //     $concern = $this->concernService->updateConcern($id, auth()->id(), $request->validated());

    //     if (!$concern) {
    //         return $this->sendError('Concern not found or you do not have permission to update it', [], 404);
    //     }

    //     return $this->sendResponse([
    //         'concern' => new ConcernResource($concern),
    //     ], 'Concern updated successfully');
    // }

    /**
     * Remove the specified resource from storage.
     * 
     * SUSPENSION CHECK: Suspended users are blocked from deleting concerns.
     */
    public function destroy(string $id)
    {
        // Check if user is suspended
        if (\App\Models\UserSuspension::isUserSuspended(auth()->id())) {
            $activeSuspension = \App\Models\UserSuspension::getActiveSuspension(auth()->id());
            
            $message = 'Your account is currently suspended and cannot delete concerns.';
            if ($activeSuspension) {
                if ($activeSuspension->punishment_type === 'suspension') {
                    $message = 'Your account has been permanently suspended and cannot delete concerns.';
                } else {
                    $expiresAt = $activeSuspension->expires_at->format('F j, Y \\a\\t g:i A');
                    $message = "Your account is suspended until {$expiresAt} and cannot delete concerns.";
                }
                
                if ($activeSuspension->reason) {
                    $message .= " Reason: {$activeSuspension->reason}";
                }
            }
            
            return $this->sendError($message, [], 403);
        }

        $this->concernService->deleteConcern($id, auth()->id());

        return $this->sendResponse([
            'concern_id' => $id,
        ], 'Concern deleted successfully');
    }
}
