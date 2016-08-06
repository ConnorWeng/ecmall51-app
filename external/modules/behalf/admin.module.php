<?php

class BehalfModule extends AdminbaseModule
{   
    var $_behalf_mod;

    function __construct()
    {
        $this->BehalfModule();
    }

    function BehalfModule()
    {
        parent::__construct();

       
        $this->_behalf_mod =& m("behalf");
    }

    function index()
    {
       echo "hi,behalf module admin!";
    }

   
}

?>