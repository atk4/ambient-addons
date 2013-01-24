<?php
namespace tools;

class Controller_FormAsterisk extends \AbstractController {
    function init(){
        parent::init();
        if (!isset($this->owner->asterixified)){
            $this->owner->asterixified = true;
            $this->owner->js(true)->find("label[class=mandatory]")->append("<span style=\"color:red\">*</span>");
        }
    }
}

