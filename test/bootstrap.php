<?php

define('ROOT_PATH', dirname(__FILE__).'/..');

require_once(ROOT_PATH.'/test/fake/ecapp.base.php');

function ajaxFunctionReturnJson($function, ...$params) {
    ob_start();
    $function(...$params);
    $json = ob_get_contents();
    ob_end_clean();
    return $json;
}

class TestCase extends PHPUnit_Framework_TestCase {

    function stub($class_name, ...$configs) {
        $stub = $this->getMockBuilder($class_name)->getMock();
        for ($i = 0; $i < count($configs); $i += 2) {
            $stub->method($configs[$i])->willReturn($configs[$i+1]);
        }
        return $stub;
    }

}

?>