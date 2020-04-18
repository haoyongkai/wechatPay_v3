<?php
/**
 * 下载平台证书
 */
namespace wechatpay_v3;

use wechatpay_v3\GuzzleMiddleware\NoopValidator;
use wechatpay_v3\GuzzleMiddleware\WechatPayMiddleware;
use wechatpay_v3\GuzzleMiddleware\Util\PemUtil;
use wechatpay_v3\GuzzleMiddleware\Auth\WechatPay2Credentials;
use wechatpay_v3\GuzzleMiddleware\Auth\PrivateKeySigner;
use Psr\Http\Message\RequestInterface;
use wechatpay_v3\GuzzleMiddleware\Util\AesUtil;

class  Certificates{

    /**
     * 获取平台证书列表
     */
    public function getCert(RequestInterface $request)
    {
        // 商户配置
        $merchantId = WxPayConfig::MCHID;
        $merchantSerialNumber = WxPayConfig::MCHSERNUM;
        $merchantPrivateKey = PemUtil::loadPrivateKey(WxPayConfig::SSLKEY_PATH);

        $wechatpayMiddleware = WechatPayMiddleware::builder()->withMerchant($merchantId, $merchantSerialNumber, $merchantPrivateKey)
                                                             ->withValidator(new NoopValidator) // NOTE: 设置一个空的应答签名验证器，**不要**用在业务请求
                                                             ->build(); 
                         

        $stack = \GuzzleHttp\HandlerStack::create();
        $stack->push($wechatpayMiddleware, 'wechatpay');

        // 创建Guzzle HTTP Client时，将HandlerStack传入
        $client = new \GuzzleHttp\Client(['handler' => $stack]); 

        $signer = new PrivateKeySigner($merchantSerialNumber, $merchantPrivateKey);
        $WechatPay2Credentials = new WechatPay2Credentials($merchantId, $signer);
  
        $token = $WechatPay2Credentials->getToken($request);
        //WECHATPAY2-SHA256-RSA2048
        $headers = ['Authorization'=> 'WECHATPAY2-SHA256-RSA2048 '.$token,
                    'Accept' => 'application/json', 
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT'],];                           
        $resp = $client->request('GET', 'https://api.mch.weixin.qq.com/v3/certificates', ['headers' => $headers]); 

        $body_contents = $resp->getBody()->getContents();
        
        return $body_contents;
    }
}
