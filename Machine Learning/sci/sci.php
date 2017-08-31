<?php
namespace Sci {
    include_once 'include/std.php';

    function Lib(...$Libs) {
        foreach($Libs as $lib) {
            include_once 'lib/'.strtolower($lib).'.lib.php';
        }
    }

    function Import($className) {
        static $BaseDir = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        (file_exists($fileName = $BaseDir.strtolower($className).'.php')) && require_once($fileName);
    }
    spl_autoload_register('Sci\Import');
}