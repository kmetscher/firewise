<?php
require_once("../db/db.php");
require_once("../vendor/autoload.php");
require_once("../model/fire.php");
use Phpml\ModelManager;

function stopwatch(string $output, bool $full = false): void {
    $fullFormat = "Y-m-d H:i:s";
    $shortFormat = "H:i:s";
    $time = new DateTimeImmutable();
    if ($full) {
        echo $time->format($fullFormat) . ": ";
    }
    else {
        echo $time->format($shortFormat) . ": ";
    }
    echo "$output\n";
}

stopwatch("Firing up", true);
$mm = new ModelManager();
$db = new DB();

stopwatch("Restoring brains");
$simpleBrain = $mm->restoreFromFile("simple.brain");
$complexBrainHN = $mm->restoreFromFile("firstround.brain");
$complexBrainHuman = $mm->restoreFromFile("secondroundhuman.brain");
$complexBrainNatural = $mm->restoreFromFile("secondroundnatural.brain");

stopwatch("Restoring accuracies");
$simpleAccuracies = json_decode(fgets(fopen("simpleaccuracies.json", 'r')), true);
$complexAccuracies = json_decode(fgets(fopen("complexaccuracies.json", 'r')), true);

stopwatch("Fetching fires");
$fires = $db->getBalancedSet();
$total = sizeof($fires);
$accurate = 0;
$causeAccuracies = [
    "Lightning" => [
        "total" => 0,
        "correct" => 0,
    ],
    "Equipment Use" => [
        "total" => 0,
        "correct" => 0,
    ],
    "Smoking" => [
        "total" => 0,
        "correct" => 0,
    ],
    "Campfire" => [
        "total" => 0,
        "correct" => 0,
    ],
    "Debris Burning" => [
        "total" => 0,
        "correct" => 0,
    ],
    "Railroad" => [
        "total" => 0,
        "correct" => 0,
    ],
    "Arson" => [
        "total" => 0,
        "correct" => 0,
    ],
    "Fireworks" => [
        "total" => 0,
        "correct" => 0,
    ],
    "Powerline" => [
        "total" => 0,
        "correct" => 0,
    ],
    "Structure" => [
        "total" => 0,
        "correct" => 0,
    ],
];

stopwatch("Predicting");
foreach ($fires as $fire) {
    $catPrediction = $complexBrainHN->predict($fire->getIndependentAttributes());
    if ($catPrediction == 0) {
        $complexPrediction = $complexBrainNatural->predict($fire->getIndependentAttributes());
    }
    else {
        $complexPrediction = $complexBrainHuman->predict($fire->getIndependentAttributes());
    }
    $simplePrediction = $simpleBrain->predict($fire->getIndependentAttributes());
    if ($simpleAccuracies[$simplePrediction] > $complexAccuracies[$complexPrediction]) {
        $finalPrediction = $simplePrediction;
    }
    else {
        $finalPrediction = $complexPrediction;
    }
    if ($fire->getCause() == $finalPrediction) {
        $accurate++;
        $causeAccuracies[$finalPrediction]["correct"]++;
    }
    $causeAccuracies[$fire->getCause()]["total"]++;
}
stopwatch("Done.", true);
$pctAccurate = floor((float) ($accurate / $total) * 100);
echo "$accurate out of $total; $pctAccurate% accuracy\n";
foreach ($causeAccuracies as $cause => $counts) {
    $accuracy = floor((float) ($counts["correct"] / $counts["total"]) * 100);
    echo "    $cause: $accuracy%\n";
}
?>
