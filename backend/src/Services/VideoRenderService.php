<?php

/**
 * Composes a static image + audio into an MP4 via ffmpeg (first shell-out
 * in this codebase — no Imagick/ffmpeg wrapper library, no build tooling).
 * Static image only, no pan/zoom, video length follows the audio.
 */
class VideoRenderService
{
    /**
     * Returns the local filesystem path to the rendered MP4 (caller is
     * responsible for uploading it and deleting the temp file afterward).
     */
    public function render(string $imagePath, string $audioPath, string $videoType): string
    {
        if (!is_file($imagePath)) {
            throw new RuntimeException("Image file not found: $imagePath");
        }
        if (!is_file($audioPath)) {
            throw new RuntimeException("Audio file not found: $audioPath");
        }

        [$width, $height] = $videoType === 'short' ? [2160, 3840] : [3840, 2160];
        $output = sys_get_temp_dir() . '/render_' . uniqid('', true) . '.mp4';
        $filter = "scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2";

        $cmd = sprintf(
            'ffmpeg -y -loop 1 -i %s -i %s -vf %s -c:v libx264 -tune stillimage -c:a aac -b:a 128k -pix_fmt yuv420p -shortest %s 2>&1',
            escapeshellarg($imagePath),
            escapeshellarg($audioPath),
            escapeshellarg($filter),
            escapeshellarg($output)
        );

        exec($cmd, $outputLines, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('ffmpeg failed: ' . implode("\n", $outputLines));
        }
        if (!is_file($output)) {
            throw new RuntimeException('ffmpeg reported success but produced no output file');
        }

        return $output;
    }
}
