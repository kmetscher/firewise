<?php  
require_once("../db/db.php");
require_once("../model/fire.php");

function fetchFire($fireId) {
    http_response_code(200);
    header("Content-Type: application/json");

    try {
        $db = new DB();
        $fire = $db->getFire($fireId);
        if (!$fire) {
            http_response_code(400);
            echo "no";
        }
        else {
            echo $fire->toJson();
        }
    }
    catch(Exception $e) {
        http_response_code(500);
        echo json_encode($e);
    }
}
