<?php
require_once("../db/db.php");

/* The dataset of wildfires has a lot of null and ambiguous values. This script will help clear out
 * missing information and will also split the datset into training and testing sets for ML best practice.
 * This operation should also, hopefully, reduce its gargantuan size! 
 * The values we're going to clear will be rows where: 
 * datetime information is missing, 
 * the listed cause is either unknown (13),
 * "miscellaneous" (9), 
 * or "children" (8), since these categories are either missing info or are too vague (children don't
 * spontaneously combust, they cause a fire through the other known ignition sources),
 * land ownership/use is null or is missing/unspecified (14). 
 * This operation clears a lot of data, around a million rows, and takes a fair bit of time. */

$db = new DB();

$statCauseCodeMisc = 9;
$statCauseCodeUnknown = 13;
$statCauseCodeChildren = 8;

$noNull = "DELETE FROM `Fires` WHERE 
    `DISCOVERY_DATE` ISNULL OR 
    `CONT_DATE` ISNULL OR
    `DISCOVERY_DOY` ISNULL OR
    `STAT_CAUSE_CODE` ISNULL OR
    `STAT_CAUSE_CODE` = :statCauseCodeMisc OR
    `STAT_CAUSE_CODE` = :statCauseCodeUnknown OR
    `STAT_CAUSE_CODE` = :statCauseCodeChildren;";

$noNullStmt = $db->prepare($noNull);
$noNullStmt->bindValue(":statCauseCodeMisc", $statCauseCodeMisc, SQLITE3_INTEGER);
$noNullStmt->bindValue(":statCauseCodeUnknown", $statCauseCodeUnknown, SQLITE3_INTEGER);
$noNullStmt->bindValue(":statCauseCodeChildren", $statCauseCodeChildren, SQLITE3_INTEGER);

if ($noNullStmt->execute()) {
    echo "Data scrubbing query executed\n";
    echo $db->changes() . " rows affected\n";
}
else {
    echo $db->lastErrorMsg() . "\n";
}

/* Now to make a "clean" table for training and testing; some transformation takes place here as well 
 * with certain data members getting dropped and dates getting turned into a more intelligible form (the original dataset has them in Julian for some unholy reason). */

$cleanTable = "CREATE TABLE IF NOT EXISTS `Clean_Fires` AS 
    SELECT `OBJECTID`, 
    `FIRE_NAME`, 
    date(`DISCOVERY_DATE`) AS `DISCOVERY_DATE`, 
    `DISCOVERY_DOY`, 
    `STAT_CAUSE_CODE`,
    `STAT_CAUSE_DESCR`, 
    date(`CONT_DATE`) AS `CONT_DATE`, 
    `CONT_DOY`, 
    `FIRE_SIZE`, 
    `LATITUDE`, 
    `LONGITUDE`, 
    `STATE`, 
    `COUNTY`,
    `FIPS_NAME` 
    FROM `Fires`";

if ($db->exec($cleanTable)) {
    echo "Clean table created\n";
    echo $db->changes() . " rows affected\n";
}
else {
    echo $db->lastErrorMsg() . "\n";
}

?>

