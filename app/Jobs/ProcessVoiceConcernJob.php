<?php

namespace App\Jobs;

use App\Events\ConcernTranscribed;
use App\Models\Citizen\Concern;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVoiceConcernJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $concernId;

    /**
     * Create a new job instance.
     */
    public function __construct($concernId)
    {
        $this->concernId = $concernId;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $geminiService)
    {
        try {
            $concern = Concern::with(['media' => function ($query) {
                $query->where('media_type', 'audio')->orderBy('created_at', 'desc');
            }])->find($this->concernId);

            if (! $concern) {
                Log::error('ProcessVoiceConcernJob: Concern not found', ['concern_id' => $this->concernId]);

                return;
            }

            // Find the audio file
            $audioMedia = $concern->media->first();

            if (! $audioMedia) {
                Log::warning('ProcessVoiceConcernJob: No audio media found for concern', ['concern_id' => $concern->id]);

                return;
            }

            // Retrieve the file content
            // Assuming public_id stores the relative storage path as per FileUploadService
            $storagePath = $audioMedia->public_id;

            // Use the default configured disk (S3 in your case)
            $disk = config('filesystems.default');

            if (! Storage::disk($disk)->exists($storagePath)) {
                Log::error('ProcessVoiceConcernJob: Audio file not found in storage', [
                    'concern_id' => $concern->id,
                    'path' => $storagePath,
                    'disk' => $disk,
                    'configured_disk' => config('filesystems.default'),
                ]);

                return;
            }

            $fileContent = Storage::disk($disk)->get($storagePath);
            $mimeType = $audioMedia->mime_type ?? 'audio/mp3'; // Default fallback

            // Call Gemini Service
            $analysis = $geminiService->analyzeAudio($fileContent, $mimeType);

            if ($analysis) {
                // Update Concern with transcription
                $concern->update([
                    'title' => $analysis['title'] ?? $concern->title,
                    'description' => $analysis['description'] ?? $concern->description,
                    'transcript_text' => $analysis['transcription_text'] ?? null,
                ]);

                Log::info('ProcessVoiceConcernJob: Concern updated with transcription', [
                    'concern_id' => $concern->id,
                ]);

                // Analyze category and severity from transcript
                $transcriptText = $analysis['transcription_text'] ?? $analysis['description'] ?? '';
                
                if (!empty($transcriptText)) {
                    $categoryAnalysis = $geminiService->analyzeConcernCategoryAndSeverity($transcriptText);
                    
                    if ($categoryAnalysis) {
                        $updateData = [
                            'ai_category' => $categoryAnalysis['category'] ?? null,
                            'ai_severity' => $categoryAnalysis['severity'] ?? null,
                            'ai_confidence' => $categoryAnalysis['confidence'] ?? null,
                            'ai_processed_at' => now(),
                        ];

                        // If confidence is high enough, update the main category and severity
                        $confidenceThreshold = 0.7;
                        if (isset($categoryAnalysis['confidence']) && $categoryAnalysis['confidence'] >= $confidenceThreshold) {
                            $updateData['category'] = $categoryAnalysis['category'];
                            $updateData['severity'] = $categoryAnalysis['severity'];
                        }

                        $concern->update($updateData);

                        Log::info('ProcessVoiceConcernJob: Concern updated with AI category analysis', [
                            'concern_id' => $concern->id,
                            'ai_category' => $categoryAnalysis['category'] ?? null,
                            'ai_severity' => $categoryAnalysis['severity'] ?? null,
                            'confidence' => $categoryAnalysis['confidence'] ?? null,
                        ]);

                        // Refresh the model to get the latest data
                        $concern->refresh();

                        Log::info('ProcessVoiceConcernJob: Concern refreshed, preparing to fire event', [
                            'concern_id' => $concern->id,
                            'ai_processed_at_type' => gettype($concern->ai_processed_at),
                        ]);

                        // Fire AI Category Updated Event
                        $concern->load('distribution');
                        
                        Log::info('ProcessVoiceConcernJob: Firing ConcernAICategoryUpdated event', [
                            'concern_id' => $concern->id,
                        ]);

                        event(new \App\Events\ConcernAICategoryUpdated($concern));

                        Log::info('ProcessVoiceConcernJob: Event fired successfully', [
                            'concern_id' => $concern->id,
                        ]);
                    } else {
                        Log::warning('ProcessVoiceConcernJob: AI category analysis returned null or failed', [
                            'concern_id' => $concern->id,
                        ]);
                    }
                } else {
                    Log::warning('ProcessVoiceConcernJob: No transcript text available for category analysis', [
                        'concern_id' => $concern->id,
                    ]);
                }

                // Fire Transcribed Event
                $concern->load('distribution');
                event(new ConcernTranscribed($concern));
            } else {
                Log::warning('ProcessVoiceConcernJob: Gemini analysis failed or returned null', [
                    'concern_id' => $concern->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessVoiceConcernJob: Error processing voice concern', [
                'concern_id' => $this->concernId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
