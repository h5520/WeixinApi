<?php
namespace houzhonghua\weixinpay;
/**
 * @Author: 小尤
 * @Date:   2017-08-30
 * @note:   公众号发红包,企业付款类
 * @from:   CSDN博客(江南极客:http://blog.csdn.net/sinat_35861727?viewmode=contents)
 */

class WxRedpack{
    
    /**
     * 默认支付参数配置
     * @var array
     */
    private $config = array(
        'wxappid'       => 'wxe588f88995adb2c3',
        'mch_id'        => '1497921262',
        'pay_apikey'    => 'ec3dcae5d6123dcdeec5fee6f696a7c1',
        'api_cert'      => './apiclient_cert.pem',   
        'api_key'       => './apiclient_key.pem'
    );
    
    public function __construct($config = array()){
        $this->config   =   array_merge($this->config,$config);
    }
    
    /**
     * 使用 $this->name=$value    配置参数
     * @param  string $name     配置名称
     * @param  string $value    配置值
     */
    public function __set($name,$value){
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }
    
    /**
     * 使用 $this->name 获取配置
     * @param  string $name 配置名称
     * @return multitype    配置值
     */
    public function __get($name) {
        return $this->config[$name];
    }
    
    public function __isset($name){
        return isset($this->config[$name]);
    }
    
    //----------------------------------------------------------重点看这里---------------------------------------------------------
    
    /**
     * 公众号发红包
     * @param string $openid    用户openID
     * @param string $money     金额
     * @param string $trade_no  订单编号
     * @param string $act_name  活动名称
     * @return multitype        支付结果
     */
    public function sendredpack($openid,$money,$trade_no,$act_name){
        $config = $this->config;
        
        $data = array(
            'nonce_str'         => self::getNonceStr(),
            'mch_billno'        => $trade_no,
            'mch_id'            => $config['mch_id'],
            'wxappid'           => $config['wxappid'],
            'send_name'         => '江南极客',
            're_openid'         => $openid,
            'total_amount'      => $money * 100, //付款金额单位为分
            'total_num'         => 1,
            'wishing'           => '祝您天天开心！',
            'client_ip'         => self::getip(),
            'act_name'          => $act_name,
            'remark'            => 'From 江南极客'
        );
        
        $data['sign'] = self::makeSign($data);
        
        //构造XML数据
        $xmldata = self::array2xml($data);
        
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        //发送post请求
        $res = self::curl_post_ssl($url, $xmldata);
        
        if(!$res){
            return array('status'=>0, 'msg'=>"Can't connect the server" );
        }
        
        // 这句file_put_contents是用来查看服务器返回的结果 测试完可以删除了
        //file_put_contents('./log.txt',$res,FILE_APPEND);
        
        $content = self::xml2array($res);
        if(strval($content['return_code']) == 'FAIL'){
            return array('status'=>0, 'msg'=>strval($content['return_msg']));
        }
        if(strval($content['result_code']) == 'FAIL'){
            return array('status'=>0, 'msg'=>strval($content['err_code']).':'.strval($content['err_code_des']));
        }
        return $content;
    }
    
    /**
     * 公众号企业支付
     * @param string $openid    用户openID
     * @param string $money     金额
     * @param string $trade_no  订单编号
     * @param string $desc      付款操作说明信息(比如:提现)
     * @return string   支付结果
     */
    public function mchpay($openid,$money,$trade_no,$desc){
        $config = $this->config;
        $data = array(
            'mch_appid' => $config['wxappid'],
            'mchid'     => $config['mch_id'],
            'nonce_str' => self::getNonceStr(),
            'partner_trade_no' => $trade_no, 
            'openid'    => $openid,
            'check_name'=> 'NO_CHECK',          //OPTION_CHECK不强制校验真实姓名, FORCE_CHECK：强制 NO_CHECK：
            'amount'    => $money * 100,        //付款金额单位为分
            'desc'      => $desc,
            'spbill_create_ip' => self::getip()
        );
        
        //生成签名
        $data['sign'] = self::makeSign($data);
        
        //return $config;
        
        //构造XML数据
        $xmldata = self::array2xml($data);
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        //发送post请求
        $res = self::curl_post_ssl($url, $xmldata);
        if(!$res){
            return array('status'=>0, 'msg'=>"Can't connect the server" );
        }
        // 这句file_put_contents是用来查看服务器返回的结果 测试完可以删除了
        //file_put_contents('./log1.txt',$res,FILE_APPEND);
        
        //付款结果分析
        $content = self::xml2array($res);
        if(strval($content['return_code']) == 'FAIL'){
            return array('status'=>0, 'msg'=>strval($content['return_msg']));
        }
        if(strval($content['result_code']) == 'FAIL'){
            return array('status'=>0, 'msg'=>strval($content['err_code']).':'.strval($content['err_code_des']));
        }
       
        return $content;
    }
    
