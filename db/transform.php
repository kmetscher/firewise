<?php
require_once("../db/db.php");

/* The dataset of wildfires has a lot of null and ambiguous values. This script will help clear out
 * missing information and will also split the datset into training and testing sets for ML best practice.
 * This operation should also, hopefully, reduce its gargantuan size! The values we're going to clear
 * will be rows where datetime information is missing, including the discovery and containment datetimes
 * as well as the relative day-of-year (DOY) of discovery, rows where the listed cause is either unknown
 * (13) or "miscellaneous" (9), or rows where the land ownership/use is null or is missing/unspecified 
 * (14). This operation clears a lot of data, around a million rows, and takes a fair bit of time. */

$db = new DB();

$ownerCode = 14;
$statCauseCodeMisc = 9;
$statCauseCodeUnknown = 13;

$noNull = "DELETE FROM `Fires` WHERE 
    `DISCOVERY_DATE` ISNULL OR 
    `DISCOVERY_TIME` ISNULL OR
    `CONT_DATE` ISNULL OR
    `CONT_TIME` ISNULL OR
    `DISCOVERY_DOY` ISNULL OR
    `OWNER_CODE` ISNULL OR
    `OWNER_CODE` = :ownerCode OR
    `STAT_CAUSE_CODE` ISNULL OR
    `STAT_CAUSE_CODE` = :statCauseCodeMisc OR
    `STAT_CAUSE_CODE` = :statCauseCodeUnknown;";

$noNullStmt = $db->prepare($noNull);
$noNullStmt->bindValue(":ownerCode", $ownerCode, SQLITE3_INTEGER);
$noNullStmt->bindValue(":statCauseCodeMisc", $statCauseCodeMisc, SQLITE3_INTEGER);
$noNullStmt->bindValue(":statCauseCodeUnknown", $statCauseCodeUnknown, SQLITE3_INTEGER);

if ($noNullStmt->execute()) {
    echo "Data scrubbing query executed\n";
    echo $db->changes() . " rows affected\n";
}
else {
    echo $db->lastErrorMsg() . "\n";
}

/* Now to make two separate tables for training and testing; we'll shoot for keeping things evenly
 * distributed by selecting odds/evens for either one. Some transformation takes place here as well 
 * with certain data members getting dropped and dates getting turned into a more intelligible form
 * (the original dataset has them in Julian for some unholy reason). */

$trainingTable = "CREATE TABLE IF NOT EXISTS `Training_Fires` AS 
    SELECT `OBJECTID`, 
    `SOURCE_REPORTING_UNIT`, 
    `SOURCE_REPORTING_UNIT_NAME`, 
    `FIRE_NAME`, 
    date(`DISCOVERY_DATE`) AS `DISCOVERY_DATE`, 
    `DISCOVERY_DOY`, 
    `DISCOVERY_TIME`, 
    `STAT_CAUSE_CODE`,
    `STAT_CAUSE_DESCR`, 
    date(`CONT_DATE`) AS `CONT_DATE`, 
    `CONT_DOY`, 
    `CONT_TIME`, 
    `FIRE_SIZE`, 
    `LATITUDE`, 
    `LONGITUDE`, 
    `OWNER_CODE`, 
    `OWNER_DESCR`, 
    `STATE`, 
    `COUNTY`,
    `FIPS_NAME`, 
    `SHAPE` 
    FROM `Fires` WHERE (`OBJECTID` % 2 = 0);";

if ($db->exec($trainingTable)) {
    echo "Training table created\n";
    echo $db->changes() . " rows affected\n";
}
else {
    echo $db->lastErrorMsg() . "\n";
}

$testingTable = "CREATE TABLE IF NOT EXISTS `Testing_Fires` AS 
    SELECT `OBJECTID`, 
    `SOURCE_REPORTING_UNIT`, 
    `SOURCE_REPORTING_UNIT_NAME`, 
    `FIRE_NAME`, 
    date(`DISCOVERY_DATE`) AS `DISCOVERY_DATE`, 
    `DISCOVERY_DOY`, 
    `DISCOVERY_TIME`, 
    `STAT_CAUSE_CODE`,
    `STAT_CAUSE_DESCR`, 
    date(`CONT_DATE`) AS `CONT_DATE`, 
    `CONT_DOY`, 
    `CONT_TIME`, 
    `FIRE_SIZE`, 
    `LATITUDE`, 
    `LONGITUDE`, 
    `OWNER_CODE`, 
    `OWNER_DESCR`, 
    `STATE`, 
    `COUNTY`,
    `FIPS_NAME`, 
    `SHAPE` 
    FROM `Fires` WHERE (`OBJECTID` % 2 = 1);";

if ($db->exec($testingTable)) {
    echo "Testing table created\n";
    echo $db->changes() . " rows affected\n";
}
else {
    echo $db->lastErrorMsg() . "\n";
}

?>
