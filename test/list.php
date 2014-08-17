<?php

namespace Mauricext4fs\Redis;

// Static require, only testing the current module
require_once(__DIR__ . "/../src/Mauricext4fs/Redis/RedisController.php");

$objRedis = new RedisController();

printf("Starting new List Test" . PHP_EOL);
// Adding duplicate ascii element to a set
$strListName = uniqid();
$arrList = array(
    "test1",
    "test2",
    "test3",
    "test3",
    "test4"
);
foreach ($arrList as $strValue) {
    $objRedis->addValueToList($strListName, $strValue);
}

// getting the List back
$arrListFromRedis = $objRedis->getList($strListName);

// Check that all value from the orginal value exist in Redis
printf("Check value from original List from Redis.......         ");
$boolFail = false;
foreach ($arrList as $strKey => $strValue) {
    if (!in_array($strValue, $arrListFromRedis)) {
        printf("Fail! %s", PHP_EOL);
        $boolFail = true;
        break;
    }

}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}

// Check that all value from Redis List exist in the orginal value
printf("Check value from Redis List in original List.......      ");
$boolFail = false;
foreach ($arrListFromRedis as $strKey => $strValue) {
    if (!in_array($strValue, $arrList)) {
        printf("Fail!%s", PHP_EOL);
        $boolFail = true;
        break;
    }

}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}

// Count
printf("Check for duplicate.......                              ");
$boolFail = false;
if (count($arrListFromRedis) !== count($arrList)) {
    printf("Fail!%s", PHP_EOL);
     $boolFail = true;
}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}
printf("End new List Test" . PHP_EOL);


printf("Starting delete List Test" . PHP_EOL);

printf("Delete existing element from List.......                ");
$boolFail = false;
$objRedis->deleteFromList($strListName, "test3");
$arrListFromRedis = $objRedis->getList($strListName);
if (count($arrListFromRedis) !== count($arrList)-2) {
    printf("Fail!%s", PHP_EOL);
     $boolFail = true;
}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}


printf("Delete whole List.......                                ");
$boolFail = false;
$objRedis->deleteList($strListName);
$arrListFromRedis = $objRedis->getList($strListName);
if (count($arrListFromRedis) !== 0) {
    printf("Fail!%s", PHP_EOL);
     $boolFail = true;
}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}
printf("End delete List Test" . PHP_EOL);


printf("Starting limit Test" . PHP_EOL);

printf("Testing the limit from List.......                      ");
$strListName = uniqid();
$arrList = array(
    "test1",
    "test2",
    "test3",
    "test3",
    "test4"
);
foreach ($arrList as $strValue) {
    $objRedis->addValueToList($strListName, $strValue);
}
$boolFail = false;
$objRedis->setLimitToList($strListName, 2);
$arrListFromRedis = $objRedis->getList($strListName);
if (count($arrListFromRedis) != 2) {
    printf("Fail!%s", PHP_EOL);
     $boolFail = true;
}
if ($boolFail === false) {
    printf("OK!%s", PHP_EOL);
}
printf("End limit Test" . PHP_EOL);


//print_r($arrSet);
