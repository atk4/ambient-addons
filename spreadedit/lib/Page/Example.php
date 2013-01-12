<?php
namespace spreadedit;

class Page_Example extends \Page {
    function initMainPage(){
        $model = "Foo";
        $this->add("View_Info")->set("Spread Editor for any model");
        $v=$this->add("spreadedit/View_Edit");
        $v->setModel($model);
        $v->add("Paginator", null, "paginator")->ipp(10)->setSource($v->getModel());
    }
}
