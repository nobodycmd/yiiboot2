<?php

namespace services\member;

use Yii;
use common\enums\StatusEnum;
use common\helpers\EchantsHelper;
use yii\web\NotFoundHttpException;
use common\models\forms\CreditsLogForm;
use common\components\Service;
use common\models\member\CreditsLog;
use common\models\member\Account;

/**
 * Class CreditsLogService
 * @package services\member
 * @author Rf <1458015476@qq.com>
 */
class CreditsLogService extends Service
{
    /**
     * 增加积分
     *
     * @param CreditsLogForm $creditsLogForm
     * @return bool|CreditsLog
     * @throws NotFoundHttpException
     */
    public function incrInt(CreditsLogForm $creditsLogForm)
    {
        $creditsLogForm->num = abs($creditsLogForm->num);
        $creditsLogForm->credit_type = CreditsLog::CREDIT_TYPE_USER_INTEGRAL;

        return $this->userInt($creditsLogForm);
    }

    /**
     * 减少积分
     *
     * @param CreditsLogForm $creditsLogForm
     * @return bool|CreditsLog
     * @throws NotFoundHttpException
     */
    public function decrInt(CreditsLogForm $creditsLogForm)
    {
        $creditsLogForm->num = -abs($creditsLogForm->num);
        $creditsLogForm->credit_type = CreditsLog::CREDIT_TYPE_USER_INTEGRAL;

        return $this->userInt($creditsLogForm);
    }

    /**
     * 赠送
     *
     * @param CreditsLogForm $creditsLogForm
     * @return CreditsLog
     * @throws NotFoundHttpException
     */
    public function giveInt(CreditsLogForm $creditsLogForm)
    {
        $creditsLogForm->num = abs($creditsLogForm->num);
        $creditsLogForm->credit_type = CreditsLog::CREDIT_TYPE_GIVE_INTEGRAL;

        /** @var Account $account */
        $account = $creditsLogForm->member->account;
        // 直接记录日志不修改
        if ($creditsLogForm->num == 0) {
            return $this->create($creditsLogForm, $account->user_integral, $account->user_integral);
        }

        // 赠送增加
        if (!Account::updateAllCounters([
            'user_integral' => $creditsLogForm->num,
            'accumulate_integral' => $creditsLogForm->num,
            'give_integral' => $creditsLogForm->num,
        ], ['id' => $account->id])) {
            throw new NotFoundHttpException('积分赠送失败');
        }

        // 记录日志
        return $this->create($creditsLogForm, $account->user_integral, $account->user_integral + $creditsLogForm->num);
    }

    /**
     * 取消赠送
     *
     * @param CreditsLogForm $creditsLogForm
     * @return CreditsLog
     * @throws NotFoundHttpException
     */
    public function closeGiveInt(CreditsLogForm $creditsLogForm)
    {
        $creditsLogForm->num = -abs($creditsLogForm->num);
        $creditsLogForm->credit_type = CreditsLog::CREDIT_TYPE_GIVE_INTEGRAL;

        /** @var Account $account */
        $account = $creditsLogForm->member->account;
        // 直接记录日志不修改
        if ($creditsLogForm->num == 0) {
            return $this->create($creditsLogForm, $account->user_integral, $account->user_integral);
        }

        // 赠送增加
        if (!Account::updateAllCounters([
            'user_integral' => $creditsLogForm->num,
            'accumulate_integral' => $creditsLogForm->num,
            'give_integral' => $creditsLogForm->num,
        ], [
            'and',
            ['id' => $account->id],
            ['>=', 'user_integral', abs($creditsLogForm->num)],
        ])) {
            throw new NotFoundHttpException('积分取消赠送失败');
        }

        // 记录日志
        return $this->create($creditsLogForm, $account->user_integral, $account->user_integral + $creditsLogForm->num);
    }

    /**
     * 增加余额
     *
     * @param CreditsLogForm $creditsLogForm
     * @return bool|CreditsLog
     * @throws NotFoundHttpException
     */
    public function incrMoney(CreditsLogForm $creditsLogForm)
    {
        $creditsLogForm->num = abs($creditsLogForm->num);
        $creditsLogForm->credit_type = CreditsLog::CREDIT_TYPE_USER_MONEY;

        return $this->userMoney($creditsLogForm);
    }

