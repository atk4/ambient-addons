<?php
class Cms_Gallery extends Cms {
    public $prefix='';
    function init(){
        parent::init();
        $this->api->jquery->addStaticInclude('lightbox/js/jquery.lightbox-0.5');
        $this->api->template->append('js_include',
            '<link type="text/css" href="'.$this->api->locateURL('js','lightbox/css/jquery.lightbox-0.5.css').'" rel="stylesheet" />'."\n");
    }
    function configureFields(){
        $this->m->addField('name');
        $this->m->addField('images')->type('string')->refModel('Model_Filestore_Image')->display("file");
    }
    function showConfigureForm($target){
        $f=parent::showConfigureForm($target);
        $f->getElement("images")->allowMultiple(30)
            ->setFormatFilesTemplate("view/gallery_files");
        return $f;
    }
    function configure($dest, $tag){
        $m = $this->add("Model_Filestore_Image");
        $files = explode(",", $this->m->get("images"));
        foreach ($files as $file){
            $m->loadData($file);
            if ($m->isInstanceLoaded()){
                $tmp[] = array('image'=>$this->prefix.$m->getPath(), 'thumb'=>$this->prefix.$m->getRef('thumb_file_id')->getPath());
                //"id" => $file, "thumb_id" => $m->get("thumb_file_id"));
            }
        }
        if (!empty($tmp)){
            $v = $dest->add("HtmlElement", null, $tag, array("view/gallery"));
            $v->add("Text", null, "name")->set($this->m->get("name"));
            $l = $v->add("Lister", null, "thumbs", array("view/gallery_lister"));
            $l->setStaticSource($tmp);
            $v->js(true)->_selector(".gallery a")->lightBox(array(
                "imageLoading" => $this->api->locateURL('js',"lightbox/images/lightbox-ico-loading.gif"),
                "imageBtnPrev" => $this->api->locateURL('js',"lightbox/images/lightbox-btn-prev.gif"),
                "imageBtnNext" => $this->api->locateURL('js',"lightbox/images/lightbox-btn-next.gif"),
                "imageBtnClose" => $this->api->locateURL('js',"lightbox/images/lightbox-btn-close.gif"),
                "imageBlank" => $this->api->locateURL('js',"lightbox/images/lightbox-blank.gif")
            ));
            return $v;
        }
    }
}
