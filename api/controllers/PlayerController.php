<?php
namespace api\controllers;

use Yii;
use common\models\Player;

/**
 * Player controller
 */
class PlayerController extends ApiController
{
    public function actionLogin() {
        $result['success'] = false;
        $name = empty($this->params['name'])?'':$this->params['name'];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $device = $_SERVER['HTTP_USER_AGENT'];
        $ip = $_SERVER["REMOTE_ADDR"];
        if (!empty($name) && !empty($key)) { // welcome the returning player
            $player = Player::findOne(['name'=>$name, 'key'=>$key]);
            if ($player) {
                $player->device = $device;
                $player->ip = $ip;
                $player->save();
            }
        } else if (!empty($name)) { // create new player
            $player = Player::findOne(['name'=>$name]);
            if (!$player) {
                $player = new Player ();
                $player->name = $name;
                $player->key = md5(microtime().rand());
                $player->device = $device;
                $player->ip = $ip;
                $player->save();
            } else {
                $player = null;
                $result['error'] = ['code'=>310, 'msg'=>'name_taken'];
            }
        }

        if (!empty($player)) {
            $result['success'] = true;
            $result['data'] = array('player'=>$player);
        } else {
            $result['error'] = ['code'=>300, 'msg'=>'login_failed'];
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }
}
