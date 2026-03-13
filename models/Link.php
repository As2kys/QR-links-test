<?php

namespace app\models;

use chillerlan\QRCode\QRCode;
use Yii;

/**
 * This is the model class for table "link".
 *
 * @property int $id
 * @property string $url_full
 * @property string $url_short
 * @property string|null $user_ip
 * @property string|null $qrcode
 * @property int $clicks
 * @property string $created_at
 * @property string|null $updated_at
 * @property LinkClick[] $clicks
 */
class Link extends \yii\db\ActiveRecord
{
    const
        URL_SHORT_LENGTH = 7,
        URL_SHORT_OFFSET = 3377;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'link';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['url_full'], 'unique'],
            [['url_full'/*, 'url_short'*/], 'required'],
            [['user_ip', 'qrcode', 'updated_at'], 'default', 'value' => null],
            [['url_full', 'qrcode'], 'string'],
            ['url_full', 'url', 'message' => 'Укажите корректный URL (например, https://ya.ru/).'],
            [['url_short'], 'string', 'max' => self::URL_SHORT_LENGTH],
            [['user_ip'], 'string', 'max' => 15],
            [['clicks'], 'default', 'value' => 0],
            [['clicks'], 'integer'],
            [['created_at', 'updated_at', 'qrcode'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url_full' => 'Исходная ссылка',
            'url_short' => 'Короткая ссылка',
            'user_ip' => 'IP пользователя',
            'qrcode' => 'QR code',
            'clicks' => 'Количество кликов',
            'created_at' => 'Создано',
            'updated_at' => 'Изменено',
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->qrcode = (new QRCode)->render($this->url_full);
            $this->url_short = $this->getUrlShort($this->qrcode);
            $this->user_ip = Yii::$app->request->userIP;
            return true;
        }
        return false;
    }

    public function getQrCode()
    {
        return (new QRCode)->render($this->url_full);
    }

    public static function getUrlShort($url = null)
    {
        return substr($url ?: $this->qrcode, self::URL_SHORT_OFFSET, self::URL_SHORT_LENGTH);
    }

    /**
     * Gets query for [[LinkClicks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinkClicks()
    {
        return $this->hasMany(LinkClick::class, ['link_id' => 'id']);
    }

    /**
     * Shorter linkClicks alias.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClicks()
    {
        return self::getLinksClicks();
    }
}
