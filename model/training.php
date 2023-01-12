<?php
require_once("../db/db.php");
require_once("../model/fire.php");
require_once("../vendor/autoload.php");
use Phpml\Classification\Ensemble\RandomForest;
use Phpml\ModelManager;

$db = new DB();
$mm = new ModelManager();
$time = new DateTimeImmutable();
$format = "Y-m-d H:i:s";
$stepFormat = "H:i:s";
echo $time->format($format) . ": ";
echo "Fetching fires...\n";
$fires = $db->getTrainingSet(2000);
$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Done.\n";

$humanSamples = [];
$naturalSamples = [];
$humanLabels = [];
$naturalLabels = [];

$uncatSamples = [];
$categoryLabels = [];

$trainingFires = 0;

foreach ($fires as $fire) {
    $uncatSamples[] = $fire->getIndependentAttributes(false);
    $categoryLabels[] = $fire->getCategoryId();
}

$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Training human/natural classifier...\n";
$classifierHN = new RandomForest();
$classifierHN->train($uncatSamples, $categoryLabels);
$mm->saveToFile($classifierHN, "firstround.brain");
$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Done.\n";

foreach ($fires as $fire) {
    $trainingFires++;
    $fire->setPredictedCategoryId($fire->getCategoryId());
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

/*
$trainingSet = new ArrayDataset($samples, $labels);
$selector = new SelectKBest(1);
$selector->fit($trainingSet->getSamples(), $trainingSet->getTargets());
$selector->transform($trainingSet->getSamples());
*/
/*
$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Training decision tree...\n";
$classifierDTreeHuman = new DecisionTree();
$classifierDTreeHuman->train($humanSamples, $humanLabels);
$time = new DateTimeImmutable();
//echo $time->format($stepFormat) . ": ";
//echo "Training decision tree (natural)...\n";
$classifierDTreeNatural = new DecisionTree();
$classifierDTreeNatural->train($naturalSamples, $naturalLabels);
*/
$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Training random forest (human-caused)...\n";
$classifierRForestHuman = new RandomForest();
$classifierRForestHuman->train($humanSamples, $humanLabels);
$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Done.\n";
echo $time->format($stepFormat) . ": ";
echo "Training random forest (natural)...\n";
$classifierRForestNatural = new RandomForest();
$classifierRForestNatural->train($naturalSamples, $naturalLabels);
$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Done.\n";
/*
$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Training Bayes...\n";
$classifierBayesHuman = new NaiveBayes();
$classifierBayesHuman->train($humanSamples, $humanLabels);
$time = new DateTimeImmutable();
//echo $time->format($stepFormat) . ": ";
//echo "Training Bayes (natural)...\n";
$classifierBayesNatural = new NaiveBayes();
$classifierBayesNatural->train($naturalSamples, $naturalLabels);

$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Training K nearest...\n";
$classifierKHuman = new KNearestNeighbors();
$classifierKHuman->train($humanSamples, $humanLabels);
$time = new DateTimeImmutable();
//echo $time->format($stepFormat) . ": ";
//echo "Training K nearest (natural)...\n";
$classifierKNatural = new KNearestNeighbors();
$classifierKNatural->train($naturalSamples, $naturalLabels);*/

$total = 0;

$accuracyHN = 0;
//$accuracyDTree = 0;
$accuracyRForest = 0;
//$accuracyBayes = 0;
//$accuracyNearestK = 0;

$accuracyByCause = [
    "Debris Burning" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
    "Lightning" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
    "Equipment Use" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
    "Arson" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
    "Campfire" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
    /*"Children" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],*/
    "Smoking" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
    "Railroad" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
    "Powerline" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
    "Structure" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
    "Fireworks" => [
        "Total" => 0,
        "DTree" => 0,
        "RForest" => 0,
        "Bayes" => 0,
        "NearestK" => 0,
    ],
];

$testFires = $db->getTestingSet(2000);

$time = new DateTimeImmutable();
echo $time->format($stepFormat) . ": ";
echo "Predicting...\n";
foreach ($testFires as $fire) {
    if ($fire) {
        $total++;
        
        $category = $classifierHN->predict($fire->getIndependentAttributes(false));
        $fire->setPredictedCategoryId($category);
        if ($fire->getCategoryId() == $fire->getPredictedCategoryId()) {
            $accuracyHN++;
        }

        $cause = $fire->getCause();
        $accuracyByCause[$cause]["Total"]++;

        if ($category == 0) {
            //$predictionBayes = $classifierBayesNatural->predict($fire->getIndependentAttributes(true));
            //$predictionNearestK = $classifierKNatural->predict($fire->getIndependentAttributes(true));
            //$predictionDTree = $classifierDTreeNatural->predict($fire->getIndependentAttributes(true));
            $predictionRForest = $classifierRForestNatural->predict($fire->getIndependentAttributes(true));
        }
        else {
            //$predictionBayes = $classifierBayesHuman->predict($fire->getIndependentAttributes(true));
            //$predictionNearestK = $classifierKHuman->predict($fire->getIndependentAttributes(true));
            //$predictionDTree = $classifierDTreeHuman->predict($fire->getIndependentAttributes(true));
            $predictionRForest = $classifierRForestHuman->predict($fire->getIndependentAttributes(true));
        }

        $verbose = false;

        if ($verbose) {
            echo "\n";
            $fire->prettyPrint();
            echo "        Predicted cause (Random forest): $predictionRForest\n";
            //echo "        Predicted cause (Decision tree): $predictionDTree\n";
            //echo "        Predicted cause (Bayes): $predictionBayes\n";
            //echo "        Predicted cause (K nearest): $predictionNearestK\n";
        }

        /*
        if ($cause == $predictionBayes) {
            $accuracyBayes++;
            $accuracyByCause[$cause]["Bayes"]++;
        }
        if ($cause == $predictionNearestK) {
            $accuracyNearestK++;
            $accuracyByCause[$cause]["NearestK"]++;
        }
        if ($cause == $predictionDTree) {
            $accuracyDTree++;
            $accuracyByCause[$cause]["DTree"]++;
        }
        */
        if ($cause == $predictionRForest) {
            $accuracyRForest++;
            $accuracyByCause[$cause]["RForest"]++;
        }
    }
}

//$pctDTree = floor((float) ($accuracyDTree / $total) * 100);
$pctRForest = floor((float) ($accuracyRForest / $total) * 100);
//$pctBayes = floor((float) ($accuracyBayes / $total) * 100);
//$pctNearestK = floor((float) ($accuracyNearestK / $total) * 100);
$pctHN = floor((float) ($accuracyHN / $total) * 100);
$time = new DateTimeImmutable();
echo $time->format($format) . ": ";
echo "$total fires tested, $trainingFires trained\n";
echo "    Human/natural: $accuracyHN correct, $pctHN% accurate\n";
/*
echo "    Bayes: $accuracyBayes correct, $pctBayes% accurate\n";
foreach ($accuracyByCause as $cause => $algos) {
    echo "        $cause: " . floor((float)($algos["Bayes"] / $algos["Total"]) * 100) . "%\n";
}
echo "    K nearest: $accuracyNearestK correct, $pctNearestK% accurate\n";
foreach ($accuracyByCause as $cause => $algos) {
    echo "        $cause: " . floor((float)($algos["NearestK"] / $algos["Total"]) * 100) . "%\n";
}
echo "    Decision tree: $accuracyDTree correct, $pctDTree% accurate\n";
foreach ($accuracyByCause as $cause => $algos) {
    echo "        $cause: " . floor((float)($algos["DTree"] / $algos["Total"]) * 100) . "%\n";
}
*/
echo "    Random forest: $accuracyRForest correct, $pctRForest% accurate\n";
foreach ($accuracyByCause as $cause => $algos) {
    echo "        $cause:\t";
    if ($cause == "Arson") {
        echo "\t";
    }
    echo floor((float)($algos["RForest"] / $algos["Total"]) * 100) . "% accuracy\n";
}
?>
