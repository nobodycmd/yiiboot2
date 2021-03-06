<?php

namespace addons\RfExample\merchant\controllers;

use yii;
use common\helpers\ArrayHelper;
use common\interfaces\AddonsSetting;
use addons\RfExample\common\models\SettingForm;

/**
 * 参数设置
 *
 * Class SettingController
 * @package addons\RfExample\merchant\controllers
 * @author Rf <1458015476@qq.com>
 */
class SettingController extends BaseController implements AddonsSetting
{
    /**
     * @return mixed|string
     */
    public function actionDisplay()
    {
        $request = Yii::$app->request;
        $model = new SettingForm();
        $model->attributes = $this->getConfig();

        if ($model->load($request->post()) && $model->validate()) {
            $this->setConfig(ArrayHelper::toArray($model));

            return $this->message('修改成功', $this->redirect(['display']));
        }

        return $this->render('display', [
            'model' => $model,
        ]);
    }

    /**
     * 钩子
     *
     * @param array $param
     * @return mixed|string
     */
    public function actionHook($param = [])
    {
        return $this->render('hook', [
            'param' => $param,
        ]);
    }
}