<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "fake_map".
 *
 * @property integer $id
 * @property integer $mark
 * @property integer $type
 * @property integer $player_id
 * @property string $updated_at
 */
class FakeMap extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'fake_map';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mark', 'type', 'player_id'], 'integer'],
            [['updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mark' => 'Mark',
            'type' => 'Type',
            'player_id' => 'Player ID',
            'updated_at' => 'Updated At',
        ];
    }
}
