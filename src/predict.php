<?php
require_once("../vendor/autoload.php");
require_once("../model/fire.php");
use Phpml\ModelManager;

$mm = new ModelManager();

$simpleBrain = $mm->restoreFromFile("../model/simple.brain");
$complexBrainHN = $mm->restoreFromFile("../model/firstround.brain");
$complexBrainHuman = $mm->restoreFromFile("../model/secondroundhuman.brain");
$complexBrainNatural = $mm->restoreFromFile("../model/secondroundnatural.brain");

$simpleAccuracies = json_decode(fgets(fopen("../model/simpleaccuracies.json", 'r')), true);
$complexAccuracies = json_decode(fgets(fopen("../model/complexaccuracies.json", 'r')), true);

$params = $_POST["fire"];

?>
