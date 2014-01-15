<?php
namespace cps;

class Controller_CPS extends \AbstractController {
    public $limit = 50;
    public $offset = null;
    public $debug = false;
    function connect($storage){
        $o = $this->api->getConfig('cps/source/'.$storage);
        $this->storage = $storage;
        $this->connection = new \CPS_Connection($o["url"], $storage, $o["user"], $o["password"], isset($o["root"])?$o["root"]:"document", isset($o["idpath"])?$o["idpath"]:"//document/id");
        $this->simple = new \CPS_Simple($this->connection);
        $this->debug($this->debug);
        return $this;
    }
    function debug($debug){
        $this->connection
            ->setDebug($debug);
    }
    function insert($model, &$data){
        /* insert new record and store back to CPS
         *
         * note, if new SUB is added, ask ID to model. otherwise, if it's not 
         * sub, then we can ask CPS to auto increment
         *
         * if we have no ID, pass nothing to CPS then autoincrement will be 
         * triggered */
        /* and store iterator */
        if ($model->sub){
            $iterator = null;
            if ($xml = $model->_get("xml")){
                /* one to many */
                $iterator = $xml->addChild($model->enclosure);
                if (!isset($data[$model->id_field])){
                    $max = 0;
                    foreach ($xml->{$model->enclosure} as $e){
                        $max = max($max, (int)(string)$e->{$model->id_field});
                    }
                    $data[$model->id_field] = $new_id = $max+1;
                    $model->{$model->id_field} = $new_id;
                }
            } else {
                $iterator = $model->_get("iterator");
            }
            if ($iterator === null){
                throw $this->exception("Cannot insert new record - iterator not available");
            }
            foreach ($data as $k => $v){
                if ($model->elements[$k]->setterGetter("xpath")){
                    throw $this->exception("Updating by xpath not supported yet");
                }
                $iterator->{$k} = $v;
            }
            $model->_get("parent")->save();
        } else {
            /* store to cluster point */
            $iterator = new \StdClass();
            foreach ($data as $k => $v){
                $iterator->{$k} = $v;
            }
            $this->simple->insertSingle(null, $iterator);
            $ids=$this->simple->response->getModifiedIds();
            $model->id=(string)$ids[0];
        }
        return $iterator;
    }
    function update($model, $data){
        /* partial update, store back to CPS */
        /* set data into iterator */
        $iterator = $model->_get("iterator");
        foreach ($data as $k => $v){
            if ($xpath=$model->elements[$k]->setterGetter("xpath")){
                $a = $iterator->xpath($xpath);
                if ($a){
                    $i = array_shift($a);
                    $i[0] = $v;
                    continue;
                } else {
                    throw $this->exception("Could not find xpath: " . $xpath);
                }
            }
            $iterator->{$k} = $v;
        }
        /* remove snippets from iterator */
        $remove = [];
        foreach ($model->elements as $k => $v){
            if ($v instanceof \Field){
                if ($r=$v->setterGetter("retrieve")){
                    if ($r != "yes"){
                        throw $this->exception("Cannot update model, as it contains non-fully loaded fields");
                    }
                }
            }
        }
        /*echo "<pre>";
        print_r($iterator);
        exit;*/
        if ($model->sub){
            $model->_get("parent")->save();
        } else {
            /* store to cluster point */
            /*$a=$this->xml2array($iterator);
            echo "<pre>";
            print_r($a);
            exit;*/
            //$this->simple->partialReplaceSingle($model[$model->id_field], $a);
            $this->simple->updateSingle($model[$model->id_field], $iterator);
        }
    }
    function xml2array($xml){
        $a = json_encode($xml);
        $a = json_decode($a,true);
        return $a;
    }
    function rewind($model){
        /* not loaded, load */
        if (isset($model->sub) && $model->sub){
            if (($i=$model->_get("iterator")) instanceof \SimpleXMLIterator){
                return [$i, $i];
            } else if ($xml = $model->_get("xml")){
                $conditions = $model->_get("conditions");
                $p = $xml->{$model->enclosure};
                $p->rewind();
                list($limit,$offset) = $model->_get("limit");
                $this->counter = 0;
                if ($offset > 0){
                    while ($this->counter < $offset){
                        if (!($c=$p->current())){
                            return false;
                        }
                        if ($this->match($c, $conditions)){
                            $this->counter++;
                        }
                        $p->next();
                    }
                } else {
                    while (true){
                        if (!($c = $p->current())){
                            return false;
                        }
                        if ($this->match($c, $conditions)){
                            break;
                        }
                        $p->next();
                    }
                }
                $model->_set("count", count($p)); // should count properly I suppose
                return [$p, $p->current()];
            }
            /* parent should be loaded */
            throw $this->exception("Trying to iterate submodel, when parent is not loaded!");
        } else {
            $xml = $model->_get("xml");
            /* not sub, top. search */
            $list = [];
            /* add all relevant fields */
            foreach ($model->getActualFields() as $field){
                if ($retrieve = $model->elements[$field]->setterGetter("retrieve")){
                    $type = $retrieve;
                } else {
                    $type = "yes";
                }
                if ($xpath = $model->elements[$field]->setterGetter("xpath")){
                    $field = $xpath;
                }
                $list[$field] = $type;
            }
            $query = $this->buildQuery($model);
            $order = $this->buildOrder($model);
            list ($limit, $offset) = $model->_get("limit");
            $limit = $limit?:10;
            $offset = $offset?:0;
            $xml = $this->buildXML($d=$this->simple->search($query, $offset, $limit, $list, $order, "DOC_TYPE_XMLIterator"));
            $model->_set("xml", $xml);
            $model->_set("count", $this->simple->response->getParam("hits"));
            $xml->rewind();
            return [$xml, $xml->current()];
        }
    }
    function find($iterator, $conditions){
        $iterator->rewind();
        foreach ($iterator as $elem){
            if ($this->match($elem, $conditions)){
                return $elem;
            }
        }
    }
    function match($xml, $cond){
        if (!empty($cond)){
            foreach ($cond as $k => $vv){
                foreach ($vv as $v){
                    $child = (string)$xml->$k;
                    if ((string)$v != $child){
                        return false;
                    }
                }
            }
        }
        return true;
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
                $ptr->addChild($k, strtr((string)$v, ["&"=>"&amp;"]));
            }
        }
    }
    function buildOrder($model){
        if ($o = $model->_get("order")){
            $os = [];
            foreach ($o as $k => $v){
                $e = $model->hasField($v[0]);
                if ($xpath = $e->setterGetter("xpath")){
                    $field = $xpath;
                } else {
                    $field = $v[0];
                }
                if ($v[2] == "numeric"){
                    $os[] = CPS_NumericOrdering($field, $v[1]); 
                } else if ($v[2] == "date"){
                    $os[] = CPS_DateOrdering($field, $v[1]); 
                } else if ($v[2] == "string"){
                    $os[] = CPS_StringOrdering($field, $v[3],$v[1]); 
                } else {
                    throw $this->exception("No idea how to sort this way")->addMoreInfo($v[2]);
                }
            }
            return $os;
        }
        return null;
    }
    function buildQuery($model){
        $query = "";
        if ($c=$model->_get("conditions")){
            foreach ($c as $k => $vv){
                if ($xpath = $model->elements[$k]->setterGetter("xpath")){
                    $k = $xpath;
                }
                foreach ($vv as $v){
                    if (is_array($v)){
                        if ($v[1] == "like"){
                            $r[] = CPS_Term("*".$v[0]."*", $k);
                        } else if ($v[1] == "!="){
                            $r[] = CPS_Term("~".$v[0], $k);
                        } else if ($v[1] == ">="){
                            $r[] = CPS_Term(">= ".$v[0], $k);
                        } else if ($v[1] == "<="){
                            $r[] = CPS_Term("<= ".$v[0], $k);
                        } else {
                            throw $this->Exception("Sorry, this comparison " . $v[1] . " is not yet supported");
                            $r[] = CPS_Term($v[0], $k);
                        }
                    } else {
                        $r[] = CPS_Term($v, $k);
                    }
                }
            }
            $query = implode(" ", $r);
            /* apply */
        }
        if ($q=$model->_get("query")){
            $query .= $query?" ":"";
            $query .= $q;
        }
        if (!$query){
            $query = "*";
        }
        return $query;
    }
    function next($model, $ptr){
        if (isset($model->sub) && $model->sub){
            /* here we do soft params */
            /* technically this is not correct. cps should not handle this.
             * sub-xml models should be handled by xml processor */
            list($limit,$offset) = $model->_get("limit");
            $conditions = $model->_get("conditions");
            $this->counter++;
            if ($this->limit && $this->counter > $this->limit - 1){
                return null;
            }
            while (true){
                $c = $ptr->next();
                $c = $ptr->current();
                if (!$c){
                    return null;
                }
                if ($this->match($c, $conditions)){
                    return $c;
                }
            }
        } else {
            /* here we already have pre-filtered results */
            $ptr->next();
            if ($ptr->valid()){
                return $ptr->current();
            } else {
                return null;
            }
        }
    }
    function count($model){
        $this->rewind($model);
        return $model->_get("count");
    }
    function delete($model, $id=null){
        if ($model->sub){
            if ($xml=$model->_get("xml")){
                if (!$id){
                    $i = $model->_get("iterator");
                    if (!$i){
                        throw $this->exception("Either load model before deleteing, or specify id");
                    } else {
                        $id = $i->{$model->id_field};
                    }
                }
                $r=$this->rewind($model);
                if ($r){
                    list($ptr, $current) = $r;
                    $counter = 0;
                    foreach ($ptr as $k => $c){
                        if ($c->{$model->id_field} == $id){
                            unset($xml->{$k}[$counter]);
                            $model->_get("parent")->save();
                            return;
                        }
                        $counter++;
                    }
                }
            }
        } else {
            if ($model->loaded()){
                $this->simple->delete($model[$model->id_field]);
            } else if ($id){
                $this->simple->delete($id);
            } else {
                throw $this->exception("Load before delete");
            }
        }
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
    function getFacets($model, $path){
        if (isset($model->sub) && $model->sub){
            throw $this->exception("Sorry this is available only at top level");
        }
        $query = $this->buildQuery($model);
        $s = new \CPS_SearchRequest($query, 0, 0);
        $s->setFacet($path);
        $r = $this->connection->sendRequest($s);
        $facets = $r->getFacets();
        if (isset($facets[$path])){
            return $facets[$path];
        }
        return null;
    }
    function getAggregate($model, $aggregate, $extra_conditions=null){
        if (isset($model->sub) && $model->sub){
            throw $this->exception("Sorry this is available only at top level");
        }
        $b = [];
        $ref = [];
        foreach ($aggregate as $k=>$v){
            $b[] = $r = CPS_term($v);
            $ref[$r] = $k;
        }
        $query = $this->buildQuery($model);
        if ($extra_conditions){
            $c = [];
            foreach ($extra_conditions as $k=>$e){
                $c[] = CPS_Term($e,$k);
            }
            if ($query != "*"){
                $query .= " " . implode(" ", $c);
            } else {
                $query = implode(" ", $c);
            }
        }
        $s = new \CPS_SearchRequest($query, 0, 0);
        $s->setAggregate($b);
        $r = $this->connection->sendRequest($s);
        $ret = [];
        if ($a=$r->getAggregate()){
            foreach ($a as $k => $v){
                if (!isset($ref[$ref[$k]])){
                    $ret[$ref[$k]] = [];
                }
                foreach ($v as $vv){
                    $ret[$ref[$k]][] = $vv;
                }
            }
        }
        return $ret;
    }
}
