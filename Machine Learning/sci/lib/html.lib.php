<?php
class Html
{
    static public function Json($Val) {
        print '<pre>'.json_encode($Val,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).'</pre>';
    }
    static public function Formula($Val) {
        print preg_replace([
            '/{(.*?)\|(.*?)}/',
            '/~(\w+)/',
        ],[
            '<v><sup>\1</sup><sub>\2</sub></v>',
            '<v overline-wavy>\1</v>',
        ],$Val);
    }

    static public function Table($Caption,$Columns,...$Tables) {
        ob_start();
        $body = '';
        $sepColumns = [];
        $prevSep = 0;
        if(!empty($Tables)) {
            ob_start();
            $maxRows = 0;
            foreach($Tables as $t) {
                $prevSep = $prevSep + $t->ColumnCount();
                $sepColumns[$prevSep] = 1;
                $maxRows = max($maxRows,$t->RowCount());
            }
            for($r=0;$r<$maxRows;$r++) {
                print '<tr><td class="th">'.($r+1).'</td>';
                foreach($Tables as $t) {
                    if($t->RowExists($r)) {
                        for($c=0;$c<($lCol = $t->ColumnCount());$c++) {
                            $val = $t->Cell($r,$c);
                            print '<td'.($c==$lCol-1?' class="sep"':'').'>'.(is_float($val) ? number_format($val,4,',',''):$val).'</td>';
                        }
                    }
                    else {
                        for($c=0;$c<($lCol = $t->ColumnCount());$c++) {
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
        foreach($Columns as $nc=>$c) {
            print '<th width="40"'.(isset($sepColumns[$nc+1])?' class="sep"':'').'>';
            self::Formula($c);
            print '</th>';
        }
        print '</tr></thead><tbody>'.$body;

        print '<table>'.ob_get_clean().'</tbody></table>';
    }

    static private function LessComplie($lessSrc) {
        static $less;
        if(is_null($less)) {
            include_once "3th/lessc/lessc.php";
            $less = new \lessc();
        }
        return $less->compile($lessSrc);
    }
    static public function Main($MainFn,...$Args) {
        ob_start();
        call_user_func_array($MainFn,$Args);
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
    <script>
        $(document).on('click', 'table>caption', function () {
            $(this).closest('table').toggleClass('collapse');
        });
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
                    margin-bottom: 16px;
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
}