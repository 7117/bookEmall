<?php

namespace app\modules\m\controllers\common;

use Yii;
use app\common\components\BaseWebController;
use app\common\services\UrlService;
use app\common\services\UtilService;
use app\models\member\Member;

class BaseController extends BaseWebController {

	protected  $auth_cookie_current_openid = "shop_m_openid";
	protected  $auth_cookie_name = "book_member";
	protected  $salt = "dm3HsNYz3Uyddd46Rjg";
	protected  $current_user = null;

	protected $allowAllAction = [
		'm/oauth/login',
		'm/oauth/logout',
		'm/oauth/callback',
		'm/user/bind',
		'm/pay/callback',
		'm/product/ops',
		'm/product/search',
	];

	//微信特殊url
	public $special_AllowAction = [
		'm/default/index',
		'm/product/index',
		'm/product/info',
        'm/user/bind',
    ];

	public function __construct($id, $module, $config = []){
		parent::__construct($id, $module, $config = []);
		$this->layout = "main";

		Yii::$app->view->params['share_info'] = json_encode( [
			'title' => Yii::$app->params['title'],
			'desc' => Yii::$app->params['title'],
			'img_url' => UrlService::buildWwwUrl("/images/common/qrcode.jpg"),
		] );
	}

	public function beforeAction( $action ){
//	    检查登录的状态  其实就是在检查cookie
	    $login_status = $this->checkLoginStatus();
		$this->setMenu();

//		获取请求的动作
		if ( in_array($action->getUniqueId(), $this->allowAllAction ) ) {
			return true;
		}

		if ( !$login_status  ) {
			if ( \Yii::$app->request->isAjax ) {
				$this->renderJSON([],"未登录,系统将引导您重新登录",-302);
			} else {
				$redirect_url = UrlService::buildMUrl( "/user/bind" );

				if( UtilService::isWechat() ){

				    $openid = $this->getCookie( $this->auth_cookie_current_openid );

				    if( $openid ){
						if( in_array( $action->getUniqueId() ,$this->special_AllowAction ) ){
							return true;
						}else{
						    return false;
                        }
					}else{
						$redirect_url = UrlService::buildMUrl( "/oauth/login" );
					}
				}else{

					if( in_array( $action->getUniqueId() ,$this->special_AllowAction ) ){
						return true;

					}
				}
				$this->redirect( $redirect_url );
			}
			return false;
		}
		return true;
	}

	protected function setMenu(){
		$menu_hide = false;
		$url = \Yii::$app->request->getPathInfo();
		if( stripos($url,"product/info") !== false ){
			$menu_hide = true;
		}

		\Yii::$app->view->params['menu_hide'] = $menu_hide;
	}


    protected function checkLoginStatus(){

        $auth_cookie = $this->getCookie( $this->auth_cookie_name );

        if( !$auth_cookie ){
            return false;
        }

        list($auth_token,$member_id) = explode("#",$auth_cookie);
        if( !$auth_token || !$member_id ){
            return false;
        }

        if( $member_id && preg_match("/^\d+$/",$member_id) ){
            $member_info = Member::findOne([ 'id' => $member_id,'status' => 1 ]);
            if( !$member_info ){
                $this->removeAuthToken();
                return false;
            }
            if( $auth_token != $this->geneAuthToken( $member_info ) ){
                $this->removeAuthToken();
                return false;
            }

//            赋予当前的用户信息
            $this->current_user = $member_info;
            \Yii::$app->view->params['current_user'] = $member_info;

            return true;
        }
        return false;
    }

//    就是设置cookie
    public function setLoginStatus( $user_info ){
        $auth_token = $this->geneAuthToken( $user_info );
        $this->setCookie($this->auth_cookie_name,$auth_token."#".$user_info['id']);
    }

    public function geneAuthToken( $member_info ){
        return md5( $this->salt."-{$member_info['id']}-{$member_info['mobile']}-{$member_info['salt']}");
    }

    protected  function removeAuthToken(){
        $this->removeCookie($this->auth_cookie_name);
    }

    protected  function removeLoginStatus(){
        $this->removeCookie($this->auth_cookie_name);
    }

    public function goHome(){
        return $this->redirect( UrlService::buildMUrl( "/default/index" ) );
    }
}