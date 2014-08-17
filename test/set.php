<?php

namespace Mauricext4fs\Redis;

// Static require, only testing the current module
require_once(__DIR__ . "/../src/Mauricext4fs/Redis/RedisController.php");

$objRedis = new RedisController();

printf("Starting new Set Test" . PHP_EOL);

// Adding duplicate ascii element to a set
$strSetName = uniqid();
$arrSet = array(
    "test1",
    "test2",
    "test3",
    "test3",
    "test4"
);
foreach ($arrSet as $strValue) {
    $objRedis->addValueToSet($strSetName, $strValue);
}

// getting the set back
$arrSetFromRedis = $objRedis->getSet($strSetName);

// Check that all value from the orginal value exist in Redis
printf("Check value from original set from Redis.......         ");
$boolFail = false;
foreach ($arrSet as $strKey => $strValue) {
    if (!in_array($strValue, $arrSetFromRedis)) {
        printf("Fail! %s", PHP_EOL);
        $boolFail = true;
        break;
    }

}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}

// Check that all value from Redis set exist in the orginal value
printf("Check value from Redis set in original set.......      ");
$boolFail = false;
foreach ($arrSetFromRedis as $strKey => $strValue) {
    if (!in_array($strValue, $arrSet)) {
        printf("Fail!%s", PHP_EOL);
        $boolFail = true;
        break;
    }

}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}

// Count, should not be the same (no duplicate)
printf("Check for consistent non duplicate result.......      ");
$boolFail = false;
if (count($arrSetFromRedis) !== count($arrSet)-1) {
    printf("Fail!%s", PHP_EOL);
     $boolFail = true;
}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}


printf("End new Set Test" . PHP_EOL);

printf("Starting delete Set Test" . PHP_EOL);
printf("Delete existing element from set.......                ");
$boolFail = false;
$objRedis->deleteFromSet($strSetName, "test3");
$arrSetFromRedis = $objRedis->getSet($strSetName);
if (count($arrSetFromRedis) !== count($arrSet)-2) {
    printf("Fail!%s", PHP_EOL);
     $boolFail = true;
}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}
printf("Delete whole set.......                                ");
$boolFail = false;
$objRedis->deleteSet($strSetName);
$arrSetFromRedis = $objRedis->getSet($strSetName);
if (count($arrSetFromRedis) !== 0) {
    printf("Fail!%s", PHP_EOL);
     $boolFail = true;
}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}
printf("End delete Set Test" . PHP_EOL);





//print_r($arrSet);
