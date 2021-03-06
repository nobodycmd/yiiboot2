<?php

namespace common\widgets\menu;

use Yii;
use yii\base\Widget;

/**
 * 左边菜单
 *
 * Class MenuLeftWidget
 * @package common\widgets\menu
 * @author Rf <1458015476@qq.com>
 */
class MenuLeftWidget extends Widget
{
    /**
     * @return string
     */
    public function run()
    {
        return $this->render('menu-left', [
            'menus' => Yii::$app->services->menu->getOnAuthList(),
            'addonsMenus' => Yii::$app->services->addons->getMenus(),
        ]);
    }
}