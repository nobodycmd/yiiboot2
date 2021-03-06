<?php

namespace addons\TinyShop\api\modules\v1\controllers\order;

use Yii;
use common\enums\StatusEnum;
use common\helpers\ResultHelper;
use common\models\api\AccessToken;
use common\helpers\ArrayHelper;
use common\helpers\AddonHelper;
use common\models\forms\CreditsLogForm;
use api\controllers\OnAuthController;
use addons\TinyShop\common\models\order\Order;
use addons\TinyShop\common\models\forms\PreviewForm;
use addons\TinyShop\common\components\InitOrderData;
use addons\TinyShop\common\components\PreviewHandler;
use addons\TinyShop\common\components\marketing\FeeHandler;
use addons\TinyShop\common\components\marketing\FullMailHandler;
use addons\TinyShop\common\components\marketing\UsePointHandler;
use addons\TinyShop\common\components\marketing\CouponHandler;
use addons\TinyShop\common\components\marketing\AfterHandler;
use addons\TinyShop\common\enums\ShippingTypeEnum;
use addons\TinyShop\common\enums\PreviewTypeEnum;
use addons\TinyShop\common\models\SettingForm;

/**
 * 订单
 *
 * Class OrderController
 * @package addons\TinyShop\api\modules\v1\controllers\order
 * @author Rf <1458015476@qq.com>
 */
class OrderController extends OnAuthController
{
    /**
     * @var Order
     */
    public $modelClass = Order::class;

    /**
     * @var array
     */
    protected $handlers = [
        FullMailHandler::class,// 满包邮
        FeeHandler::class,// 运费计算
        CouponHandler::class,// 优惠券
        UsePointHandler::class,// 积分抵现
        AfterHandler::class,// 统一处理
    ];

    /**
     * 执行外部营销
     *
     * @var PreviewHandler
     */
    protected $previewHandler;

    /**
     * 订单预览
     *
     * @var PreviewForm
     */
    protected $previewForm;

    /**
     * @param $action
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->previewHandler = new PreviewHandler($this->handlers);

        /** @var SettingForm $setting */
        $setting = Yii::$app->tinyShopService->config->setting();
        /** @var AccessToken $identity */
        $identity = Yii::$app->user->identity;

        $model = new PreviewForm();
        $model = $model->loadDefaultValues();
        $model->buyer_id = $identity->member_id;
        $model->close_all_logistics = $setting->close_all_logistics; // 配送关闭
        $model->is_open_logistics = $setting->is_open_logistics; // 物流开启
        $model->is_logistics = $setting->is_logistics; // 物流可选
        $model->buyer_self_lifting = $setting->buyer_self_lifting ?? 0; // 开启自提
        $model->pickup_point_is_open = $setting->pickup_point_is_open ?? 0; // 自提运费开启
        $model->pickup_point_fee = $setting->pickup_point_fee ?? 0;
        $model->pickup_point_freight = $setting->pickup_point_freight ?? 0;
        $model->order_invoice_tax = $setting->order_invoice_tax; // 税率
        $model->invoice_content_default = isset($setting->order_invoice_content) ? explode(',', $setting->order_invoice_content) : [];

        $this->previewForm = $model;

