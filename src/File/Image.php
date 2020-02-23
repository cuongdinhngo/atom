<?php

namespace Atom\File;

use Atom\File\Exception\ImageException;
use Atom\Libs\Image\GD;
use Atom\File\File;

class Image extends File
{
    public $type;
    public $gd;
    public $file;
    public $core;
    public $extension;
    public $name;

    /**
     * Image Construct
     * @param string|null $type
     */
    public function __construct(string $type = null)
    {
        parent::__construct($type);
        $this->gd = new GD();

    }

    /**
     * Upload Image
     * @param  string $directory
     * @param  array  $file
     * @param  string $fileName
     * @return void
     */
    protected function upload(string $directory, array $file, string $fileName = null)
    {
        list($storagePath, $core, $imageType) = $this->process($directory, $file, $fileName);
        $this->gd->create($storagePath, $core, $imageType);
        return end(explode('/', $storagePath));
    }

    /**
     * Upload and Resize
     * @param  string      $directory
     * @param  array       $file
     * @param  array       $size
     * @param  string|null $fileName
     * @return void
     */
    protected function uploadResize(string $directory, array $file, array $size, string $fileName = null)
    {
        if (empty($size)) {
            throw new ImageException(ImageException::ERR_MSG_BAD_REQUEST);
        }

        list($storagePath, $core, $imageType) = $this->process($directory, $file, $fileName);
        $this->gd->createAndResize($storagePath, $core, $imageType, $size);
        return end(explode('/', $storagePath));
    }

    /**
     * Get Image Metadata
     * @param  array  $file
     * @return array
     */
    protected function getMetadata(array $file)
    {
        $this->parse($file);
        $exif = exif_read_data($this->core(), 0, true);
        return $exif ? $exif : [];
    }

    /**
     * Delete file
     * @param  string $fileName
     * @return void
     */
    protected function delete(string $fileName)
    {
        if (false === file_exists($fileName)) {
            throw new ImageException(ImageException::ERR_MSG_FILE_NOT_EXIST);
        }
        $this->storage->remove($fileName);
    }
}
