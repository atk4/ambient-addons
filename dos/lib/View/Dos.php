<?php
namespace dos;

class View_Dos extends \AbstractView {
    public $width = 80;
    public $height = 25;
    public $n = 0;
    public $tree;
    public $dir = ".";
    public $dir_stack = ["."];
    function init(){
        parent::init();
    }
    function render(){
        $r = $this->template->cloneRegion("row");
        $c = $this->template->cloneRegion("cell");
        $this->template->del("row");
        $this->template->del("cell");
        $a = "";
        $dy = 0;
        if ($this->n >= $this->height){
            $dy = $this->n-$this->height;
        }
        for ($y = 0; $y < $this->height; $y++){
            $out = "";
            for ($x = 0; $x < $this->width; $x++){
                $c->set("x", $x);
                $c->set("y", $y);
                $c->setHTML("c", isset($this->screen[$y+$dy][$x])?("&#0".$this->screen[$y+$dy][$x][0].";"):'');
                if (isset($this->screen[$y+$dy][$x][1])){
                    $c->setHTML("style", "color: " . $this->screen[$y+$dy][$x][1]);
                } else {
                    $c->set("style", "");
                }
                $out .= $c->render();
            }
            $a .= $r->setHTML("content", $out)->render();
        }
        $this->template->setHTML("area", $a);
        return parent::render();
    }
    function defaultTemplate(){
        $l=$this->api->locate('addons',__NAMESPACE__,'location');
        $lp=$this->api->locate('addons',__NAMESPACE__);

        $this->api->addLocation($lp,array(
                    'template'=>'templates/default',
                    'css'=>'templates/default/css',
                    'js'=>'templates/js'
                    )
                )
                ->setParent($l);


        return array("view/dos");
    }
    function demo(){
        for ($y = 0; $y < $this->height; $y++){
            for ($x = 0; $x < $this->width; $x++){
                $this->setCell($x,$y,$y*25+$x);
            }
        }
    }
    function setCell($x,$y,$code, $color=null){
        $this->screen[$y][$x] = array(($code>255)?($code%255):$code, $color);
    }
    function printStrN($string=null, $color=null){
        $this->printStr($string, 0, $this->n++, $color);
    }
    function printStr($string=null, $x=0,$y=0, $color=null){
        $l = strlen($string);
        $dx = 0;
        $dy = 0;
        for ($i = 0; $i < $l; $i++){
            if ($x+$i+$dx >= $this->width){
                $dx -= 80;
                $dy += 1;
                $this->n++;
            }
            $this->setCell($x+$i+$dx, $y+$dy, ord($string[$i]),$color);
        }
    }
    function clear(){
        $this->screen = array();
    }
    function dirPrint(){
        $c = $this->dir_stack;
        unset($c[0]);
        return implode("\\", $c);
    }
    function line($location){
        if ($location == "top" || $location == "bottom" || $location == "middle"){
            $o = "";
            for ($i = 0; $i < 78; $i++){
                $o .= chr(205);
            }
            if ($location == "top"){
                return chr(201) . $o . chr(187);
            } else if ($location == "bottom") {
                return chr(200) . $o . chr(188);
            } else if ($location == "middle") {
                return chr(204) . $o . chr(185);
            }
        } else {
            return "n/a";
        }
    }
    function lineWrap($stuff){
        $len = strlen($stuff);
        $len = $len / 76;
        $out = "";
        for ($i = 0; $i < $len; $i++){
            $out .= chr(186) . " " . ($chunk=substr($stuff, 0, 76));
            if (strlen($chunk) < 76){
                for ($j = strlen($chunk); $j < 76; $j++){
                    $out .= " ";
                } 
            }
            $out .= " " . chr(186);
            $stuff = substr($stuff, 76);
        }
        return $out;
    }
    function dirSelect($dir){
        if ($dir == ".."){
            if($this->dir != '.'){
                array_pop($this->dir_stack);
                $this->dir = $this->dir_stack[count($this->dir_stack) - 1];
            }
        } else {
            if (in_array($dir, $this->tree[$this->dir]["dir"])){
                array_push($this->dir_stack, $dir);
                $this->dir = $dir;
            } else {
                $this->printStrN("Directory $dir does not exist");
            }
        }

    }
    function proc_dir($params){
        $this->printStrN(" Contents of folder C:\\" . $this->dirPrint());
        if (isset($this->tree[$this->dir]["dir"])){
            foreach ($this->tree[$this->dir]["dir"] as $dir){
                $this->printStrN("01.01.2013  09:00    <DIR>        $dir");
            }
        }
        if (isset($this->tree[$this->dir]["files"])){ 
            foreach ($this->tree[$this->dir]["files"] as $file){
                $this->printStrN("01.01.2013  09:00                 $file");
            }
        } 
        $this->printStrN();
    }
    function proc_cat($params){
        $file = $params[1];
        if ($file[0] == "\\"){
            $file = substr($file,1);
        }
        $dir = $this->dirPrint();
        $file = preg_replace("/\\\/", "/", ($dir?($dir . "\\"):"") . $file);
        if (preg_match("/config/", $file)){
            $this->printStrN(" Access denied");
            $this->printStrN("");
            return;
        }
        if (file_exists($file)){
            $f = file($file);
            foreach ($f as $row){
                $this->printStrN($row);
            }
        } else {
            $this->printStrN(" File $file not found");
        }
        $this->printStrN("");
    }
    function proc_reset(){
        $this->api->memorize("buf", array());
        $this->js(true)->reload();
    }
    function proc_cd($params){
        $target = explode("\\", $params[1]);
        foreach($target as $act){
            $this->dirSelect($act);
        }
    }
}
