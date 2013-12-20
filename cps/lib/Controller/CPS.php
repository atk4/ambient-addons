<?php
namespace cps;

class Controller_CPS extends \AbstractController {
    public $limit = 50;
    public $offset = null;
    public $debug = false;
    function connect($storage){
        $o = $this->api->getConfig('cp/source/'.$storage);
        $this->storage = $storage;
        $this->connection = new \CPS_Connection($o["url"], $storage, $o["user"], $o["password"]);
        $this->simple = new \CPS_Simple($this->connection);
        $this->debug($this->debug);
        return $this;
    }
    function debug($debug){
        $this->connection
            ->setDebug($debug);
    }
    function insert($model, $data){
        /* insert new record and store back to CPS
         *
         * note, if new SUB is added, ask ID to model. otherwise, if it's not 
         * sub, then we can ask CPS to auto increment
         *
         * if we have no ID, pass nothing to CPS then autoincrement will be 
         * triggered */
        /* and store iterator */
        if ($model->sub){
            if ($xml = $model->_get("xml")){
                /* one to many */
                $iterator = $xml->{$model->_get("field")}->addChild($model->enclosure);
                if (!isset($data[$model->id_field])){
                    $data[$model->id_field] = count($xml->{$model->_get("field")});
                }
            } else {
                $iterator = $model->_get("iterator");
            }
            if (!$iterator){
                throw $this->exception("Cannot insert new record - iterator not available");
            }
            foreach ($data as $k => $v){
                $iterator->{$k} = $v;
            }
            $model->_get("parent")->save();
        } else {
            /* store to cluster point */
            $iterator = new StdClass();
            foreach ($data as $k => $v){
                $iterator->{$k} = $v;
            }
            $this->simple->insertSingle(null, $iterator);
            $ids=$this->simple->response->getModifiedIds();
            $model->id=(string)$id[0];
        }
        return $iterator;
    }
    function update($model, $data){
        /* partial update, store back to CPS */
        /* set data into iterator */
        $iterator = $model->_get("iterator");
        foreach ($data as $k => $v){
            $iterator->{$k} = $v;
        }
        if ($model->sub){
            $model->_get("parent")->save();
        } else {
            /* store to cluster point */
            $this->simple->updateSingle($model[$model->id_field], $iterator);
        }
    }
    function rewind($model){
        /* not loaded, load */
        if (isset($model->sub) && $model->sub){
            if (($i=$model->_get("iterator")) instanceof \SimpleXMLIterator){
                return $i;
            } else if ($xml = $model->_get("xml")){
                $xml->{$model->_get("field")}->rewind();
                return $xml->{$model->_get("field")}->current();
            }
            /* parent should be loaded */
            throw $this->exception("Trying to iterate submodel, when parent is not loaded!");
        } else {
            $xml = $model->_get("xml");
            /* not sub, top. search */
            $list = [];
            $list["id"] = "yes";
            $query = $this->buildQuery($model);
            $order = $this->buildOrder($model);
            list ($limit, $offset) = $model->_get("limit");
            $limit = $limit?:10;
            $offset = $offset?:0;
            $xml = $this->buildXML($this->simple->search($query, $offset, $limit, $list, $order, "DOC_TYPE_XMLIterator"));
            $model->_set("xml", $xml);
            $model->_set("count", $this->simple->response->getParam("hits"));
            $xml->rewind();
            return $xml->current();
        }
    }
    function buildXML($array){
        $xml = simplexml_load_string('<?'.'xml version="1.0" encoding="UTF-8"'.'?><documents></documents>', 'SimpleXMLIterator');
        foreach ($array as $child){
            $d = $xml->addChild("document");
            $this->appendXML($d, $child);
        }
        return $xml;
    }
    function appendXML($ptr, $what){
        foreach ($what as $k => $v){
            if ($v->children()){
                $p = $ptr->addChild($k);
                $this->appendXML($p, $v);
            } else {
                $ptr->addChild($k, (string)$v);
            }
        }
    }
    function buildOrder($model){
        if ($o = $model->_get("order")){
            $os = [];
            foreach ($o as $k => $v){
                if ($v[2] == "numeric"){
                    $os[] = CPS_NumericOrdering($v[0], $v[1]); 
                } else if ($v[2] == "date"){
                    $os[] = CPS_DateOrdering($v[0], $v[1]); 
                } else if ($v[2] == "string"){
                    $os[] = CPS_StringOrdering($v[0], $v[3],$v[1]); 
                } else {
                    throw $this->exception("No idea how to sort this way")->addMoreInfo($v[2]);
                }
            }
            return $os;
        }
        return null;
    }
    function buildQuery($model){
        if ($c=$model->_get("conditions")){
            foreach ($c as $k => $v){
                if (is_array($v)){
                    if ($v[1] == "like"){
                        $r[] = CPS_Term("*".$v[0]."*", $k);
                    } else {
                        $r[] = CPS_Term($v[0], $k);
                    }
                } else {
                    $r[] = CPS_Term($v, $k);
                }
            }
            return implode(" ", $r);
            /* apply */
        } else {
            return "*";
        }
    }
    function next($model){
        if (!$model->sub){
            $xml=$model->_get("xml");
            $xml->next();
            return $xml->current();
        } else {
            if ($xml = $model->_get("xml")){
                $xml->{$model->_get("field")}->rewind();
                return $xml->{$model->_get("field")}->current();
            }
        }
        throw $this->exception("No idea how to rewind this object");
    }
    function count($model){
        $this->rewind($model);
        return $model->_get("count");
    }
    function delete($read,$write, $id_field, $value){
    }
    function getRootIterator(){
        return $this->xml;
    }
    function getNode($model, $field){
        $iterator = $model->_get("iterator");
        if (isset($iterator->{$field})){
            return $iterator->{$field};
        } else {
            return $iterator->addChild($field);
        }
    }
}
