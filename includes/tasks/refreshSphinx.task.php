<?php

/**
 *    自动交易
 *
 *    @author    Garbin
 *    @usage    none
 */
class RefreshSphinxTask extends BaseTask
{
    function run()
    {
//       header("Location: ./index.php?app=default&act=refreshSphinx");
       
       $url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?app=default&act=refreshSphinx";
       $result = @json_decode(Get($url),true);
       $result=var_export($result,true);
       Log::write($result);
    }

}

?>
