<?php

function Post($curlPost, $url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
    $return_str = curl_exec($curl);
    curl_close($curl);
    return $return_str;
}

function Get($sms) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $sms);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
    $output = curl_exec($curl);
    echo $output;
}

$username = $_GET['username'];
echo 'username is : ' . $username;
$u1 = 'http://ecmall.51zwd.com/index.php?app=member&act=register';
$name = 'test-'.$username;
$data = 'agree=true&password=' . $name . '&password_confirm=' . $name . '&user_name=' . $name . '&email=' . $name . '@qq.com' . '&batchcreate=1';
$user_id = Post($data, $u1);
echo 'user_id is ' . $user_id;
//创建店铺
//$user_id='23';
$u1 = 'http://ecmall.51zwd.com/admin/index.php?app=fakestore&act=add&user_id=' . $user_id;
$data = 'store_name=' . $_POST['store_name'];
$data.= '&owner_name=' . $user_id;
$data.= '&owner_card=';
$data.= '&region_id=2';
$data.= '&region_name=中国';
$data.= '&address=';
$data.= '&zipcode=';
$data.= '&tel=';
$data.= '&sgrade=1';
$data.= '&end_time=';
$data.= '&state=1';
$data.= '&recommended=';
$data.= '&sort_order=';
$data.= '&add_time=';
$data.= '&domain=';
//我添加的
$data.='&shop_mall='. $_POST['shop_mall'];
$data.='&floor='. $_POST['floor'];
$data.='&address='. $_POST['address'];
$data.='&see_price='. $_POST['see_price'];
$data.='&im_qq='. $_POST['im_qq'];
$data.='&im_ww='. $_POST['im_ww'];
//$data.='&tel='. $_POST['tel'];
$data.='&shop_http='. $_POST['shop_http'];
$data.='&has_link='. $_POST['has_link'];
$data.='&serv_refund='. $_POST['serv_refund'];
$data.='&serv_exchgoods='. $_POST['serv_exchgoods'];
$data.='&serv_sendgoods='. $_POST['serv_sendgoods'];
$data.='&serv_probexch='. $_POST['serv_probexch'];
$data.='&serv_deltpic='. $_POST['serv_deltpic'];
$data.='&serv_modpic='. $_POST['serv_modpic'];
$data.='&serv_golden='. $_POST['serv_golden'];
echo 'data is :'.$data;
Post($data, $u1);
?>
