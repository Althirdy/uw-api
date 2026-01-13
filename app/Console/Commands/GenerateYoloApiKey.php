<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateYoloApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yolo:generate-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a secure API key for YOLO Python script authentication';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = 'yolo_' . Str::random(64);

        $this->info('YOLO API Key generated successfully!');
        $this->line('');
        $this->line('Add this to your .env file:');
        $this->warn("YOLO_API_KEY={$apiKey}");
        $this->line('');
        $this->line('Use this same key in your Python script when making requests to:');
        $this->comment('GET /api/v1/yolo/enabled-cctvs');
        $this->line('');
        $this->info('Example Python code:');
        $this->line('headers = {"x-api-key": "' . $apiKey . '"}');
        $this->line('response = requests.get(url, headers=headers)');

        return Command::SUCCESS;
    }
}
