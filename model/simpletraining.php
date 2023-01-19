<?php
require_once("../vendor/autoload.php");
require_once("../model/fire.php");
require_once("../db/db.php");
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
stopwatch("Fetching training fires");
$fires = $db->getBalancedSet();
shuffle($fires);
$trainingFires = array_slice($fires, 0, floor(sizeof($fires) * 0.66));
$testFires = array_slice($fires, floor(sizeof($fires) * 0.66));
stopwatch(sizeof($trainingFires) . " fetched");
$samples = [];
$labels = [];
foreach ($trainingFires as $fire) {
    $samples[] = $fire->getIndependentAttributes();
    $labels[] = $fire->getCause();
}
$forest = new RandomForest(100);
$columns = ["latitude", "longitude", "discoveryDate", "discoveryDoy", "containmentDate", "containmentDoy", "size"];
$forest->setColumnNames($columns);
$forest->setSubsetRatio(1.0);
stopwatch("Training random forest");
$forest->train($samples, $labels);
stopwatch("Saving brain");
$mm = new ModelManager();
$mm->saveToFile($forest, "simple.brain");
stopwatch("Predicting");
$total = sizeof($testFires);
$correct = 0;
$accuracies = [
    "Lightning" => [
        "total" => 0,
        "accurate" => 0,
    ],
    "Debris Burning" => [
        "total" => 0,
        "accurate" => 0,
    ],
    "Equipment Use" => [
        "total" => 0,
        "accurate" => 0,
    ],
    "Campfire" => [
        "total" => 0,
        "accurate" => 0,
    ],
    "Arson" => [
        "total" => 0,
        "accurate" => 0,
    ],
    "Railroad" => [
        "total" => 0,
        "accurate" => 0,
    ],
    "Smoking" => [
        "total" => 0,
        "accurate" => 0,
    ],
    "Powerline" => [
        "total" => 0,
        "accurate" => 0,
    ],
    "Structure" => [
        "total" => 0,
        "accurate" => 0,
    ],
    "Fireworks" => [
        "total" => 0,
        "accurate" => 0,
    ],
];
foreach ($testFires as $fire) {
    $prediction = $forest->predict($fire->getIndependentAttributes()); 
    $accuracies[$fire->getCause()]["total"]++;
    if ($prediction == $fire->getCause()) {
        $correct++;
        $accuracies[$fire->getCause()]["accurate"]++;
    }
    else {

    }
    $fire->prettyPrint();
    echo "    Predicted cause: $prediction\t\n";
}
stopwatch("Done", true);
$importances = $forest->getFeatureImportances();
echo "Feature importance:\n";
foreach ($importances as $feature => $importance) {
    echo "    $feature: $importance\n";
}
$accuracyMatrix = [];
echo "$correct out of $total, " . floor(((float) $correct / $total) * 100) . "% accuracy\n";
foreach ($accuracies as $type => $nums) {
    $accuracy = floor(((float) $nums["accurate"] / $nums["total"]) * 100);
    $accuracyMatrix[$type] = $accuracy;
    echo "    $type: $accuracy%\n";
}
$serializedAccuracy = fopen("simpleaccuracies.json", 'w');
fwrite($serializedAccuracy, json_encode($accuracyMatrix));

?>
