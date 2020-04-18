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
 * ● 退款：通过退款接口将支付款退还给买家；
 */
namespace wechatpay_v3;

use wechatpay_v3\GuzzleMiddleware\WechatPayMiddleware;
use wechatpay_v3\GuzzleMiddleware\Util\PemUtil;
use wechatpay_v3\WxPayConfig;

class  ECrefunds{

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
     * 退款申请API
     * 当交易发生之后一段时间内，由于买家或者卖家的原因需要退款时，卖家可以通过退款接口将支付款退还给买家，
     * 微信支付将在收到退款请求并且验证成功之后，按照退款规则将支付款按原路退到买家帐号上。
     * 
     * 注意：
     * • 交易时间超过一年的订单无法提交退款。
     * • 微信支付退款支持单笔交易分多次退款，多次退款需要提交原支付订单的商户订单号和设置不同的退款单号。申请退款总金额不能超过订单金额。 一笔退款失败后重新提交，请不要更换退款单号，请使用原商户退款单号。
     * • 请求频率限制：150qps，即每秒钟正常的申请退款请求次数不超过150次，错误或无效请求频率限制：6qps，即每秒钟异常或错误的退款申请请求不超过6次。
     * • 每个支付订单的部分退款次数不能超过50次。
     * 
     * 接口说明
     * 适用对象：电商平台
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/refunds/apply
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function refundsApply($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
           $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/refunds/apply', 
                             ['headers' =>$headers, 'body' => json_encode($form_params)]); 
           return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
           \tool\Commontool::error_log($e->getCode(), $e);
           return $e->getResponse()->getBody()->getContents();
        } 
     }  
     
     /**
      * 查询退款API
      * 提交退款申请后，通过调用该接口查询退款状态。退款有一定延时，用零钱支付的退款20分钟内到账，银行卡支付的退款3个工作日后重新查询退款状态。
      * 注意：
      * ● 退款查询API可按以下两种不同方式查询：
      * 1、通过微信支付退款单号查询退款；
      * 2、通过商户退款单号查询退款。
      * ● 两种不同查询方式返回结果相同
      */
      /**
       * 1、通过微信支付退款单号查询退款
       * 接口说明
       * 适用对象：电商平台
       * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/refunds/id/{refund_id}
       * 请求方式：GET
       * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
       */
      public function getRefundsByid($refund_id, $sub_mchid){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
           $resp = $this->client->request('GET', 'https://api.mch.weixin.qq.com/v3/ecommerce/refunds/id/'.$refund_id.'?sub_mchid='.$sub_mchid, ['headers' =>$headers]); 
           return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
           \tool\Commontool::error_log($e->getCode(), $e);
           return $e->getResponse()->getBody()->getContents();
        } 
     }       
      /**
       * 2、通过商户退款单号查询退款
       * 接口说明
       * 适用对象：电商平台
       * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/refunds/out-refund-no/{out_refund_no}
       * 请求方式：GET
       * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
       */ 
      public function getRefundsByno($out_refund_no, $sub_mchid){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
           $resp = $this->client->request('GET', 'https://api.mch.weixin.qq.com/v3/ecommerce/refunds/out-refund-no/'.$out_refund_no.'?sub_mchid='.$sub_mchid, ['headers' =>$headers]); 
           return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
           \tool\Commontool::error_log($e->getCode(), $e);
           return $e->getResponse()->getBody()->getContents();
        } 
     }
     
     /**
      * 退款结果通知API
      * 退款状态改变后，微信会把相关退款结果发送给商户。
      * 接口说明
      * 适用对象：电商
      * 请求方式：POST
      * 请求URL：该链接是通过[商户配置]提交service_notify_url设置，必须为https协议。如果链接无法访问，商户将无法接收到微信通知。 
      * 通知url必须为直接可访问的url，不能携带参数。示例： “https://pay.weixin.qq.com/wxpay/pay.action”
      * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
      */
      public function refundsNotify($url, $form_params){
         // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
         $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                     'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
         try {
            $resp = $this->client->request('POST', $url, 
                              ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
         }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
         } 
      }  
}