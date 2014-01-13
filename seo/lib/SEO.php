<?php
namespace seo;

class SEO extends \AbstractController {
    function init(){
        parent::init();
        $this->page = $this->api->page;
        $this->api->addHook("pre-render", array($this, "setMeta"));
    }
    function setMeta($o){
        $m=$this->add("seo/Model_SEO")->addCondition("page", $this->page)->tryLoadAny();
        if ($m->loaded()){
            $fields = array("title", "keywords", "description");
            foreach ($fields as $field){
                $this->api->template->append("meta_$field", $m[$field]);
            }
        } else {
            $m->save();
        }
    }
}
