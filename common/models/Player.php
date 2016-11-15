<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "player".
 *
 * @property integer $id
 * @property string $name
 * @property string $key
 * @property string $device
 * @property string $ip
 * @property integer $score
 * @property integer $current_round_id
 * @property integer $current_score
 * @property integer $current_resource
 * @property integer $current_bomb
 * @property string $updated_at
 * @property string $created_at
 */
class Player extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'player';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'key'], 'required'],
            [['score', 'current_round_id', 'current_score', 'current_resource', 'current_bomb'], 'integer'],
            [['updated_at', 'created_at'], 'safe'],
            [['name', 'key', 'device'], 'string', 'max' => 255],
            [['ip'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'key' => 'Key',
            'device' => 'Device',
            'ip' => 'Ip',
            'score' => 'Score',
            'current_round_id' => 'Current Round ID',
            'current_score' => 'Current Score',
            'current_resource' => 'Current Resource',
            'current_bomb' => 'Current Bomb',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
