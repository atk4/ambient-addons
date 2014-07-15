<?php
namespace firstdata;
class Model_Transaction extends \Model_Table {
    function init(){
        $this->table = $this->api->getConfig("firstdata/table/transaction");
        parent::init();
        $this->addField("trans_id");
        $this->addField("amount");
        $this->addField("currency");
        $this->addField("client_ip_addr");
        $this->addField("description");
        $this->addField("language");
        $this->addField("dms_ok");
        $this->addField("result");
        $this->addField("result_code");
        $this->addField("result_3dsecure");
        $this->addField("card_number");
        $this->addField("t_date")
            ->defaultValue(date("Y-m-d H:i:s"));
        $this->addField("response")
            ->type("text");
        $this->addField("reversal_amount");
        $this->addField("makeDMS_amount");
    }
    function updateStatus(){
        $c = $this->add("firstdata/Controller_FirstData");
        $resp = $c->getTransResult(urlencode($this["trans_id"]), $this["client_ip_addr"]);

        if (!$resp){
            throw $this->exception("Missing response");
        }
        if (strstr($resp, 'RESULT:')) {
            //$resp example RESULT: OK RESULT_CODE: 000 3DSECURE: NOTPARTICIPATED RRN: 915300393049 APPROVAL_CODE: 705368 CARD_NUMBER: 4***********9913 
            if (strstr($resp, 'RESULT:')) {
                $result = explode('RESULT: ', $resp);
                $result = preg_split( '/\r\n|\r|\n/', $result[1] );
                $result = $result[0];
            }else{
                $result = '';
            }

            if (strstr($resp, 'RESULT_CODE:')) {
                $result_code = explode('RESULT_CODE: ', $resp);
                $result_code = preg_split( '/\r\n|\r|\n/', $result_code[1] );
                $result_code = $result_code[0];
            }else{
                $result_code = '';
            }

            if (strstr($resp, '3DSECURE:')) {
                $result_3dsecure = explode('3DSECURE: ', $resp);
                $result_3dsecure = preg_split( '/\r\n|\r|\n/', $result_3dsecure[1] );
                $result_3dsecure = $result_3dsecure[0];
            }else{
                $result_3dsecure = '';
            }

            if (strstr($resp, 'CARD_NUMBER:')) {
                $card_number = explode('CARD_NUMBER: ', $resp);
                $card_number = preg_split( '/\r\n|\r|\n/', $card_number[1] );
                $card_number = $card_number[0];
            }else{
                $card_number = '';
            }

            $this->set([
                "result" => $result,
                "result_code" => $result_code,
                "result_3dsecure" => $result_3dsecure,
                "card_number" => $card_number,
            ]);
        }
        $this->set("response", $this["response"]."\n".$resp);
        $this->save();
    }
    function reverse($amount=null){
        if (!$this->loaded()){
            throw $this->exception("Load transaction before reversal");
        }
        $c=$this->add("firstdata/Controller_FirstData");
        $resp = $c->reverse(urlencode($this["trans_id"]), $amount=$amount?:$this["amount"]);
        if (substr($resp,8,2) == "OK" OR substr($resp,8,8) == "REVERSED") {           
            if (strstr($resp, 'RESULT:')) {
                $result = explode('RESULT: ', $resp);
                $result = preg_split( '/\r\n|\r|\n/', $result[1] );
                $result = $result[0];
            }else{
                $result = '';
            }
            if (strstr($resp, 'RESULT_CODE:')) {
                $result_code = explode('RESULT_CODE: ', $resp);
                $result_code = preg_split( '/\r\n|\r|\n/', $result_code[1] );
                $result_code = $result_code[0];
            } else {
                $result_code = '';
            }
            $this->set(["reversal_amount" => $amount, "result" => $result, "result_code" => $result_code, "resp" => $this["resp"] . "\n" . $resp])->save();
        } else {
            throw $this->exception("Could not reverse");
        }
    }
    function startTrz($amount, $ip, $details, $lang="en"){
        $c = $this->add("firstdata/Controller_FirstData");
        $resp = $c->startSMSTrans($amount, $this->api->getConfig("firstdata/currency"), $ip, $details, $lang);
        if (substr($resp,0,14) == "TRANSACTION_ID"){
            $trans_id = substr($resp,16,28);
            $url = $this->api->getConfig("firstdata/client_url")."?trans_id=". urlencode($trans_id);
            $this->set([
                "trans_id" => $trans_id,
                "amount" => $amount,
                "currency" => $this->api->getConfig("firstdata/currency"),
                "client_ip_addr" => $ip,
                "description" => $details,
                "language" => "en",
                "dms_ok" => "---",
                "response" => $resp
                ])->save();
            return $url;
        } else {
            $me = $this->add("firstdata/Model_Error");
            $me->set([
                "error_time" => date("Y-m-d H:i:s"),
                "action" => "startSMSTrans",
                "response" => $resp
                ])->save();
            throw $this->exception($resp);
        }
    }
}
