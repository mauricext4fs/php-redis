<?php

namespace Mauricext4fs\Redis;

// Static require, only testing the current module
require_once(__DIR__ . "/../src/Mauricext4fs/Redis/RedisController.php");

$objRedis = new RedisController();

printf("Starting standard test" . PHP_EOL);

// Adding duplicate ascii element to a set
$strKey = uniqid();


// Check that all value from the orginal value exist in Redis
printf("Set and Get test.......                               ");
$boolFail = false;
$objRedis->set($strKey, "test3");
$strResponse = $objRedis->get($strKey);
if ($strResponse !== "test3") {
    printf("Fail! %s", PHP_EOL);
    $boolFail = true;
} else {
    printf("OK!%s", PHP_EOL);
}

// Check that all value from Redis set exist in the orginal value
printf("delete test .......      ");
$boolFail = false;
$objRedis->delete($strKey);
$strResponse = $objRedis->get($strKey);
if (!empty($strResponse)) {
    printf("Fail! %s", PHP_EOL);
    $boolFail = true;
    break;
} else {
    printf("OK!%s", PHP_EOL);
}

printf("End Standard Test" . PHP_EOL);

//print_r($arrSet);
