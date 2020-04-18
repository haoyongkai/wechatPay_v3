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
 * ● 二级商户入驻：通过该接口将电商平台的二级商户入驻成为微信支付二级商户；
 */
namespace wechatpay_v3;

use wechatpay_v3\GuzzleMiddleware\WechatPayMiddleware;
use wechatpay_v3\GuzzleMiddleware\Util\PemUtil;
use wechatpay_v3\WxPayConfig;
use wechatpay_v3\GuzzleMiddleware\Auth\WechatPay2Credentials;
use wechatpay_v3\GuzzleMiddleware\Auth\PrivateKeySigner;
use Psr\Http\Message\RequestInterface;
use think\Db;

class  ECapplyments{

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
     * 二级商户入驻：通过该接口将电商平台的二级商户入驻成为微信支付二级商户；
     * 
     * 接口说明
     * 适用对象：电商平台
     * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/applyments/
     * 请求方式：POST
     * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
   public function applyments($form_params){
      // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
      $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                  'User-Agent' => $_SERVER['HTTP_USER_AGENT'], 'Wechatpay-Serial' => WxPayConfig::WXPAYSERNUM];   
      try {
         $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/ecommerce/applyments/', 
                           ['headers' =>$headers, 'body' => json_encode($form_params)]); 
         return $resp->getBody()->getContents();
      }catch (\GuzzleHttp\Exception\RequestException $e){
         \tool\Commontool::error_log($e->getCode(), $e);
         return $e->getResponse()->getBody()->getContents();
      } 
   }
   
    /**
     * 检查商户 是否是二级商户 
     * @author haoyongkai
     * @return bool  
     */
    public  function checkSubMerchant( $supplier_id = 0 )
    {
        if ( $supplier_id == 0) { return false; }
        $data = Db::table('clt_merchant')->where(['supplier_id' => $supplier_id])->field('applyment_id, applyment_state, sub_mchid')->find();
        if (!$data) {
           return false;
        }
        $applyment_id = isset($data['applyment_id'])?$data['applyment_id']:0;
        if (isset($data['applyment_state']) and $data['applyment_state'] == 'FINISH') {
           return ['applyment_state' => 'FINISH', 'sub_mchid' => $data['sub_mchid'] ,'applyment_id' => $applyment_id];
        }
        if ($applyment_id) {
            $resp = $this->getApplymentsByid($applyment_id);
            //返回形如
            //{ applyment_id: 2000002138678796,applyment_state: "FINISH",applyment_state_desc: "完成",audit_detail: [ ],out_request_no: "TTG2020033100000000004030970",sub_mchid: "1583380561"}
            $resp_arr = json_decode($resp, true);
            if (isset($resp_arr['applyment_state']) and isset($resp_arr['sub_mchid']) ) {
               Db::table('clt_merchant')->where(['supplier_id' => $supplier_id])->update([
                  'applyment_state' => $resp_arr['applyment_state'], 
                  'sub_mchid' => $resp_arr['sub_mchid'],
                  'update_at' => date('Y-m-d H:i:s', time())]);
            }
            if (isset( $resp_arr['applyment_state'] ) and $resp_arr['applyment_state']== 'FINISH') {
               return $resp_arr;
            }
        }
        return false;
    }
   /**
    * 查询申请状态API 电商平台通过查询申请状态API查询二级商户入驻申请结果
    *1、通过申请单ID查询申请状态
    *接口说明
    *适用对象：电商平台
    *请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/applyments/{applyment_id}
    *请求方式：GET
    *接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
    */
    public function getApplymentsByid($applyment_id){
      // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
      $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                  'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
      try {
         $resp = $this->client->request('GET', 'https://api.mch.weixin.qq.com/v3/ecommerce/applyments/'.$applyment_id, ['headers' =>$headers]); 
         return $resp->getBody()->getContents();
      }catch (\GuzzleHttp\Exception\RequestException $e){
         \tool\Commontool::error_log($e->getCode(), $e);
         return $e->getResponse()->getBody()->getContents();
      } 
   }   
   
   /**
    * 查询申请状态API 电商平台通过查询申请状态API查询二级商户入驻申请结果
    * 2、通过业务申请编号查询申请状态
    * 接口说明
    * 适用对象：电商平台
    * 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/applyments/out-request-no/{out_request_no}
    * 请求方式：GET
    * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
    */
    public function getApplymentsByno($out_request_no){
      // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
      $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                  'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
      try {
         $resp = $this->client->request('GET', 'https://api.mch.weixin.qq.com/v3/ecommerce/applyments/out-request-no/'.$out_request_no, ['headers' =>$headers]); 
         return $resp->getBody()->getContents();
      }catch (\GuzzleHttp\Exception\RequestException $e){
         \tool\Commontool::error_log($e->getCode(), $e);
         return $e->getResponse()->getBody()->getContents();
      } 
   } 
   
   /**
    * 修改结算帐号API
    * 普通服务商（支付机构、银行不可用），可使用本接口修改其进件、已签约的特约商户-结算账户信息。
    * 注意：本接口无需传银行开户名称参数。若账户类型为“经营者个人银行卡”，则系统自动拉取特约商户的经营者姓名为开户名称。
    * 若账户类型为“对公银行账户”，则系统自动拉取特约商户的公司名称为开户名称。
    * 接口说明
    * 适用对象：普通服务商
    * 请求URL：https://api.mch.weixin.qq.com/v3/apply4sub/sub_merchants/{sub_mchid}/modify-settlement
    * 请求方式：POST
    * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
    */
    public function modifySettlement($sub_mchid, $form_params){
      // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
      $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                  'User-Agent' => $_SERVER['HTTP_USER_AGENT'], 'Wechatpay-Serial' => WxPayConfig::WXPAYSERNUM];   
      try {
         $resp = $this->client->request('POST', 'https://api.mch.weixin.qq.com/v3/apply4sub/sub_merchants/'.$sub_mchid.'/modify-settlement', 
                           ['headers' =>$headers, 'body' => json_encode($form_params)]); 
         return $resp->getBody()->getContents();
      }catch (\GuzzleHttp\Exception\RequestException $e){
         \tool\Commontool::error_log($e->getCode(), $e);
         return $e->getResponse()->getBody()->getContents();
      } 
   } 
   
   /**
    * 查询结算账户API
    * 普通服务商（支付机构、银行不可用），可使用本接口查询其进件、已签约的特约商户-结算账户信息（敏感信息掩码）。 
    * 该接口可用于核实是否成功修改结算账户信息、及查询系统汇款验证结果。
    * 接口说明
    * 适用对象：电商平台
    * 请求URL：https://api.mch.weixin.qq.com/v3/apply4sub/sub_merchants/{sub_mchid}/settlement
    * 请求方式：GET
    * 接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
    */
    public function settlement($sub_mchid){
      // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
      $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
                  'User-Agent' => $_SERVER['HTTP_USER_AGENT']];   
      try {
         $resp = $this->client->request('GET', 'https://api.mch.weixin.qq.com/v3/apply4sub/sub_merchants/'.$sub_mchid.'/settlement', ['headers' =>$headers]); 
         return $resp->getBody()->getContents();
      }catch (\GuzzleHttp\Exception\RequestException $e){
         \tool\Commontool::error_log($e->getCode(), $e);
         return $e->getResponse()->getBody()->getContents();
      } 
   }

}






