<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%link_click}}`.
 */
class m260311_132926_create_link_click_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%link_click}}', [
            'id'            => $this->primaryKey(),
            'link_id'       => $this->integer()->notNull(),
            'user_ip'       => $this->string(15)->notNull(),
            'created_at'    => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->notNull(),
            'updated_at'    => $this->timestamp(),
        ]);

        $this->addForeignKey(
            'fk-click-link_id',
            '{{%link_click}}',
            'link_id',
            'link',
            'id',
            'RESTRICT'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-click-link_id', '{{%link_click}}');
        $this->dropTable('{{%link_click}}');
    }
}
