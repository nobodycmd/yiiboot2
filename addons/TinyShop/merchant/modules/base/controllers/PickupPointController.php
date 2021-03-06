<?php

namespace addons\TinyShop\merchant\modules\base\controllers;

use Yii;
use common\enums\StatusEnum;
use common\traits\MerchantCurd;
use common\helpers\ArrayHelper;
use common\models\base\SearchModel;
use addons\TinyShop\common\models\pickup\Point;
use addons\TinyShop\merchant\forms\PickupPointConfigForm;
use addons\TinyShop\merchant\controllers\BaseController;

/**
 * Class PickupPointController
 * @package addons\TinyShop\merchant\controllers
 * @author Rf <1458015476@qq.com>
 */
class PickupPointController extends BaseController
{
    use MerchantCurd;

    /**
     * @var Point
     */
    public $modelClass = Point::class;

    /**
     * 首页
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => ['name', 'contact', 'mobile'], // 模糊查询
            'defaultOrder' => [
                'sort' => SORT_ASC,
                'id' => SORT_DESC,
            ],
            'pageSize' => $this->pageSize,
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * @return mixed|string
     */
    public function actionConfig()
    {
        $request = Yii::$app->request;
        $model = new PickupPointConfigForm();
        $model->attributes = $this->getConfig();
        if ($model->load($request->post()) && $model->validate()) {
            $this->setConfig(ArrayHelper::toArray($model));

            return $this->message('修改成功', $this->redirect(['config']));
        }

        return $this->render('config', [
            'model' => $model,
        ]);
    }
}