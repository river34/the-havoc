<?php
namespace api\controllers;

use Yii;
use common\models\Bomb;
use common\models\Player;
use common\models\Round;
use common\models\FakeMap;

/**
 * Bomb controller
 */
class BombController extends ApiController
{

    private $filename = '../web/track.txt';

    public function handshake($key) {
        if (empty($key)) { // new user
            $player = new Player();
            $player->name = microtime().rand();
            $key = md5(microtime().rand());
            $player->key = $key;
            $player->device = $_SERVER['HTTP_USER_AGENT'];
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $player->ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $player->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $player->ip = $_SERVER['REMOTE_ADDR'];
            }
            $player->save();
        }
        return $key;
    }

    public function actionPlace() {
        $result['success'] = false;
        $result['data'] = [];
        $id = empty($this->params['id'])?'':$this->params['id'];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        $round = Round::find()->orderBy('id DESC')->one();
        $grid = FakeMap::findOne(['id'=>$id, 'mark'=>Yii::$app->params['mark_empty']]);
        if ($player && $player->current_bomb > 0 && $round && $grid) {
            $player->current_bomb -= 1;
            $player->save();
            $player->refresh();
            $result['data']['player'] = $player;
            $bomb = new Bomb();
            $bomb->round_id = $round->id;
            $bomb->grid_id = $id;
            $bomb->player_id = $player->id;
            $bomb->type = Yii::$app->params['bomb_type_default'];
            $bomb->save();
            $bomb->refresh();
            $result['data']['bomb'] = $bomb;
            $result['success'] = true;
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }
}
