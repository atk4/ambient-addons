<?php
class Cms_Contact extends Cms {
    function configureFields(){
        $this->m->addField("sendto")->caption('Email To');
        $this->m->addField("template");
        $this->m->addField("chimp")->type('boolean');
        $this->m->addField("chimp_key");
    }
    function configure($dest, $tag){
        $dest->add('Text',null,$tag)->set($this->api->getDestinationURL());
        if($dest->add('Button',null,$tag)->isClicked()){
            $dest->js()->univ()->alert(123)->execute();
        }
#http://jpoint.demo.agiletech.ie/about/contacts.html
# posted to: 
#http://jpoint.demo.agiletech.ie/cms.html?cms_page=about%2Fcontacts.html&jpoint_cms_button_2=clicked


        $f=$dest->add("Form", null, $tag);
        $f->addField('line','name','Your Name');
        $f->addField('line','email','Your Email');
        $f->addField('text','msg','Message');
        $f->addSubmit('Send');
        if($f->isSubmitted()){
            $t=$this->add('TMail');
            $t->loadTemplate($this->m->get('template'));
            $t->set($f->getAllData());
            $t->send($this->m->get('sendto'));
            //$dest->js()->univ()->alert(123)->execute();
            $dest->js()->univ()->dialogOK('Sent','Your message was sent',
                    $dest->js()->_enclose()->univ()
                    ->location($dest->api->getDestinationURL('/',array(
                            'cms_page'=>null)))
                        )
                    ->execute();
        }
    }
}
