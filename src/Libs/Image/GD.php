<?php

namespace Atom\Libs\Image;

use Atom\Libs\Image\Exception\ImageException;

class GD
{
    /**
     * Create Image
     * @param  string $storagePath /storage/images/test.jpg
     * @param  string $file
     * @param  string $imageType
     * @return void
     */
    public function create(string $storagePath, string $file, string $imageType)
    {
        list($width, $height) = getimagesize($file);
        try {
            $dstImg = imagecreatetruecolor($width, $height);
            $srcImg = $this->imageCreateFromType($imageType, $file);
            imagecopy($dstImg, $srcImg, 0, 0, 0, 0, $width, $height);
            $this->outputImageByType($dstImg, $storagePath, $imageType);
        } catch (\Exception $e) {
            throw new ImageException(ImageException::ERR_MSG_UPLOAD_FAIL);
        }
    }

    /**
     * Create and Resize Image
     * @param  string $storagePath /storage/images/test.jpg
     * @param  string $file
     * @param  string $imageType
     * @param  array  $size
     * @return void
     */
    public function createAndResize(string $storagePath, string $file, string $imageType, array $size)
    {
        list($width, $height, $widthOrg, $heightOrg) = $this->resizeInfo($file, $size);

        try {
            $dstImg = imagecreatetruecolor($width, $height);
            $srcImg = $this->imageCreateFromType($imageType, $file);
            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $width, $height, $widthOrg, $heightOrg);
            $this->outputImageByType($dstImg, $storagePath, $imageType);
        } catch (\Exception $e) {
            throw new ImageException(ImageException::ERR_MSG_UPLOAD_FAIL);
        }
    }

    /**
     * Get Resize Info
     * @param  string $file
     * @param  array  $size
     * @return array
     */
    public function resizeInfo(string $file, array $size)
    {
        list($width, $height) = $size;
        if (false === is_numeric($width) || false === is_numeric($height)) {
            throw new ImageException(ImageException::ERR_MSG_BAD_REQUEST);
        }
        list($widthOrg, $heightOrg) = getimagesize($file);

        $ratioOrg = $widthOrg / $heightOrg;
        if ($width / $height > $ratioOrg) {
           $width = $height * $ratioOrg;
        } else {
           $height = $width / $ratioOrg;
        }

        return [$width, $height, $widthOrg, $heightOrg];
    }

    /**
     * Create Image From Type
     * @param  string $type
     * @param  string $file
     * @return mixed
     */
    protected function imageCreateFromType(string $type, string $file)
    {
        switch ($type) {
            case 'png':
                $image = imagecreatefrompng($file);
                break;
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file);
                break;
            case 'gif':
                $image = imagecreatefromgif($file);
                break;
            default:
                throw new ImageException(ImageException::ERR_MSG_UNKNOW_FILE);
        }

        return $image;
    }

    /**
     * Output Image By Type
     * @param  mixed $image
     * @param  string $path
     * @param  string $type
     * @return void
     */
    protected function outputImageByType($image, $path, $type)
    {
        switch ($type) {
            case 'png':
                imagepng($image, $path);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, $path);
                break;
            case 'gif':
                imagegif($image, $path);
                break;
        }
    }
}
