<?php

namespace common\models\forms;

use Yii;
use yii\base\Model;
use common\models\member\Member;
use common\models\member\Level;

/**
 * Class CreditsLogForm
 * @package common\models\forms
 * @author Rf <1458015476@qq.com>
 */
class CreditsLogForm extends Model
{
    /**
     * @var Member
     */
    public $member;
    public $num = 0;
    public $credit_group;
    public $remark = '';
    public $map_id = 0;
    /**
     * 记录到消费数量
     *
     * @var bool
     */
    public $consume_change = false;

    /**
     * 支付类型
     *
     * @var int
     */
    public $pay_type = 0;

    /**
     * 字段类型(请不要占用)
     *
     * @var string
     */
    public $credit_type;

    /**
     * 更新级别
     *
     * @param float $consume_money 累计消费金额
     * @param int $accumulate_integral 累计积分
     */
    public function updateLevel(float $consume_money, int $accumulate_integral)
    {
        /** @var Level $level */
        $level = Yii::$app->services->memberLevel->getLevel(
            $this->member->current_level,
            $consume_money,
            $accumulate_integral
        );

        $level != false && Member::updateAll(['current_level' => $level->level], ['id' => $this->member->id]);
    }
}