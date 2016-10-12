<?php

class Mobile_addressApp extends Mobile_frontendApp {
    private $_address_mod = null;

    function __construct() {
        parent::__construct();
        $this->_address_mod =& m('address');
    }

    function index() {
        $address_list = $this->_address_mod->find(array(
            'conditions' => 'user_id = '.$this->visitor->get('user_id'),
            'fields' => '*',
            'index_key' => false));
        echo ecm_json_encode($address_list);
    }
}

?>