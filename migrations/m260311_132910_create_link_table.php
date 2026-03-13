<?php

use app\models\Link;
use chillerlan\QRCode\QRCode;
use yii\db\Migration;

/**
 * Handles the creation of table `{{%link}}`.
 */
class m260311_132910_create_link_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%link}}', [
            'id'            => $this->primaryKey(),
            'url_full'      => $this->string(255)->notNull()->unique(),
            'url_short'     => $this->string(Link::URL_SHORT_LENGTH*2)->notNull(),
            'user_ip'       => $this->string(15),
            'qrcode'        => $this->text(),
            'clicks'        => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'created_at'    => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->notNull(),
            'updated_at'    => $this->timestamp(),
        ]);

        $url_full   = 'https://www.google.com/search?q=%D0%B4%D0%B5%D0%BD%D1%8C+%D0%BD%D0%B5%D0%B4%D0%B5%D0%BB%D0%B8';
        $qrcode     = (new QRCode)->render($url_full);
        $url_short  = Link::getUrlShort($qrcode);
        $this->insert('{{%link}}', [
            'qrcode'    => $qrcode,
            'url_full'  => $url_full,
            'url_short' => $url_short,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%link}}');
    }
}
