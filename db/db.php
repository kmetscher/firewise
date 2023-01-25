<?php
require_once("../model/fire.php");

class DB extends SQLite3 {
    
    public function __construct() {
        $this->open("../db/wildfiredb.sqlite");
    }
    
    public function getAllFires(): array {
        $fires = [];
        $stmt = $this->prepare(
            "SELECT * FROM `Clean_Fires`;"
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
            "SELECT * FROM `Clean_Fires` WHERE `OBJECTID` = :objectID;"
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

   public function getBalancedSet(): array {
        $fires = [];
        // Retrieve the count of fires with the least common ignition source for balancing
        $balanceRes = $this->querySingle(
            "SELECT `STAT_CAUSE_CODE`, COUNT(*) AS `count` 
            FROM `Clean_Fires` GROUP BY `STAT_CAUSE_CODE` 
            ORDER BY `count` ASC LIMIT 1;"
        , true);
        $testCount = $balanceRes["count"];
        for ($i = 1; $i < 13; $i++) {
            $stmt = $this->prepare(
                "SELECT * FROM `Clean_Fires` 
                WHERE `STAT_CAUSE_CODE` = :causeId
                LIMIT :testCount"
            );
            $stmt->bindValue(":causeId", $i, SQLITE3_INTEGER);
            $stmt->bindValue(":testCount", $testCount, SQLITE3_INTEGER);
            $res = $stmt->execute();
            while ($arr = $res->fetchArray()) {
                $fires[] = new Fire($arr);
            }
        }
        return $fires;
    }
}
?>
