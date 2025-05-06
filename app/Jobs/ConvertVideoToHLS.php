<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ConvertVideoToHLS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $videoPath;
    public $hlsDirectory;
    public $outputFileName;

    /**
     * Create a new job instance.
     *
     * @param string $videoPath
     * @param string $hlsDirectory
     * @param string $outputFileName
     * @return void
     */
    public function __construct($videoPath, $hlsDirectory, $outputFileName)
    {
        $this->videoPath = $videoPath;
        $this->hlsDirectory = $hlsDirectory;
        $this->outputFileName = $outputFileName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Run the conversion command using ffmpeg
        $outputPath = $this->hlsDirectory . $this->outputFileName . ".m3u8";
        $ffmpegCommand = "ffmpeg -i {$this->videoPath} -c:v libx264 -c:a aac -strict experimental -f hls -hls_time 10 -hls_list_size 0 -hls_segment_filename {$this->hlsDirectory}{$this->outputFileName}_%03d.ts {$outputPath}";

        exec($ffmpegCommand, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error("Video conversion failed for {$this->videoPath}");
        } else {
            Log::info("Video conversion succeeded for {$this->videoPath}");
        }
    }
}