<?php
class Cms_Html extends Cms {
    function init(){
        parent::init();
        $this->api->jquery->addStylesheet('elrte/css/elrte.full','.css',true);
    }
    function configureFields(){
        $this->m->addField('content')->type('text')->allowHtml(true);
        $this->m->addField('images')->type('string')->refModel('Model_Filestore_Image')->display("file");
    }
    function showConfigureForm($target){
        $f=parent::showConfigureForm($target);
        $f->setFormClass("empty");
        $f->getElement("images")->allowMultiple(10)
            ->setFormatFilesTemplate("view/cms_files");

        $f->getElement('content')->js(true)->_selectorRegion()->show();
        $f->getElement('content')
            ->setCaption('')
            ->js(true)->_load('elrte/js/elrte.min')
            ->addClass("elrte_editor")
            ->elrte(array('width'=>900,
                'height'=>300,
                'cssfiles'=>array('/cms/templates/default/css/editor.css'),
                'toolbar'=> 'maxi')
            );
        $this->submit_btn->js(true)->unbind("click")->bind("click", $this->submit_btn->js(null, '$(".elrte_editor").elrte()[0].save();return false')->_enclose());
        return $f;
    }
    function configure($dest, $tag){
        return $dest->add('HtmlElement',null,$tag)
            ->set($this->m->get('content'));
    }
}
