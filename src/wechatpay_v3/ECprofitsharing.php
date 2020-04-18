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
 * ● 分账：通过分账接口，根据实际业务场景将交易款项分账到其他业务参与方的账户(如：平台抽取佣金)，目前默认最高分账比例30%；同时通过该接口，实现合单交易冻结资金的解冻，从而实现对二级商户的账期；
 */
namespace wechatpay_v3;

use wechatpay_v3\GuzzleMiddleware\WechatPayMiddleware;
use wechatpay_v3\GuzzleMiddleware\Util\PemUtil;
use wechatpay_v3\WxPayConfig;

class  ECprofitsharing{

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
     * 请求分账API
     * 微信订单支付成功后，由电商平台发起分账请求，将结算后的资金分给分账接收方。
     * 注意：
     * • 微信订单支付成功后，服务商代特约商户发起分账请求，将结算后的钱分到分账接收方。
     * • 对同一笔订单最多能发起20次分账请求，每次请求最多分给5个接收方。
     * • 此接口采用异步处理模式，即在接收到商户请求后，会先受理请求再异步处理，最终的分账结果可以通过查询分账接口获取。
     * 接口说明
     * 适用对象：电商平台 
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/orders
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function shareOrders($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/orders', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   } 
   /**
    * 查询分账结果API
    *发起分账请求后，可调用此接口查询分账结果 ;发起分账完结请求后，可调用此接口查询分账完结的结果
    *接口说明
    *适用对象：电商平台
    *请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/orders
    *请求方式：GET
    *接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
    */
    public function getShareOrders($sub_mchid, $transaction_id, $out_order_no){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
           $resp = $this->client->request('GET', 
           'https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/orders?sub_mchid='.$sub_mchid.'&transaction_id='.$transaction_id.'&out_order_no='.$out_order_no, 
           ['headers' =>$headers]); 
           return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
           \tool\Commontool::error_log($e->getCode(), $e);
           return $e->getResponse()->getBody()->getContents();
        } 
     }
    /**
     * 请求分账回退API
     * 订单已经分账，在退款时，可以先调此接口，将已分账的资金从分账接收方的账户回退给分账方，再发起退款。
     * 注意：
     * • 分账回退以原分账单为依据，支持多次回退，申请回退总金额不能超过原分账单分给该接收方的金额。
     * • 此接口采用同步处理模式，即在接收到商户请求后，会实时返回处理结果。
     * • 此功能需要接收方在商户平台开启同意分账回退后，才能使用。
     * • 对同一笔分账单最多能发起20次分账回退请求。
     * 接口说明
     * 适用对象：电商平台 
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/returnorders
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function returnOrders($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/returnorders', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   }
   
   /**
    * 查询分账回退结果API
    *商户需要核实回退结果，可调用此接口查询回退结果;如果分账回退接口返回状态为处理中，可调用此接口查询回退结果
    *接口说明
    *适用对象：电商平台
    *请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/returnorders
    *请求方式：GET
    *接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
    */
    public function getReturnOrders($sub_mchid,  $out_return_no, $out_order_no = 0, $order_id = 0){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $url = 'https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/returnorders?sub_mchid='.$sub_mchid.'&out_return_no='.$out_return_no;
            if ($out_order_no) {
                $url = $url.'&out_order_no='.$out_order_no;
            }
            if ($order_id) {
                $url = $url.'&order_id='.$order_id;
            }
           $resp = $this->client->request('GET', $url, ['headers' =>$headers]); 
           return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
           \tool\Commontool::error_log($e->getCode(), $e);
           return $e->getResponse()->getBody()->getContents();
        } 
     }

    /**
     * 完结分账API
     * 不需要进行分账的订单，可直接调用本接口将订单的金额全部解冻给特约商户。
     * 注意：
     * • 调用分账接口后，需要解冻剩余资金时，调用本接口将剩余的分账金额全部解冻给特约商户。
     * 接口说明
     * 适用对象：电商平台 
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/finish-order
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function finishOrder($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/finish-order', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   } 
   
    /**
     * 添加分账接收方API
     * 1. 电商平台可通过此接口添加分账接收方，建立分账接收方列表。后续通过发起分账请求，将电商平台下的二级商户结算后的资金，分给分账接收方列表中具体的分账接收方。
     * 2. 添加的分账接收方统一都在电商平台维度进行管理，其他二级商户，均可向该分账接收方列表中的接收方进行分账，避免在二级商户维度重复维护。
     * 接口说明
     * 适用对象：电商平台 
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/receivers/add
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function receiversAdd($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/receivers/add', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   } 
   
    /**
     * 删除分账接收方API
     * 电商平台发起删除分账接收方请求。删除后，不支持将电商平台下二级商户结算后的资金，分到该分账接收方。
     * 接口说明
     * 适用对象：电商平台 
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/receivers/delete
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function receiversDel($form_params){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
        try {
            $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/profitsharing/receivers/delete', 
                            ['headers' =>$headers, 'body' => json_encode($form_params)]); 
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        } 
   }
   
   /**
    * 分账动账通知API
    * 分账动账金额变动后，微信会把相关变动结果发送给商户。
    * 接口说明
    * 适用对象：直联商户电商服务商 服务商
    * 请求URL：该链接是通过[商户配置]提交service_notify_url设置，必须为https协议。
    * 如果链接无法访问，商户将无法接收到微信通知。 
    * 通知url必须为直接可访问的url，不能携带参数。示例： “https://pay.weixin.qq.com/wxpay/pay.action”
    * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
    */
    
}






