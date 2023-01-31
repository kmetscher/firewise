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

$verbose = false;

foreach ($argv as $arg) {
    if ($arg == "--with-accuracies" || $arg == "-w") {
        $withAccuracies = true;
        stopwatch("Fetching accuracy matrix");
        $accuracies = json_decode(fgets(fopen("accuracies.json", 'r')), true);
        $simpleAccuracies = $accuracies["simple"];
        $complexAccuracies = $accuracies["complex"]; 
    }
    if ($arg == "--verbose" || $arg == "-v") {
        $verbose = true;
        stopwatch("Running verbose");
    }
}

stopwatch("Fetching fires");
$fires = $db->getBalancedSet();
shuffle($fires);
$trainFires = array_slice($fires, 0, floor(sizeof($fires) * 0.66));
$testFires = array_slice($fires, floor(sizeof($fires) * 0.66));
stopwatch("Done (" . sizeof($trainFires) . ")");

$classifierHN = new RandomForest(100);
$classifierSimple = new RandomForest(100);
$classifierRForestHuman = new RandomForest(100);
$classifierRForestNatural = new RandomForest(100);

$humanSamples = [];
$naturalSamples = [];
$humanLabels = [];
$naturalLabels = [];

$uncatSamples = [];
$uncatLabels = [];
$categoryLabels = [];

$trainingFires = 0;
stopwatch("Splitting samples");
foreach ($trainFires as $fire) {
    $uncatSamples[] = $fire->getIndependentAttributes();
    $uncatLabels[] = $fire->getCause();
    $categoryLabels[] = $fire->getCategoryId();
}

stopwatch("Training human/natural split forest");
$classifierHN->train($uncatSamples, $categoryLabels);
stopwatch("Saving brain");
$mm->saveToFile($classifierHN, "firstround.brain");

stopwatch("Training simple forest");
$classifierSimple->train($uncatSamples, $uncatLabels);
stopwatch("Saving brain");
$mm->saveToFile($classifierSimple, "simple.brain");

stopwatch("Splitting categories on training set");
foreach ($trainFires as $fire) {
    $trainingFires++;
    switch ($fire->getCategoryId()) {
        case 0:
            $naturalSamples[] = $fire->getIndependentAttributes();
            $naturalLabels[] = $fire->getCause();
            break;
        default:
            $humanSamples[] = $fire->getIndependentAttributes();
            $humanLabels[] = $fire->getCause();
    }
}

$columns = ["latitude", "longitude", "discoveryDate", "discoveryDoy", "containmentDate", "containmentDoy", "size"];
$classifierHN->setColumnNames($columns);
$classifierSimple->setColumnNames($columns);
$classifierSimple->setSubsetRatio(1.0);
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
$accuracySimple = 0;
$accuracyCombined = 0;

$accuracyByCause = [
    "Debris Burning" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
    ],
    "Lightning" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
    ],
    "Equipment Use" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
    ],
    "Arson" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
    ],
    "Campfire" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
    ],
    "Smoking" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
    ],
    "Railroad" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
    ],
    "Powerline" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
    ],
    "Structure" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
    ],
    "Fireworks" => [
        "Total" => 0,
        "RForest" => 0,
        "Simple" => 0,
        "Combined" => 0,
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

    $predictionSimple = $classifierSimple->predict($fire->getIndependentAttributes());

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
    if ($cause == $predictionSimple) {
        $accuracySimple++;
        $accuracyByCause[$cause]["Simple"]++;
    }
    if ($withAccuracies) {
        if ($simpleAccuracies[$cause] > $complexAccuracies[$cause]) {
            $predictionCombined = $predictionSimple;
        }
        else {
            $predictionCombined = $predictionRForest;
        }
        if ($predictionCombined == $cause) {
            $accuracyByCause[$cause]["Combined"]++;
            $accuracyCombined++;
        }
    }
    if ($verbose) {
        $fire->prettyPrint();
        echo "    Predicted cause: $predictionCombined\n";
    }
}

$pctRForest = floor((float) ($accuracyRForest / $total) * 100);
$pctHN = floor((float) ($accuracyHN / $total) * 100);
$pctSimple = floor((float) ($accuracySimple / $total) * 100);
$pctCombined = floor((float) ($accuracyCombined / $total) * 100);
$importanceHN = $classifierHN->getFeatureImportances();
$importanceSimple = $classifierSimple->getFeatureImportances();
$importanceRFHuman = $classifierRForestHuman->getFeatureImportances();
$importanceRFNatural = $classifierRForestNatural->getFeatureImportances();
stopwatch("Done.", true);
echo "$total fires tested, $trainingFires trained\n";
echo "    Feature importance (H/N):\n";
foreach ($importanceHN as $element => $importance) {
    echo "        $element: $importance\n";
    $importanceMatrix["category"][$element] = $importance;
}
$importanceMatrix = [];
echo "    Feature importance (Human forest):\n";
foreach ($importanceRFHuman as $element => $importance) {
    echo "        $element: $importance\n";
    $importanceMatrix["human"][$element] = $importance;
}
echo "    Feature importance (Simple forest):\n";
foreach ($importanceSimple as $element => $importance) {
    echo "        $element: $importance\n";
    $importanceMatrix["simple"][$element] = $importance;
}
echo "    Feature importance (Natural forest):\n";
foreach ($importanceRFNatural as $element => $importance) {
    echo "        $element: $importance\n";
    $importanceMatrix["natural"][$element] = $importance;
}
$serializedImportance = fopen("importances.json", 'w');
fwrite($serializedImportance, json_encode($importanceMatrix));
echo "    Human/natural: $accuracyHN correct, $pctHN% accurate\n";
echo "    Random forest: $accuracyRForest correct, $pctRForest% accurate\n";
echo "    Simple forest: $accuracySimple correct, $pctSimple% accurate\n";
echo "    Combined model: $accuracyCombined correct, $pctCombined% accurate\n";
$accuracyMatrix = [];
foreach ($accuracyByCause as $cause => $algos) {
    echo "        $cause:\n";
    $causeAccuracyComplex = floor((float)($algos["RForest"] / $algos["Total"]) * 100);
    $causeAccuracySimple = floor((float)($algos["Simple"] / $algos["Total"]) * 100);
    $causeAccuracyCombined = floor((float)($algos["Combined"] / $algos["Total"]) * 100);
    echo "            Complex: $causeAccuracyComplex%\n";
    echo "            Simple: $causeAccuracySimple%\n";
    $accuracyMatrix["complex"][$cause] = $causeAccuracyComplex;
    $accuracyMatrix["simple"][$cause] = $causeAccuracySimple;
}
$serializedAccuracy = fopen("accuracies.json", 'w');
fwrite($serializedAccuracy, json_encode($accuracyMatrix));
?>

