<?php
require_once("../vendor/autoload.php");
require_once("../model/fire.php");
use Phpml\ModelManager;

function predict(array $args) {
    $mm = new ModelManager();
    $simpleBrain = $mm->restoreFromFile("../model/simple.brain");
    //$complexBrainHN = $mm->restoreFromFile("../model/firstround.brain");
    //$complexBrainHuman = $mm->restoreFromFile("../model/secondroundhuman.brain");
    //$complexBrainNatural = $mm->restoreFromFile("../model/secondroundnatural.brain");
    $simpleAccuracies = json_decode(fgets(fopen("../model/simpleaccuracies.json", 'r')), true);
    $complexAccuracies = json_decode(fgets(fopen("../model/complexaccuracies.json", 'r')), true);
    $response = [];
    header("Content-Type: application/json");
    http_response_code(200);
    $startTime = new DateTimeImmutable();

    try {
        $discoveryDate = new DateTimeImmutable($args["startDate"]);
        $contDate = new DateTimeImmutable($args["endDate"]);

        $fireAttrs = [
            $args["latitude"],
            $args["longitude"],
            $discoveryDate->getTimestamp(),
            (int) $discoveryDate->format("z"),
            $contDate->getTimestamp(),
            (int) $contDate->format("z"),
            $args["size"],
        ];

        $simplePrediction = $simpleBrain->predict($fireAttrs);
        /*$complexFirstPrediction = $complexBrainHN->predict($fireAttrs);
        if ($complexFirstPrediction == 0) {
            $complexSecondPrediction = $complexBrainNatural->predict($fireAttrs);
        }
        else {
            $complexSecondPrediction = $complexBrainHuman->predict($fireAttrs);
        }
        if ($simpleAccuracies[$simplePrediction] > $complexAccuracies[$complexSecondPrediction]) {
            $finalPrediction = $simplePrediction;
        }
        else {
            $finalPrediction = $complexSecondPrediction;
        }*/
        
        $response["prediction"] = $simplePrediction;

    }
    catch(Exception $e) {
        http_response_code(400);
        echo json_encode(["error" => $e->getMessage()]);
    }
    $duration = $startTime->diff(new DateTimeImmutable());
    $response["duration"] = $duration;
    echo json_encode($response);
}
?>
