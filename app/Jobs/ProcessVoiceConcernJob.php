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

            if (!$concern) {
                Log::error('ProcessVoiceConcernJob: Concern not found', ['concern_id' => $this->concernId]);
                return;
            }

            // Find the audio file
            $audioMedia = $concern->media->first();

            if (!$audioMedia) {
                Log::warning('ProcessVoiceConcernJob: No audio media found for concern', ['concern_id' => $concern->id]);
                return;
            }

            // Retrieve the file content
            // Assuming public_id stores the relative storage path as per FileUploadService
            $storagePath = $audioMedia->public_id;
            
            // Determine disk based on configuration or try default
            // FileUploadService logic suggests it uses 'public' or 's3' or 'local' (mapped to public)
            // We'll try the default disk first, then 'public' if not found
            $disk = config('filesystems.default');
            if (!Storage::disk($disk)->exists($storagePath)) {
                $disk = 'public'; // Fallback
            }

            if (!Storage::disk($disk)->exists($storagePath)) {
                Log::error('ProcessVoiceConcernJob: Audio file not found in storage', [
                    'concern_id' => $concern->id,
                    'path' => $storagePath,
                    'disk' => $disk
                ]);
                return;
            }

            $fileContent = Storage::disk($disk)->get($storagePath);
            $mimeType = $audioMedia->mime_type ?? 'audio/mp3'; // Default fallback

            // Call Gemini Service
            $analysis = $geminiService->analyzeAudio($fileContent, $mimeType);

            if ($analysis) {
                // Update Concern
                $concern->update([
                    'title' => $analysis['title'] ?? $concern->title,
                    'description' => $analysis['description'] ?? $concern->description,
                    'transcript_text' => $analysis['transcription_text'] ?? null,
                ]);

                Log::info('ProcessVoiceConcernJob: Concern updated with transcription', [
                    'concern_id' => $concern->id
                ]);

                // Fire Event
                event(new ConcernTranscribed($concern));
            } else {
                Log::warning('ProcessVoiceConcernJob: Gemini analysis failed or returned null', [
                    'concern_id' => $concern->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessVoiceConcernJob: Error processing voice concern', [
                'concern_id' => $this->concernId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
