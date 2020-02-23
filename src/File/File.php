<?php

namespace Atom\File;

use Atom\Http\Globals;
use Atom\File\Exception\FileException;
use Atom\Storage\StorageFactory;

class File extends Globals
{
    public $file;
    public $type;
    public $storage;

    /**
     * Image Construct
     * @param string|null $type
     */
    public function __construct(string $type = null)
    {
        $this->type = $type ?? env('STORAGE_DRIVER');
        $this->storage = (new StorageFactory($this->type))->init();
    }

    /**
     * Parse File
     * @param  array  $file
     * @return $this
     */
    protected function parse(array $file)
    {
        if (empty($file["tmp_name"])) {
            throw new FileException(FileException::ERR_MSG_FILE_TOO_LARGE);
        }
        $this->file = $file;
        return $this;
    }

    /**
     * Extract File Info
     * @param  string      $path
     * @param  string|null $fileName
     * @return array
     */
    protected function extractFileName(string $path, string $fileName = null)
    {
        list($name, $extension) = explode('.', $fileName);
        if (empty($fileName)) {
            $fileName = $this->name();
            $extension = pathinfo($fileName)["extension"];
        }

        if (empty($extension)) {
            throw new FileException(FileException::ERR_MSG_UNKNOW_FILE);
        }

        $fullDirectory = $this->storage->getFullUrl($path);
        $this->isExist($fullDirectory . $fileName);

        return [$fileName, strtolower($extension)];
    }

    /**
     * File is existed
     * @param  string  $fileName
     * @return boolean
     */
    protected function isExist(string $fileName)
    {
        if (file_exists($fileName)) {
            throw new FileException(FileException::ERR_MSG_FILENAME_ALREADY_USED);
        }
        return;
    }

    /**
     * Get Core
     * @return string
     */
    protected function core()
    {
        return $this->file["tmp_name"];
    }

    /**
     * Get Extension
     * @return string
     */
    protected function extension()
    {
        return pathinfo($this->file["name"])["extension"];
    }

    /**
     * Get Name
     * @return string
     */
    protected function name()
    {
        return $this->file["name"];
    }

    /**
     * Get Type
     * @return string
     */
    protected function type()
    {
        return $this->file["type"];
    }

    /**
     * Get Size
     * @return string
     */
    protected function size()
    {
        return $this->file["size"];
    }

    /**
     * Get Metadata
     * @return array
     */
    protected function metadata()
    {
        $exif = exif_read_data($this->core(), 0, true);
        return $exif ? $exif : [];
    }

    /**
     * Encode file to base64
     * @param  array  $file
     * @return array
     */
    protected function encode(array $file)
    {        
        $this->parse($file);
        $binaryContent = file_get_contents($this->core());
        return [
            "name" => $this->name(),
            "type" => $this->type(),
            "metadata" => $this->metadata(),
            "content" => base64_encode($binaryContent),
        ];
    }

    /**
     * Decode base64
     * @param  string $content
     * @return string
     */
    protected function decode(string $content)
    {
        return base64_decode($content);
    }

    /**
     * Decode and Save as
     * @param  string $directory /storage/images/test.jpg
     * @param  string $content
     * @return void
     */
    protected function decodeSaveAs(string $directory, string $content)
    {
        try {
            $this->isExist($directory);
            $decode = $this->decode($content);
            file_put_contents($directory, $decode);
        } catch (\Exception $e) {
            throw new FileException(FileException::ERR_MSG_SAVE_FILE_FAIL . ': ' . $e->getMessage());
        }
    }

    /**
     * Process File
     * @param  string      $directory /storage/images
     * @param  array       $file
     * @param  string|null $fileName
     * @return array
     */
    protected function process(string $directory, array $file, string $fileName = null)
    {
        $fullDirectory = $this->storage->getFullUrl($directory);
        $this->storage->checkDirectory($fullDirectory);

        $core = $this->parse($file)->core();
        list($fileName, $imageType) = $this->extractFileName($directory, $fileName);
        $storagePath = $fullDirectory . '/' . $fileName;

        $this->isExist($storagePath);
        return [$storagePath, $core, $imageType];
    }

    /**
     * Upload File
     * @param  string      $directory /storage/images
     * @param  array       $file
     * @param  string|null $fileName
     * @return void
     */
    protected function upload(string $directory, array $file, string $fileName = null)
    {
        list($storagePath, $core, $imageType) = $this->process($directory, $file, $fileName);
        $this->storage->upload($storagePath, $core);
    }
}
