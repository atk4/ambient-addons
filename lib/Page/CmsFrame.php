<?php
namespace cms;
/**
  * Implements editing capabilities of CMS
  */
class Page_CmsFrame extends Page_CmsCore{
    function init(){
        parent::init();
        if(!$_GET['cms_page']){
            throw $this->exception('cms_page must be set');
        }
        //$this->initPage();
    }
}

