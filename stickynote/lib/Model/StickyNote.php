<?php

namespace stickynote;

class Model_StickyNote extends \Model_Table {
    public $table = "stickynote";
    function init(){
        parent::init();
        $this->addField("name");
        $this->addField("content")->type("text")->mandatory("Must be filled");
        $this->addField("url");
        $this->addField("is_global")->type("boolean")->enum(array("Y","N"));
        $this->addField("color")
            ->listData(array(
                "yellow" => "yellow",
                "red" => "red",
                "pink" => "pink",
                "blue" => "blue",
                "green" => "green"
            ));
        $this->addField("x");
        $this->addField("y");
        $this->addField("width");
        $this->addField("height");
        $this->addField("created_dts")->type("datetime")->defaultValue(date("Y-m-d H:i:s"));
        /* optional user id */
    }
}
