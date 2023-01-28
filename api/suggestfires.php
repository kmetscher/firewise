<?php
require_once("../db/db.php");

function getSuggestions() {
    $fireIds = json_decode(fgets(fopen("../model/suggestedfires.json", 'r')), true);
    shuffle($fireIds);

    $db = new DB();

    $fires = [];
    while (sizeof($fires) < 6) { 
        $fire = $db->getFire(array_pop($fireIds));
        if ($fire == null) {
            continue;
        }
        $fires[] = json_decode($fire->toJson());
    }

    echo json_encode($fires);
}
