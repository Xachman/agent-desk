<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SlackIconService
{
    public function generateForAgent(string $name, ?string $seed = null): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $initials = $this->initials($name);
        $seed = $seed ?? Str::uuid()->toString();
        $background = $this->hashColor($seed);
        $foreground = $this->contrastColor($background);

        $size = 512;
        $image = imagecreatetruecolor($size, $size);

        $bg = imagecolorallocate($image, $background[0], $background[1], $background[2]);
        $fg = imagecolorallocate($image, $foreground[0], $foreground[1], $foreground[2]);

        imagefill($image, 0, 0, $bg);

        $fontSize = $size * 0.45;
        $bbox = imagettfbbox($fontSize, 0, $this->fontPath(), $initials);

        if ($bbox === false) {
            imagedestroy($image);
            return null;
        }

        $textWidth = abs($bbox[4] - $bbox[0]);
        $textHeight = abs($bbox[5] - $bbox[1]);
        $x = (int) (($size - $textWidth) / 2 - $bbox[0]);
        $y = (int) (($size + $textHeight) / 2 - $bbox[1]);

        imagettftext($image, $fontSize, 0, $x, $y, $fg, $this->fontPath(), $initials);

        $path = 'slack-icons/' . Str::uuid()->toString() . '.png';
        $full = Storage::disk('local')->path($path);
        $dir = dirname($full);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagepng($image, $full, 6);
        imagedestroy($image);

        return $path;
    }

    public function getIconBase64(string $storagePath): ?string
    {
        $full = Storage::disk('local')->path($storagePath);

        if (!file_exists($full)) {
            return null;
        }

        return base64_encode(file_get_contents($full));
    }

    protected function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));
        $words = array_filter($words);

        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 2));
    }

    protected function hashColor(string $seed): array
    {
        $hash = md5($seed);

        return [
            (int) hexdec(substr($hash, 0, 2)),
            (int) hexdec(substr($hash, 2, 2)),
            (int) hexdec(substr($hash, 4, 2)),
        ];
    }

    protected function contrastColor(array $rgb): array
    {
        $luminance = (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255;

        return $luminance > 0.5 ? [30, 30, 30] : [255, 255, 255];
    }

    protected function fontPath(): string
    {
        $candidates = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
        ];

        foreach ($candidates as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }

        $search = shell_exec('fc-list :bold | head -n1');
        if ($search) {
            $parts = explode(':', $search);
            $file = trim($parts[0] ?? '');
            if (file_exists($file)) {
                return $file;
            }
        }

        throw new RuntimeException('No TrueType font available for Slack icon generation.');
    }
}
