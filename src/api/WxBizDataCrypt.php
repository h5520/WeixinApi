<?php
namespace houzhonghua\weixin\api;
/**
 * 对微信小程序用户加密数据的解密示例代码.
 *
 * @copyright Copyright (c) 1998-2014 Tencent Inc.
 */

class WXBizDataCrypt
{

	public static function ErrorCode($code){

		$msg = [
			'200'	=> 'ok',
			'41001' => 'encodingAesKey 非法',
			'41002' => 'iv 非法',
			'41003' => 'aes 解密失败',
			'41004' => '解密后得到的buffer非法',
			'41005' => 'base64加密失败',
			'41016' => 'base64解密失败'
		];

		return ['code' => (int)$code,'msg' => $msg[$code]];
	}

    private $appid;
	private $sessionKey;

	/**
	 * 构造函数
	 * @param $sessionKey string 用户在小程序登录后获取的会话密钥
	 * @param $appid string 小程序的appid
	 */
	public function __construct( $appid, $sessionKey)
	{
		$this->sessionKey = $sessionKey;
		$this->appid = $appid;
	}


	/**
	 * 检验数据的真实性，并且获取解密后的明文.
	 * @param $encryptedData string 加密的用户数据
	 * @param $iv string 与用户数据一同返回的初始向量
	 * @param $data string 解密后的原文
     *
	 * @return int 成功0，失败返回对应的错误码
	 */
	public function decryptData( $encryptedData, $iv, &$data)
	{
		if (strlen($this->sessionKey) != 24) {
			return self::ErrorCode('41001');
		}
		$aesKey=base64_decode($this->sessionKey);

        
		if (strlen($iv) != 24) {
			return self::ErrorCode('41002');
		}
		$aesIV=base64_decode($iv);

		$aesCipher=base64_decode($encryptedData);

		$result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

		$dataObj=json_decode($result);

		if($dataObj  == NULL)
		{
			return self::ErrorCode('41003');
		}
		if( $dataObj->watermark->appid != $this->appid )
		{
			return self::ErrorCode('41003');
		}

		$data = $result;
		return self::ErrorCode('200');
	}

}

