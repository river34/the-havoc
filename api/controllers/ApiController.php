<?php
namespace api\controllers;

use Yii;

class ApiController extends \yii\web\Controller
{
    protected $params = array();
    protected $ini_time = 0;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        $request = Yii::$app->request;
        $this->params = $request->get();
        $this->ini_time = microtime(true);
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result) {
        return parent::afterAction($action, $result);
    }
}
?>
