<?php
require_once("../vendor/autoload.php");
require_once("../model/fire.php");
use Phpml\ModelManager;

function predict(array $args) {
    $mm = new ModelManager();
    $simpleBrain = $mm->restoreFromFile("../model/simple.brain");
    $complexBrainHN = $mm->restoreFromFile("../model/firstround.brain");
    $complexBrainHuman = $mm->restoreFromFile("../model/secondroundhuman.brain");
    $complexBrainNatural = $mm->restoreFromFile("../model/secondroundnatural.brain");
    $matrixAccuracies = json_decode(fgets(fopen("../model/accuracies.json", 'r')), true);
    $simpleAccuracies = $matrixAccuracies["simple"]; 
    $complexAccuracies = $matrixAccuracies["complex"]; 
    $response = [];
    header("Content-Type: application/json");
    http_response_code(200);
    $startTime = new DateTimeImmutable();

    try {
        $discoveryDate = new DateTime();
        $ecmaDiscoveryDate = $args["startDate"] / 1000;
        $discoveryDate->setTimestamp($ecmaDiscoveryDate);
        $contDate = new DateTime();
        $ecmaContDate = $args["endDate"] / 1000;
        $contDate->setTimestamp($ecmaContDate);

        $fireAttrs = [
            $args["latitude"],
            $args["longitude"],
            $ecmaDiscoveryDate,
            (int) $discoveryDate->format("z"),
            $ecmaContDate,
            (int) $contDate->format("z"),
            $args["size"],
        ];

        $simplePrediction = $simpleBrain->predict($fireAttrs);
        $complexFirstPrediction = $complexBrainHN->predict($fireAttrs);
        if ($complexFirstPrediction == 0) {
            $response["category"] = "naturally-caused";
            $complexSecondPrediction = $complexBrainNatural->predict($fireAttrs);
        }
        else {
            $response["category"] = "human-caused";
            $complexSecondPrediction = $complexBrainHuman->predict($fireAttrs);
        }
        if ($simpleAccuracies[$simplePrediction] > $complexAccuracies[$complexSecondPrediction]) {
            $finalPrediction = $simplePrediction;
        }
        else {
            $finalPrediction = $complexSecondPrediction;
        }
        switch ($finalPrediction) {
            case "Structure":
                $finalPrediction = "a structure fire";
                break;
            case "Debris Burning":
                $finalPrediction = "burning debris";
                break;
            case "Railroad":
                $finalPrediction = "the railroad";
                break;
            case "Powerline":
                $finalPrediction = "powerline infrastructure";
                break;
        }
        
        
        $response["prediction"] = strtolower($finalPrediction);

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
