<?php

namespace forum;
class Page_Frontend extends \Page {
    function initMainPage(){
        $this->add("forum/Controller_Forum");
        if (isset($_GET["thread"])){
            $this->api->stickyGET("thread");
            $thread_id = $_GET["thread"];
            /* show thread */
            if (isset($_GET["post"])){
                $this->api->stickyGET("post");
                $post_id = $_GET["post"];
                $this->showPath($thread_id, $post_id);
                $pt = $this->add("forum/Model_Post")->load($post_id);
                $lister = $this->add("forum/DLister", null, null, array("view/dlister"));
                $lister->template->trySet($pt->get());
                $lister->setModel($pt->ref("forum/Post"));
                $m = $this->add("forum/Model_Post")
                    ->addCondition("forum_thread_id", $pt->get("forum_thread_id"))
                    ->addCondition("forum_post_id", $post_id)
                    ->addCondition("user_id", $this->api->auth->get("id"));
                $f = $this->add("Form");
                $f->setModel($m, array("comment"));
                $f->addSubmit("Reply");
                if ($f->isSubmitted()){
                    $f->update();
                    $f->getModel()->ref("forum_post_id")->set("updated_dts", date("Y-m-d H:i:s"))->save();
                    $f->js(null,
                        $f->js(null,
                            $lister->js()->reload()
                        )->reload()
                    )->univ()->successMessage("Post has been added")->execute();
                }
            } else {
                $this->showPath($thread_id);
                $c = $this->add("forum/Model_Post_Title");
                $c->addCondition("forum_thread_id", $thread_id);
                $lister = $this->add("forum/PLister", null, null, array("view/plister"));
                $lister->setModel($c);
                $m = $this->add("forum/Model_Post_Title")
                    ->addCondition("forum_thread_id", $thread_id)
                    ->addCondition("forum_post_id", "0")
                    ->addCondition("user_id", $this->api->auth->get("id"));
                $f = $this->add("Form");
                $f->setModel($m, array("name", "comment"));
                $f->addSubmit("Create New Discussion");
                if ($f->isSubmitted()){
                    $f->update();
                    $f->js(null,
                        $f->js(null,
                            $lister->js()->reload()
                        )->reload()
                    )->univ()->successMessage("Your discussion has been added")->execute();
                }
            }
        } else {
            /* render categories */
            $c = $this->add("forum/Model_Category");
            $c->addCondition("threads", ">", "0");
            $lister = $this->add("forum/XLister", null, null, array("view/clister"))->setModel($c);
        }
    }
    function showPath($thread_id, $post_id = null){
        $v = $this->add("View", null, null, array("view/breadcrumb"));
        if ($thread_id){
            $m = $this->add("forum/Model_Thread")->load($thread_id);
            $v->template->set("thread", $m["name"]);
            $v->template->set("turl", $this->api->url(null, array("thread" => $thread_id, "post" => null)));
        } else {
            $v->template->del("thread_cnt");
        }
        if ($post_id){
            $m = $this->add("forum/Model_Post")->load($post_id);
            $v->template->set("post", $m->get("name"));
        } else {
            $v->template->del("post_cnt");
        }
        $v->template->set("url", $this->api->url(null, array("thread" => null, "post" => null)));
    }
}
class XLister extends \CompleteLister {
    function formatRow(){
        $t = $this->add("forum/Model_Thread");
        $t->addCondition("forum_category_id", $this->current_row["id"]);
        $tl = $this->owner->add("forum/TLister", null, null, array("view/tlister"));
        $tl->setModel($t);
        $tl->render();
        $this->current_row_html["threads"] = $tl->dump();
        unset($tl);
    }
}
class TLister extends \CompleteLister {
    private $buffer = "";
    function formatRow(){
        $this->current_row["url"] = $this->api->url(null, array("thread" => $this->current_row["id"]));
    }
    function output($string){
        $this->buffer .= $string;
    }
    function dump(){
        return $this->buffer;
    }
}
class PLister extends \CompleteLister {
    function formatRow(){
        $this->current_row["url"] = $this->api->url(null, array("post" => $this->current_row["id"]));
    }
}
class DLister extends \CompleteLister {
    function formatRow(){
    }
}

