<?php

namespace Atom\File;

use Atom\File\Exception\CsvException;

class CSV
{
    protected static $checkEmpty;
    protected static $setNull;
    protected static $setHeader;

    /**
     * Set Header
     * @param array $header
     */
    public static function setHeader(array $header)
    {
        return static::$setHeader = $header;
    }

    /**
     * Check Empty
     * @return void
     */
    public static function checkEmpty()
    {
        return static::$checkEmpty = true; 
    }

    /**
     * Set Null
     */
    public static function setNull()
    {
        return static::$setNull = true;
    }

    /**
     * Read CSV
     * @param  array   $file
     * @param  array   $standardHeader
     * @param  boolean $skipEmpty
     * @return array
     */
    public static function read(array $file, array $standardHeader = [], bool $skipEmpty = true, bool $nullable = false)
    {
        return static::parseCsv($file, $standardHeader, $skipEmpty, $nullable, false);
    }

    /**
     * CSV to Array
     * @param  array        $filePath
     * @param  array        $standardHeader
     * @param  bool|boolean $skipEmpty
     * @param  bool|boolean $nullable
     * @return array
     */
    public static function toArray(array $file, array $standardHeader = [], bool $skipEmpty = true, bool $nullable = false)
    {
        return static::parseCsv($file, $standardHeader, $skipEmpty, $nullable, true);
    }

    /**
     * Parse CSV
     * @param  array        $file
     * @param  array        $standardHeader
     * @param  bool|boolean $skipEmpty
     * @param  bool|boolean $nullable
     * @param  bool|boolean $toArray
     * @return array
     */
    public static function parseCsv(array $file, array $standardHeader = [], bool $skipEmpty = true, bool $nullable = false, bool $toArray = false)
    {
        $data = [];
        $content = fopen($file["tmp_name"], "r");

        if (false === empty($standardHeader)) {
            $fileHeader = fgetcsv($content);
            $fileHeader = array_map('trim', $fileHeader);

            if (array_values($standardHeader) != $fileHeader) {
                throw new CsvException(CsvException::ERR_MSG_INVALID_FILE);
            }
        }
        
        while ($line = fgetcsv($content)) {
            if ((!$skipEmpty || static::$checkEmpty) && (empty($line) || false === (bool)array_filter($line))) {
                throw new CsvException(CsvException::ERR_MSG_INVALID_DATA);
            }

            $line = array_map(function ($item) {
                return (($nullable || static::$setNull) && empty($item)) ? null : trim($item);
            }, $line);

            if ($toArray) {
                $line = array_combine(array_keys($standardHeader), $line);
            }

            $data[] = $line;
        }

        return $data;
    }

    /**
     * Save CSV
     * @param  string $fileName
     * @param  array  $data
     * @param  bool  $header
     * @return void
     */
    public static function save(string $fileName, array $data, bool $header = false)
    {
        try {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $fileName . '.csv');
            $output = fopen('php://output', 'w');

            if (false === empty(static::$setHeader) || ($header && is_array($data[0]))) {
            	$csvHeader = $header ? array_keys($data[0]) : static::$setHeader;
            	fputcsv($output, $csvHeader);
            }

            foreach ($data as $items) {
                fputcsv($output, $items);
            }
            fclose($output);
            exit;
        } catch (\Exception $ex) {
            throw new CsvException(CsvException::ERR_MSG_SAVE_FAIL);
        }
    }
}
