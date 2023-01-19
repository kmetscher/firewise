<?php
require_once("../db/db.php");
require_once("../model/fire.php");
require_once("../vendor/autoload.php");

use Phpml\Classification\Ensemble\RandomForest;
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

$db = new DB();
$mm = new ModelManager();

stopwatch("Fetching fires");
$fires = $db->getBalancedSet();
shuffle($fires);
$trainFires = array_slice($fires, 0, floor(sizeof($fires) * 0.66));
$testFires = array_slice($fires, floor(sizeof($fires) * 0.66));
stopwatch("Done (" . sizeof($trainFires) . ")");

$classifierHN = new RandomForest(100);
$classifierRForestHuman = new RandomForest(100);
$classifierRForestNatural = new RandomForest(100);

$humanSamples = [];
$naturalSamples = [];
$humanLabels = [];
$naturalLabels = [];

$uncatSamples = [];
$categoryLabels = [];

$trainingFires = 0;
stopwatch("Splitting samples");
foreach ($trainFires as $fire) {
    $uncatSamples[] = $fire->getIndependentAttributes();
    $categoryLabels[] = $fire->getCategoryId();
}

stopwatch("Training human/natural split forest");
$classifierHN->train($uncatSamples, $categoryLabels);
stopwatch("Saving brain");
$mm->saveToFile($classifierHN, "firstround.brain");

stopwatch("Splitting categories on training set");
foreach ($trainFires as $fire) {
    $trainingFires++;
    switch ($fire->getCategoryId()) {
        case 0:
            $naturalSamples[] = $fire->getIndependentAttributes(true);
            $naturalLabels[] = $fire->getCause();
            break;
        default:
            $humanSamples[] = $fire->getIndependentAttributes(true);
            $humanLabels[] = $fire->getCause();
    }
}

$columns = ["latitude", "longitude", "discoveryDate", "discoveryDoy", "containmentDate", "containmentDoy", "size"];
$classifierHN->setColumnNames($columns);
$classifierRForestHuman->setColumnNames($columns);
$classifierRForestHuman->setSubsetRatio(1.0);
$classifierRForestNatural->setColumnNames($columns);
$classifierRForestNatural->setSubsetRatio(1.0);

stopwatch("Training human-caused forest");
$classifierRForestHuman->train($humanSamples, $humanLabels);
stopwatch("Saving brain");
$mm->saveToFile($classifierRForestHuman, "secondroundhuman.brain");

stopwatch("Training natural-caused forest");
$classifierRForestNatural->train($naturalSamples, $naturalLabels);
stopwatch("Saving brain");
$mm->saveToFile($classifierRForestNatural, "secondroundnatural.brain");

$total = 0;
$accuracyHN = 0;
$accuracyRForest = 0;

$accuracyByCause = [
    "Debris Burning" => [
        "Total" => 0,
        "RForest" => 0,
    ],
    "Lightning" => [
        "Total" => 0,
        "RForest" => 0,
    ],
    "Equipment Use" => [
        "Total" => 0,
        "RForest" => 0,
    ],
    "Arson" => [
        "Total" => 0,
        "RForest" => 0,
    ],
    "Campfire" => [
        "Total" => 0,
        "RForest" => 0,
    ],
    "Smoking" => [
        "Total" => 0,
        "RForest" => 0,
    ],
    "Railroad" => [
        "Total" => 0,
        "RForest" => 0,
    ],
    "Powerline" => [
        "Total" => 0,
        "RForest" => 0,
    ],
    "Structure" => [
        "Total" => 0,
        "RForest" => 0,
    ],
    "Fireworks" => [
        "Total" => 0,
        "RForest" => 0,
    ],
];

stopwatch("Predicting");
foreach ($testFires as $fire) {
    $total++;
    
    $category = $classifierHN->predict($fire->getIndependentAttributes());
    if ($fire->getCategoryId() == $category) {
        $accuracyHN++;
    }

    $cause = $fire->getCause();
    $accuracyByCause[$cause]["Total"]++;

    if ($category == 0) {
        $predictionRForest = $classifierRForestNatural->predict($fire->getIndependentAttributes(true));
    }
    else {
        $predictionRForest = $classifierRForestHuman->predict($fire->getIndependentAttributes(true));
    }
    if ($cause == $predictionRForest) {
        $accuracyRForest++;
        $accuracyByCause[$cause]["RForest"]++;
    }
}

$pctRForest = floor((float) ($accuracyRForest / $total) * 100);
$pctHN = floor((float) ($accuracyHN / $total) * 100);
$importanceHN = $classifierHN->getFeatureImportances();
$importanceRFHuman = $classifierRForestHuman->getFeatureImportances();
$importanceRFNatural = $classifierRForestNatural->getFeatureImportances();
stopwatch("Done.", true);
echo "$total fires tested, $trainingFires trained\n";
echo "    Feature importance (H/N):\n";
foreach ($importanceHN as $element => $importance) {
    echo "        $element: $importance\n";
}
$importanceMatrix = [];
echo "    Feature importance (Human forest):\n";
foreach ($importanceRFHuman as $element => $importance) {
    echo "        $element: $importance\n";
    $importanceMatrix["human"][$element] = $importance;
}
echo "    Feature importance (Natural forest):\n";
foreach ($importanceRFNatural as $element => $importance) {
    echo "        $element: $importance\n";
    $importanceMatrix["natural"][$element] = $importance;
}
$serializedImportance = fopen("compleximportances.json", 'w');
fwrite($serializedImportance, json_encode($importanceMatrix));
echo "    Human/natural: $accuracyHN correct, $pctHN% accurate\n";
echo "    Random forest: $accuracyRForest correct, $pctRForest% accurate\n";
$accuracyMatrix = [];
foreach ($accuracyByCause as $cause => $algos) {
    echo "        $cause:\t";
    if ($cause == "Arson") {
        echo "\t";
    }
    $causeAccuracy = floor((float)($algos["RForest"] / $algos["Total"]) * 100);
    echo "$causeAccuracy%\n";
    $accuracyMatrix[$cause] = $causeAccuracy;
}
$serializedAccuracy = fopen("complexaccuracies.json", 'w');
fwrite($serializedAccuracy, json_encode($accuracyMatrix));
?>
