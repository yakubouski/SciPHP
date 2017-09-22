<?php
namespace Sci\Math\Classification;
class DecisionTree
{
    public $TreeNodes,$TreeDepth;
    public function Training($DataSet,$Attributes=[],$Attribute=0,$DepthThreshold=50,$EntropyThreshold=0.01,$ItemsCountThreshold=1) {
        $Predictions = $this->Predictions(empty($Attributes) ?array_keys($DataSet[0]) : $Attributes);
        $this->TreeDepth = 0;
        $this->TreeNodes = [];
        $this->buildDecisionTree($this->TreeNodes,$DataSet, array_keys($DataSet), $Predictions,$Attribute,0,$this->TreeDepth,$DepthThreshold,$EntropyThreshold,$ItemsCountThreshold);
    }
    private function Predictions($Attributes) {
        $Predictions = [
            '=='    =>function(&$l,&$r){ return $l==$r; },
            '>'     =>function(&$l,&$r){ return $l>$r; },
            '<'     =>function(&$l,&$r){ return $l<$r; },
            '>='    =>function(&$l,&$r){ return $l>=$r; },
            '<='    =>function(&$l,&$r){ return $l<=$r; },
            '!='    =>function(&$l,&$r){ return $l!=$r; }];
        $List = [];
        foreach($Attributes as $v) {
            if(is_array($v)) {
                $List[$v[0]] = [$v[1],$Predictions[$v[1]]??$Predictions[$v['>=']]];
            }
            else {
                $List[$v] = ['>=',$Predictions['>=']];
            }
        }
        return $List;
    }

    private function split(&$DataSet,$Indexes,$Attribute,$Pivot,$Prediction,&$Left,&$Right) {
        $Left = [];
        $Right = [];
        foreach($Indexes as $i) {
            $Prediction($DataSet[$i][$Attribute],$Pivot) ? ($Left[] = $i) : ($Right[] = $i);
        }
    }
    private function calcEntropy(&$DataSet,&$Indexes,$Attribute) {
        $Counter = [];
        $TotalItems = count($Indexes);
        foreach($Indexes as $i) {
            !isset($Counter[$DataSet[$i][$Attribute]]) && $Counter[$DataSet[$i][$Attribute]] = 0;
            $Counter[$DataSet[$i][$Attribute]] += 1;
        }
        $Entropy = 0;
        foreach($Counter as $cv) {
            $p = $cv / $TotalItems;
            $Entropy += -$p * log($p);
        }
        return $Entropy;
    }

    private function nodeBuild(&$DataSet,$Indexes,&$Attributes,$Attribute,&$SplitLeft,&$SplitRight,$Entropy) {

        $Entropy = is_null($Entropy) ? $this->calcEntropy($DataSet,$Indexes,$Attribute) : $Entropy;
        $Result = false;

        $SplitRight = ['Entropy'=>0,'Indexes'=>[]];
        $SplitLeft = ['Entropy'=>0,'Indexes'=>[]];

        $Node = [
            'Gain' => 0,
            'Indexes' => $Indexes,
            'Left' => null,
            'Right' => null,
            'Attribute' => null,
            'Predict' => '',
            'Pivot' => 0,
        ];

        $Pivot = null;
        $Gain = 0;
        $IsChecked = [];

        foreach($Indexes as $i) {
            $item =& $DataSet[$i];
            foreach($Attributes as $Attr => list($Predict,$Fn)) {
                $Pivot = $item[$Attr];
                if($Attr == $Attribute || isset($IsChecked["{$Attr}:{$Pivot}"])) continue;
                $IsChecked["{$Attr}:{$Pivot}"] = 1;

                $this->split($DataSet,$Indexes,$Attr,$Pivot,$Fn,$LeftIndexes,$RightIndexes);

                $LeftEntropy = $this->calcEntropy($DataSet,$LeftIndexes,$Attribute);
                $RightEntropy = $this->calcEntropy($DataSet,$RightIndexes,$Attribute);

                $newEntropy = 0;
                $newEntropy += $LeftEntropy * count($LeftIndexes);
                $newEntropy += $RightEntropy * count($RightIndexes);
                $newEntropy /= count($Indexes);
                $currGain = $Entropy - $newEntropy;

                if ($currGain > $Gain) {
                    $Node['Gain'] = $Gain = $currGain;
                    $Node['Attribute'] = $Attr;
                    $Node['Predict'] = $Predict;
                    $Node['Pivot'] = $Pivot;
                    $SplitLeft = ['Entropy'=>$LeftEntropy,'Indexes'=>$LeftIndexes];
                    $SplitRight = ['Entropy'=>$RightEntropy,'Indexes'=>$RightIndexes];
                    $Result = true;
                }
            }
        }
        return $Result ? $Node : false;
    }

