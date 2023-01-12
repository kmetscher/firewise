<?php
require_once("../db/db.php");
require_once("../model/fire.php");
require_once("../vendor/autoload.php");
use Phpml\Classification\NaiveBayes;
use Phpml\Classification\KNearestNeighbors;
use Phpml\Classification\DecisionTree;
use Phpml\Classification\Ensemble\RandomForest;
//use Phpml\Classification\Ensemble\AdaBoost;
//use Phpml\Dataset\ArrayDataset;
//use Phpml\FeatureSelection\SelectKBest;

$db = new DB();
echo "Fetching fires...\n";
$fires = $db->getTrainingSet(4000);

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

echo "Training human/natural classifier...\n";

$classifierHN = new RandomForest();
$classifierHN->train($uncatSamples, $categoryLabels);

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

/*$trainingSet = new ArrayDataset($samples, $labels);
$selector = new SelectKBest(1);
$selector->fit($trainingSet->getSamples(), $trainingSet->getTargets());
$selector->transform($trainingSet->getSamples());*/

echo "Training decision tree (human-caused)...\n";
$classifierDTreeHuman = new DecisionTree();
$classifierDTreeHuman->train($humanSamples, $humanLabels);
echo "Training decision tree (natural)...\n";
$classifierDTreeNatural = new DecisionTree();
$classifierDTreeNatural->train($naturalSamples, $naturalLabels);

echo "Training random forest (human-caused)...\n";
$classifierRForestHuman = new RandomForest();
$classifierRForestHuman->train($humanSamples, $humanLabels);
echo "Training random forest (natural)...\n";
$classifierRForestNatural = new RandomForest();
$classifierRForestNatural->train($naturalSamples, $naturalLabels);

echo "Training Bayes (human-caused)...\n";
$classifierBayesHuman = new NaiveBayes();
$classifierBayesHuman->train($humanSamples, $humanLabels);
echo "Training Bayes (natural)...\n";
$classifierBayesNatural = new NaiveBayes();
$classifierBayesNatural->train($naturalSamples, $naturalLabels);

echo "Training K nearest (human-caused)...\n";
$classifierKHuman = new KNearestNeighbors();
$classifierKHuman->train($humanSamples, $humanLabels);
echo "Training K nearest (natural)...\n";
$classifierKNatural = new KNearestNeighbors();
$classifierKNatural->train($naturalSamples, $naturalLabels);
$total = 0;

$accuracyHN = 0;
$accuracyDTree = 0;
$accuracyRForest = 0;
$accuracyBayes = 0;
$accuracyNearestK = 0;

$testFires = $db->getTestingSet(4000);

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

        if ($category == 0) {
            $predictionBayes = $classifierBayesNatural->predict($fire->getIndependentAttributes(true));
            $predictionNearestK = $classifierKNatural->predict($fire->getIndependentAttributes(true));
            $predictionDTree = $classifierDTreeNatural->predict($fire->getIndependentAttributes(true));
            $predictionRForest = $classifierRForestNatural->predict($fire->getIndependentAttributes(true));
        }
        else {
            $predictionBayes = $classifierBayesHuman->predict($fire->getIndependentAttributes(true));
            $predictionNearestK = $classifierKHuman->predict($fire->getIndependentAttributes(true));
            $predictionDTree = $classifierDTreeHuman->predict($fire->getIndependentAttributes(true));
            $predictionRForest = $classifierRForestHuman->predict($fire->getIndependentAttributes(true));
        }
        
        echo "\n";
        $fire->prettyPrint();
        echo "        Predicted cause (Random forest): $predictionRForest\n";
        echo "        Predicted cause (Decision tree): $predictionDTree\n";
        echo "        Predicted cause (Bayes): $predictionBayes\n";
        echo "        Predicted cause (K nearest): $predictionNearestK\n";
  
        if ($cause == $predictionBayes) {
            $accuracyBayes++;
        }
        if ($cause == $predictionNearestK) {
            $accuracyNearestK++;
        }
        if ($cause == $predictionDTree) {
            $accuracyDTree++;
        }
        if ($cause == $predictionRForest) {
            $accuracyRForest++;
        }
    }
}

$pctDTree = floor((float) ($accuracyDTree / $total) * 100);
$pctRForest = floor((float) ($accuracyRForest / $total) * 100);
$pctBayes = floor((float) ($accuracyBayes / $total) * 100);
$pctNearestK = floor((float) ($accuracyNearestK / $total) * 100);
$pctHN = floor((float) ($accuracyHN / $total) * 100);
echo "\n$total fires tested, $trainingFires trained\n";
echo "    Human/natural: $accuracyHN correct, $pctHN% accurate\n";
echo "    Bayes: $accuracyBayes correct, $pctBayes% accurate\n";
echo "    K nearest: $accuracyNearestK correct, $pctNearestK% accurate\n";
echo "    Decision tree: $accuracyDTree correct, $pctDTree% accurate\n";
echo "    Random forest: $accuracyRForest correct, $pctRForest% accurate\n";
?>
