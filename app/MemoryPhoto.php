<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MemoryPhoto extends Model
{
    protected $table = 'memories_photos';

    protected $fillable = ['memory_id', 'filename'];

    public function memory()
    {
        return $this->belongsTo('App\Memory');
    }

    public static function correctImageOrientation($directory)
    {
        // Exif extension must be loaded for this to work
        if (!extension_loaded('exif')) {
            return;
        }

        $scanned_directory = array_diff(scandir($directory), array('..', '.'));
        foreach ($scanned_directory as &$file) {
            if (is_dir($directory . "/" . $file)) {
                self::correctImageOrientation($directory . "/" . $file);
            } else {
                $filen = explode(".", $file);
                $ext = end($filen);
                try {
                    $exif = @exif_read_data($directory . "/" . $file);

                    if (isset($exif['Orientation'])) {
                        $orientation = $exif['Orientation'];

                        if (isset($orientation) && $orientation != 1) {
                            switch ($orientation) {
                                case 3:
                                    $deg = 180;
                                    break;
                                case 6:
                                    $deg = 270;
                                    break;
                                case 8:
                                    $deg = 90;
                                    break;
                            }
                            if ($deg) {
                                if ($ext == "png") {
                                    $img_new = imagecreatefrompng($directory . "/" . $file);
                                    $img_new = imagerotate($img_new, $deg, 0);

                                    // Save rotated image
                                    imagepng($img_new, $directory . $file);
                                } else {
                                    $img_new = imagecreatefromjpeg($directory . "/" . $file);
                                    $img_new = imagerotate($img_new, $deg, 0);

                                    // Save rotated image
                                    imagejpeg($img_new, $directory . "/" . $file, 80);
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // echo "error:";
                    // echo $e;
                    // echo "error";
                }
            }
        }
        unset($file);
    }
}
