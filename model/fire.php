<?php

class Fire {
    private int $id;
    private string $name;

    private int $categoryId;
    private ?int $predictedCategoryId;
    private string $category;
    private ?string $predictedCategory = null;

    private int $causeId;
    private string $cause;
    
    private string $reportingUnitId;
    private string $reportingUnitName;
    
    private DateTimeImmutable $discoveryDate;
    private int $discoveryDoy;
    private DateTimeImmutable $containmentDate;
    private int $containmentDoy;

    private float $size;
    private float $latitude;
    private float $longitude;
    
    private int $ownerId;
    private string $owner;
    
    private string $state;
    private string $county;
    
    private string $shape;

    private function categorize(int $categoryId): void {
        if ($categoryId == 0) {
            $this->predictedCategory = "Natural";
        }
        else {
            $this->predictedCategory = "Human-caused";
        }
    }

    public function __construct(array $vals) {
        $this->id = $vals["OBJECTID"];
        if ($vals["FIRE_NAME"]) {
            $this->name = $vals["FIRE_NAME"];
        }
        else {
            $this->name = "Unnamed";
        }
        
        $this->causeId = $vals["STAT_CAUSE_CODE"];
        $this->cause = $vals["STAT_CAUSE_DESCR"];
        switch ($this->causeId) {
            case 1:
            case 5:
                $this->categoryId = 0;
                $this->category = "Natural";
                break;
            default:
                $this->categoryId = 1;
                $this->category = "Human-caused";
        }
        
        $this->reportingUnitId = $vals["SOURCE_REPORTING_UNIT"];
        $this->reportingUnitName = $vals["SOURCE_REPORTING_UNIT_NAME"];
        
        $discoveryDate = DateTime::createFromFormat("Y-m-d", $vals["DISCOVERY_DATE"]);
        $containmentDate = DateTime::createFromFormat("Y-m-d", $vals["CONT_DATE"]);

        // This is an extremely hacky way to deal with weird dataset datetime choices
        $discoveryDate->setTime(substr($vals["DISCOVERY_TIME"], 0, 2), substr($vals["DISCOVERY_TIME"], 2));
        $containmentDate->setTime(substr($vals["CONT_TIME"], 0, 2), substr($vals["CONT_TIME"], 2));
        $this->discoveryDate = DateTimeImmutable::createFromMutable($discoveryDate);
        $this->containmentDate = DateTimeImmutable::createFromMutable($containmentDate); 

        $this->discoveryDoy = $vals["DISCOVERY_DOY"];
        $this->containmentDoy = $vals["CONT_DOY"];

        $this->size = $vals["FIRE_SIZE"];
        $this->latitude = $vals["LATITUDE"];
        $this->longitude = $vals["LONGITUDE"];
        
        $this->ownerId = $vals["OWNER_CODE"];
        $this->owner = $vals["OWNER_DESCR"];
        
        $this->state = $vals["STATE"];
        
        if ($vals["COUNTY"]) {
            $this->county = $vals["FIPS_NAME"];
        } 
        else {
            $this->county = "Unknown";
        }
        
        $this->shape = $vals["Shape"];
    }

    public function getIndependentAttributes(bool $withPreCat): array {
        $attrs = [
            $this->latitude, 
            $this->longitude, 
            $this->discoveryDate->getTimestamp(),
            $this->discoveryDoy,
            $this->containmentDate->getTimestamp(),
            $this->containmentDoy,
            $this->size,
            $this->ownerId,
        ];
        if ($withPreCat) {
            $attrs[] = $this->predictedCategoryId;
        }
        return $attrs;
    }

    public function getCause(): string {
        return $this->cause;
    }

    public function getCategoryId(): string {
        return $this->categoryId;
    }

    public function setPredictedCategoryId(int $categoryId): void {
        $this->predictedCategoryId = $categoryId;
        $this->categorize($categoryId);
    }

    public function getPredictedCategoryId(): int {
        return $this->predictedCategoryId;
    }

    public function prettyPrint(): void {
        echo "#" . $this->id . ": " . $this->name . "\n";
        echo "    " . $this->discoveryDate->format("Y-m-d") . " to " . $this->containmentDate->format("Y-m-d") . "\n";
        echo "    Burn size: " . $this->size . "\n";
        echo "    Category: " . $this->category . "\n";
        echo "        Predicted category: " . $this->predictedCategory . "\n";
        echo "    Cause: $this->cause\n";
    }
}

?>
