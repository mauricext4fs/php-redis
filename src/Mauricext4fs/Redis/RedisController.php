<?php
/**
 * See licensing info in ../../LICENSE
 */
namespace Mauricext4fs\Redis;

class RedisController {

    private  $objServer = null;

    public function __construct()
    {
    
    }

    public function getList($strKey, $numLimit = 0)
    {
        $arrReturn = array();
        $objServer = $this->getInstance();

        $strMsg = sprintf("LRANGE %s %d %d", $strKey, ($numLimit) ? $numLimit * -1 : -10000, ($numLimit) ? $numLimit : 10000);
        $strResponse = $this->sendFormattedCommand($strMsg);
        $numResult = intval(str_replace("*", "", $strResponse));
        for ($i=0; $i<$numResult; $i++) {
            // Geting the length of result from response
            $strResponse = fgets($objServer);
            $numLength = intval(str_replace("$", "", $strResponse));
            // Getting the actual value
            $strResponse = fread($objServer, $numLength);
            // Remove enclosing quotes from value
            //$strResponse = substr($strResponse, 1, -1);
            // Getting the carriage return and disregard it
            $strAbfall = fgets($objServer);
            // Adding the value to the array
            $arrReturn[] = $strResponse;
        }
        return $arrReturn;
    }

    public function addValueToEndOfList($strKey, $strValue)
    {
        $objServer = $this->getInstance();
        
        $strMsg = sprintf("RPUSH %s %s", $strKey, $strValue);
        $this->sendFormattedCommand($strMsg);
        
        return;
    }

    public function sendFormattedCommand($strCmd)
    {
        $objServer = $this->getInstance();

        $strCmd = sprintf("%s\r\n", $strCmd);
        fputs($objServer, $strCmd);
        $strResponse = fgets($objServer);

        return $strResponse;
    }


    protected function getInstance()
    {
        if(empty($this->objServer)) {
            // Connecting
            if (!file_exists("/tmp/redis.sock")) {
                throw new \Exception("/tmp/redis.sock not existing");
            }
            $this->objServer = stream_socket_client("unix:///tmp/redis.sock", $errno, $errstr, 30);
            // Test connection
            $this->sendFormattedCommand("ping");
            if ($errno) {
                throw new Exception(sprintf("%s\n", $errno));
            }
            if ($errstr) {
               throw new Exception( printf("%s\n", $errstr));
            }
        }

        return $this->objServer;
    }
}
