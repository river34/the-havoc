<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "bomb".
 *
 * @property integer $id
 * @property integer $round_id
 * @property integer $grid_id
 * @property integer $player_id
 * @property integer $type
 * @property integer $is_sent
 * @property string $created_at
 */
class Bomb extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bomb';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['round_id', 'grid_id', 'player_id'], 'required'],
            [['round_id', 'grid_id', 'player_id', 'type', 'is_sent'], 'integer'],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'round_id' => 'Round ID',
            'grid_id' => 'Grid ID',
            'player_id' => 'Player ID',
            'type' => 'Type',
            'is_sent' => 'Is Sent',
            'created_at' => 'Created At',
        ];
    }
}
