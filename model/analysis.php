<?php
require_once("../db/db.php");

$db = new DB();

$allCausesArray = [];
$allCausesCountRs = $db->query(
    "SELECT count(*) AS `AMOUNT`, `STAT_CAUSE_DESCR`
    FROM `Clean_Fires` GROUP BY `STAT_CAUSE_DESCR`;"
);
while ($row = $allCausesCountRs->fetchArray()) {
    $allCausesArray[$row["STAT_CAUSE_DESCR"]] = $row["AMOUNT"];
}

$countByYearArray = [];
$countByDoyArray = [];
$countByYearRs = $db->query(
    "SELECT `DISCOVERY_DATE`, `STAT_CAUSE_DESCR`, `FIRE_SIZE`, `DISCOVERY_DOY`
    FROM `Clean_Fires`;"
);
while ($row = $countByYearRs->fetchArray()) {
    $year = substr($row["DISCOVERY_DATE"], 0, 4);
    $countByYearArray[$year]["total"]++;
    $countByYearArray[$year][$row["STAT_CAUSE_DESCR"]]++;
    $countByYearArray[$year]["acresBurned"] += $row["FIRE_SIZE"];
    $countByDoyArray[$row["DISCOVERY_DOY"]]++;
}

$countByStateArray = [];
$countByStateRs = $db->query(
    "SELECT count(*) AS `AMOUNT`, sum(`FIRE_SIZE`) AS `ACRES_BURNED`, `STATE` FROM `Clean_Fires`
    GROUP BY `STATE`;"
);
while ($row = $countByStateRs->fetchArray()) {
    $countByStateArray[$row["STATE"]]["amount"] = $row["AMOUNT"];
    $countByStateArray[$row["STATE"]]["acresBurned"] = $row["ACRES_BURNED"];
    
}

$analysis = [
    "allCauses" => $allCausesArray,
    "countByYear" => $countByYearArray,
    "countByState" => $countByStateArray,
    "countByDoy" => $countByDoyArray,
];

$serializedAnalysis = fopen("analysis.json", 'w');
fwrite($serializedAnalysis, json_encode($analysis));

?>
