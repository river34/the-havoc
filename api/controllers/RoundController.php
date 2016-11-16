<?php
namespace api\controllers;

use Yii;
use common\models\Round;
use common\models\Player;
use common\models\Bomb;
use common\models\RoundPlayer;

/**
 * Game controller
 */
class RoundController extends ApiController
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

    public function actionEnd() {
        $result['success'] = false;
        $is_win = empty($this->params['is_win'])?0:$this->params['is_win'];
        $round = Round::find()->orderBy('id DESC')->one();
        if ($round && $round->game_end == 0) {
            $round->battle_start = 0;
            $round->save();
            $roundPlayers = RoundPlayer::find()->where(['round_id'=>$round->id])->all();
            if ($roundPlayers) {
                foreach ($roundPlayers as $roundPlayer) {
                    $roundPlayer->is_win = $is_win;
                    $roundPlayer->save();
                    $player = Player::findOne(['id'=>$roundPlayer->player_id]);
                    if ($player) {
                        $player->current_is_win = $roundPlayer->is_win;
                        $player->save();
                    }
                }
            }
            $result['success'] = true;
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionStartBattle() {
        $result['success'] = false;
        $round = Round::find()->orderBy('id DESC')->one();
        if ($round && $round->game_end == 0) {
            $round->battle_start = 1;
            $round->save();
            $result['success'] = true;
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    // start a new round (from unity side)
    // clear map data
    public function actionRestart() {
        $result['success'] = false;
        $result['data'] = [];
        $result['data']['round'] = [];
        $round = Round::find()->orderBy('id DESC')->one();
        if (!$round || ($round && $round->game_end == 1)) {
            try {
                Yii::$app->db->createCommand("UPDATE fake_map SET mark = 0, type = 0, player_id = 0 WHERE type <> ".Yii::$app->params['type_core']." AND type <> ".Yii::$app->params['type_wall'])->execute();
                Yii::$app->db->createCommand("UPDATE fake_map SET mark = 1 WHERE type = ".Yii::$app->params['type_core']." OR type = ".Yii::$app->params['type_wall'])->execute();
                file_put_contents($this->filename, '');
                $round = new Round();
                $round->save();
                $round->refresh();
                // $mech_track = new MechTrack();
                // $mech_track->round_id = $round->id;
                // $mech_track->save();
                $result['success'] = true;
                $result['data']['round'] = $round;
            } catch (Exception $e) {
                throw new Exception("Error : ".$e);
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    // start a new round (from mobile side)
    public function actionStart() {
        $result['success'] = false;
        $result['data'] = [];
        $result['data']['player'] = [];
        $result['data']['bomb'] = Yii::$app->params['bomb'];
        $result['data']['resource'] = Yii::$app->params['resource'];
        $result['data']['bomber_timer'] = Yii::$app->params['bomb_timer'];
        $result['data']['bomb_delay'] = Yii::$app->params['bomb_delay'];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player) {
            if (!empty($player->current_round_id)) {
                $roundPlayer = RoundPlayer::findOne(['round_id'=>$player->current_round_id, 'player_id'=>$player->id]);
                if ($roundPlayer) {
                    $roundPlayer->score = $player->current_score;
                    $roundPlayer->save();
                }
            }
            $result['data']['player'] = $player;
            $round = Round::find()->orderBy('id DESC')->one();
            if ($round && $round->game_end == 0) {
                $player->current_round_id = $round->id;
                $player->current_score = 0;
                $player->current_bomb = Yii::$app->params['bomb'];
                $player->current_resource = Yii::$app->params['resource'];
                $player->save();
                $roundPlayer = new RoundPlayer();
                $roundPlayer->player_id = $player->id;
                $roundPlayer->round_id = $round->id;
                $roundPlayer->is_mech = 0;
                $roundPlayer->save();
                $result['success'] = true;
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    // check if the player is in the game (mobile side)
    // last active round = player's current round
    public function actionCheck() {
        $result['success'] = false;
        $result['data'] = [];
        $result['data']['player'] = [];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player) {
            $result['data']['player'] = $player;
            $round = Round::find()->orderBy('id DESC')->one();
            // print_r($round->id. " " .$player->current_round_id); exit;
            if ($round && $player->current_round_id == $round->id) {
                $result['success'] = true;
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    // check if mech is ready (mobile side)
    public function actionReady() {
        $result['success'] = false;
        $result['data'] = [];
        $result['data']['player'] = [];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player) {
            $result['data']['player'] = $player;
            $round = Round::find()->orderBy('id DESC')->one();
            if ($round && $round->game_end == 0) {
                $result['success'] = true;
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetSettings() {
        $result['success'] = true;
        $result['data'] = [];
        $result['data']['bomb'] = Yii::$app->params['bomb'];
        $result['data']['resource'] = Yii::$app->params['resource'];
        $result['data']['bomber_timer'] = Yii::$app->params['bomb_timer'];
        $result['data']['bomb_delay'] = Yii::$app->params['bomb_delay'];

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetRoundResult() {
        $result['success'] = false;
        $result['data'] = [];
        $result['data']['player'] = [];
        $result['data']['roundPlayer'] = [];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player) {
            $result['data']['player'] = $player;
            $roundPlayer = RoundPlayer::findOne(['round_id'=>$player->current_round_id, 'player_id'=>$player->id]);
            if ($roundPlayer) {
                $result['data']['roundPlayer'] = $roundPlayer;
                $result['success'] = true;
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    // Update player's location, destroyed building (Same location), ...
    public function actionUpdateMap() {
        $result['success'] = false;
        // $result['params'] = $this->params;
        $result['data'] = [];
        $result['data']['bombs'] = [];
        $error = false;
        $mech = empty($this->params['mech'])?'':$this->params['mech'];    // mech's location
        $towers = empty($this->params['towers'])?'':$this->params['towers'];    // destroyed tower's location
        $scores = empty($this->params['scores'])?'':$this->params['scores'];    // scores of this round
        if (!empty($mech)) {
            // Open the file to get existing content
            if (!file_exists($this->filename)) {
                file_put_contents($this->filename, '');
            }
            $track = file_get_contents($this->filename);
            $track = explode(',', $track);
            // $track .= $mech.",";
            if ($mech != $track[sizeof($track)-1]) {
                $track[] = $mech;
            }
            $track = implode(',', $track);
            file_put_contents($this->filename, $track);
        }
        if (!empty($towers)) {
            foreach ($towers as $element) {
                if (!MapController::removeMark($element)) {
                    $error = true;
                }
            }
        }
        // print_r($this->params);exit;
        if (!empty($scores)) {
            foreach ($scores as $element) {
                try {
                    Yii::$app->db->createCommand("UPDATE player SET score = score + ". $element['score'] .
                    ", current_score = current_score + ". $element['score'] .
                    " WHERE id = ".$element['player_id'])->execute();
                } catch (Exception $e) {
                    throw new Exception("Error : ".$e);
                    $error = true;
                }
            }
        }

        // return bomb
        $round = Round::find()->orderBy('id DESC')->one();
        if ($round) {
            $start_time = date('Y-m-d H:i:s', strtotime('-5 second', time()));
            $bombs = Bomb::find()->where(['round_id'=>$round->id])->andWhere(['>', 'created_at', $start_time])->andWhere(['is_sent'=>0])->all(); // 'DATE_SUB(NOW(), INTERVAL 5 SECOND)'
            if ($bombs) {
                foreach ($bombs as $bomb) {
                    $bomb->is_sent = 1;
                    $bomb->save();
                }
                $result['data']['bombs'] = $bombs;
            }
        }

        if (!$error) {
            $result['success'] = true;
        }
        if (!$result['success']) {
            $result['error'] = ['code'=>200, 'msg'=>'data_not_found'];
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }
}