    private function calcRange($DataSet,$Indexes,$Attribute) {
        $FrequentCount = 0; $FrequentValue = 0; $Counter = [];
        foreach($Indexes as $i) {
            !isset($Counter[$DataSet[$i][$Attribute]]) && $Counter[$DataSet[$i][$Attribute]] = 0;
            ++ $Counter[$DataSet[$i][$Attribute]];

            if($Counter[$DataSet[$i][$Attribute]] > $FrequentCount) {
                $FrequentCount = $Counter[$DataSet[$i][$Attribute]];
                $FrequentValue = $DataSet[$i][$Attribute];
            }
        }
        return ['MostFrequentValue'=>$FrequentValue,'Frequencies'=>$Counter];
    }

    private function buildDecisionTree(&$Leaf,&$DataSet,$Indexes,&$Attributes,$Attribute,$Depth,&$NestedDepth,$DepthThreshold,$EntropyThreshold,$ItemsCountThreshold,$Entropy=null) {

        if(($Node = $this->nodeBuild($DataSet,$Indexes,$Attributes,$Attribute,$SplitLeft,$SplitRight,$Entropy)) !== false) {
            $Leaf['Depth'] = $Depth;
            $Leaf['Attribute'] = $Node['Attribute'];
            $Leaf['Predict'] = $Node['Predict'];
            $Leaf['Pivot'] = $Node['Pivot'];
            $Leaf['Left'] = [];
            $Leaf['Right'] = [];

            $NestedDepth = max($NestedDepth,$Depth+1);

            if($SplitLeft['Entropy'] > $EntropyThreshold && $Depth < $DepthThreshold && count($SplitLeft['Indexes']) > $ItemsCountThreshold) {
                $Leaf['Left']['Branch']['%'] = count($SplitLeft['Indexes']) / count($Indexes);
                $this->buildDecisionTree($Leaf['Left']['Branch'],$DataSet,$SplitLeft['Indexes'],$Attributes,$Attribute,$Depth+1,$NestedDepth,$DepthThreshold,$EntropyThreshold,$ItemsCountThreshold,$SplitLeft['Entropy']);
            }
            else {
                $Leaf['Left']['Category'] = $this->calcRange($DataSet,$SplitLeft['Indexes'],$Attribute);
            }

            if($SplitRight['Entropy'] > $EntropyThreshold && $Depth < $DepthThreshold && count($SplitRight['Indexes']) > $ItemsCountThreshold) {
                $Leaf['Right']['Branch']['%'] = count($SplitRight['Indexes']) / count($Indexes);;
                $this->buildDecisionTree($Leaf['Right']['Branch'],$DataSet,$SplitRight['Indexes'],$Attributes,$Attribute,$Depth+1,$NestedDepth,$DepthThreshold,$EntropyThreshold,$ItemsCountThreshold,$SplitRight['Entropy']);
            }
            else {
                $Leaf['Right']['Category'] = $this->calcRange($DataSet,$SplitRight['Indexes'],$Attribute);
            }
        }
        else {
            $Leaf['Category'] = $this->calcRange($DataSet,$SplitRight['Indexes'],$Attribute);
        }
    }
}