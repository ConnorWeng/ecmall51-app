<?php
return array (
  'version' => '1.0',
  'subject' => '{$site_name}提醒:您有一个新订单需要处理',
  'content' => '【51zwd】亲，您的51订单{$order.order_sn}已经发货，收货人：{$order.consignee}，物流信息:{$order.dl_name}，{$order.invoice_no}！',
);
?>