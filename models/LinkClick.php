<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "link_click".
 *
 * @property int $id
 * @property int $link_id
 * @property string $user_ip
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Link $link
 */
class LinkClick extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'link_click';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['link_id'], 'integer'],
            [['link_id', 'user_ip'], 'required'],
            [['link_id'], 'exist', 'skipOnError' => true, 'targetClass' => Link::class, 'targetAttribute' => ['link_id' => 'id']],
            [['user_ip'], 'string', 'max' => 15],
            [['created_at', 'updated_at'], 'safe'],
            [['updated_at'], 'default', 'value' => null],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'link_id' => 'Ссылка',
            'user_ip' => 'IP-адрес кликера',
            'created_at' => 'Создано',
            'updated_at' => 'Изменено',
        ];
    }

    /**
     * Gets query for [[Link]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLink()
    {
        return $this->hasOne(Link::class, ['id' => 'link_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if ($insert) {
            $this->link->updateCounters(['clicks' => 1]); 
        }
    }
}
