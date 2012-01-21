<?php
class Page_CmsMode extends Page {
    function init(){
        parent::init();

        session_destroy();
        $a=new ApiWeb($this->api->getConfig("frontend/token"));
        $a->initializeSession(true);
        $a->memorize('cmsediting',true);
        $a->memorize('cmslevel','dev'); // switch to true to have plain cms mode
        header('Location: /');

    }
}
