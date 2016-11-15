<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "round".
 *
 * @property integer $id
 * @property integer $player_id
 * @property integer $battle_start
 * @property integer $game_end
 * @property string $updated_at
 * @property string $created_at
 */
class Round extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'round';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['player_id', 'battle_start', 'game_end'], 'integer'],
            [['updated_at', 'created_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'player_id' => 'Player ID',
            'battle_start' => 'Battle Start',
            'game_end' => 'Game End',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
