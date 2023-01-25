<?php
require_once("../api/getfire.php");
require_once("../api/predict.php");
require_once("../api/suggestfires.php");
require_once("../api/analysis.php");

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if ($_GET["suggest"]) {
        getSuggestions();
    }
    else if ($_GET["fireId"]) {
        $fireId = (int) $_GET["fireId"];
        fetchFire($fireId); 
    }
    else if ($_GET["analysis"]) {
        analysis($_GET["analysis"]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $attrs = json_decode(file_get_contents("php://input"), true);
    predict($attrs);
}

