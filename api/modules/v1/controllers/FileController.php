<?php

namespace api\modules\v1\controllers;

use common\traits\FileActions;
use api\controllers\OnAuthController;

/**
 * 资源上传控制器
 *
 * Class FileController
 * @package api\modules\v1\controllers
 * @property \yii\db\ActiveRecord $modelClass
 * @author Rf <1458015476@qq.com>
 */
class FileController extends OnAuthController
{
    use FileActions;

    /**
     * @var string
     */
    public $modelClass = '';
}