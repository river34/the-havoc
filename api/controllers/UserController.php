<?php
namespace api\controllers;

use Yii;
use common\models\User;

/**
 * User controller
 */
class UserController extends ApiController
{
    public function actionLogin() {
        $username = empty($this->params['username'])?'':$this->params['username'];
        $user = User::findOne(['username'=>$username]);
        if ($user) {
            $result['success'] = true;
            $result['data'] = array('user'=>$user);
        } else {
            $result['error'] = ['code'=>100, 'msg'=>'login_fail'];
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }
}
