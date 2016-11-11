<?php
namespace api\controllers;

use Yii;
use common\models\FakeMap;

/**
 * Map controller
 */
class MapController extends ApiController
{
    public function actionMark() {
        $result['success'] = false;
        $id = empty($this->params['id'])?'':$this->params['id'];
        $grid = FakeMap::findOne(['id'=>$id, 'mark'=>0]);
        if ($grid) {
            $grid->mark = 4;
            $grid->save();
            $result['success'] = true;
            $result['data'] = array('grid'=>$grid);
        } else {
            $result['error'] = ['code'=>200, 'msg'=>'data_not_found'];
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionMarks() {
        $result['success'] = false;
        $ids = empty($this->params['ids'])?'':$this->params['ids'];
        // print_r($ids); exit;
        $grids = FakeMap::find()->where(['id'=>$ids])->andWhere(['<', 'mark', 4])->all();
        // print_r($grids); exit;
        if ($grids && count($grids) == count($ids) && count($ids) > 0) {
            foreach ($grids as $grid) {
                if ($grid->id == $ids[0]) {
                    $grid->mark = 4;
                } else {
                    if ($grid->mark < 4) {
                        $grid->mark += 1;
                    }
                }
                $grid->save();
            }
            $result['success'] = true;
            $result['data'] = array('grids'=>$grids);
        } else {
            $result['error'] = ['code'=>200, 'msg'=>'data_not_found'];
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }

    public function actionGetMarked() {
        $result['success'] = false;
        $mark = empty($this->params['mark'])?'':$this->params['mark'];
        $grids = FakeMap::findAll(['mark'=>$mark]);
        if ($grids) {
            $result['success'] = true;
            $result['data'] = array('grids'=>$grids);
        } else {
            $result['error'] = ['code'=>200, 'msg'=>'data_not_found'];
        }

        $result['query_time'] = microtime(true) - $this->ini_time;
        return $result;
    }
}
