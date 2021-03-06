<?php

namespace merapi\modules\v1\controllers;

use common\traits\FileActions;
use merapi\controllers\OnAuthController;

/**
 * 资源上传控制器
 *
 * Class FileController
 * @package merapi\modules\v1\controllers
 * @property \yii\db\ActiveRecord $modelClass
 * @author Rf <1458015476@qq.com>
 */
class FileController extends OnAuthController
{
    use FileActions;

    public $modelClass = '';
}