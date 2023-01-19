<?php
require_once("../db/db.php");
require_once("../model/weatherreport.php");
require_once("../db/weathertransform.php");

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

$ownerCode = 14;
$statCauseCodeMisc = 9;
$statCauseCodeUnknown = 13;
$statCauseCodeChildren = 8;

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
    `STAT_CAUSE_CODE` = :statCauseCodeUnknown OR
    `STAT_CAUSE_CODE` = :statCauseCodeChildren;";

$noNullStmt = $db->prepare($noNull);
$noNullStmt->bindValue(":ownerCode", $ownerCode, SQLITE3_INTEGER);
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
 * with certain data members getting dropped and dates getting turned into a more intelligible form
 * (the original dataset has them in Julian for some unholy reason). */

$cleanTable = "CREATE TABLE IF NOT EXISTS `Clean_Fires` AS 
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
    FROM `Fires`";

if ($db->exec($cleanTable)) {
    echo "Clean table created\n";
    echo $db->changes() . " rows affected\n";
}
else {
    echo $db->lastErrorMsg() . "\n";
}

/*$stationTable = "CREATE TABLE IF NOT EXISTS `Weather_Stations`(
    `ID` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `STATION_ID` TEXT NOT NULL UNIQUE,
    `LATITUDE` REAL,
    `LONGITUDE` REAL,
    `ELEVATION` REAL,
    `STATE` TEXT,
    `NAME` TEXT);";

if ($db->exec($stationTable)) {
    echo "Weather station table created\n";
    echo $db->changes() . " rows affected\n";
}
else {
    echo $db->lastErrorMsg() . "\n";
}

$weatherStations = parseStations("stations.txt");
$stationStmt = $db->prepare("
    INSERT INTO `Weather_Stations` 
    (`STATION_ID`, `LATITUDE`, `LONGITUDE`,
    `ELEVATION`, `STATE`, `NAME`) VALUES 
    (:stationId, :latitude, :longitude, :elevation, :state, :name);"
);
foreach ($weatherStations as $station) {
    $stationStmt->bindValue(":stationId", $station["stationId"], SQLITE3_TEXT);
    $stationStmt->bindValue(":latitude", $station["latitude"], SQLITE3_FLOAT);
    $stationStmt->bindValue(":longitude", $station["longitude"], SQLITE3_FLOAT);
    $stationStmt->bindValue(":elevation", $station["elevation"], SQLITE3_FLOAT);
    $stationStmt->bindValue(":state", $station["state"], SQLITE3_TEXT);
    $stationStmt->bindValue(":name", $station["name"], SQLITE3_TEXT);
    $stationStmt->execute();
}

echo "Weather station data added\n";

$weatherTable = "CREATE TABLE IF NOT EXISTS Weather_Reports (
    `ID` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `STATION_ID` NOT NULL,
    `REPORT_DATE` DATE NOT NULL,
    `LOW_TEMP` INT NOT NULL,
    `HIGH_TEMP` INT NOT NULL,
    `WIND_SPEED` INT NOT NULL,
    `PRECIPITATION` INT NOT NULL)";

if ($db->exec($weatherTable)) {
    echo "Weather report table created\n";
    echo $db->changes() . " rows affected\n";
}
else {
    echo $db->lastErrorMsg() . "\n";
}

$reports = walkReports("ghcnd_hcn/wind");
$reportStmt = $db->prepare("
    INSERT INTO `Weather_Reports`
    (`STATION_ID`, `REPORT_DATE`, `LOW_TEMP`, `HIGH_TEMP`,
    `WIND_SPEED`, `PRECIPITATION`) VALUES
    (:stationId, :reportDate, :lowTemp, :highTemp, :windSpeed, :precipitation);"
);
foreach ($reports as $report) {
    foreach ($report["observations"] as $date => $observations) {
        $reportStmt->bindValue(":stationId", $report["stationId"], SQLITE3_TEXT);
        $reportStmt->bindValue(":reportDate", $date, SQLITE3_TEXT);

        $reportStmt->bindValue(":lowTemp", $observations["TMIN"], SQLITE3_INTEGER);
        $reportStmt->bindValue(":highTemp", $observations["TMAX"], SQLITE3_INTEGER);
        
        if (!array_key_exists("AWND", $observations)) {
            continue;
        }
        $reportStmt->bindValue(":windSpeed", $observations["AWND"], SQLITE3_INTEGER);
        
        $reportStmt->bindValue(":precipitation", $observations["PRCP"], SQLITE3_INTEGER);
        $reportStmt->execute();
    }
}
 */


?>

