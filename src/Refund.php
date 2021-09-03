<?php 
namespace houzhonghua\weixinapi;

class Refund
{
    protected $config;

    public function wxrefundapi($order,$config){

        $this->config = $config;

        // 通过微信api进行退款流程
        $parma = array(
            'appid' => $config['Public_Appid'],
            'mch_id' => $config['Public_mchid'],
            'nonce_str' => $this->createNoncestr(),
            'out_refund_no' => $order['out_trade_no'],
            'transaction_id' => $order['transaction_id'],// 微信订单号
            'total_fee' => $order['total_fee']*100, // 单位为分，实际支付金额
            'refund_fee' => $order['refund_fee']*100, // 单位为分，实际退款金额

            //'total_fee'=> 10, //单位为分，实际支付金额
            //'refund_fee'=> 10, //单位为分，实际退款金额
        );

        $parma['sign'] = $this->getSign($parma);
        $xmldata = $this->arrayToXml($parma);
        $xmlresult = $this->postXmlSSLCurl($xmldata,'https://api.mch.weixin.qq.com/secapi/pay/refund');
        $result = $this->xmlToArray($xmlresult);
        return $result;
    }
    /*
    * 对要发送到微信统一下单接口的数据进行签名
    */
    protected function getSign($Obj){

        foreach ($Obj as $k => $v){
            $param[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($param);
        $String = $this->formatBizQueryParaMap($param, false);
        //签名步骤二：在string后加入KEY
        $wx_key = $this->config['Public_key']; //申请支付后有给予一个商户账号和密码，登陆后自己设置的key
        $String = $String."&key=".$wx_key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        // var_dump($result_);
        return $result_;
    }
    /*
    *排序并格式化参数方法，签名时需要使用
    */
    protected function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v){
            if($urlencode){
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = "";
        if (strlen($buff) > 0){
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }
    /*
    * 生成随机字符串方法
    */
    protected function createNoncestr($length = 32 ){
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ ) {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    
    //数组转字符串方法
    protected function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    
    //将xml字符串转换为数组
    protected static function xmlToArray($xml){
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    //需要使用证书的请求
    //发送xml请求方法
    protected function postXmlSSLCurl($xml, $url, $second = 30){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $this->config['apiclient_cert']);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $this->config['apiclient_key']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            //echo "curl出错，错误码:$error" . "<br>";
            curl_close($ch);
            return false;
        }
    }
}
?>