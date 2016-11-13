<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "fake_map".
 *
 * @property integer $id
 * @property integer $mark
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
            [['mark', 'type'], 'integer'],
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
            'updated_at' => 'Updated At',
        ];
    }
}
