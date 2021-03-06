<?php

namespace app\models\sms;

use Yii;

class SmsCaptcha extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'sms_captcha';
    }

    public function rules()
    {
        return [
            [['expires_at', 'created_time'], 'safe'],
            [['status'], 'required'],
            [['status'], 'integer'],
            [['mobile', 'ip'], 'string', 'max' => 20],
            [['captcha'], 'string', 'max' => 10],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mobile' => 'Mobile',
            'captcha' => 'Captcha',
            'ip' => 'Ip',
            'expires_at' => 'Expires At',
            'status' => 'Status',
            'created_time' => 'Created Time',
        ];
    }

    public static function checkCaptcha ($mobile,$captcha) {
        $info = self::find()->where(['mobile' => $mobile,'captcha' => $captcha ])->one();
//        var_dump($info);
//        die();
        if ($info && strtotime($info['expires_at']) >= time()){
            $info->expires_at = date("Y-m-d H:i:s",time()-1);
            $info->status = 1;
            $info->save();
            return true;
        }
        return false;
    }

    public function geneCustomCaptcha( $mobile,$ip,$sign = '',$channel = '' ) {
        $this->mobile = $mobile;
        $this->ip = $ip;
        $this->captcha = rand(10000,99999);
        $this->expires_at = date("Y-m-d H:i:s",time() + 60*10 );
        $this->created_time = date("Y-m-d H:i:s",time());
        $this->status = 0;
        return $this->save();
    }

}
