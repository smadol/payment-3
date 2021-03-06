<?php

namespace leolei\Unionpay;

use leolei\Unionpay\Lib\Rsa;
use leolei\Unionpay\Config;

/**
 * 手机APP支付接口
 *
 * @author leolei <346991581@qq.com>
 */
class AppPay
{
    //请求地址
    private $frontTransUrl;
    private $backTransUrl;
    private $appTransUrl;
    private $singleQueryUrl;
    //基本信息
    private $version        = '5.0.0';
    private $sign_method    = '01';//rsa
    //商户信息---构造
    private $merchant_id;
    private $back_url;
    //common---构造
    private $cert_dir;
    private $cert_path;
    private $cert_pwd;
    //订单信息
    private $order_id;
    private $txn_amt;
    private $txn_time;
    private $origin_query_id;

    /**
     * 初始化参数配置
     *
     * @author leolei <346991581@qq.com>
     */
    public function __construct()
    {
        //通讯网址
        $this->frontTransUrl = Config::frontTransUrl();
        $this->backTransUrl = Config::backTransUrl();
        $this->appTransUrl = Config::appTransUrl();
        $this->singleQueryUrl = Config::singleQueryUrl();
        //参数配置
        $this->cert_dir = Config::getCerDir(); //公钥目录
        $this->cert_path = Config::getCerPath(); //证书路径
        $this->cert_pwd = Config::getCerPwd(); //证书密码
        $this->merchant_id = Config::getMerchantId(); //商户号
    }

    /**
     * 获取APP支付参数
     */
    public function consume()
    {
        $params = [
            'version'       => $this->version,                 //版本号
            'encoding'      => 'utf-8',               //编码方式
            'txnType'       => '01',                      //交易类型
            'txnSubType'    => '01',                  //交易子类
            'bizType'       => '000201',                  //业务类型
            'certId'        => $this->getCertId(),   //签名私钥证书
            'backUrl'       => $this->back_url,     //后台通知地址
            'signMethod'    => $this->sign_method,    //签名方法
            'channelType'   => '08',                  //渠道类型，07-PC，08-手机
            'accessType'    => '0',               //接入类型
            'currencyCode'  => '156',             //交易币种，境内商户固定156

            //TODO 以下信息需要填写
            'merId'         => $this->merchant_id,      //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'orderId'       => $this->order_id,     //商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime'       => $this->txn_time,     //订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt'        => $this->txn_amt,  //交易金额，单位分，此处默认取demo演示页面传递的参数

        ];

        $params['signature'] = $this->makeSignature($params);

        //发送数据
        $result_arr = Rsa::post($this->appTransUrl, $params);
        //验证请求
        if (sizeof($result_arr) <= 0) {
            return null;
        }

        //接收处理结果
        if ($result_arr["respCode"] == "00") {
            return $result_arr["tn"];
        }
        return null;
    }

    //撤销交易
    public function consumeUndo()
    {
        $params = [
            'version'       => $this->version,            //版本号
            'encoding'      => 'utf-8',           //编码方式
            'signMethod'    => $this->sign_method,            //签名方法
            'txnType'       => '31',                  //交易类型
            'txnSubType'    => '00',              //交易子类
            'bizType'       => '000201',              //业务类型
            'certId'        => $this->getCertId(),   //签名私钥证书
            'accessType'    => '0',           //接入类型
            'channelType'   => '07',              //渠道类型
            'backUrl'       => $this->back_url, //后台通知地址

            //TODO 以下信息需要填写
            'orderId'       => $this->order_id,     //商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'merId'         => $this->merchant_id,          //商户代码，请改成自己的测试商户号，此处默认取demo演示页面传递的参数
            'origQryId'     => $this->origin_query_id, //原消费的queryId，可以从查询接口或者通知接口中获取，此处默认取demo演示页面传递的参数
            'txnTime'       => $this->txn_time,     //订单发送时间，格式为YYYYMMDDhhmmss，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'txnAmt'        => $this->txn_amt,       //交易金额，消费撤销时需和原消费一致，此处默认取demo演示页面传递的参数
        ];

        $params['signature'] = $this->makeSignature($params);

        //发送数据
        $result_arr = Rsa::post($this->backTransUrl, $params);

        return $result_arr;

/*		//接收处理结果
		if ($result_arr["respCode"] == "00"){
			//交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
			//TODO
		} else if ($result_arr["respCode"] == "03"
			|| $result_arr["respCode"] == "04"
			|| $result_arr["respCode"] == "05" ){
			//后续需发起交易状态查询交易确定交易状态
			//TODO
		} else {
			//其他应答码做以失败处理
			//TODO
		}*/
    }

