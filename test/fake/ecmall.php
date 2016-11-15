<?php

define('CHARSET', 'utf8');

if (!function_exists('m')) {
    function &m($model_name, $params = array(), $is_new = false) {
        $model_class = ucfirst($model_name).'Model';
        return new $model_class();
    }
}

if (!function_exists('db')) {
    class FakeDB {
        function query() {

        }
    }

    function &db() {
        $db = new FakeDB();
        return $db;
    }
}

?>