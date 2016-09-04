<?php

define('ROOT_PATH', dirname(__FILE__).'/..');

require_once(ROOT_PATH.'/test/fake/ecapp.base.php');

function ajax_method_return_json($instance, $method, ...$params) {
    ob_start();
    call_user_func(array($instance, $method), ...$params);
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