    /**
     * 减少余额
     *
     * @param CreditsLogForm $creditsLogForm
     * @return bool|CreditsLog
     * @throws NotFoundHttpException
     */
    public function decrMoney(CreditsLogForm $creditsLogForm)
    {
        $creditsLogForm->num = -abs($creditsLogForm->num);
        $creditsLogForm->credit_type = CreditsLog::CREDIT_TYPE_USER_MONEY;

        return $this->userMoney($creditsLogForm);
    }

    /**
     * 赠送
     *
     * @param CreditsLogForm $creditsLogForm
     * @return CreditsLog
     * @throws NotFoundHttpException
     */
    public function giveMoney(CreditsLogForm $creditsLogForm)
    {
        $creditsLogForm->num = abs($creditsLogForm->num);
        $creditsLogForm->credit_type = CreditsLog::CREDIT_TYPE_GIVE_MONEY;

        /** @var Account $account */
        $account = $creditsLogForm->member->account;
        // 直接记录日志不修改
        if ($creditsLogForm->num == 0) {
            return $this->create($creditsLogForm, $account->user_money, $account->user_money);
        }

        // 赠送增加
        if (!Account::updateAllCounters([
            'user_money' => $creditsLogForm->num,
            'accumulate_money' => $creditsLogForm->num,
            'give_money' => $creditsLogForm->num,
        ], ['id' => $account->id])) {
            throw new NotFoundHttpException('金额赠送失败');
        }

        // 变动级别
        $creditsLogForm->updateLevel($account->consume_money, $account->accumulate_integral + $creditsLogForm->num);

        // 记录日志
        return $this->create($creditsLogForm, $account->user_money, $account->user_money + $creditsLogForm->num);
    }

    /**
     * 消费
     *
     * 一般用于微信/支付宝/银联消费记录使用
     *
     * @param CreditsLogForm $creditsLogForm
     * @return CreditsLog
     * @throws NotFoundHttpException
     */
    public function consumeMoney(CreditsLogForm $creditsLogForm)
    {
        $creditsLogForm->num = -abs($creditsLogForm->num);
        $creditsLogForm->credit_type = CreditsLog::CREDIT_TYPE_CONSUME_MONEY;

        /** @var Account $account */
        $account = $creditsLogForm->member->account;
        // 直接记录日志不修改
        if ($creditsLogForm->num == 0) {
            return $this->create($creditsLogForm, $account->consume_money, $account->consume_money);
        }

        // 消费
        if (!Account::updateAllCounters(['consume_money' => $creditsLogForm->num,], ['id' => $account->id])) {
            throw new NotFoundHttpException('消费失败');
        }

        // 变动级别
        $creditsLogForm->updateLevel($account->consume_money + $creditsLogForm->num, $account->accumulate_integral);

        // 记录日志
        return $this->create($creditsLogForm, $account->consume_money, $account->consume_money + $creditsLogForm->num);
    }

    /**
     * 积分变动
     *
     * @param CreditsLogForm $creditsLogForm
     * @return CreditsLog
     * @throws NotFoundHttpException
     */
    protected function userInt(CreditsLogForm $creditsLogForm)
    {
        /** @var Account $account */
        $account = $creditsLogForm->member->account;
        // 直接记录日志不修改
        if ($creditsLogForm->num == 0) {
            return $this->create($creditsLogForm, $account->user_integral, $account->user_integral);
        }

        if ($creditsLogForm->num > 0) {
            // 增加
            $status = Account::updateAllCounters([
                'user_integral' => $creditsLogForm->num,
                'accumulate_integral' => $creditsLogForm->num,
            ], ['id' => $account->id]);

            // 变动级别
            $creditsLogForm->updateLevel($account->consume_money, $account->accumulate_integral + $creditsLogForm->num);
        } else {
            // 消费
            $status = Account::updateAllCounters([
                'user_integral' => $creditsLogForm->num,
                'consume_integral' => $creditsLogForm->num
            ],
                [
                    'and',
                    ['id' => $account->id],
                    ['>=', 'user_integral', abs($creditsLogForm->num)],
                ]);
        }

        if ($status == false && $creditsLogForm->num < 0) {
            throw new NotFoundHttpException('积分不足');
        }

        if ($status == false && $creditsLogForm->num > 0) {
            throw new NotFoundHttpException('增加积分失败');
        }

        // 记录日志
        return $this->create($creditsLogForm, $account->user_integral, $account->user_integral + $creditsLogForm->num);
    }

