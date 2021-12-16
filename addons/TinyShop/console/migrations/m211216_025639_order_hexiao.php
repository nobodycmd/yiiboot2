<?php

use yii\db\Migration;

/**
 *
 */
class m211216_025639_order_hexiao extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%addon_shop_product}}','hexiaoma','char(32) default "" comment "核销码，一般针对线下自提或者到店消费"');
        $this->addColumn('{{%addon_shop_product}}','hexiaoma_used','int default 0 comment "核销码被使用"');
        $this->addColumn('{{%addon_shop_product}}','hexiaoma_used_time','int default 0 comment "核销码被使用时间"');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211216_025639_order_hexiao cannot be reverted.\n";

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
