# 使用方式

```bash
composer require houzhonghua/weixinapi dev-main
```

## 引入

```php
use houzhonghua\weixinapi\Weixin;
```

## 自定义配置文件
```php

$config = [
    // 小程序appid
    "Applets_Appid" => "1",
    // 小程序appsecret
    "Applets_AppSecret" => "2",
    // 商户号
    "Public_mchid" => "3",
    // 公众号appid
    "Public_Appid" => "",
    // 微信商户 key
    "Public_key" => "",
    // 微信退款证书
    'apiclient_cert' => dirname(__FILE__)."\\cert\\apiclient_cert.pem", 
    'apiclient_key' => dirname(__FILE__)."\\cert\\apiclient_key.pem"
];

$query = new Weixin($config);

```
>或者
```php
// 在 config 目录下创建一个 weixin.php 的配置文件
// 把上面的配置信息复制进去，然后用下面的方法调取
$query = new Weixin(Config.get('wexin'));

```
## 获取 OpenID
```php
$query = new Weixin;
$res = $query->OpenID($jscode);
print_r($res);
```

## 获取用户手机号
```php
$query = new Weixin;
$res = $query->getMobile($sessionKey,$encryptedData,$iv);
print_r($res);
```

## 创建支付订单
```php
$query = new Weixin();
$data = [
    'out_trade_no' => "123123", # 订单号
    'openid' => "openid", # openid
    'price' => "0.01", # 价格
    'notify' => "test", # 支付界面显示文字
    'attach' => "test", # 额外参数
    'notify_url' => "test", # 回调地址
];
$res = $query->pay($data);
print_r($res);
```

## 微信退款
```php
$query = new Weixin();
$out_trade_no = "123123"; # 订单号
$transaction_id = "123123"; # 微信流水号
$total_fee = 0.01; # 当前订单的总支付金额
$refund_fee = 2; # 退款金额
$res = $query->refund($out_trade_no,$transaction_id,$total_fee,$refund_fee);
print_r($res);
```

## 微信提现
```php
$query = new Weixin();
$fee = 0.01; # 提现金额
$openid = "123123"; # openid
$out_trade_no = "123123"; # 创建订单号
$act_name = "提现"; # 描述
$res = $query->Cashout($fee,$openid,$act_name);
print_r($res);
```