<?php
/**
 * 解密
 */
namespace wechatpay_v3;

use wechatpay_v3\GuzzleMiddleware\Util\AesUtil;

class  Decrypt{

    /**
     * 回调报文解密
     */
    public function getDecrypt($encrypt_data)
    {
        $aesutil = new AesUtil(WxPayConfig::APPSECRET);
        $r = $aesutil->decryptToString($encrypt_data['associated_data'], $encrypt_data['nonce'], $encrypt_data['ciphertext']);
        return $r;
    }
}
