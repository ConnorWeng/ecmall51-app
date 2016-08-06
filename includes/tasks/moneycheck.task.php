<?php

/**
 *    自动交易
 *
 *    @author    Garbin
 *    @usage    none
 */
class MoneycheckTask extends BaseTask
{
    function run()
    {
//       header("Location: ./index.php?app=default&act=checkMoney2");
       $url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?app=default&act=checkMoney2";
       $result = @json_decode(Get($url),true);
       $result=var_export($result,true);
       Log::write($result);
    }

}

?>
