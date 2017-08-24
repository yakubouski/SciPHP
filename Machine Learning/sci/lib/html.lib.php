<?php
class Html
{
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
        table {
            min-width: 20vw;
            border-collapse: collapse;
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
                color: #808080;
                position: relative;
                white-space: nowrap;
                &:before
                {
                    display: inline-block;
                    float:left;
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
                padding: 0 4px;
            }
            th {
                border-bottom-width: 2px;
            }
            td.th {
                border-right-width: 2px;
                color: #808080;
                text-align: right;
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