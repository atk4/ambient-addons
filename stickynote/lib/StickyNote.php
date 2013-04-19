<?php
namespace stickynote;

class StickyNote extends \AbstractController {
    public $can_edit = true;
    public $can_add = true;
    public $can_del = true;
    public $can_resize = true;
    public $can_move = true;
    function init(){
        parent::init();

        $l=$this->api->locate('addons',__NAMESPACE__,'location');
        $lp=$this->api->locate('addons',__NAMESPACE__);

        $this->api->addLocation($lp,array(
                    'template'=>'templates/default',
                    'css'=>'templates/default/css',
                    'js'=>'templates/js'
                    )
                )
                ->setParent($l);
        $this->api->template->appendHTML("js_include", "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $this->api->locateURL('css','stickynote.css') . "\"/>");
        
        $vv = $this->owner->add("View", null, null, array("view/stickies"));
        $vvr = $vv->js()->reload();
        $vv->js(true)->_selector(".sticky-note")->detach();

        $vp = $this->add("VirtualPage");
        if ($this->can_add){
            $this->owner->add("Button")->set("Add Sticky")->addClass("sticky-add")->js("click")
                ->univ()->frameURL("Add Note", $vp->getURL(), array("width" => "400", "dialogClass" => "sticky-note-form"));
        }
        $this->owner->add("Button")->set("Toggle Stickies")->addClass("sticky-toggle")->js("click",
            $this->owner->js()->_selector(".sticky-note")->toggle("fold", 1000)
        );

        $owner = $this->owner;
        $self = $this;
        /* existing */
        $m = $this->add("stickynote/Model_StickyNote");
        $base = $this->api->page;
        $m->_dsql()->where($m->dsql()->expr("url = '[1]' or is_global = 'Y'")->setCustom("1", (string) $base));
        $ref = array();
        foreach ($m as $note){

            $v=$vv->add("View", null, null, array("view/stickynote"));

            $edit = $v->js()->univ()->frameURL("Edit Note", $vp->getURL($note["id"]), array("dialogClass" => "sticky-note-form", "width" => "400"))->_enclose();
            $del = $v->js()->univ()->dialogConfirm("Confirm", "Do you really want to delete?",
                $v->js()->univ()->ajaxec($this->api->url(null, array("note" => $note["id"], "delete" => true)))->_enclose());
            $content = nl2br(htmlspecialchars($note["content"]));
            $v->template->trySetHTML("content", $content);
            $v->template->trySet("created_dts", $note["created_dts"]);
            if ($this->can_edit){
                $v->js(true)->on("dblclick", $edit);
            }
            $v->js(true)->dialog(
                array(
                    "resizable" => $this->can_resize,
                    "dialogClass" => "sticky-note " . $note["color"],
                    "closeOnEscape" => false,
                    "closeText" => "Delete?",
                    "open" => $v->js()->parent()->offset(array("left" => (int)$note["x"], "top" => (int)$note["y"]))->_enclose(),
                    "draggable" => $this->can_move,
                    "dragStop" => $v->js()->univ()->ajaxec($this->api->url(null, array("note" => $note["id"])), array("pos" => $v->js()->parent()->position()))->_enclose(),
                    "resizeStop" => $v->js()->univ()->ajaxec($this->api->url(null, array("note" => $note["id"])), array(
                        "width" => $v->js()->dialog("option", "width"), 
                        "height" => $v->js()->dialog("option", "height")
                    ))->_enclose(),
                    "width" => $note["width"]?:250,
                    "height" => $note["height"]?:150,
                    "beforeClose" => $v->js(null, array($this->can_del?$del:'', "return false;"))->_enclose()
                )
            );
            $ref[$note["id"]] = $v->js()->reload();
            $refd[$note["id"]] = $v->js()->parent()->detach();
            $v->js("click", array( $v->js()->_selector(".sticky-note")->removeClass("top"), $v->js()->addClass("top")));
        }


        $vp->set(function($p) use ($vp,$owner, $self, $ref, $base, $vvr){
            $m = $this->add("stickynote/Model_StickyNote");
            $id = $_GET[$vp->name];
            if ((int)$id){
                $m->load($id);
            }
            $f=$p->add("Form");
            $f->setModel($m, array("content", "is_global", "color"));
            if (!$m->loaded()){
                $x=$f->addField("Hidden", "x");
                $y=$f->addField("Hidden", "y");
                $x->js(true)->val($f->js()->offset()->left);
                $y->js(true)->val($f->js()->offset()->top);
            }
            $f->addSubmit();
            if ($f->isSubmitted()){
                if ((int)$id && !$self->can_edit){
                    $f->displayError("content", "Sorry, editing is not allowed");
                } else if (!(int)$id && !$self->can_add){
                    $f->displayError("content", "Sorry, adding is not allowed");
                }
                $f->update();
                $m=$f->getModel();
                if (!$m["url"]){
                    $m->set("x", $f->get("x"));
                    $m->set("y", $f->get("y"));
                    $m->set("url", (string)$base)->save();
                }
                if ((int)$id){
                    $p->js(null, $ref[$id])->univ()->closeDialog()->execute();
                }
                $owner->js(null, array($vvr, $p->js()->univ()->closeDialog()))->execute();
            }
        });

        if (isset($_GET["note"])){
            $m = $this->add("stickynote/Model_StickyNote");
            $m->tryLoad($_GET["note"]);
            if ($m->loaded()){
                if (isset($_GET["delete"]) && $this->can_del){
                    $v=$refd[$m["id"]];
                    $m->delete();
                    $v->execute();
                }
                if (isset($_POST["pos"]) && $this->can_move){
                    $m->set("x", (int)$_POST["pos"]["left"])->set("y", (int)$_POST["pos"]["top"])->save();
                }
                if ($this->can_resize){
                    if (isset($_POST["width"])){
                        $m->set("width", (int)$_POST["width"])->save();
                    }
                    if (isset($_POST["height"])){
                        $m->set("height", (int)$_POST["height"])->save();
                    }
                }
            }
            $owner->js()->execute();
        }
    }
}
