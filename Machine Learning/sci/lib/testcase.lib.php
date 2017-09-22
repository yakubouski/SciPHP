<?php
class TestCase {
    static function Run($TestFile,$Args=[]) {
        ob_start();
        {
            @extract($Args,EXTR_REFS);
            include ($TestFile);
        }
        $Result = ob_get_clean();
        ob_start();
        header('Content-Type: text/html; charset=UTF-8');
        $HTML=<<<"HTML"
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed:300,400&amp;subset=cyrillic,cyrillic-ext" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha256-k2WSCIexGzOj3Euiig+TlR8gA0EmPjuc79OEeY5L45g=" crossorigin="anonymous"></script>
        <script src="https://www.gstatic.com/charts/loader.js"></script>
        <script>
            $(document).on('click', 'table>caption', function () {
                $(this).closest('table').toggleClass('collapse');
            });
        </script>
        <script src="/test/js/dt.js"></script>
<script >

var data =
    [{ person: 'Homer', hairLength: 0, weight: 250, age: 36, sex: 'male' },
    { person: 'Marge', hairLength: 10, weight: 150, age: 34, sex: 'female' },
    { person: 'Bart', hairLength: 2, weight: 90, age: 10, sex: 'male' },
    { person: 'Lisa', hairLength: 6, weight: 78, age: 8, sex: 'female' },
    { person: 'Maggie', hairLength: 4, weight: 20, age: 1, sex: 'female' },
    { person: 'Abe', hairLength: 1, weight: 170, age: 70, sex: 'male' },
    { person: 'Selma', hairLength: 8, weight: 160, age: 41, sex: 'female' },
    { person: 'Otto', hairLength: 10, weight: 180, age: 38, sex: 'male' },
    { person: 'Krusty', hairLength: 6, weight: 200, age: 45, sex: 'male' }];


var config = {
    // обучающая выборка
    trainingSet: data,

    // название атрибута, который содержит название класса, к которому относится тот или иной элемент обучающей выборки
    categoryAttr: 'sex',

    // масив атрибутов, которые должны игнорироваться при построении дерева
    ignoredAttributes: ['person']

    // при желании, можно установить ограничения:

    // максимальная высота дерева
    // maxTreeDepth: 10

    // порог энтропии, при достижении которого следует остановить построение дерева
    // entropyThrehold: 0.05

    // порог количества элементов обучающей выборки, при достижении которого следует остановить построение дерева
    // minItemsCount: 3
};

// построение дерева принятия решений:
var decisionTree = new dt.DecisionTree(config);
</script>
        <style type="text/less">
            body {
                font-family: 'Roboto Condensed', sans-serif;
                font-size: 14px;
                font-weight: 300;
            }

            v {
                display: inline-block;
                position: relative;
                &[overline] { text-decoration: overline; }
                &[overline-wavy] { text-decoration: overline; text-decoration-style: dotted;}
                &[underline] { text-decoration: underline; }
                &[strike] { text-decoration: underline; }

                &>sub {
                    font-size: 0.7em;
                    font-weight: 300;
    position: relative;
    bottom: -0.05em;
                }
                &>sup {
                    font-size: 0.7em;
                        font-weight: 300;
    position: absolute;
    top: -0.2em;
                }
            }

            table {
                min-width: 20vw;
                border-collapse: collapse;
                max-width: 95vw;
                &+* {
                        margin-top: 16px;
                }
                caption,th,td.th {
                    background-color: #F0F0F0;
                    font-weight: 400;
                }
                &.collapse {
                    &>tbody { display: none; }
                    caption:before {content: '▴'; }
                }
                caption {
                    text-align: left;
                    padding: 4px;
                    text-transform: uppercase;
                    color: #F5F5F5;
    background-color: #D0D0D0;
                    position: relative;
                    white-space: nowrap;
                    &:before
                    {
                        display: inline-block;
                        float:left;
    color: #808080;
                        width: 18px; height: 18px;
    box-sizing: border-box;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #D0D0D0;
    background-color: white;
    line-height: 16px;
    margin-right: 4px;
                        font-size: 16px;
                        left: 0;
                        top: 0;
                        content: '▾';
                        position: relative;
                    }
                }
                th, td {
                    border: 1px solid #D0D0D0;
                    padding: 0 2px;

                    &.sep {

                        & + td,& + th {
    border-left-width: 2px;
                        }
                    }

                }
                th {
                    border-bottom-width: 2px;
                    padding: 2px;
                }
                th.th {
                    border-right-width: 2px;
                }
                td.th {
                    border-right-width: 2px;
                    color: #808080;
                    text-align: center;
    font-size: 0.75em;
                }
                td:not(.th) {
    font-size: 0.9em;
    padding: 2px;
    text-align: center;
                }
            }

        </style>
    </head>
    <body>
        {$Result}
    </body>
    </html>
HTML;
        print preg_replace_callback_array([
            '%<style(.*?)type="text/less"(.*?)>(.*?)</style>%si'=>function($m) {
                return '<style '.$m[1].'type="text/css"'.$m[2].'>'.self::LessComplie($m[3]).'</style>';
            }
        ],$HTML);
        ob_end_flush();
        exit;
    }
    static function LessComplie($lessSrc) {
        static $less;
        if(is_null($less)) {
            include_once "3th/lessc/lessc.php";
            $less = new \lessc();
        }
        return $less->compile($lessSrc);
    }
}
    function printTable($Caption,$Columns,...$Tables) {
        ob_start();
        $body = '';
        $sepColumns = [];
        $prevSep = 0;
        if(!empty($Tables)) {
            ob_start();
            $maxRows = 0;
            foreach($Tables as $t) {
                $prevSep = $prevSep + count(reset($t));
                $sepColumns[$prevSep] = 1;
                $maxRows = max($maxRows,count($t));
            }
            for($r=0;$r<$maxRows;$r++) {
                print '<tr><td class="th">'.($r+1).'</td>';
                foreach($Tables as $t) {
                    $lCol = count(reset($t));
                    if($r < count($t)) {
                        $c=0;
                        foreach($t[$r] as $val) {
                            print '<td'.($c==$lCol-1?' class="sep"':'').'>'.(is_float($val) ? number_format($val,4,',',''):(is_scalar($val) ? $val: implode(',',$val)) ).'</td>';
                            $c++;
                        }
                    }
                    else {
                        for($c=0;$c<$lCol;$c++) {
                            print '<td'.($c==$lCol-1?' class="sep"':'').'></td>';
                        }
                    }
                }
                print '</tr>';
            }
            $body = ob_get_clean();
        }

        !empty($Caption) && print('<caption>'.$Caption.'</caption>');
        print '<thead><tr><th class="th" width="30">#</th>';
        foreach(empty($Columns) ? range(1,$prevSep,1) : $Columns as $nc=>$c) {
            print '<th width="40"'.(isset($sepColumns[$nc+1])?' class="sep"':'').'>';
            printFormula($c);
            print '</th>';
        }
        print '</tr></thead><tbody>'.$body;

        print '<table>'.ob_get_clean().'</tbody></table>';
    }

    function printChartTree($Tree) {
        $id = 'ChartTree-'.sha1(rand());
        $Tree = json_encode($Tree,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        print <<<"CHART"
<div class="chart" id="$id"  style="width: 900px; height: 500px;"></div>
 <script type="text/javascript">
      google.charts.load('current', {packages:["treemap"]});
      google.charts.setOnLoadCallback(function(){
        var data = google.visualization.arrayToDataTable($Tree);
        var chart = new google.visualization.TreeMap(document.getElementById('$id'));
        chart.draw(data, {
            allowHtml:true,
            maxDepth: 1,
            maxPostDepth: 2,
            minHighlightColor: '#8c6bb1',
            midHighlightColor: '#9ebcda',
            maxHighlightColor: '#edf8fb',
            minColor: '#009688',
            midColor: '#f7f7f7',
            maxColor: '#ee8100',
            headerHeight: 15,
            showScale: true,
            useWeightedAverageForAggregation: true
        });
      });

</script>
CHART;
    }

    function printVector($Caption,$Columns,...$Vectors) {
        ob_start();
        $body = '';
        $sepColumns = [];
        $prevSep = 0;
        if(!empty($Vectors)) {
            ob_start();
            $maxRows = 0;
            foreach($Vectors as $t) {
                $prevSep = $prevSep + $t->Dimension;
                $sepColumns[$prevSep] = 1;
                $maxRows = max($maxRows,$t->Rows);
            }
            for($r=0;$r<$maxRows;$r++) {
                print '<tr><td class="th">'.($r).'</td>';
                foreach($Vectors as $t) {
                    if($r<$t->Rows) {
                        for($c=0;$c<($lCol = $t->Dimension);$c++) {
                            $val = $t($c,$r);
                            print '<td'.($c==$lCol-1?' class="sep"':'').'>'.(is_float($val) ? number_format($val,4,',',''):$val).'</td>';
                        }
                    }
                    else {
                        for($c=0;$c<($lCol = $t->Dimension);$c++) {
                            print '<td'.($c==$lCol-1?' class="sep"':'').'></td>';
                        }
                    }
                }
                print '</tr>';
            }
            $body = ob_get_clean();
        }

        !empty($Caption) && print('<caption>'.$Caption.'</caption>');
        print '<thead><tr><th class="th" width="30">#</th>';
        foreach(empty($Columns) ? range(1,$prevSep) : $Columns as $nc=>$c) {
            print '<th width="40"'.(isset($sepColumns[$nc+1])?' class="sep"':'').'>';
            printFormula($c);
            print '</th>';
        }
        print '</tr></thead><tbody>'.$body;

        print '<table>'.ob_get_clean().'</tbody></table>';
    }

    function printJson($Val) {
        print '<pre>'.json_encode($Val,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).'</pre>';
    }
    function printFormula($Val) {
        print preg_replace([
            '/{(.*?)\|(.*?)}/',
            '/~(\w+)/',
        ],[
            '<v><sup>\1</sup><sub>\2</sub></v>',
            '<v overline-wavy>\1</v>',
        ],$Val);
    }
