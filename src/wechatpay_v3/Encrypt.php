<?php
/**
 * 实现敏感信息加密。
 */
namespace wechatpay_v3;

class  Encrypt{

    /**
     * 敏感信息加密
     */
    public static function getEncrypt($str)
    {
        //$str是待加密字符串 
        $public_key_path = WxPayConfig::SSLCERT_PLAT_PATH; 
        $public_key = file_get_contents($public_key_path); 
        $encrypted = ''; 
        if (openssl_public_encrypt($str,$encrypted,$public_key,OPENSSL_PKCS1_OAEP_PADDING)) { 
            //base64编码 
            $sign = base64_encode($encrypted);
        } else {
            throw new \Exception('encrypt failed');
        }
        return $sign;
    }

}
