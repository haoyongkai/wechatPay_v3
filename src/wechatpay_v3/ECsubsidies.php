<?php
/**
 * 电商收付通 相关接口封装
 * @author haoyongkai
 * @time 2020-03-17
 * 开发文档 参考 https://pay.weixin.qq.com/wiki/doc/apiv3/wxpay/pages/ecommerce.shtml
 * 
 * 电商收付通简介：
 * 电商收付通是微信支付专为电商行业场景打造的支付、结算解决方案。
 * 电商平台的平台商户入驻微信支付成为二级商户。
 * 电商收付通支持将多个二级商户的订单进行合单支付（如电商购物车中的多笔订单合并支付），
 * 合单支付款项分别进入到二级商户各自的账户（资金为冻结状态，可用于实现二级商户账期）；
 * 电商平台在满足业务流程条件下（如确认收货等），可将二级商户的冻结状态的资金解冻，并收取平台佣金。
 * 
 * 功能介绍
 * 电商收付通API能力如下：
 * ● 补差：针对电商平台补贴场景，通过补差接口在分账前将补贴款转入二级商户账户，统一进行分账；
 */
namespace wechatpay_v3;

use wechatpay_v3\GuzzleMiddleware\WechatPayMiddleware;
use wechatpay_v3\GuzzleMiddleware\Util\PemUtil;
use wechatpay_v3\WxPayConfig;

class  ECsubsidies{

   /**
    * 客户端
    */
   protected $client;
   /**
   * Constructor
   */
    public function __construct()
    {
      // 商户配置
      $merchantId = WxPayConfig::MCHID;
      $merchantSerialNumber = WxPayConfig::MCHSERNUM;
      $merchantPrivateKey = PemUtil::loadPrivateKey(WxPayConfig::SSLKEY_PATH);
      $wechatpayCertificate = PemUtil::loadCertificate(WxPayConfig::SSLCERT_PLAT_PATH);

      // 构造一个WechatPayMiddleware
      $wechatpayMiddleware = WechatPayMiddleware::builder()
                              ->withMerchant($merchantId, $merchantSerialNumber, $merchantPrivateKey)
                              ->withWechatPay([ $wechatpayCertificate ]) // 可传入多个微信支付平台证书，参数类型为array
                              ->build();

      // 将WechatPayMiddleware添加到Guzzle的HandlerStack中
      $stack = \GuzzleHttp\HandlerStack::create();
      $stack->push($wechatpayMiddleware, 'wechatpay_v3');
      // 创建Guzzle HTTP Client时，将HandlerStack传入
      $this->client = new \GuzzleHttp\Client(['handler' => $stack]);
    }
    /**
     * 请求补差API
     * 服务商下单的时候带上补差标识，微信订单支付成功并结算完成后，发起分账前，调用该口进行补差。
     * 注意：
     * • 电商平台下单时传入补差金额，详见【合单下单API】文档中补差字段说明。
     * • 在发起分账前，调用该接口进行补差。
     * • 补差金额需要和下单的时候传入的补差金额保持一致(发生用户退款时可以小于下单时的补差金额，须有对应的微信退款单号，任意一笔该订单的微信退款单)。
     * • 该接口支持重入，请求参数相同只会扣款一次，重入有效期180天。
     * • 系统异常（如返回SYSTEM_ERROR），请使用相同参数稍后重新调用，请务必用原参数来重入此接口，如更换金额重试，可能会导致重复扣款，系统会在1天后回退到原账户。
     * 接口说明
     * 适用对象：电商平台 
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/subsidies/create
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function subsidiesCreate($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/subsidies/create', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   }
    /**
     * 请求补差回退API
     * 订单发送退款的时候，可以对补贴成功的补差单发起回退。
     * 注意：
     * • 补差回退以原补差单位依据，支持多次回退，申请回退总金额不能超过补差金额。
     * • 此接口采用同步处理模式，即在接收到商户请求后，会实时返回处理结果。
     * • 补差回退的前置条件是订单发生退款。
     * • 系统异常（如返回SYSTEM_ERROR），请使用相同参数稍后重新调用，请务必用原商户补差回退单号和原参数来重入此接口。
     * 接口说明
     * 适用对象：电商平台 
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/subsidies/return
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function subsidiesReturn($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/subsidies/return', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   } 
   
    /**
     * 取消补差API
     * 对带有补差标识的订单，如果不需要补差，可在发起发起分账前，可调用这个接口进行取消补差。
     * 注意：
     * • 取消补差完成后，商户可以对未补差的订单进行分账。
     * • 订单补差取消的前置条件是订单发生退款。
     * 接口说明
     * 适用对象：电商平台 
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/subsidies/cancel
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function subsidiesCancel($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/subsidies/cancel', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   }   

}






