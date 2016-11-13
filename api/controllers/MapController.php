<?php
namespace api\controllers;

use Yii;
use common\models\FakeMap;
use common\models\Player;

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
        if ($player) {
            $result['data']['player'] = $player;
            $grid = FakeMap::findOne(['id'=>$id, 'mark'=>Yii::$app->params['mark_empty']]);
            if ($grid) {
                $ids = [];
                // calculate the up, down, left and right
                $grid_x = Yii::$app->params['grid_x'];
                $grid_y = Yii::$app->params['grid_y'];
                // up
                if ($grid->id - $grid_x >= 1) {
                    array_push ($ids, $grid->id - $grid_x);
                }
                // down
                if ($grid->id + $grid_x <= $grid_x * $grid_y) {
                    array_push ($ids, $grid->id + $grid_x);
                }
                // left
                if ($grid->id % $grid_x != 1 && $grid->id - 1 >= 1) {
                    array_push ($ids, $grid->id - 1);
                }
                // right
                if (($grid->id) % $grid_x != 0 && $grid->id + 1 <= $grid_x * $grid_y) {
                    array_push ($ids, $grid->id + 1);
                }
                // print_r($id);
                // print_r($ids);exit;
                $grids = FakeMap::find()->where(['id'=>$ids])->andWhere(['<', 'mark', Yii::$app->params['mark_building']])->all();
                if ($grids && count($grids) == count($ids) && count($ids) > 0) {
                    foreach ($grids as $element) {
                        if ($element->mark < Yii::$app->params['mark_building']) {
                            $element->mark += Yii::$app->params['mark_building_part'];
                        }
                        $element->save();
                    }
                    $grid->mark = Yii::$app->params['mark_building'];
                    $grid->type = Yii::$app->params['type_shooting_tower'];
                    $grid->player_id = $player->id;
                    $grid->save();
                    $result['success'] = true;
                    $result['data']['grid'] = $grid;
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
        $result['success'] = true;
        $result['data'] = [];
        $grids = FakeMap::find()->where(['mark'=>Yii::$app->params['mark_building']])->all();
        if ($grids) {
            $result['data']['grids'] = $grids;
        }

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
            $my_grids = FakeMap::find()->where(['mark'=>Yii::$app->params['mark_building']])->andWhere(['player_id'=>$player->id])->all();
            if ($my_grids) {
                $result['data']['my_grids'] = $my_grids;
            }
            $other_grids = FakeMap::find()->where(['mark'=>Yii::$app->params['mark_building']])->andWhere(['<>', 'player_id', $player->id])->all();
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
            $grids = FakeMap::find()->where(['mark'=>Yii::$app->params['mark_building']])->andWhere(['player_id'=>$player->id])->all();
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
            $grids = FakeMap::find()->where(['mark'=>Yii::$app->params['mark_building']])->andWhere(['<>', 'player_id', $player->id])->all();
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

    public function actionClear() {
        $result['success'] = false;
        try {
            Yii::$app->db->createCommand("UPDATE fake_map SET mark = 0, type = 0, player_id = 0 WHERE mark <> 5")->execute();
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
        if (!empty($mech)) {
            // Open the file to get existing content
            if (!file_exists($this->filename)) {
                file_put_contents($this->filename, '');
            }
            $track = file_get_contents($this->filename);
            $track .= $mech.",";
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
            $grid->save();
            // calculate the up, down, left and right
            $ids = [];
            // calculate the up, down, left and right
            $grid_x = Yii::$app->params['grid_x'];
            $grid_y = Yii::$app->params['grid_y'];
            // up
            if ($grid->id - $grid_x >= 0) {
                array_push ($ids, $grid->id - $grid_x);
            }
            // down
            if ($grid->id + $grid_x < $grid_x * $grid_y) {
                array_push ($ids, $grid->id + $grid_x);
            }
            // left
            if ($grid->id % $grid_x > 0) {
                array_push ($ids, $id - 1);
            }
            // right
            if (($grid->id+1) % $grid_x > 0) {
                array_push ($ids, $id + 1);
            }
            $grids = FakeMap::find()->where(['id'=>$ids])->all();
            if ($grids) {
                foreach ($grids as $element) {
                    if ($element->mark > Yii::$app->params['mark_empty']) {
                        $element->mark -= Yii::$app->params['mark_building_part'];
                    }
                    $element->save();
                }
            }
            return true;
        }
        return false;
    }
}
