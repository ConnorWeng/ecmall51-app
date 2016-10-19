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

    function add() {
        if (!IS_POST) {
            $this->_ajax_error(400, NOT_POST_ACTION, 'not a post action');
            return;
        }

        $consignee = $this->_make_sure_string('consignee', 60, '');
        $region_id = $this->_make_sure_numeric('region_id', 0);
        $region_name = $this->_make_sure_string('region_name', 255, '');
        $address = $this->_make_sure_string('address', 255, '');
        $zipcode = $this->_make_sure_numeric('zipcode', 0);
        $phone = $this->_make_sure_numeric('phone_mob', 0);

        $data = array(
            'user_id'       => $this->visitor->get('user_id'),
            'consignee'     => $consignee,
            'region_id'     => $region_id,
            'region_name'   => $region_name,
            'address'       => $address,
            'zipcode'       => $zipcode,
            'phone_mob'     => $phone);

        $data = $this->_check_region($data);
        if(empty($data)) {
            return;
        }
        $model_address =& m('address');
        if (!($address_id = $model_address->add($data))) {
            $this->_ajax_error(500, ADD_ADDRESS_ERROR, $model_address->get_error());
            return;
        }

        echo ecm_json_encode(array(
            'success' => true));
    }

    private function _check_region($data) {
        $model_region =& m('region');
        $regionArr = $model_region->get_layer($data['region_id']);
        $region_name ='';

        if(!$data['region_id']) {
            $this->_ajax_error(400, REGION_ILLEAGE, '地址无效1');
            return;
        }
        if(!$model_region->isleaf($data['region_id'])) {
            $this->_ajax_error(400, REGION_ILLEAGE, '地址无效2');
            return;
        }
        foreach ($regionArr as $region) {
            if(strpos($data['region_name'],$region['region_name'])===false) {
                $this->_ajax_error(400, REGION_ILLEAGE, '地址无效');
                return;
            }
            $region_name .= $region['region_name'].' ';
        }
        if(!preg_match('/^1[34578][0-9]{9}$/',$data['phone_mob'])) {
            var_dump($data['phone_mob']);
            $this->_ajax_error(400, PHONE_ILLEAGE, '手机号无效');
            return;
        }
        if(!empty($data['zipcode'])) {
            if(!preg_match('/\d{6}/',$data['zipcode'])||preg_match('/\d{7,}/',$data['zipcode'])) {
                $this->_ajax_error(400, ZIPCODE_ILLEAGE, '邮政编码无效');
                return;
            }
        }
        if (empty($data['consignee'])) {
            $this->_ajax_error(400, CONSIGNEE_REQUIRED, '收货人姓名必须填写');
            return ;
        }
        if (empty($data['address'])) {
            $this->_ajax_error(400, ADDRESS_REQUIRED, '收货人地址必须填写');
            return ;
        }

        $data['region_name'] = $region_name;
        return $data;
    }

    function get_regions() {
        $pid = $this->_make_sure_numeric('pid', -1);
        $layer = $this->_make_sure_numeric('layer', -1);
        if ($pid === -1 || $layer === -1) {
            $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
        } else {
            $mod_region =& m('region');
            $regions = $mod_region->get_list($pid);
            foreach ($regions as $key => $region)
            {
                $regions[$key]['region_name'] = htmlspecialchars($region['region_name']);
                $regions[$key]['layer'] = $layer;
            }
            echo ecm_json_encode(array_values($regions));
        }
    }
}

?>