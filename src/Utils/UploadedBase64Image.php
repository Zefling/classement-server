<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class UploadedBase64Image extends UploadedFile
{

    public const MAX_WIDTH = 300;
    public const MAX_HEIGHT = 300;

    private $name;
    private $filePath;

    /**
     * save base 64 image
     * 
     * @param string $base64String file in BASE 64
     * @param string $rootPath     root path (symfony)
     * @param string $folder       save name folder for images
     */
    public function __construct(string $base64String, string $rootPath, string $folder = 'images')
    {
        $this->rootPath = $rootPath;
        $this->folder = $folder;
        $content =  $this->extractBase64String($base64String);

        $this->name = sha1($content['file']);

        $this->filePath = tempnam(sys_get_temp_dir(), $this->name);
        $data = base64_decode($content['file']);
        file_put_contents($this->filePath, $data);
        $mimeType = $content['mimeType'];

        $error = null;
        $test = true;

        parent::__construct($this->filePath, $this->name, $mimeType, $error, $test);
    }

    /**
     * Save image in final folder ($rootPath/$folder/xx/xx/xxx.webp)
     * @return string URI path of image
     */
    public function saveImage(
        int $widthTarget = self::MAX_WIDTH,
        int $heightTarget = self::MAX_HEIGHT,
        ?string $path = null,
        bool $overwrite = false
    ): array {
        $present = true;
        $size = 0;

        // move to tmp folder
        $this->move("{$this->rootPath}/{$this->folder}/~tmp/", $this->name);

        // resize image
        $source = "{$this->rootPath}/{$this->folder}/~tmp/{$this->name}";
        $this->resize($source, $widthTarget, $heightTarget);

        // for new name
        if ($path === null) {
            $nameTarget = sha1_file("{$source}.webp");
            preg_match('!(.)(.)(.)(.)(.*)!', $nameTarget, $matches);
            $nameTarget = $matches[5] . '.webp';

            // move file to final folder if it does not exist yet
            $folder = "/{$this->folder}/{$matches[1]}/{$matches[2]}/{$matches[3]}/{$matches[4]}";
        } else {
            preg_match('!(.*)/([^/]*)!', $path, $matches);

            $folder = "/{$this->folder}/{$matches[1]}";
            $nameTarget = $matches[2];
        }

        $target = "{$this->rootPath}{$folder}/{$nameTarget}";

        if (!$overwrite) {
            if (!file_exists($target)) {
                if (!file_exists("{$this->rootPath}{$folder}")) {
                    mkdir("{$this->rootPath}{$folder}", 0777, true);
                }
                rename("{$source}.webp", $target);
                $present = false;
                $size = filesize($target);
            } else {
                // already exists, delete this one (avoid duplicate)
                unlink("{$source}.webp");
            }
        } else {
            if (!file_exists("{$this->rootPath}{$folder}")) {
                mkdir("{$this->rootPath}{$folder}", 0777, true);
            }
            $present = file_exists($target);
            if ($present) {
                unlink("{$target}");
            }
            rename("{$source}.webp", $target);
        }

        // remove source file
        unlink($source);

        // return
        return [
            "{$folder}/{$nameTarget}",
            $size,
            $present
        ];
    }

    /**
     * Remove image with path
     * @return boolean if a file has been deleted
     */
    public static function removeImage(string $path): bool
    {
        if (file_exists("{$path}")) {
            unlink("{$path}");
            return true;
        }
        return false;
    }

    /**
     * resize image
     * @param string $filename file name
     */
    private function resize(
        string $filename,
        $widthTarget = self::MAX_WIDTH,
        $heightTarget = self::MAX_HEIGHT
    ): void {
        // Animated GIFs and animated WebPs must be handled frame by frame.
        // GD only processes the first frame, so we use Imagick or CLI instead.
        if ($this->isAnimated($filename)) {
            $this->resizeAnimatedToWebP($filename, $widthTarget, $heightTarget);
            return;
        }

        list($width, $height) = getimagesize($filename);
        $size  = $this->resizeDimension($width, $height, $widthTarget, $heightTarget);

        $imagine = new Imagine();
        $imagine
            ->open($filename)
            ->resize(new Box($size['width'], $size['height']))
            ->save($filename . '.webp', ['quality' => 95]);
    }

    /**
     * Resize an animated image (GIF or WebP) to an animated WebP.
     * Uses Imagick extension if available, otherwise falls back to ImageMagick CLI.
     * @param string $filename file path
     */
    private function resizeAnimatedToWebP(
        string $filename,
        int $widthTarget = self::MAX_WIDTH,
        int $heightTarget = self::MAX_HEIGHT
    ): void {
        $output = $filename . '.webp';

        if (extension_loaded('imagick')) {
            // Use Imagick PHP extension: handles all frames natively
            $imagick = new \Imagick($filename);
            $imagick = $imagick->coalesceImages();

            $size = $this->resizeDimension(
                $imagick->getImageWidth(),
                $imagick->getImageHeight(),
                $widthTarget,
                $heightTarget
            );

            foreach ($imagick as $frame) {
                $frame->resizeImage(
                    $size['width'],
                    $size['height'],
                    \Imagick::FILTER_LANCZOS,
                    1
                );
            }

            $imagick = $imagick->deconstructImages();
            $imagick->setFormat('WEBP');
            $imagick->writeImages($output, true);
            $imagick->destroy();
        } else {
            // Fallback: ImageMagick CLI (convert)
            list($width, $height) = getimagesize($filename);
            $size = $this->resizeDimension($width, $height, $widthTarget, $heightTarget);

            $geometry = escapeshellarg("{$size['width']}x{$size['height']}!");
            $input    = escapeshellarg($filename);
            $out      = escapeshellarg($output);

            exec("convert {$input} -coalesce -resize {$geometry} -quality 95 {$out}");
        }
    }

    /**
     * Detect if an image file is animated (multi-frame GIF or animated WebP).
     * @param string $filename file path
     */
    private function isAnimated(string $filename): bool
    {
        $mime = mime_content_type($filename);

        if ($mime === 'image/gif') {
            // Count GIF frame header signatures (0x00 0x21 0xF9 0x04)
            $content = file_get_contents($filename);
            return substr_count($content, "\x00\x21\xF9\x04") > 1;
        }

        if ($mime === 'image/webp') {
            // Animated WebP containers carry an 'ANIM' chunk
            $content = file_get_contents($filename);
            return strpos($content, 'ANIM') !== false;
        }

        return false;
    }

    /**
     * calculte box dimension for resize
     * @param int  $widthSource  source width
     * @param int  $heightSource source height
     * @param int  $widthTarget  target width
     * @param int  $heightTarget target height
     * @param bool $ratio        if true respect ratio of source
     * @param bool $enlarge      if true enlarge image if to small
     */
    private function resizeDimension(
        $widthSource,
        $heightSource,
        $widthTarget = self::MAX_WIDTH,
        $heightTarget = self::MAX_HEIGHT,
        $ratio = true,
        $enlarge = false,
    ) {
        $heightTemp = $heightSource;
        $widthTemp = $widthSource;
        $widthCible = $heightSource;
        $heightCible = $widthSource;
        $resize = true;

        if (!$enlarge && $widthTemp <= $widthTarget && $heightTemp <= $heightTarget) {
            $widthCible = $widthSource;
            $heightCible = $heightSource;
            $resize = false;
        } else if ($widthTarget > 0 && $heightTarget > 0) {
            if ($ratio) {
                if ($widthTarget <= $widthTemp || $heightTarget <= $heightTemp) {
                    if ($widthTarget / $widthTemp > $heightTarget / $heightTemp) {
                        $widthCible = round(($widthTemp / $heightTemp) * $heightTarget);
                        $heightCible = $heightTarget;
                    } else {
                        $widthCible = $widthTarget;
                        $heightCible = round(($heightTemp / $widthTemp) * $widthTarget);
                    }
                }
            } else {
                $widthCible = $widthTarget;
                $heightCible = $heightTarget;
            }
        }
        return [
            'width' => $widthCible,
            'height' => $heightCible,
            'resize' => $resize
        ];
    }

    /**
     * extracte BASE 64 file string et mineType
     */
    private function extractBase64String(string $base64Content)
    {
        $data = explode(';base64,', $base64Content);
        return [
            'mimeType' => str_replace('base:', '', $data[0]),
            'file' => $data[1]
        ];
    }
}
