<?php
/**
 * See licensing info in ../../LICENSE
 * 
 * Default behavoir is that the RedisController will try to connect 
 * to redis unix socket situated at: /tmp/redis.sock
 * Unless you pass the server host and port as argument to the 
 * constructor.
 * 
 */
namespace Mauricext4fs\Redis;

class RedisController {

    protected $objServer = null;
    protected $strServerHost = null;
    protected $strServerPort = null;
    private $arrListLimit = [];

    /**
     * If you do not pass the host and port in arg 
     * it is infer that you wish to connect through 
     * unix socket with /tmp/redis.sock
     */
    public function __construct($strServerHost = "", $strServerPort = "")
    {
        $this->strServerHost = $strServerHost;
        $this->strServerPort = $strServerPort;
    }

    /**
     * Ths method is used to convert the value pass to set, add, etc... 
     * to a format that will pass through. For example, double quote 
     * are use to contain string... if the value contain a JSON string 
     * it will fail to be add to list or set.
     *
     * @param String $strValue
     * @return Formated Value
     */
    protected function formatValueForCommandUse($strValue)
    {
        $strCleanValue = $strValue;

        /*
         * If the value has a quote or single quote then we need
         * to escape it and quote it!
         * We also need to double escape esacped 
         * quote and / or double quote!!!
         */
        if (strpos($strValue, '"') !== false) {
            // This is the case when a quote is found
            /*$strCleanValue = preg_replace("/" . preg_quote('"', "/") . "/", preg_quote('\\"'), $strCleanValue);
            $strCleanValue = preg_replace("/" . preg_quote('\\\\"', "/") . "/", preg_quote('\\\\\\"'), $strCleanValue);
            $strCleanValue = sprintf('"%s"', $strCleanValue);*/
            $strCleanValue = sprintf('"%s"', addslashes($strCleanValue));
        } else if (strpos($strValue, "'") !== false) {
            // Without quote but with single quote
            /*$strCleanValue = preg_replace("/" . preg_quote("'", "/") . "/", preg_quote("\\'"), $strCleanValue);
            $strCleanValue = preg_replace("/" . preg_quote("\\\\'", "/") . "/", preg_quote("\\\\\\'"), $strCleanValue);
            $strCleanValue = sprintf('"%s"', $strCleanValue);*/
            $strCleanValue = sprintf('"%s"', addslashes($strCleanValue));
        } else if (strpos($strValue, " ") !== false) {
            /*
             * Finally anything else with a space without 
             * quotes
             */
            $strCleanValue = sprintf('"%s"', $strCleanValue);
        }

        return $strCleanValue;
    }

