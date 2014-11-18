<?php
/**
 * See licensing info in ../../LICENSE
 */
namespace Mauricext4fs\Redis;

class RedisController {

    protected $objServer = null;
    private $arrListLimit = [];

    public function __construct()
    {

    }

    /**
     * Basic functionality get, set, delete
     */
    public function get($strKey)
    {
        $arrReturn = array();
        $objServer = $this->getInstance();

        $strMsg = sprintf("GET %s", $strKey);
        $strResponse = $this->sendFormattedCommand($strMsg);
        $numLength = intval(str_replace("$", "", $strResponse));
        if ($numLength < 1) {
            // Key does not exist
            return;
        }
        // Getting the actual value + the line feed
        $strResponse = trim(fread($objServer, $numLength + 2));

        return $strResponse;
    }

    public function set($strKey, $strValue)
    {
        $objServer = $this->getInstance();
        
        $strMsg = sprintf("SET %s %s", $strKey, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function delete($strKey)
    {
        $objServer = $this->getInstance();
        
        $strMsg = sprintf("DEL %s", $strKey);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    /**
     * This will get you the value from a list from the top (redis left)
     * to $numLimit it will also call automatically the trimList method 
     * to enforce the limit on the list before getting you the result
     */
    public function getList($strListName, $numLimit = null)
    {
        $arrReturn = array();
        $objServer = $this->getInstance();

        // Trim list if there is a limit set
        if (!empty($this->arrListLimit) && isset($this->arrListLimit[$strListName])) {
            $this->trimList($strListName, $this->arrListLimit[$strListName]);
        }

        $strMsg = sprintf("LRANGE %s 0 %d", $strListName, ($numLimit === null) ? -1 : $numLimit);
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

    /**
     * Prior to version 2, this method was call getList,
     * it give you the end of the list with the limit pass in arg
     *
     * @param $numLimit HARDCODE TO 10'000
     */
    public function getEndOfList($strListName, $numLimit = 0)
    {
        $arrReturn = array();
        $objServer = $this->getInstance();

        $strMsg = sprintf("LRANGE %s %d %d", $strListName, ($numLimit) ? $numLimit * -1 : -10000, ($numLimit) ? $numLimit : 10000);
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

    /**
     * Beaware that for performance reason the limit is only 
     * enforce when getllist is called.
     *
     */
    public function setLimitToList($strListName, $numLimit)
    {
        $this->arrListLimit[$strListName] = $numLimit;
    }

    /**
     * This will always use LTRIM... because there is 
     * no such things as RTRIM. Beaware it will 
     * keep the value from the top to $numLimit
     * and not the other way arround
     */
    public function trimList($strListName, $numLimit)
    {
        $objServer = $this->getInstance();
        
        $strMsg = sprintf("LTRIM %s 0 %d", $strListName, $numLimit - 1);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function addValueToList($strListName, $strValue)
    {
        $objServer = $this->getInstance();
        
        $strMsg = sprintf("LPUSH %s %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function addValueToEndOfList($strListName, $strValue)
    {
        $objServer = $this->getInstance();
        
        $strMsg = sprintf("RPUSH %s %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function deleteFromList($strListName, $strValue)
    {
        $objServer = $this->getInstance();

        $strMsg = sprintf("LREM %s 0 %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function deleteList($strListName)
    {
        $objServer = $this->getInstance();

        $strMsg = sprintf("DEL %s", $strListName);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function getSet($strListName)
    {
        $arrReturn = array();
        $objServer = $this->getInstance();

        $strMsg = sprintf("SMEMBERS %s", $strListName);
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

    public function addValueToSet($strListName, $strValue)
    {
        $objServer = $this->getInstance();

        $strMsg = sprintf("SADD %s %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function deleteFromSet($strListName, $strValue)
    {
        $objServer = $this->getInstance();

        $strMsg = sprintf("SREM %s %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function deleteSet($strListName)
    {
        $objServer = $this->getInstance();

        $strMsg = sprintf("DEL %s", $strListName);
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


    /**
     * Feel free to overload this method if you wish to use network 
     * base redis server
     */
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
