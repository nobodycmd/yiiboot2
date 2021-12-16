<?php

use yii\db\Migration;

/**
 * Class m211216_025639_product_is_open_wholesale
 */
class m211216_025639_product_is_open_wholesale extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%addon_shop_product}}','is_open_wholesale','int default 0 comment "是否开启拼团"');
        $this->addColumn('{{%addon_shop_product}}','wholesale_people','int default 2 comment "成团人数"');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211216_025639_product_is_open_wholesale cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211216_025639_product_is_open_wholesale cannot be reverted.\n";

        return false;
    }
    */
}
