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
 * ● 合单支付：通过该接口可以实现多商户（最多50个二级商户）商品同时支付的场景；通过在该接口传入需要分账的标识，交易资金分别进入到各二级商户账户中并处于冻结状态，系统默认冻结180天；
 */
namespace wechatpay_v3;

use wechatpay_v3\GuzzleMiddleware\WechatPayMiddleware;
use wechatpay_v3\GuzzleMiddleware\Util\PemUtil;
use wechatpay_v3\WxPayConfig;

class  ECtransactions{

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
     * 合单下单-APP支付API
     * 使用合单支付接口，用户只输入一次密码，即可完成多个订单的支付。目前最多一次可支持50笔订单进行合单支付。
     * 注意：
     * • 订单如果需要进行抽佣等，需要在合单中指定需要进行分账（profit_sharing为true）；指定后，交易资金进入二级商户账户，处于冻结状态，可在后续使用分账接口进行分账，利用分账完结进行资金解冻，实现抽佣和对二级商户的账期。
     * • 合单中同一个二级商户只允许有一笔子订单。
     * 接口说明
     * 适用对象：电商平台 服务商 直连商户
     * 请求URL：https://api.mch.weixin.qq.com/v3/combine-transactions/app
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function app($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/combine-transactions/app', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $json = $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   }
    /**
     * 合单下单-JS支付API
     * 使用合单支付接口，用户只输入一次密码，即可完成多个订单的支付。目前最多一次可支持50笔订单进行合单支付。
     * 
     * 注意： 
     * 订单如果需要进行抽佣等，需要在合单中指定需要进行分账（profit_sharing为true）；
     * 指定后，交易资金进入二级商户账户，处于冻结状态，
     * 可在后续使用分账接口进行分账，利用分账完结进行资金解冻，实现抽佣和对二级商户的账期。
     * 合单中同一个二级商户只允许有一笔子订单。
     * 接口说明
     * 适用对象：电商平台 服务商 直连商户
     * 请求URL：https://api.mch.weixin.qq.com/v3/combine-transactions/jsapi
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
   public function jsapi($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/combine-transactions/jsapi', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   }

    /**
     * 合单查询订单API
     * 电商平台通过合单查询订单API查询订单状态，完成下一步的业务逻辑。
     * 注意： 
     * 需要调用查询接口的情况：
     * 1、当商户后台、网络、服务器等出现异常，商户系统最终未接收到支付通知。
     * 2、调用支付接口后，返回系统错误或未知交易状态情况。
     * 3、调用刷卡支付API，返回USERPAYING的状态。
     * 4、调用关单或撤销接口API之前，需确认支付状态。
     * 接口说明
     * 适用对象：电商平台 服务商 直连商户
     * 请求URL：https://api.mch.weixin.qq.com/v3/combine-transactions/out-trade-no/{combine_out_trade_no}
     * 请求方式：GET
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function getCombineOrder($combine_out_trade_no){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('GET', 'https://api.mch.weixin.qq.com/v3/combine-transactions/out-trade-no/'.$combine_out_trade_no, 
                            ['headers' =>$headers]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   }
   
    /**
     * 合单关闭订单API
     * 合单支付订单只能使用此合单关单api完成关单。
     * 
     * 接口说明
     * 适用对象：电商平台 服务商 直连商户
     * 请求URL：https://api.mch.weixin.qq.com/v3/combine-transactions/out-trade-no/{combine_out_trade_no}/close
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function close($combine_out_trade_no, $form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/combine-transactions/out-trade-no/'.$combine_out_trade_no.'/close', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
    }   

}






