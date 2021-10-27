<?php
namespace houzhonghua\weixin;
use houzhonghua\weixin\api\WXBizDataCrypt;
use houzhonghua\weixin\api\Refund;
use houzhonghua\weixin\api\WxRedpack;

class Weixin
{

	protected static $config;

	public function __construct($config = []){

		self::$config = [
			// 小程序appid
			"Applets_Appid" => "",
			// 小程序appsecret
			"Applets_AppSecret" => "",
			// 商户号
			"Public_mchid" => "",
			// 公众号appid
			"Public_Appid" => "",
			// 微信商户 key
			"Public_key" => "",
			// 微信退款证书
			'apiclient_cert' => dirname(__FILE__)."\\cert\\apiclient_cert.pem", 
	        'apiclient_key' => dirname(__FILE__)."\\cert\\apiclient_key.pem"
		];

		self::$config = array_merge(self::$config,$config);
	}

    /**
     * 公众号支付
     * @ $notify:
     * 		['Wheelchair'=>'免费寄存']
     *		['After_oper_diagnosis' => '术后待诊']
     *		['Drug' => '预约开药']
     *		['productlist' => '商超订单']
     */
    public function pay($data = []){

		$appid        = self::$config['Applets_Appid'];
		$mch_id       = self::$config['Public_mchid'];
		$key          = self::$config['Public_key'];

		// openid
		$openid       = $data['openid'];
		// 订单号
		$out_trade_no = $data['out_trade_no'];
		// 金额
		$total_fee    = floatval($data['price'] * 100);
		// 显示文字
		$body         = $data['notify'];
		// 额外参数
		$attach		  = $data['attach'];
		// 回调地址
		$notify_url		  = $data['notify_url'];
		
		$weixinapi = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$attach,$notify_url);

		$return = $weixinapi->pay();

		return $return;
    }

	/*
	 * 获取 openid
	*/
	public function OpenID($code = ""){
		$APPID = self::$config['Applets_Appid'];
		$AppSecret = self::$config['Applets_AppSecret'];

		$url="https://api.weixin.qq.com/sns/jscode2session?appid=".$APPID."&secret=".$AppSecret."&js_code=".$code."&grant_type=authorization_code";

		$arr = $this->vget($url);  // 一个使用curl实现的get方法请求
		
		return $arr;
	}

	/*
	 * 获取用户手机号
	*/
	public function getMobile($sessionKey = '',$encryptedData = '',$iv = ''){

        $appid = self::$config['Applets_Appid'];
        $sessionKey = $sessionKey;
        $encryptedData= str_replace(" ", "+", $encryptedData);
        $iv = $iv;
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData,$iv,$data);
        $str = stripslashes($data); 
		$arr = json_decode($str,true);
        if($errCode['code'] != 200){
        	return $errCode;
        }
        return $arr;
	}

	/*
	 * 微信退款
	*/
	public function refund($out_trade_no = '',$transaction_id = '',$total_fee = '',$refund_fee = ''){

		$par = [
	        'out_trade_no' => $out_trade_no, // 用户自己生成的订单号
	        'transaction_id' => $transaction_id, // 微信订单号
	        'total_fee' => $total_fee, // 实际支付金额
	        'refund_fee' => $refund_fee // 实际退款金额
	    ];

	    $obj = new Refund();
	    $refundRes = $obj->wxrefundapi($par,self::$config);
	}

	/*
	 * 微信提现
	*/
	public function Cashout($price = '',$openid = '',$out_trade_no = '',$act_name = ''){

	    $config = array(
	        'wxappid'        => self::$config['Public_Appid'],//微信appid
	        'mch_id'         => self::$config['Public_mchid'],//商户号
	        'pay_apikey'     => self::$config['Public_key'],
	        'api_cert'       => self::$config['apiclient_cert'],
	        'api_key'        => self::$config['apiclient_key']
	    );

	    $redpack = new WxRedpack(self::$config); //初始化类(同时传递参数)
       	$openid = $openid;
       	$money = $price;//提现1
       	$trade_no = $out_trade_no;//商户订单号
       	$act_name = $act_name;
	    //$redpack->sendredpack($openid,$money,$trade_no,$act_name); //发红包
	    $result = $redpack->mchpay($openid,$money,$trade_no,$act_name);            //企业付款
	    
	    return $result;
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