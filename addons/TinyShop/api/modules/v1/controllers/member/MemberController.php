<?php

namespace addons\TinyShop\api\modules\v1\controllers\member;

use Yii;
use yii\web\NotFoundHttpException;
use common\helpers\ResultHelper;
use common\models\member\Member;
use common\enums\StatusEnum;
use common\helpers\AddonHelper;
use api\controllers\OnAuthController;
use addons\TinyShop\common\models\SettingForm;

/**
 * 个人信息
 *
 * Class MemberController
 * @package addons\TinyShop\api\controllers
 * @author Rf <1458015476@qq.com>
 */
class MemberController extends OnAuthController
{
    /**
     * @var Member
     */
    public $modelClass = Member::class;

    /**
     * 个人中心
     *
     * @return array|null|\yii\data\ActiveDataProvider|\yii\db\ActiveRecord
     */
    public function actionIndex()
    {
        $member_id = Yii::$app->user->identity->member_id;

        $member = $this->modelClass::find()
            ->where(['id' => $member_id])
            ->with(['account', 'memberLevel'])
            ->asArray()
            ->one();

        // 优惠券数量
        $member['coupon_num'] = Yii::$app->tinyShopService->marketingCoupon->findCountByMemberId($member_id);
        // 购物车数量
        $member['cart_num'] = Yii::$app->tinyShopService->memberCartItem->count($member_id);
        // 订单数量统计
        $member['order_synthesize_num'] = Yii::$app->tinyShopService->order->getOrderCountGroupByMemberId($member_id);
        $member['promoter'] = '';

        // 开启分销商
        $setting = new SettingForm();
        $setting->attributes = AddonHelper::getConfig();
        $member['is_open_commission'] = $setting->is_open_commission;
        if ($setting->is_open_commission == StatusEnum::ENABLED) {
            $member['promoter'] = Yii::$app->tinyDistributionService->promoter->findByMemberId($member_id);
        }

        return $member;
    }

    /**
     * 更新
     *
     * @param $id
     * @return bool|mixed
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $data = Yii::$app->request->post();
        unset(
            $data['password_hash'],
            $data['mobile'],
            $data['username'],
            $data['auth_key'],
            $data['password_reset_token'],
            $data['promo_code']
        );

        $model = $this->findModel($id);
        $model->attributes = $data;
        if (!$model->save()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        return 'ok';
    }

    /**
     * 权限验证
     *
     * @param string $action 当前的方法
     * @param null $model 当前的模型类
     * @param array $params $_GET变量
     * @throws \yii\web\BadRequestHttpException
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (empty($id) || !($model = Member::find()->where(['id' => Yii::$app->user->identity->member_id])->one())) {
            throw new NotFoundHttpException('请求的数据不存在或您的权限不足.');
        }

        return $model;
    }
}