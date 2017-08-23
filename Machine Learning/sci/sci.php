<?php
namespace Sci {
    function Import($className) {
        static $BaseDir = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        (file_exists($fileName = $BaseDir.strtolower($className).'.php')) && require_once($fileName);
    }
}

namespace {
    ini_set('display_errors','on');
    ini_set('display_startup_errors','on');

    include_once 'include/typelib.php';
    include_once 'include/dblib.php';

    spl_autoload_register(function($className){
        \Sci\Import($className);
    });
}