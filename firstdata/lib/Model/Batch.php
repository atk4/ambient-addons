<?php
namespace firstdata;
class Model_Batch extends \Model_Table {
    function init(){
        $this->table = $this->api->getConfig("firstdata/table/batch");
        parent::init();
        $this->addField("result")
            ->type("text");
        $this->addField("result_code");
        $this->addField("count_reversal");
        $this->addField("count_transaction");
        $this->addField("amount_reversal");
        $this->addField("amount_transaction");
        $this->addField("close_date");
        $this->addField("response")
            ->type("text");
    }
    function closeDay(){
        $c = $this->add("firstdata/Controller_FirstData");
        $resp = $c->closeDay();
        if (strstr($resp, 'RESULT:')) {
            //RESULT: OK RESULT_CODE: 500 FLD_075: 4 FLD_076: 6 FLD_087: 40 FLD_088: 60  
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

            if (strstr($resp, 'FLD_075:')) {
                $count_reversal = explode('FLD_075: ', $resp);
                $count_reversal = preg_split( '/\r\n|\r|\n/', $count_reversal[1] );
                $count_reversal = $count_reversal[0];
            }else{
                $count_reversal = '';
            }

            if (strstr($resp, 'FLD_076:')) {
                $count_transaction = explode('FLD_076: ', $resp);
                $count_transaction = preg_split( '/\r\n|\r|\n/', $count_transaction[1] );
                $count_transaction = $count_transaction[0];
            }else{
                $count_transaction = '';
            }

            if (strstr($resp, 'FLD_087:')) {
                $amount_reversal = explode('FLD_087: ', $resp);
                $amount_reversal = preg_split( '/\r\n|\r|\n/', $amount_reversal[1] );
                $amount_reversal = $amount_reversal[0];
            }else{
                $amount_reversal = '';
            }

            if (strstr($resp, 'FLD_088:')) {
                $amount_transaction = explode('FLD_088: ', $resp);
                $amount_transaction = preg_split( '/\r\n|\r|\n/', $amount_transaction[1] );
                $amount_transaction = $amount_transaction[0];
            }else{
                $amount_transaction = '';
            }

            $this->set([
                "result" => $result,
                "result_code" => $result_code,
                "count_reversal" => $count_reversal,
                "amount_reversal" => $amount_reversal,
                "count_transaction" => $count_transaction,
                "amount_transaction" => $amount_transaction,
                "close_date" => date("Y-m-d H:i:s"),
                "response" => $resp
            ])->save();
        } else {
            throw $this->exception("Could not close the business day: $resp");
        }

    }
}
