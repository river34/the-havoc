<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mech_track".
 *
 * @property integer $id
 * @property integer $round_id
 * @property string $track
 * @property string $updated_at
 * @property string $created_at
 */
class MechTrack extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mech_track';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['round_id'], 'required'],
            [['round_id'], 'integer'],
            [['track'], 'string'],
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
            'track' => 'Track',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
