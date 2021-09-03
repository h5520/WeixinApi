<?php
use think\facade\Config;


class Weixin
{

	/**
     * notify_url接收页面
     */
    public function notify(){
        // 导入微信支付sdk
        $wxpay = new WeixinPay;
        $result = $wxpay->notify();
        
        return $result;
    }

    /**
     * 公众号支付
     * @ $notify:
     * 		['Wheelchair'=>'免费寄存']
     *		['After_oper_diagnosis' => '术后待诊']
     *		['Drug' => '预约开药']
     *		['productlist' => '商超订单']
     */
    public function pay($out_trade_no,$openid,$price,$notify,$attach){

		$appid        = Config::get('Applets_Appid'); // appid
		$openid       = $openid; //openid
		$mch_id       = Config::get('Public_mchid'); // 商户号
		$key          = Config::get('Public_key'); // key
		$out_trade_no = $out_trade_no; //订单号
		$total_fee    = floatval($price * 100); // 金额
		$body         = $notify;
		$attach		  = $attach;
		
		$weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$attach);

		$return = $weixinpay->pay();

		return $return;
    }

	/*
	 * 获取 openid
	*/
	public function OpenID($code){
		$APPID = Config::get('Applets_Appid');
		$AppSecret = Config::get('Applets_AppSecret');

		$url="https://api.weixin.qq.com/sns/jscode2session?appid=".$APPID."&secret=".$AppSecret."&js_code=".$code."&grant_type=authorization_code";

		$arr = $this->vget($url);  // 一个使用curl实现的get方法请求
		
		return $arr;
	}

	/*
	 * 获取用户手机号
	*/
	public function getMobile($sessionKey,$encryptedData,$iv){

        $appid = Config::get('Applets_Appid');
        $sessionKey = $sessionKey;
        $encryptedData= str_replace(" ", "+", $encryptedData);
        $iv = $iv;
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData,$iv,$data);
        $str = stripslashes($data); 
		$arr = json_decode($str,true);

        if(!$arr){
        	return $errCode;
        }else{
        	return json_encode($arr);
        }
	}

	public function vget($url){

        $info=curl_init();
		curl_setopt($info,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($info,CURLOPT_HEADER,0);
		curl_setopt($info,CURLOPT_NOBODY,0);
		curl_setopt($info,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($info,CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($info,CURLOPT_URL,$url);
		$output= curl_exec($info);
		curl_close($info);

		return $output;
    }
}
?>