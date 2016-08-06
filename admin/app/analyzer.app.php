<?php

/**
 *    数据分析控制器
 *
 *    @author    xiaoweng
 *    @usage    none
 */
class AnalyzerApp extends BackendApp
{
    /**
     *    一键上传
     *
     *    @author    xiaoweng
     *    @return    void
     */
    function yjsc_analyzer()
    {
        $before7days = date('Ymd', strtotime('-7 day'));
        $store_mod = &m('store');
        $top50_stores = $store_mod->db->getAll('select s.store_name, s.im_qq, s.mk_name, s.dangkou_address, s.tel, s.auto_sync, a.yjsc from (select store_id, sum(count) yjsc from analyze_yjsc where date > '.$before7days.' group by store_id) a, ecm_store s where a.store_id = s.store_id order by a.yjsc DESC limit 50');
        $this->assign('before7days', strval($before7days));
        $this->assign('top50_stores', $top50_stores);
        $this->display('yjsc_analyzer.html');
    }

}

?>
