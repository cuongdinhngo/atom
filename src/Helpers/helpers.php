<?php

use Atom\Template\Template;

if (!function_exists('config')) {
    /**
     * Load configured files
     * @param  string $params
     * @return mixed
     */
    function config($params = null)
    {
        if (is_null($params)) {
            return;
        }

        $keys = explode('.', $params);
        $fileName = array_shift($keys);
        $searchFile = searchFile(CONFIG_PATH . $fileName . '.*');

        if (strpos($searchFile, '.ini')) {
            $envs = parse_ini_file($searchFile, false);
            foreach ($envs as $env => $value) {
                putenv("{$env}={$value}");
            }
        } else {
            return obtainValue($searchFile, $keys);
        }
    }
}

if (!function_exists('route')) {
    /**
     * Load route file
     * @param  string $params
     * @return mixed
     */
    function route($params = null)
    {
        if (is_null($params)) {
            return;
        }

        $keys = explode('.', $params);
        $fileName = array_shift($keys);
        $filePath = ROUTE_PATH . $fileName . '.php';

        return obtainValue($filePath, $keys);
    }
}

if (!function_exists('searchFile')) {

    function searchFile(string $path)
    {
        $searchFile = glob($path);
        if (empty($searchFile)) {
            throw new \Exception('File Is Not Existed');
        }
        return $searchFile[0];
    }
}

if (!function_exists('obtainValue')) {

    function obtainValue(string $file, array $keys)
    {
        if (!file_exists($file)) {
            throw new \Exception('File Is Not Existed');
        }

        $data = require($file);
        foreach ($keys as $key) {
            $data = $data[$key];
        }
        return $data;
    }
}

