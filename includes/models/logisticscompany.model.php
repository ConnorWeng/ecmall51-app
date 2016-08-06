<?php

class LogisticscompanyModel extends BaseModel {
    var $table = 'logistics_company';
    var $prikey = 'id';

    function get_company_code($dl_id) {
        $sql = "select * from ecm_delivery d, ecm_logistics_company c where d.dl_id = ".$dl_id." and d.dl_name = c.name";
        $res = $this->db->query($sql);
        $row = $this->db->fetchRow($res);
        if ($row) {
            return $row['code'];
        } else {
            return false;
        }
    }
}