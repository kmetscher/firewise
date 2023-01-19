<?php

class Fire {
    private int $id;
    private string $name;

    private int $categoryId;
    private string $category;

    private int $causeId;
    private string $cause;
    
    private DateTimeImmutable $discoveryDate;
    private int $discoveryDoy;
    private DateTimeImmutable $containmentDate;
    private int $containmentDoy;

    private float $size;
    private float $latitude;
    private float $longitude;
    
    private string $state;
    
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
        
        $this->state = $vals["STATE"];
    }

    public function getIndependentAttributes(): array {
        $attrs = [
            $this->latitude, 
            $this->longitude, 
            $this->discoveryDate->getTimestamp(),
            $this->discoveryDoy,
            $this->containmentDate->getTimestamp(),
            $this->containmentDoy,
            $this->size,
        ];
        return $attrs;
    }

    public function getCause(): string {
        return $this->cause;
    }

    public function getCategoryId(): string {
        return $this->categoryId;
    }

    public function prettyPrint(): void {
        echo "#" . $this->id . ": " . $this->name . "(" . $this->state . ")\n";
        echo "    $this->latitude, $this->longitude\n";
        echo "    " . $this->discoveryDate->format("Y-m-d") . " to " . $this->containmentDate->format("Y-m-d") . "\n";
        echo "    Burn size: " . $this->size . "\n";
        echo "    Category: " . $this->category . "\n";
        echo "    Known cause: $this->cause\t\n";
    }

    public function toJson() {
    
    }
}

?>