    /**
     * Basic functionality get, set, delete
     */
    public function get($strKey)
    {
        $arrReturn = array();
        $objServer = $this->getInstance();

        $strKey = $this->formatValueForCommandUse($strKey);
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
        
        $strValue = $this->formatValueForCommandUse($strValue);
        $strKey = $this->formatValueForCommandUse($strKey);
        $strMsg = sprintf("SET %s %s", $strKey, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function delete($strKey)
    {
        $objServer = $this->getInstance();
        
        $strKey = $this->formatValueForCommandUse($strKey);
        $strMsg = sprintf("DEL %s", $strKey);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    /**
     * Useful to get the number of entries in a list
     *
     * @author Adam Mackiewicz
     * @param String strKey
     * @return Int numResult
     */
    public function getListLength($strKey) {
        $strKey = $this->formatValueForCommandUse($strKey);
        $strMsg = sprintf("LLEN %s", $strKey);
        $strResponse = $this->sendFormattedCommand($strMsg);
        $numResult = intval(substr($strResponse, 1));

        return $numResult;
    }

    /**
     * Useful to get the number of entries in a Set
     *
     * @author Adam Mackiewicz
     * @param String strKey
     * @return Int numResult
     */
    public function getSetLength($strKey) {
        $strKey = $this->formatValueForCommandUse($strKey);
        $strMsg = sprintf("SCARD %s", $strKey);
        $strResponse = $this->sendFormattedCommand($strMsg);
        $numResult = intval(substr($strResponse, 1));

        return $numResult;
    }

    /**
     * Useful to get the number of entries in Sorted Set
     *
     * @author Adam Mackiewicz
     * @param String strKey
     * @return Int numResult
     */
    public function getSortedSetLength($strKey) {
        $strKey = $this->formatValueForCommandUse($strKey);
        $strMsg = sprintf("ZCARD %s", $strKey);
        $strResponse = $this->sendFormattedCommand($strMsg);
        $numResult = intval(substr($strResponse, 1));

        return $numResult;
    }

    /**
     * Useful to get the number of entries for a givent key.
     * Presently set, sorted_set and list (default) are supported.
     *
     * @author Adam Mackiewicz
     * @param String strKey
     * @param String strResourceType can be one of (set, sorted_set, list)
     * @return Int numResult
     */
    public function getLength($strKey, $strResourceType = 'list') {
        $numResult = 0;

        if ($strResourceType === 'set') {
            $numResult = $this->getSetLength($strKey);
        } elseif ($strResourceType === 'sorted_set') {
            $numResult = $this->getSortedSetLength($strKey);
        } else {
            $numResult = $this->getListLength($strKey);
        }

        return $numResult;
    }

    /**
     * Useful to get the number of entries for a givent key.
     * Presently set, sorted_set and list (default) are supported.
     *
     * @author Adam Mackiewicz
     * @param String strKey
     * @param String strResourceType can be one of (set, sorted_set, list)
     * @return Int numResult
     */
     public function getKeys($strNamePattern = '*') {
        $arrReturn = array();
        $objServer = $this->getInstance();

        $strNamePattern = $this->formatValueForCommandUse($strNamePattern);
        $strMsg = sprintf("KEYS %s", $strNamePattern);
        $strResponse = $this->sendFormattedCommand($strMsg);
        $numResult = intval(str_replace("*", "", $strResponse));
        for ($i=0; $i<$numResult; $i++) {
            $strResponse = fgets($objServer);
            $numLength = intval(str_replace("$", "", $strResponse));
            $strResponse = fread($objServer, $numLength);
            $strAbfall = fgets($objServer);
            $arrReturn[] = $strResponse;
        }

        return $arrReturn;
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

        $strListName = $this->formatValueForCommandUse($strListName);
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
     * it gives you the end of the list with the limit pass in arg
     *
     * @param $numLimit HARDCODED TO 10'000
     */
    public function getEndOfList($strListName, $numLimit = 0)
    {
        $arrReturn = array();
        $objServer = $this->getInstance();

        $strListName = $this->formatValueForCommandUse($strListName);
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
        
        $strListName = $this->formatValueForCommandUse($strListName);
        $strMsg = sprintf("LTRIM %s 0 %d", $strListName, $numLimit - 1);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function addValueToList($strListName, $strValue)
    {
        $objServer = $this->getInstance();
        $strListName = $this->formatValueForCommandUse($strListName);
        $strValue = $this->formatValueForCommandUse($strValue);
        $strMsg = sprintf("LPUSH %s %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function addValueToEndOfList($strListName, $strValue)
    {
        $objServer = $this->getInstance();
        $strValue = $this->formatValueForCommandUse($strValue);
        $strMsg = sprintf("RPUSH %s %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function deleteFromList($strListName, $strValue)
    {
        $objServer = $this->getInstance();

        $strListName = $this->formatValueForCommandUse($strListName);
        $strValue = $this->formatValueForCommandUse($strValue);
        $strMsg = sprintf("LREM %s 0 %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function deleteList($strListName)
    {
        $objServer = $this->getInstance();

        $strListName = $this->formatValueForCommandUse($strListName);
        $strMsg = sprintf("DEL %s", $strListName);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function getSet($strListName)
    {
        $arrReturn = array();
        $objServer = $this->getInstance();

        $strListName = $this->formatValueForCommandUse($strListName);
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

        $strListName = $this->formatValueForCommandUse($strListName);
        $strValue = $this->formatValueForCommandUse($strValue);
        $strMsg = sprintf("SADD %s %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function deleteFromSet($strListName, $strValue)
    {
        $objServer = $this->getInstance();
        $strListName = $this->formatValueForCommandUse($strListName);
        $strValue = $this->formatValueForCommandUse($strValue);
        $strMsg = sprintf("SREM %s %s", $strListName, $strValue);
        $this->sendFormattedCommand($strMsg);

        return;
    }

    public function deleteSet($strListName)
    {
        $objServer = $this->getInstance();
        $strListName = $this->formatValueForCommandUse($strListName);
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

    protected function getInstance()
    {
        if (empty($this->objServer)) {
            if (empty($this->strServerHost)) {
                // Connecting
                if (!file_exists("/tmp/redis.sock")) {
                    throw new \Exception("/tmp/redis.sock not existing or No Host / port provided");
                }
                $this->objServer = stream_socket_client("unix:///tmp/redis.sock", $errno, $errstr, 30);
            } else {
                $strStreamConnect = sprintf("tcp://%s:%d", $this->strServerHost, $this->strServerPort);
                $this->objServer = stream_socket_client($strStreamConnect, $errno, $errstr, 30);
            }
            // Test connection
            $this->sendFormattedCommand("ping");
            if ($errno) {
                throw new Exception(sprintf("%s\n", $errno));
            }
            if ($errstr) {
               throw new Exception(sprintf("%s\n", $errstr));
            }
        }

        return $this->objServer;
    }
}
