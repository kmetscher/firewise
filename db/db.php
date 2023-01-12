<?php
require_once("../model/fire.php");

class DB extends SQLite3 {
    
    public function __construct() {
        $this->open("../db/wildfiredb.sqlite");
    }
    
    public function getAllFires(): array {
        $fires = [];
        $stmt = $this->prepare(
            "SELECT * FROM `Training_Fires` UNION ALL SELECT * FROM `Testing_Fires`;"
        );
        $res = $stmt->execute();
        while ($arr = $res->fetchArray()) {
            $fire = new Fire($arr);
            $fires[] = $fire;
        }
        return $fires;
    }

    public function getFire(int $objectID): ?Fire {
        $stmt = $this->prepare(
            "SELECT *, date(`DISCOVERY_DATE`) AS `DISCOVERY_DATE`, 
            date(`CONT_DATE`) AS `CONT_DATE` 
            FROM `Fires` WHERE `OBJECTID` = :objectID"
        );
        $stmt->bindValue(":objectID", $objectID, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $set = $res->fetchArray();
        if ($set) {
            foreach ($set as $attr) {
                if (!$attr) {
                    return null;
                }
            }
            return new Fire($set);
        }
        return null;
    }

    public function getTrainingSet(int $classTotal): array {
        $fires = [];
        for ($i = 1; $i < 13; $i++) {
            $stmt = $this->prepare(
                "SELECT * FROM `Training_Fires` 
                WHERE `STAT_CAUSE_CODE` = :causeId
                LIMIT :classTotal"
            );
            $stmt->bindValue(":causeId", $i, SQLITE3_INTEGER);
            $stmt->bindValue(":classTotal", $classTotal, SQLITE3_INTEGER);
            $res = $stmt->execute();
            while ($arr = $res->fetchArray()) {
                $fires[] = new Fire($arr);
            }
        }
        return $fires;
    }
    public function getTestingSet(int $classTotal): array {
        $fires = [];
        for ($i = 1; $i < 13; $i++) {
            $stmt = $this->prepare(
                "SELECT * FROM `Testing_Fires` 
                WHERE `STAT_CAUSE_CODE` = :causeId
                LIMIT :classTotal"
            );
            $stmt->bindValue(":causeId", $i, SQLITE3_INTEGER);
            $stmt->bindValue(":classTotal", $classTotal, SQLITE3_INTEGER);
            $res = $stmt->execute();
            while ($arr = $res->fetchArray()) {
                $fires[] = new Fire($arr);
            }
        }
        return $fires;
    }
}
?>
