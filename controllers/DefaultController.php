<?php
namespace app\controllers;

use app\common\components\BaseWebController;
use app\common\services\captcha\ValidateCode;
use app\common\services\UtilService;
use app\common\services\weixin\TemplateService;
use app\models\sms\SmsCaptcha;
use app\common\services\AreaService;
use dosamigos\qrcode\lib\Enum;
use dosamigos\qrcode\QrCode;

class DefaultController extends BaseWebController {

    private  $captcha_cookie_name = "validate_code";

    public function actionIndex(){
        return $this->render( "index" );
    }

    //纸质验证码
    public function actionImg_captcha(){
        $font_path = \Yii::$app->getBasePath() ."/web/fonts/captcha.ttf";
        $captcha_handle = new ValidateCode( $font_path );
        $captcha_handle->doimg();
        $this->setCookie( $this->captcha_cookie_name,$captcha_handle->getCode() );
    }

    //手机验证码
    public function actionGet_captcha(){
        $mobile = $this->post( "mobile","" );
        $img_captcha = $this->post( "img_captcha","" );
        if( !$mobile || !preg_match('/^1[0-9]{10}$/',$mobile ) ){
            $this->removeCookie( $this->captcha_cookie_name );
            return $this->renderJson( [],"手机号码不对",-1 );
        }

        $captcha_code = $this->getCookie( $this->captcha_cookie_name );
        if( strtolower( $img_captcha  )  != $captcha_code ){
            $this->removeCookie( $this->captcha_cookie_name );
            return $this->renderJson( [],"校验码不匹配",-1 );
        }
//        新建的话就是new()
//        更新的话，就是找到之后进行对模型赋值
        $model_sms = new SmsCaptcha();
        $model_sms->mobile = $mobile;
        $model_sms->ip = UtilService::getIP();
        $model_sms->captcha = rand(10000,99999);
        $model_sms->expires_at = date("Y-m-d H:i:s",time() + 60*10 );
        $model_sms->created_time = date("Y-m-d H:i:s",time());
        $model_sms->status = 0;
        $model_sms->save(0);

        $this->removeCookie( $this->captcha_cookie_name );
        if( $model_sms ){
            return $this->renderJson( [],"发送成功".$model_sms->captcha );
        }
        return $this->renderJson( [],ConstantMapService::$default_syserror,-1 );
    }


    public function actionCascade(){
        $province_id = $this->get('id',0);
        $tree_info = AreaService::getProvinceCityTree($province_id);
        return $this->renderJSON( $tree_info );
    }

}