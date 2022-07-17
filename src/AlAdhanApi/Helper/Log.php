<?php
namespace AlAdhanApi\Helper;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class Log
 * @package Helper\Log
 */

class Log
{
    public $id;

    public function __construct()
    {
        $this->id = uniqid();
    }

    /**
     * Extracts client IP
     * @param  array $server $_SERVER
     * @return String
     */
    public function getIp($server)
    {
        // If we have a forwarding address, return that.
        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            return $server['HTTP_X_FORWARDED_FOR'];
        }
        // Otherwise, remote address
        if (isset($server['REMOTE_ADDR'])) {
            return $server['REMOTE_ADDR'];
        }
        // Otherwise, Unknown
        return 'Unknown';
    }

    /**
     * Returns a formatted log array§
     * @param  Array $server  $_SERVER
     * @param  Array $request $_REQUEST
     * @return Array
     */
    public function format($server, $request)
    {
        $l = [];
        // Request Params
        $l['request'] = $request;
        // Compute IP
        $this->getIp($server);
        $l['server'] = [
            'ip' => $this->getIp($server),
            'url' => isset($server['SCRIPT_URL']) ? $server['SCRIPT_URL'] : (isset($server['REDIRECT_URL']) ? $server['REDIRECT_URL'] : 'Unknown' )  ,
            'method' => isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : 'Unknown',
        ];

        $l['server']['useragent'] = isset($server['HTTP_USER_AGENT']) ? $server['HTTP_USER_AGENT'] : 'Unknown';

        $l['server']['origin'] = isset($server['HTTP_ORIGIN']) ? $server['HTTP_ORIGIN'] : 'Unknown';

        $l['server']['referer'] = isset($server['HTTP_REFERER']) ? $server['HTTP_REFERER'] : 'Unknown';

        $l['server']['querystring'] = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : 'Unknown';

        return $l;
    }

    /**
     * Writes a Long Entry for the AskGeo API
     * @param String $message
     */
    public function writeAskGeoQueryLog($message)
    {
        $logFile = 'AskGeo_' . date('Y-m-d');
        // Create the logger
        $logger = new Logger('AskGeo');
        // Now add some handlers
        $logger->pushHandler( new \Monolog\Handler\StreamHandler('php://stdout', Logger::INFO));
        $l = $this->format($_SERVER, $_REQUEST);

        return $logger->info($this->id . ' :: ' . $message . ' :: ' . json_encode([$l['server']['referer'], $l['server']['useragent'], $l['server']['querystring'], $l]));
    }

    /**
     * Writes a Long Entry for the Google Maps API
     * @param String $message
     */
    public function writeGoogleQueryLog($message)
    {
        // Create the logger
        $logger = new Logger('Google');
        // Now add some handlers
        $logger->pushHandler( new \Monolog\Handler\StreamHandler('php://stdout', Logger::INFO));
        $l = $this->format($_SERVER, $_REQUEST);

        return $logger->info($this->id . ' :: ' . $message . ' :: ' . json_encode([$l['server']['referer'], $l['server']['useragent'], $l['server']['querystring'], $l]));
    }

    /**
     * [write description]
     * @return [type] [description]
     */
    public function write()
    {
        $logger = new Logger('ApiService');
        // Now add some handlers
        $logger->pushHandler( new \Monolog\Handler\StreamHandler('php://stdout', Logger::INFO));

        return $logger->info($this->id . ' :: ' . date('Y-m-d H:i:s') . ' :: Incoming request :: ', $this->format($_SERVER, $_REQUEST));
    }

    /**
     * [write description]
     * @return [type] [description]
     */
    public function error($message = '')
    {
        $logger = new Logger('ApiError');
        // Now add some handlers
        $logger->pushHandler( new \Monolog\Handler\StreamHandler('php://stderr', Logger::INFO));

        return $logger->error($this->id . ' :: ' . date('Y-m-d H:i:s') . ' :: ' . $message);
    }
}
