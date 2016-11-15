<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "round_player".
 *
 * @property integer $id
 * @property integer $round_id
 * @property integer $player_id
 * @property integer $is_mech
 * @property integer $is_win
 * @property integer $score
 * @property string $updated_at
 * @property string $created_at
 */
class RoundPlayer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'round_player';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['round_id', 'player_id'], 'required'],
            [['round_id', 'player_id', 'is_mech', 'is_win', 'score'], 'integer'],
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
            'round_id' => 'Round ID',
            'player_id' => 'Player ID',
            'is_mech' => 'Is Mech',
            'is_win' => 'Is Win',
            'score' => 'Score',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
