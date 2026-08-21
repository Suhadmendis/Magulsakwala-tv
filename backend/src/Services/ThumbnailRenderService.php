<?php

/**
 * Composites background_image + image_1..5 + text_1..3 into a single
 * rendered PNG thumbnail, using GD.
 */
class ThumbnailRenderService
{
    private const CANVAS_WIDTH = 1280;
    private const CANVAS_HEIGHT = 720;
    private const FONT_PATH = __DIR__ . '/../../assets/fonts/DejaVuSans-Bold.ttf';

    public function render(array $thumbnail, int $accountId): string
    {
        $canvas = imagecreatetruecolor(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        $gray = imagecolorallocate($canvas, 40, 40, 40);
        imagefill($canvas, 0, 0, $gray);

        if (!empty($thumbnail['background_image']) && is_file($thumbnail['background_image'])) {
            $this->drawScaledToFill($canvas, $thumbnail['background_image'], self::CANVAS_WIDTH, self::CANVAS_HEIGHT, 0, 0);
        }

        $overlaySlots = ['image_1', 'image_2', 'image_3', 'image_4', 'image_5'];
        $overlaySize = 160;
        $x = 20;
        foreach ($overlaySlots as $slot) {
            if (!empty($thumbnail[$slot]) && is_file($thumbnail[$slot])) {
                $this->drawScaledToFill($canvas, $thumbnail[$slot], $overlaySize, $overlaySize, $x, 20);
            }
            $x += $overlaySize + 20;
        }

        $textSlots = [
            'text_1' => ['size' => 64, 'y' => self::CANVAS_HEIGHT - 220],
            'text_2' => ['size' => 44, 'y' => self::CANVAS_HEIGHT - 140],
            'text_3' => ['size' => 32, 'y' => self::CANVAS_HEIGHT - 80],
        ];
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 0, 0, 0);

        foreach ($textSlots as $slot => $style) {
            if (empty($thumbnail[$slot])) {
                continue;
            }
            $text = $thumbnail[$slot];
            $x = 40;
            $y = $style['y'];
            $size = $style['size'];

            if (is_file(self::FONT_PATH)) {
                // Outline for legibility, then the fill text on top.
                foreach ([[-2, 0], [2, 0], [0, -2], [0, 2]] as [$dx, $dy]) {
                    imagettftext($canvas, $size, 0, $x + $dx, $y + $dy, $black, self::FONT_PATH, $text);
                }
                imagettftext($canvas, $size, 0, $x, $y, $white, self::FONT_PATH, $text);
            } else {
                imagestring($canvas, 5, $x, $y, $text, $white);
            }
        }

        $filename = 'rendered_' . uniqid('', true) . '.png';
        $dir = (new StorageService())->thumbnailsDir($accountId);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            imagedestroy($canvas);
            throw new RuntimeException("Failed to create thumbnail folder: $dir");
        }
        $destination = $dir . '/' . $filename;
        if (!imagepng($canvas, $destination)) {
            imagedestroy($canvas);
            throw new RuntimeException("Failed to write rendered thumbnail to $destination");
        }
        imagedestroy($canvas);

        return $destination;
    }

    private function drawScaledToFill($canvas, string $sourcePath, int $width, int $height, int $destX, int $destY): void
    {
        $info = @getimagesize($sourcePath);
        if (!$info) {
            return;
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : null,
            default => null,
        };

        if (!$source) {
            return;
        }

        imagecopyresampled(
            $canvas,
            $source,
            $destX,
            $destY,
            0,
            0,
            $width,
            $height,
            imagesx($source),
            imagesy($source)
        );
        imagedestroy($source);
    }
}
