<?php

function analysis(string $report) {
    try {
        $analysis = json_decode(fgets(fopen("../model/analysis.json", 'r')), true);

        switch ($report) {
        case "allCauses":
            arsort($analysis[$report]);
            $max = $analysis[$report]["Lightning"];
            $map = [];
            foreach ($analysis[$report] as $cause => $count) {
                $map[] = [
                    "cause" => $cause,
                    "count" => $count,
                    "pct" => floor((float) $count / $max * 100),
                ];
            }
            echo json_encode($map);
            break;

        case "countByYear":
            $map = [];
            $max = 0;
            foreach ($analysis[$report] as $year => $stats) {
                $yearMap = ["year" => $year];
                foreach ($stats as $key => $value) {
                    switch ($key) {
                    case "total":
                        $yearMap["total"] = $value;
                        break;
                    case "acresBurned":
                        $yearMap["acresBurned"] = round($value, 1);
                        if ($value > $max) $max = $value;
                        break;
                    default:
                        $yearMap["causes"][] = [
                            "cause" => $key, 
                            "count" => $value,
                            "pct" => floor((float) ($value / $yearMap["total"]) * 100),
                        ];
                        break;
                    }
                }
                $map["years"][] = $yearMap;
            }
            $map["max"] = $max;
            echo json_encode($map);
            break;

        case "countByDoy":
            ksort($analysis[$report]);
            $map = [];
            foreach($analysis[$report] as $dayOfYear => $count) {
                $adjusted = (int) $dayOfYear - 1;
                $map[] = [
                    "doy" => $dayOfYear, 
                    "count" => $count, 
                    "datestamp" => DateTimeImmutable::createFromFormat("Y z", "2017 $adjusted")->format("F d"),
                    "pct" => floor((float) ($count / $analysis[$report][185]) * 100),
                ];
            }
            echo json_encode($map);
            break;

        case "countByState":
            $map = [];
            foreach($analysis[$report] as $state => $stats) {
                $map[] = [
                    "state" => $state,
                    "count" => $stats["amount"],
                    "acresBurned" => round($stats["acresBurned"], 1),
                    "pct" => floor((float) ($stats["acresBurned"] / $analysis[$report]["AK"]["acresBurned"]) * 100),
                ];
            }
            echo json_encode($map);
            break;

        default:
            echo json_encode($analysis);
            break;
        }
    }
    catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

