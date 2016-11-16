<?php
namespace api\controllers;

use Yii;

class ApiController extends \yii\web\Controller
{
    protected $params = array();
    protected $ini_time = 0;
    public $debug = false;

    public function beforeAction($action) {
        date_default_timezone_set('us/eastern');
        $this->enableCsrfValidation = false;
        $request = Yii::$app->request;

        $data = $request->get("data");
        if ($data) {
            $this->params = json_decode($data, true);
        } else {
            $this->params = $request->get();
            if ($this->params) {
                foreach ($this->params as $key=>$element) {
                    if ($element) {
                        //
                    } else if (is_array(json_decode($element, true))) {
                        $this->params[$key] = json_decode($element, true);
                    }
                }
            }
        }

        if ($this->debug && $this->params) {
            try {
                Yii::$app->db->createCommand("INSERT INTO log (log) VALUES ('". json_encode($this->params) ."')")->execute();
            } catch (Exception $e) {
                throw new Exception("Error : ".$e);
            }
        }

        // if ($this->params) {
        //     foreach ($this->params as $key=>$element) {
        //         if ($element) {
        //             //
        //         } else if (is_array(json_decode($element, true))) {
        //             $this->params[$key] = json_decode($element, true);
        //         }
        //     }
        //     // try {
        //     //     Yii::$app->db->createCommand("INSERT INTO log (log) VALUES ('". json_encode($this->params) ."')")->execute();
        //     // } catch (Exception $e) {
        //     //     throw new Exception("Error : ".$e);
        //     // }
        // }
        $this->ini_time = microtime(true);

        return parent::beforeAction($action);
    }

    public function afterAction($action, $result) {
        return parent::afterAction($action, $result);
    }
}
?>
