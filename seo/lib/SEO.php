<?php
namespace seo;

class SEO extends \AbstractController {
    function init(){
        parent::init();
        $page = $this->api->page;
        $m=$this->add("seo/Model_SEO")->addCondition("page", $page)->tryLoadAny();
        if ($m->loaded()){
            $fields = array("title", "keywords", "description");
            foreach ($fields as $field){
                $this->api->template->trySet("meta_$field", $m[$field]);
            }
        } else {
            $m->save();
        }
    }
}
