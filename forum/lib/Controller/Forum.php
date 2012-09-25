<?php
namespace forum;
class Controller_Forum extends \AbstractController {
    function init(){
        parent::init();
        $this->api->addLocation('ambient/forum',array(
                    'template'=>'templates/default',
                    'css'=>'templates/default/css',
                    'js'=>'templates/js'
                    )
                )
                ->setParent($this->api->pathfinder->base_location);
    }
}
 
