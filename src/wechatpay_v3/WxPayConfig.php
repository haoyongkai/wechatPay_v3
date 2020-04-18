<?php
/**
* 	配置账号信息
*/
namespace wechatpay_v3;

abstract class WxPayConfig
{
    const MINI_APPID = 'xxxxxxxxxxxxxxxx';///小程序微信APPID
    const APP_APPID = 'xxxxxxxxxxxxxxxxxxx';//app微信appID

    const APPSECRET = 'xxxxxxxxxxxxxxxxxxxxxxxxxxx';//微信商户微信支付 秘钥 XPAY_APPSECRET
	//=======【基本信息设置】=====================================
    const MCHID = 'xxxxxxxxxxxxxxxxxxxxxxx';//微信商户号
    const MCHSERNUM = 'xxxxxxxxxxxxxxxxxxxxxxxx';//商户序列号

    const WXPAYSERNUM = 'xxxxxxxxxxxxxxxxxxxxxxxxx';//平台证书序列号
    
    //=======【证书路径设置】=====================================
    const SSLKEY_PATH = 'src/wechatpay_v3/cert/apiclient_key.pem';
    const SSLCERT_PATH = 'src/wechatpay_v3/cert/apiclient_cert.pem';
    //https://api.mch.weixin.qq.com/v3/certificates 微信访问 ：平台证书信息
    const SSLCERT_PLAT_PATH = 'src/wechatpay_v3/cert/apiclient_plat_cert.pem';//平台证书信息
}
