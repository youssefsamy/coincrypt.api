<?php
/**
 * Created by PhpStorm.
 * User: Tall Prince
 * Date: 3/6/2017
 * Time: 6:44 PM
 */

namespace App\Libs;


class SMS
{
    const API_URL = "http://api.netgsm.com.tr/bulkhttppost.asp";
    const USER_CODE = "5467445477";
    const USER_PWD = "692624";

    private function xml_to_array($xml) {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches)){
            $count = count($matches[0]);
            for($i = 0; $i < $count; $i++){
                $subxml= $matches[2][$i];
                $key = $matches[1][$i];
                if(preg_match( $reg, $subxml )){
                    $arr[$key] = $this->xml_to_array( $subxml );
                }else{
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

    private function post($curlPost) {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$curlPost);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    public function send($mobile_number, $content) {

        $startdate=date('d.m.Y H:i');
        $startdate=str_replace('.', '',$startdate );
        $startdate=str_replace(':', '',$startdate);
        $startdate=str_replace(' ', '',$startdate);

        $stopdate=date('d.m.Y H:i', strtotime('+1 day'));
        $stopdate=str_replace('.', '',$stopdate );
        $stopdate=str_replace(':', '',$stopdate);
        $stopdate=str_replace(' ', '',$stopdate);


        $message = $content.' Sistemcoin\'e giris yapmak icin kullanacagınız tek kullanımlık guvenlik kodudur. ';
        $header = 'Sistemsa';

        $message = html_entity_decode($message, ENT_COMPAT, "UTF-8");
        $message = rawurlencode($message);


        $header = html_entity_decode($header, ENT_COMPAT, "UTF-8");
        $header = rawurlencode($header);

        $post_data = $this::API_URL."?usercode=" . $this::USER_CODE . "&password=" . $this::USER_PWD  . "&gsmno={$mobile_number}&message={$message}&msgheader={$header}&startdate=$startdate&stopdate=$stopdate";

        return $this->post($post_data);
    }
}