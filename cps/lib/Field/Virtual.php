<?php

namespace cps;
class Field_Virtual extends \Field {
    function init(){
        parent::init();
        $this->editable(false);
        $this->visible(false);
    }
}