    /**
     * 余额变动
     *
     * @param CreditsLogForm $creditsLogForm
     * @return CreditsLog
     * @throws NotFoundHttpException
     */
    protected function userMoney(CreditsLogForm $creditsLogForm)
    {
        /** @var Account $account */
        $account = $creditsLogForm->member->account;
        // 直接记录日志不修改
        if ($creditsLogForm->num == 0) {
            return $this->create($creditsLogForm, $account->user_money, $account->user_money);
        }

        if ($creditsLogForm->num > 0) {
            // 增加
            $status = Account::updateAllCounters([
                'user_money' => $creditsLogForm->num,
                'accumulate_money' => $creditsLogForm->num,
            ], ['id' => $account->id]);
        } else {
            // 消费
            $counters = ['user_money' => $creditsLogForm->num];
            // 增加消费数量
            $creditsLogForm->consume_change && $counters['consume_money'] = $creditsLogForm->num;

            $status = Account::updateAllCounters(
                $counters,
                [
                    'and',
                    ['id' => $account->id],
                    ['>=', 'user_money', abs($creditsLogForm->num)],
                ]);

            // 变动级别
            $creditsLogForm->updateLevel($account->consume_money + $creditsLogForm->num, $account->accumulate_integral);
        }

        if ($status == false && $creditsLogForm->num < 0) {
            throw new NotFoundHttpException('余额不足');
        }

        if ($status == false && $creditsLogForm->num > 0) {
            throw new NotFoundHttpException('增加余额失败');
        }

        // 记录日志
        return $this->create($creditsLogForm, $account->user_money, $account->user_money + $creditsLogForm->num);
    }

    /**
     * 获取区间充值
     *
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getRechargeStat($type, $title = '充值统计')
    {
        $fields = [
            'price' => $title,
        ];

        // 获取时间和格式化
        list($time, $format) = EchantsHelper::getFormatTime($type);
        // 获取数据
        return EchantsHelper::lineOrBarInTime(function ($start_time, $end_time, $formatting) {
            $data = CreditsLog::find()
                ->select(['sum(num) as price', "from_unixtime(created_at, '$formatting') as time"])
                ->where(['credit_type' => CreditsLog::CREDIT_TYPE_USER_MONEY])
                ->andWhere(['in', 'credit_group', ['backend', 'recharge']])
                ->andWhere(['>', 'status', StatusEnum::DISABLED])
                ->andWhere(['between', 'created_at', $start_time, $end_time])
                ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
                ->groupBy(['time'])
                ->asArray()
                ->all();

            foreach ($data as &$datum) {
                $datum['price'] = abs($datum['price']);
            }

            return $data;
        }, $fields, $time, $format);
    }

    /**
     * 获取区间消费统计
     *
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getBetweenCountStat($type, $credit_type = CreditsLog::CREDIT_TYPE_CONSUME_MONEY, $title = '第三方消费统计')
    {
        $fields = [
            'price' => $title,
        ];

        // 获取时间和格式化
        list($time, $format) = EchantsHelper::getFormatTime($type);
        // 获取数据
        return EchantsHelper::lineOrBarInTime(function ($start_time, $end_time, $formatting) use ($credit_type) {
            $data = CreditsLog::find()
                ->select(['sum(num) as price', "from_unixtime(created_at, '$formatting') as time"])
                ->where(['credit_type' => $credit_type])
                ->andWhere(['>', 'status', StatusEnum::DISABLED])
                ->andWhere(['between', 'created_at', $start_time, $end_time])
                ->groupBy(['time'])
                ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
                ->asArray()
                ->all();

            foreach ($data as &$datum) {
                $datum['price'] = abs($datum['price']);
            }

            return $data;
        }, $fields, $time, $format);
    }

    /**
     * @param CreditsLogForm $creditsLogForm
     * @param $oldNum
     * @param $newNum
     * @return CreditsLog
     * @throws NotFoundHttpException
     */
    protected function create(CreditsLogForm $creditsLogForm, $oldNum, $newNum)
    {
        $model = new CreditsLog();
        $model = $model->loadDefaultValues();
        $model->ip = Yii::$app->request->userIP;
        $model->merchant_id = $creditsLogForm->member->merchant_id;
        $model->member_id = $creditsLogForm->member->id;
        $model->pay_type = $creditsLogForm->pay_type;
        $model->old_num = $oldNum;
        $model->new_num = $newNum;
        $model->num = $creditsLogForm->num;
        $model->credit_type = $creditsLogForm->credit_type;
        $model->credit_group = $creditsLogForm->credit_group;
        $model->remark = $creditsLogForm->remark;
        $model->map_id = $creditsLogForm->map_id;

        if (!$model->save()) {
            throw new NotFoundHttpException($this->getError($model));
        }

        return $model;
    }
}