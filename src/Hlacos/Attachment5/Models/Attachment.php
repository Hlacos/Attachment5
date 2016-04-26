<?php

namespace Hlacos\Attachment5\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Config;

class Attachment extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'attachments';

    /**
     * Image in these sizes will created.
     *
     * @var array $sizes
     */
    protected $sizes;

    /**
     * The temporary path of the upladed file.
     *
     * @var string
     */
    public $path;

    /**
     * Defines polymorphic relations to any other Model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function attachable() {
        return $this->morphTo();
    }

    public static function boot() {
        static::created(function($attachment) {
            if (!$attachment->path || !$attachment->moveFile($attachment->path)) {
                return false;
            }

            $attachment->regenerate();
        });

        static::updating(function($attachment) {
            if ($attachment->path) {
                return false;
            }
        });

        static::deleting(function($attachment) {
            $attachment->removeFile();
        });
    }

    /**
     * Saves the file.
     *
     * When saves a file, it persists the model and move the uploaded file into the place.
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = array()) {
        return parent::save($options);
    }

    public function regenerate() {
        if (count($this->sizes)) {
            foreach ($this->sizes as $size) {
                $this->copySize($size);
            }
        }
    }

    /**
     * Sets model instance attributes.
     *
     * @param string $path
     *
     * @return void
     */
    public function addFile($path) {
        $this->path = $path;
        $this->filename = pathinfo($path, PATHINFO_FILENAME);
        $this->extension = pathinfo($path, PATHINFO_EXTENSION);
        $this->size = filesize($path);
        $this->file_type = mime_content_type($path);
    }

    /**
     * Moves uploaded file to place.
     *
     * @param string $path
     *
     * @return bool
     */
    public function moveFile($path) {
        if (!file_exists(public_path().'/'.$this->basePath())) {
            mkdir(public_path().'/'.$this->basePath(), 0777, true);
        }

        if (copy($path, $this->publicPath())) {
	    if (file_exists($path)) {
                unlink($path);
            }
            return true;
        }

        return false;
    }

    public function removeFile() {
        if (file_exists($this->publicPath())) {
            unlink($this->publicPath());
        }

        if (count($this->sizes)) {
            foreach ($this->sizes as $size) {
                if (file_exists($this->publicPath($size))) {
                    unlink($this->publicPath($size));
                }
            }
        }
    }

    /**
     * Gets the public path of the file.
     *
     * @return string
     */
    public function publicPath($size = null) {
        $publicPath = public_path().$this->publicFilename();
        if ($size) {
            return str_replace('.'.$this->extension, '_'.$size.'.'.$this->extension, $publicPath);
        } else {
            return $publicPath;
        }
    }

    /**
     * Gets the public url of the file.
     *
     * @param int $size
     *
     * @return string
     */
    public function publicUrl($size = null) {
        $publicUrl = asset($this->publicFilename());

        if ($size) {
            return str_replace('.'.$this->extension, '_'.$size.'.'.$this->extension, $publicUrl);
        } else {
            return $publicUrl;
        }
    }

    private function basePath() {
        //TODO: könyvtárszerkezetet módosítani, esetleg uuid-s megoldással.
        return '/'.config::get('attachment5.folder').'/'
            .self::sanitize($this->get_real_class(), true, true)
            .'/'.$this->id.'/';
    }

    private function publicFilename() {
        return $this->basePath().$this->baseFilename();
    }

    private function baseFilename() {
        return $this->filename.'.'.$this->extension;
    }

    private function copySize($size) {
        list($width, $height) = getimagesize($this->publicPath());

        list($newWidth, $newHeight, $destinationX, $destinationY, $sourceX, $sourceY, $destinationWidth, $destinationHeight, $sourceWidth, $sourceHeight) = $this->calcSizes($size, $width, $height);

        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);

        if (strtolower($this->extension) == 'jpg') {
            $source = imagecreatefromjpeg($this->publicPath());
            imagecopyresampled($thumb, $source, $destinationX, $destinationY, $sourceX, $sourceY, $destinationWidth, $destinationHeight, $sourceWidth, $sourceHeight);
            imagejpeg($thumb, $this->publicPath($size));
        } elseif (strtolower($this->extension) == 'jpeg') {
            $source = imagecreatefromjpeg($this->publicPath());
            imagecopyresampled($thumb, $source, $destinationX, $destinationY, $sourceX, $sourceY, $destinationWidth, $destinationHeight, $sourceWidth, $sourceHeight);
            imagejpeg($thumb, $this->publicPath($size));
        } elseif (strtolower($this->extension) == 'png') {
            $source = imagecreatefrompng($this->publicPath());
            imagecopyresampled($thumb, $source, $destinationX, $destinationY, $sourceX, $sourceY, $destinationWidth, $destinationHeight, $sourceWidth, $sourceHeight);
            imagepng($thumb, $this->publicPath($size));
        } elseif (strtolower($this->extension) == 'gif') {
            $source = imagecreatefromgif($this->publicPath());
            imagecopyresampled($thumb, $source, $destinationX, $destinationY, $sourceX, $sourceY, $destinationWidth, $destinationHeight, $sourceWidth, $sourceHeight);
            imagegif($thumb, $this->publicPath($size));
        }
    }

    private function calcSizes($size, $width, $height) {
        switch ($size) {
            case (preg_match('/^([0-9]*)w$/i', $size, $matches) ? true : false) :
                $newWidth = $matches[1];
                return $this->resizeWidth($width, $height, $newWidth);
                break;
            case (preg_match('/^([0-9]*)h$/i', $size, $matches) ? true : false) :
                $newHeight = $matches[1];
                return $this->resizeHeight($width, $height, $newHeight);
                break;
            case (preg_match('/^([0-9]*)x([0-9]*)b$/i', $size, $matches) ? true : false) :
                $newWidth = $matches[1];
                $newHeight= $matches[2];
                return $this->resizeBox($width, $height, $newWidth, $newHeight);
                break;
            case (preg_match('/^([0-9]*)x([0-9]*)c$/i', $size, $matches) ? true : false) :
                $newWidth = $matches[1];
                $newHeight= $matches[2];
                return $this->cropBox($width, $height, $newWidth, $newHeight);
                break;
            case (preg_match('/^([0-9]*)x([0-9]*)e$/i', $size, $matches) ? true : false) :
                $newWidth = $matches[1];
                $newHeight= $matches[2];
                return $this->expandBox($width, $height, $newWidth, $newHeight);
                break;
            default:
                return $this->resizeBox($width, $height);
                break;
        }

    }

    private function resizeWidth($width, $height, $newWidth) {
        $newHeight = round(($newWidth / $width) * $height);

        return array(
            $newWidth,
            $newHeight,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );
    }

    private function resizeHeight($width, $height, $newHeight) {
        $newWidth = round(($newHeight / $height) * $width);

        return array(
            $newWidth,
            $newHeight,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );
    }

    private function cropBox($width, $height, $newWidth, $newHeight) {
        if ($width / $height < $newWidth / $newHeight) {
            $ratioHeight = round(($newWidth / $width) * $height);

            $sourceX = 0;
            $sourceY = round(($height - ($newHeight / ($newWidth / $width))) / 2);

            $cropWidth = $width;
            $cropHeight = round($newHeight / ($newWidth / $width));
        } else {
            $ratioWidth = round(($newHeight / $height) * $width);

            $sourceX = round(($width - ($newWidth / ($newHeight / $height))) / 2);
            $sourceY = 0;

            $cropHeight = $height;
            $cropWidth = round($newWidth / ($newHeight / $height));
        }

        return array(
            $newWidth,
            $newHeight,
            0,
            0,
            $sourceX,
            $sourceY,
            $newWidth,
            $newHeight,
            $cropWidth,
            $cropHeight
        );
    }

    private function expandBox($width, $height, $newWidth, $newHeight) {
        if ($width / $height < $newWidth / $newHeight) {
            $containWidth = round($width * ($newHeight / $height));
            $containHeight = $newHeight;

            $destinationX = round(($newWidth - $containWidth) / 2);
            $destinationY = 0;
        } else {
            $containWidth = $newWidth;
            $containHeight = round($height * ($newWidth / $width));

            $destinationX = 0;
            $destinationY = round(($newHeight - $containHeight) / 2);
        }

        return array(
            $newWidth,
            $newHeight,
            $destinationX,
            $destinationY,
            0,
            0,
            $containWidth,
            $containHeight,
            $width,
            $height
        );
    }

    private function resizeBox($width, $height, $newWidth, $newHeight) {
        if ($width / $height >= $newWidth / $newHeight) {
            $newHeight = round(($newWidth / $width) * $height);
        } else {
            $newWidth = round(($newHeight / $height) * $width);
        }

        return array(
            $newWidth,
            $newHeight,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );
    }

    public static function sanitize($string, $forceLowercase = false, $alpha = false) {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "=", "+", "[", "{", "]",
            "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
            "â€”", "â€“", ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($alpha) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
        return ($forceLowercase) ?
            (function_exists('mb_strtolower')) ?
                mb_strtolower($clean, 'UTF-8') :
                strtolower($clean) :
            $clean;
    }

    private function get_real_class() {
        $classname = get_class($this);

        if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
            $classname = $matches[1];
        }

        return $classname;
    }
}
