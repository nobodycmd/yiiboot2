<?php

namespace addons\TinyShop\common\components\delivery;

use Yii;
use yii\web\UnprocessableEntityHttpException;
use addons\TinyShop\common\components\PreviewInterface;
use addons\TinyShop\common\models\forms\PreviewForm;
use common\enums\StatusEnum;

/**
 * 自提
 *
 * Class PickupDelivery
 * @author Rf <1458015476@qq.com>
 */
class PickupDelivery extends PreviewInterface
{
    /**
     * @param PreviewForm $form
     * @return PreviewForm
     * @throws UnprocessableEntityHttpException
     */
    public function execute(PreviewForm $form): PreviewForm
    {
        if ($form->buyer_self_lifting != StatusEnum::ENABLED) {
            throw new UnprocessableEntityHttpException('未开启商品自提');
        }

        if (!$form->pickup_id) {
            throw new UnprocessableEntityHttpException('请选择自提地点');
        }

        if (!($form->pickup = Yii::$app->tinyShopService->pickupPoint->findById($form->pickup_id))) {
            throw new UnprocessableEntityHttpException('自提地点不存在');
        }

        // 自提运费开启
        if ($form->pickup_point_is_open == StatusEnum::ENABLED) {
            // 计算运费
            $form->shipping_money = $form->pickup_point_fee;
            // 免邮
            if (!empty($form->pickup_point_freight) && $form->pickup_point_freight <= $form->product_money) {
                $form->shipping_money = 0;
            }
        }

        return $form;
    }

    /**
     * 排斥营销
     *
     * @return array
     */
    public function rejectNames()
    {
        return [];
    }

    /**
     * 营销名称
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'pickup';
    }
}