<?php

class FakeSetting {
    function getAll() {
        return array();
    }
}

function &af($type, $params = array()) {
    $fs = new FakeSetting();
    return $fs;
}

function &cache_server() {
    $a = array();
    return $a;
}

function didiOrder($token,$data) {
    return '';
}


?>