        return true;
    }

    /**
     * 订单预览
     *
     * @return array|mixed
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionPreview()
    {
        /** @var AccessToken $identity */
        /** @var PreviewForm $model */

        $identity = Yii::$app->user->identity;
        $model = $this->previewForm;
        !$model->shipping_type && $model->shipping_type = ShippingTypeEnum::LOGISTICS;
        $model->member = Yii::$app->tinyShopService->member->findById($identity->member_id);
        empty($model->address_id) && $model->address = Yii::$app->services->memberAddress->findDefaultByMemberId($identity->member_id); // 默认地址
        $model->attributes = Yii::$app->request->get();

        // 默认运费模板
        $company = [];
        if ($model->is_logistics == true && ($company = Yii::$app->tinyShopService->expressCompany->getList()) && !$model->company_id) {
            $model->company_id = $company[0]['id'];
        }

        // 触发 - 初始化数据
        $initOrderData = new InitOrderData();
        $model = $initOrderData->execute($model, $model->type);
        !empty($model->address_id) && $model->address = Yii::$app->services->memberAddress->findById($model->address_id, $identity->member_id);

        // 触发 - 营销
        $model = $this->previewHandler->start($model);
        if ($model->getErrors() || !$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        // 用户自提
        $pickup_point = [];
        if ($model->buyer_self_lifting == StatusEnum::ENABLED) {
            $pickup_point = Yii::$app->tinyShopService->pickupPoint->getList();
        }

        $coupons = Yii::$app->tinyShopService->marketingCoupon->getListByMemberId($identity->member_id, $model->orderProducts);
        return [
            'account' => $model->member->account,
            'address' => $model->address,
            'products' => $model->orderProducts,
            'marketing_full_details' => $model->marketingDetails,
            'marketing_details' => Yii::$app->tinyShopService->marketing->mergeIdenticalMarketing($model->marketingDetails), // 被触发的自带营销规则
            'is_full_mail' => $model->is_full_mail,
            'is_logistics' => $model->is_logistics,
            'is_open_logistics' => $model->is_open_logistics,
            'close_all_logistics' => $model->close_all_logistics,
            'max_use_point' => $model->max_use_point,
            'preview' => ArrayHelper::toArray($model),
            'company' => $company,
            'point_config' => Yii::$app->tinyShopService->marketingPointConfig->one($model->merchant_id),
            'coupons' => $coupons,
            'full_payment' => $model->full_payment,
            'pickup_point_config' => [
                'list' => $pickup_point,
                'buyer_self_lifting' => $model->buyer_self_lifting,
                'pickup_point_is_open' => $model->pickup_point_is_open,
                'pickup_point_fee' => $model->pickup_point_fee,
                'pickup_point_freight' => $model->pickup_point_freight,
            ],
            'invoice' => [
                'list' => $model->invoice_content_default,
                'order_invoice_tax' => $model->order_invoice_tax,
            ],
        ];
    }

    /**
     * 创建订单
     *
     * @return Order|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        /** @var AccessToken $identity */
        /** @var PreviewForm $model */
        $identity = Yii::$app->user->identity;
        $model = $this->previewForm;
        $model->setScenario('create');
        $model->attributes = Yii::$app->request->post();
        $model->member = Yii::$app->tinyShopService->member->findById($identity->member_id);

        // 触发 - 初始化数据
        $initOrderData = new InitOrderData();
        $initOrderData->isNewRecord = true;
        $model = $initOrderData->execute($model, $model->type);
        $model->address_id && $model->address = Yii::$app->services->memberAddress->findById($model->address_id, $identity->member_id);

        // 触发 - 营销
        $model = $this->previewHandler->start($model, true);
        if ($model->getErrors() || !$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        // 配置信息
        $setting = new SettingForm();
        $setting->attributes = AddonHelper::getConfig(false, '', $model->merchant_id);
        $model->close_time = time() + $setting->order_buy_close_time * 60;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 订单来源
            $model->order_from = Yii::$app->user->identity->group;
            $order = Yii::$app->tinyShopService->order->create($model);
            // 消耗积分
            if ($order->point > 0) {
                Yii::$app->services->memberCreditsLog->decrInt(new CreditsLogForm([
                    'member' => $model->member,
                    'num' => $order->point,
                    'credit_group' => 'orderCreate',
                    'map_id' => $order->id,
                    'remark' => Yii::$app->params['tinyShopName']  . '订单支付',
                ]));
            }

            // 支付金额为0 直接支付
            $order->pay_money == 0 && Yii::$app->tinyShopService->order->pay($order, $order->payment_type);

            // 删除购物车
            if ($model->type == PreviewTypeEnum::CART) {
                $sku_ids = ArrayHelper::getColumn($model->orderProducts, 'sku_id');
                !empty($sku_ids) && Yii::$app->tinyShopService->memberCartItem->deleteBySkuIds($sku_ids, $order->buyer_id);
            }

            $transaction->commit();

            return $order;
        } catch (\Exception $e) {
            $transaction->rollBack();

            return ResultHelper::json(422, $e->getMessage());
        }
    }

    /**
     * 运费计算
     *
     * @return array|mixed
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionFreightFee()
    {
        /** @var AccessToken $identity */
        /** @var PreviewForm $model */
        $identity = Yii::$app->user->identity;
        $model = $this->previewForm;

        // 关闭了物流配送或者全部物流的情况下返回 0
        if (!empty($model->close_all_logistics) || empty($model->is_open_logistics)) {
            return [
                'shipping_money' => 0,
            ];
        }

        $model->shipping_type = ShippingTypeEnum::LOGISTICS;
        $model->member = Yii::$app->tinyShopService->member->findById($identity->member_id);
        $model->attributes = Yii::$app->request->get();
        $model->address = Yii::$app->services->memberAddress->findById($model->address_id, $identity->member_id); // 默认地址

        if (!$model->address) {
            return ResultHelper::json(422, '找不到收货地址');
        }

        // 触发 - 初始化数据
        $model = (new InitOrderData())->execute($model, $model->type);
        // 触发 - 营销
        $model = $this->previewHandler->start($model, true);
        if ($model->getErrors() || !$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        // 包邮
        if ($model->is_full_mail == true) {
            return [
                'shipping_money' => 0,
            ];
        }

        return [
            'shipping_money' => $model->shipping_money,
        ];
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
        if (in_array($action, ['view', 'delete', 'update'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}