    //退款
    public function refund()
    {
        $params = [
            'version'       => $this->version,            //版本号
            'encoding'      => 'utf-8',           //编码方式
            'signMethod'    => $this->sign_method,            //签名方法
            'txnType'       => '04',                  //交易类型
            'txnSubType'    => '00',              //交易子类
            'bizType'       => '000201',              //业务类型
            'certId'        => $this->getCertId(),   //签名私钥证书
            'accessType'    => '0',           //接入类型
            'channelType'   => '07',              //渠道类型
            'backUrl'       => $this->back_url,       //后台通知地址

            //TODO 以下信息需要填写
            'orderId'       => $this->order_id,     //商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'merId'         => $this->merchant_id,          //商户代码，请改成自己的测试商户号，此处默认取demo演示页面传递的参数
            'origQryId'     => $this->origin_query_id, //原消费的queryId，可以从查询接口或者通知接口中获取，此处默认取demo演示页面传递的参数
            'txnTime'       => $this->txn_time,     //订单发送时间，格式为YYYYMMDDhhmmss，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'txnAmt'        => $this->txn_amt,       //交易金额，退货总金额需要小于等于原消费
        ];

        $params['signature'] = $this->makeSignature($params);

        //发送数据
        $result_arr = Rsa::post($this->backTransUrl, $params);

        return $result_arr;

/*		//处理报文
		if ($result_arr["respCode"] == "00"){
			//交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
			//TODO
		} else if ($result_arr["respCode"] == "03"
			|| $result_arr["respCode"] == "04"
			|| $result_arr["respCode"] == "05" ){
			//后续需发起交易状态查询交易确定交易状态
			//TODO
		} else {
			//其他应答码做以失败处理
			//TODO
		}*/
    }

    //查询
    public function query()
    {
        $params = [
            'version'       => $this->version,        //版本号
            'encoding'      => 'utf-8',       //编码方式
            'signMethod'    => $this->sign_method,        //签名方法
            'txnType'       => '00',              //交易类型
            'txnSubType'    => '00',          //交易子类
            'bizType'       => '000000',          //业务类型
            'certId'        => $this->getCertId(),   //签名私钥证书
            'accessType'    => '0',       //接入类型
            'channelType'   => '07',          //渠道类型

            //TODO 以下信息需要填写
            'orderId'       => $this->order_id,     //请修改被查询的交易的订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数
            'merId'         => $this->merchant_id,//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'txnTime'       => $this->txn_time,     //请修改被查询的交易的订单发送时间，格式为YYYYMMDDhhmmss，此处默认取demo演示页面传递的参数
        ];

        $params['signature'] = $this->makeSignature($params);

        //发送数据
        $result_arr = Rsa::post($this->singleQueryUrl, $params);

        return $result_arr;

/*		//报文处理
		if ($result_arr["respCode"] == "00"){
			if ($result_arr["origRespCode"] == "00"){
				//交易成功
				//TODO
			} else if ($result_arr["origRespCode"] == "03"
				|| $result_arr["origRespCode"] == "04"
				|| $result_arr["origRespCode"] == "05"){
				//后续需发起交易状态查询交易确定交易状态
				//TODO
			} else {
				//其他应答码做以失败处理
				//TODO
			}
		} else if ($result_arr["respCode"] == "03"
			|| $result_arr["respCode"] == "04"
			|| $result_arr["respCode"] == "05" ){
			//后续需发起交易状态查询交易确定交易状态
			//TODO
		} else {
			//其他应答码做以失败处理
			//TODO
		}*/
    }

    /**
     *  验签
     */
    public function verify($data = null)
    {
        if (!$data) {
            if (empty($_POST) && empty($_GET)) {
                return false;
            }
            $data = $_POST ?  : $_GET;
        }

        return Rsa::verify($data, $this->cert_dir);
    }

    /**
     * 生成签名
     */
    private function makeSignature($params)
    {
        return  Rsa::getParamsSignatureWithRSA($params, $this->cert_path, $this->cert_pwd);
    }

    /**
     * 获取秘钥ID
     */
    private function getCertId()
    {
        return Rsa::getCertId($this->cert_path, $this->cert_pwd);
    }

    public function setMerId($value)
    {
        $this->merchant_id = $value;
        return $this;
    }

    public function setNotifyUrl($value)
    {
        $this->back_url = $value;
        return $this;
    }

    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    public function setTxnAmt($value)
    {
        $this->txn_amt = $value;
        return $this;
    }

    public function setTxnTime($value)
    {
        $this->txn_time = $value;
        return $this;
    }

    public function setCertDir($value)
    {
        $this->cert_dir = $value;
        return $this;
    }

    public function setCertPath($value)
    {
        $this->cert_path = $value;
        return $this;
    }

    public function setCertPwd($value)
    {
        $this->cert_pwd = $value;
        return $this;
    }

    public function setOriginQueryId($value)
    {
        $this->origin_query_id = $value;
        return $this;
    }
}