    //-------------------------------------------------------------------------------------------------------------------------------
    
    /**
     * 将一个数组转换为 XML 结构的字符串
     * @param array $arr 要转换的数组
     * @param int $level 节点层级, 1 为 Root.
     * @return string XML 结构的字符串
     */
    protected function array2xml($arr, $level = 1) {
        $s = $level == 1 ? "<xml>" : '';
        foreach($arr as $tagname => $value) {
            if (is_numeric($tagname)) {
                $tagname = $value['TagName'];
                unset($value['TagName']);
            }
            if(!is_array($value)) {
                $s .= "<{$tagname}>".(!is_numeric($value) ? '<![CDATA[' : '').$value.(!is_numeric($value) ? ']]>' : '')."</{$tagname}>";
            } else {
                $s .= "<{$tagname}>" . $this->array2xml($value, $level + 1)."</{$tagname}>";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $level == 1 ? $s."</xml>" : $s;
    }
    
    
    /**
     * 将xml转为array
     * @param  string   $xml xml字符串
     * @return array    转换得到的数组
     */
    protected function xml2array($xml){   
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
        return $result;
    }
    
    /**
     * 
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    protected function getNonceStr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {  
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
        } 
        return $str;
    }
    
    /**
    * 生成签名
    * @return 签名
    */
    protected function makeSign($data){
        //获取微信支付秘钥
        $key = $this->config['pay_apikey'];
        // 去空
        $data=array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a=http_build_query($data);
        $string_a=urldecode($string_a);
        //签名步骤二：在string后加入KEY
        //$config=$this->config;
        $string_sign_temp=$string_a."&key=".$key;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result=strtoupper($sign);
        return $result;
    }
    
    /**
     * 获取IP地址
     * @return [String] [ip地址]
     */
    protected function getip() {
        static $ip = '';
        $ip = $_SERVER['REMOTE_ADDR'];
        if(isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] AS $xip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }
        return $ip;
    }
    
    /**
     * 微信支付发起请求
     */
    protected function curl_post_ssl($url, $xmldata, $second=30,$aHeader=array()){
        $config = $this->config;
        
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        
        //默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT,$config['api_cert']);
        //默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY,$config['api_key']);
        
        //curl_setopt($ch,CURLOPT_CAINFO,$config['rootca']);
     
        if( count($aHeader) >= 1 ){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }
     
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xmldata);
        $data = curl_exec($ch);
        if($data){
            curl_close($ch);
            return $data;
        }else { 
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n"; 
            curl_close($ch);
            return false;
        }
    }
    
    
    
    //请求测试
    public function test(){
        
        $openid = 'omS-E035AC4CXzcU0-d2SAuoPyIM';
        $money = 1;
        $trade_no = date('YmdHis').mt_rand(1000,9999);
        $act_name = '江南极客';
        
        $res = self::mchpay($openid,$money,$trade_no,$act_name);
        //$res = self::sendredpack($openid,$money,$trade_no,$act_name);
        return $res;
    }
    
    
}

/*===============================================使用方法=======================================================


//使用方法一:
    * 配置好自己的参数,注意这里的两个证书路径得根据你自己的项目证书路径来写,同时存放证书的目录要开放可读权限
    $config = array(
        'wxappid'       => 'wx123456789876',
        'mch_id'        => '123456789',
        'pay_apikey'    => '123456789876123456789876123456789876',
        'api_cert'      => getcwd().'/cert/apiclient_cert.pem', 
        'api_key'       => getcwd().'/cert/apiclient_key.pem'
    );

    $redpack = new WxRedpack($config);                              //初始化类(同时传递参数)
    $redpack->sendredpack($openid,$money,$trade_no,$act_name);      //发红包
    $redpack->mchpay($openid,$money,$trade_no,$act_name);           //企业付款
    
    
//使用方法二:
    $redpack = new WxRedpack();                                     //初始化类
    
    $redpack->wxappid       = 'wx123456789876';                     //配置参数
    $redpack->mch_id        = '123456789';
    $redpack->pay_apikey    = '123456789876123456789876123456789876';
    $redpack->api_cert      = getcwd().'/cert/apiclient_cert.pem';
    $redpack->api_key       = getcwd().'/cert/apiclient_key.pem';

    $redpack->sendredpack($openid,$money,$trade_no,$act_name);      //发红包
    $redpack->mchpay($openid,$money,$trade_no,$act_name);           //企业付款

================================================================================================================*/