if (!function_exists('getHeaders'))
{
    /**
     * Get HTTP Headers
     * @return array
     */
    function getHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

if (!function_exists('isApi'))
{
    function isApi()
    {
        $headers = getHeaders();
        return (bool) strpos($_SERVER['REQUEST_URI'], 'api') || (isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/json');
    }
}

if (!function_exists('env'))
{
    /**
     * Get variable's value
     * @param  string|null $varName [description]
     * @return mixed
     */
    function env(string $varName = null)
    {
        $value = getenv($varName);
        if (false === $value){
            throw new \Exception('INVALID ENV VALUE');
        }
        return $value;
    }
}

if (!function_exists('template'))
{
    /**
     * Render view
     * @param  string $tempKey   Template Key
     * @param  string $directory Directory (ex: 'admin.users.list')
     * @param  array  $data      Data
     * @return void
     */
    function template(string $tempKey, string $directory = "", array $data = [])
    {
        $viewTemplates = config('templates.'.$tempKey.'.template');
        $filledTemp = array_search(null, $viewTemplates);

        if ($filledTemp !== false && empty($directory) === false) {
            $viewTemplates[$filledTemp] = $directory;
        }

        $html = (new Template($viewTemplates, $data))->render();
        echo $html;
        exit();
    }
}

if (!function_exists('view'))
{
    /**
     * Include view file
     * @param  string $directory Directory
     * @param  array  $data      Data
     * @return void
     */
    function view(string $directory, array $data = [])
    {
        $file = VIEW_PATH . str_replace('.', '/', $directory) . '.php';
        if (!file_exists($file)) {
            throw new \Exception('Invalid Directory');
        }
        extract($data);
        include $file;
    }
}

if (!function_exists('stripSpace'))
{
    /**
     * Strip whitespace
     * @param  string|null $string
     * @return string
     */
    function stripSpace(string $string = null)
    {
        return str_replace(' ', '', $string);
    }
}

if (!function_exists('now'))
{
    /**
     * Now
     * @return string
     */
    function now()
    {
        return date("Y-m-d H:i:s");
    }
}

if (!function_exists('today'))
{
    /**
     * Today
     * @return string
     */
    function today()
    {
        return date("Y-m-d");
    }
}

if (!function_exists('json'))
{
    /**
     * Convert to Json
     * @param $data
     * @param $option
     * @return json
     */
    function json($data, $option = JSON_UNESCAPED_UNICODE)
    {
        $json = json_encode($data, $option);
        if (json_last_error()) {
            throw new \Exception("Invalid Json");
        }
        return $json;
    }
}

if (!function_exists('storage_path'))
{
    /**
     * Get Storage path
     * @param  string $path
     * @return string
     */
    function storage_path($path = '')
    {
        if (empty($path)) {
            return STORAGE_PATH;
        }
        if (false === file_exists(STORAGE_PATH . $path))
        {
            throw new \Exception("Storage Directory Not Found");
        }

        return STORAGE_PATH . $path;
    }
}

if (!function_exists('imageLocation'))
{
    /**
     * Get Image Location
     * @param  array $file
     * @return array
     */
    function imageLocation(array $file)
    {
        $exif = exif_read_data($file["tmp_name"], 0, true);
        $location = [];
        if($exif && isset($exif['GPS'])){
            $GPSLatitudeRef = $exif['GPS']['GPSLatitudeRef'];
            $GPSLatitude    = $exif['GPS']['GPSLatitude'];
            $GPSLongitudeRef= $exif['GPS']['GPSLongitudeRef'];
            $GPSLongitude   = $exif['GPS']['GPSLongitude'];

            $lat_degrees = count($GPSLatitude) > 0 ? gps2Num($GPSLatitude[0]) : 0;
            $lat_minutes = count($GPSLatitude) > 1 ? gps2Num($GPSLatitude[1]) : 0;
            $lat_seconds = count($GPSLatitude) > 2 ? gps2Num($GPSLatitude[2]) : 0;

            $lon_degrees = count($GPSLongitude) > 0 ? gps2Num($GPSLongitude[0]) : 0;
            $lon_minutes = count($GPSLongitude) > 1 ? gps2Num($GPSLongitude[1]) : 0;
            $lon_seconds = count($GPSLongitude) > 2 ? gps2Num($GPSLongitude[2]) : 0;

            $lat_direction = ($GPSLatitudeRef == 'W' or $GPSLatitudeRef == 'S') ? -1 : 1;
            $lon_direction = ($GPSLongitudeRef == 'W' or $GPSLongitudeRef == 'S') ? -1 : 1;

            $latitude = $lat_direction * ($lat_degrees + ($lat_minutes / 60) + ($lat_seconds / (60*60)));
            $longitude = $lon_direction * ($lon_degrees + ($lon_minutes / 60) + ($lon_seconds / (60*60)));

            $location = ['latitude' => $latitude, 'longitude' => $longitude];
        }

        return $location;
    }
}

if (!function_exists('gps2Num'))
{
    /**
     * Convert GPS coord part in float val
     * @param  [type] $coordPart
     * @return [type]
     */
    function gps2Num($coordPart){
        $parts = explode('/', $coordPart);
        if(count($parts) <= 0) return 0;
        if(count($parts) == 1) return $parts[0];
        return floatval($parts[0]) / floatval($parts[1]);
    }
}

if (!function_exists('resources_path'))
{
    /**
     * Get Resource path
     * @param  string $path
     * @return string
     */
    function resources_path($path = '')
    {
        if (empty($path)) {
            return RESOURCES_PATH;
        }
        if (false === file_exists(RESOURCES_PATH . $path))
        {
            throw new \Exception("Resource Directory Not Found");
        }

        return RESOURCES_PATH . $path;
    }
}

if (!function_exists('assets'))
{
    /**
     * Get assets path in Public folder
     * @param  string $path
     * @return string
     */
    function assets($path = '')
    {
        $assets = url() . '/assets';
        if (empty($path)) {
            return $assets;
        }
        if (false === file_exists(ASSETS_PATH . $path))
        {
            throw new \Exception("Asset Directory Not Found");
        }

        return $assets . $path;
    }
}

if (!function_exists('public_path'))
{
    /**
     * Get Public path
     * @param  string $path
     * @return string
     */
    function public_path($path = '')
    {
        if (empty($path)) {
            return DOC_ROOT;
        }
        if (false === file_exists(DOC_ROOT . $path))
        {
            throw new \Exception("Public Directory Not Found");
        }

        return DOC_ROOT. $path;
    }
}

if (!function_exists('url'))
{
    /**
     * Get current url
     * @param  string $path
     * @return string
     */
    function url($path = '')
    {
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        if (empty($path)) {
            return $url;
        }
        return $url . '/' . $path;
    }
}

if (!function_exists('back'))
{
    /**
     * Go back
     * @param  string $path
     * @return string
     */
    function back()
    {
        header("Location: {$_SERVER['HTTP_REFERER']}");
    }
}
