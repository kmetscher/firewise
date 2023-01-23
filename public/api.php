<?php
require_once("../api/getfire.php");
require_once("../api/predict.php");

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $fireId = (int) $_GET["fireId"];
    fetchFire($fireId); 
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $attrs = json_decode(file_get_contents("php://input"), true);
    predict($attrs);
}

