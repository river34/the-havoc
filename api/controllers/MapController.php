<?php
namespace api\controllers;

use Yii;
use common\models\FakeMap;
use common\models\Player;
use common\models\Round;
use common\models\MechTrack;
use common\models\Bomb;
use common\models\RoundPlayer;

/**
 * Map controller
 */
class MapController extends ApiController
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

    public function actionMark() {
        $result['success'] = false;
        $result['data'] = [];
        $id = empty($this->params['id'])?'':$this->params['id'];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player && $player->current_resource > 0) {
            $result['data']['player'] = $player;
            $grid = FakeMap::findOne(['id'=>$id, 'mark'=>Yii::$app->params['mark_empty']]);
            if ($grid) {
                $ids = [];
                // calculate the up, down, left and right
                $row = Yii::$app->params['row'];
                $column = Yii::$app->params['column'];
                // up
                if ($grid->id - $column >= 1) {
                    array_push ($ids, $grid->id - $column);
                }
                // down
                if ($grid->id + $column <= $row * $column) {
                    array_push ($ids, $grid->id + $column);
                }
                // left
                if ($grid->id % $column != 1 && $grid->id - 1 >= 1) {
                    array_push ($ids, $grid->id - 1);
                }
                // right
                if (($grid->id) % $column != 0 && $grid->id + 1 <= $row * $column) {
                    array_push ($ids, $grid->id + 1);
                }
                // print_r($id);
                // print_r($ids);exit;
                $grids = FakeMap::find()->where(['id'=>$ids])->andWhere(['<', 'mark', Yii::$app->params['mark_full']])->all();
                if ($grids && count($grids) == count($ids) && count($ids) > 0) {
                    foreach ($grids as $element) {
                        if ($element->mark < Yii::$app->params['mark_full']) {
                            $element->mark += Yii::$app->params['mark_part'];
                        }
                        $element->save();
                    }
                    $grid->mark = Yii::$app->params['mark_full'];
                    $grid->type = Yii::$app->params['type_default'];
                    $grid->player_id = $player->id;
                    $grid->save();
                    $result['data']['grid'] = $grid;
                    $player->current_resource -= 1;
                    $player->save();
                    $player->refresh();
                    $result['success'] = true;
                }
            } else {
                $result['error'] = ['code'=>200, 'msg'=>'data_not_found'];
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionRemoveMark() {
        $result['success'] = false;
        $id = empty($this->params['id'])?'':$this->params['id'];
        $result['success'] = $this->removeMark($id);
        if (!$result['success']) {
            $result['error'] = ['code'=>200, 'msg'=>'data_not_found'];
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetMarked() {
        $result['success'] = false;
        $result['data'] = [];
        $result['data']['grids'] = [];
        $grids = FakeMap::find()->where(['<>', 'type', Yii::$app->params['type_empty']])->andWhere(['<>', 'mark', Yii::$app->params['type_empty']])->all();
        if ($grids) {
            $result['data']['grids'] = $grids;
        }
        $result['success'] = true;

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetTowers() {
        $result['success'] = false;
        $result['data'] = [];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player) {
            $result['success'] = true;
            $result['data']['player'] = $player;
            $my_grids = FakeMap::find()->where(['mark'=>Yii::$app->params['mark_full']])->andWhere(['player_id'=>$player->id])->all();
            if ($my_grids) {
                $result['data']['my_grids'] = $my_grids;
            }
            $other_grids = FakeMap::find()->where(['mark'=>Yii::$app->params['mark_full']])->andWhere(['<>', 'player_id', $player->id])->all();
            if ($other_grids) {
                $result['data']['other_grids'] = $other_grids;
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetMyTowers() {
        $result['success'] = false;
        $result['data'] = [];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player) {
            $result['success'] = true;
            $result['data']['player'] = $player;
            $grids = FakeMap::find()->where(['mark'=>Yii::$app->params['mark_full']])->andWhere(['player_id'=>$player->id])->all();
            if ($grids) {
                $result['data']['grids'] = $grids;
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetOtherTowers() {
        $result['success'] = false;
        $result['data'] = [];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player) {
            $result['success'] = true;
            $result['data']['player'] = $player;
            $grids = FakeMap::find()->where(['mark'=>Yii::$app->params['mark_full']])->andWhere(['<>', 'player_id', $player->id])->all();
            if ($grids) {
                $result['data']['grids'] = $grids;
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetMechTrack() {
        $result['success'] = false;
        $result['data'] = [];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player) {
            $result['success'] = true;
            $result['data']['player'] = $player;
            // Open the file to get existing content
            if (!file_exists($this->filename)) {
                file_put_contents($this->filename, '');
            }
            $track = file_get_contents($this->filename);
            $result['data']['track'] = $track;
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetMap() {
        $result['success'] = false;
        $result['data'] = [];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        if ($player) {
            $result['data']['player'] = $player;
            $grids = FakeMap::find()->all();
            if ($grids) {
                $result['success'] = true;
                $result['data']['grids'] = $grids;
                $my_grids = [];
                $other_grids = [];
                foreach ($grids as $element) {
                    if ($element->player_id == $player->id) {
                        $my_grids[] = $element;
                    } else if ($element->player_id != 0) {
                        $other_grids[] = $element;
                    }
                }
                $result['data']['my_grids'] = $my_grids;
                $result['data']['other_grids'] = $other_grids;
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetMapAndTrack() {
        $result['success'] = false;
        $result['data'] = [];
        $result['data']['my_grids'] = [];
        $result['data']['other_grids'] = [];
        $result['data']['wall_grids'] = [];
        $result['data']['track'] = [];
        $result['data']['round'] = [];
        $result['data']['other_bombs'] = [];
        $result['data']['is_win'] = [];
        $key = empty($this->params['key'])?'':$this->params['key'];
        $key = $this->handshake($key);
        $player = Player::findOne(['key'=>$key]);
        $round = Round::find()->orderBy('id DESC')->one();
        if ($player && $round) {
            $result['data']['player'] = $player;
            $roundPlayer = RoundPlayer::findOne(['round_id'=>$round->id, 'player_id'=>$player->id]);
            if ($roundPlayer) {
                $result['data']['is_win'] = $roundPlayer->is_win;
            }
            $grids = FakeMap::find()->all();
            if ($grids) {
                $result['success'] = true;
                $result['data']['grids'] = $grids;
                $my_grids = [];
                $other_grids = [];
                $wall_grids = [];
                foreach ($grids as $element) {
                    if ($element->type >= Yii::$app->params['type_default'] && $element->player_id == $player->id && $element->mark <> Yii::$app->params['mark_empty']) {
                        $my_grids[] = $element;
                    } else if ($element->type >= Yii::$app->params['type_default'] && $element->player_id != 0 && $element->mark <> Yii::$app->params['mark_empty']) {
                        $other_grids[] = $element;
                    } else if ($element->type == Yii::$app->params['type_wall'] && $element->mark <> Yii::$app->params['mark_empty']) {
                        $wall_grids[] = $element;
                    }
                }
                $result['data']['my_grids'] = $my_grids;
                $result['data']['other_grids'] = $other_grids;
                $result['data']['wall_grids'] = $wall_grids;
            }

            // $mech_track = MechTrack::find()->orderBy('updated_at DESC')->one();
            // if ($mech_track) {
            //     $track = $mech_track->track;
            // } else {
            //     // Open the file to get existing content
            //     if (!file_exists($this->filename)) {
            //         file_put_contents($this->filename, '');
            //     }
            //     $track = file_get_contents($this->filename);
            // }
            if (!file_exists($this->filename)) {
                file_put_contents($this->filename, '');
            }
            $track = file_get_contents($this->filename);
            $result['data']['track'] = $track;
            $result['data']['round'] = $round;

            $start_time = date('Y-m-d H:i:s', strtotime('-5 second', time()));
            $bombs = Bomb::find()->where(['round_id'=>$round->id])->andWhere(['>', 'created_at', $start_time])->andWhere(['<>', 'player_id', $player->id])->all();
            if ($bombs) {
                $result['data']['other_bombs'] = $bombs;
            }
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    // clear map data
    public function actionClear() {
        $result['success'] = false;
        try {
            Yii::$app->db->createCommand("UPDATE fake_map SET mark = 0, type = 0, player_id = 0 WHERE type <> ".Yii::$app->params['type_core'])->execute();
            file_put_contents($this->filename, '');
            $result['success'] = true;
        } catch (Exception $e) {
            throw new Exception("Error : ".$e);
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    // Update player's location, destroyed building (Same location), ...
    public function actionUpdateMap() {
        $result['success'] = false;
        $error = false;
        $mech = empty($this->params['mech'])?'':$this->params['mech'];    // mech's location
        $towers = empty($this->params['towers'])?'':$this->params['towers'];    // destroyed tower's location
        $scores = empty($this->params['scores'])?'':$this->params['scores'];    // scores of this round
        if (!empty($mech)) {
            // $mech_track = MechTrack::find()->orderBy('updated_at DESC')->one();
            // if ($mech_track) {
            //     $track = $mech_track->track;
            //     $track = explode(',', $track);
            //     if ($mech != $track[sizeof($track)-1]) {
            //         $track[] = $mech;
            //     }
            //     $track = implode(',', $track);
            //     $mech_track->track = $track;
            //     $mech_track->save();
            // } else {
            //     // Open the file to get existing content
            //     if (!file_exists($this->filename)) {
            //         file_put_contents($this->filename, '');
            //     }
            //     $track = file_get_contents($this->filename);
            //     $track = explode(',', $track);
            //     // $track .= $mech.",";
            //     if ($mech != $track[sizeof($track)-1]) {
            //         $track[] = $mech;
            //     }
            //     $track = implode(',', $track);
            //     file_put_contents($this->filename, $track);
            // }

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
            $towers = explode(',', $towers);
            foreach ($towers as $tower) {
                if (!$this->removeMark($tower)) {
                    $error = true;
                }
            }
        }
        // print_r($this->params);exit;
        if (!empty($scores)) {
            foreach ($scores as $element) {
                try {
                    Yii::$app->db->createCommand("UPDATE player SET score = score + ". $element['score'] .", current_score = current_score + ". $element['score'] ." WHERE id = ".$element['player_id'])->execute();
                } catch (Exception $e) {
                    throw new Exception("Error : ".$e);
                    $error = true;
                }
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

    public function removeMark($id) {
        $grid = FakeMap::findOne(['id'=>$id]);
        if ($grid) {
            $grid->mark = Yii::$app->params['mark_empty'];
            // $grid->type = 0;
            $grid->player_id = 0;
            $grid->save();
            // calculate the up, down, left and right
            $ids = [];
            // calculate the up, down, left and right
            $row = Yii::$app->params['row'];
            $column = Yii::$app->params['column'];
            // up
            if ($grid->id - $column >= 0) {
                array_push ($ids, $grid->id - $column);
            }
            // down
            if ($grid->id + $column < $row * $column) {
                array_push ($ids, $grid->id + $column);
            }
            // left
            if ($grid->id % $column > 0) {
                array_push ($ids, $id - 1);
            }
            // right
            if (($grid->id+1) % $column > 0) {
                array_push ($ids, $id + 1);
            }
            $grids = FakeMap::find()->where(['id'=>$ids])->all();
            if ($grids) {
                foreach ($grids as $element) {
                    if ($element->mark > Yii::$app->params['mark_empty']) {
                        $element->mark -= Yii::$app->params['mark_part'];
                    }
                    $element->save();
                }
            }
            return true;
        }
        return false;
    }
}
