<?php

namespace addons\Merchants\merchant\modules\base\controllers;

use common\traits\AuthRoleTrait;
use common\models\rbac\AuthRole;
use common\enums\AppEnum;
use addons\Merchants\merchant\controllers\BaseController;

/**
 * Class AuthRoleController
 * @package addons\Merchants\merchant\modules\base\controllers
 * @author Rf <1458015476@qq.com>
 */
class AuthRoleController extends BaseController
{
    use AuthRoleTrait;

    /**
     * @var AuthRole
     */
    public $modelClass = AuthRole::class;

    /**
     * 默认应用
     *
     * @var string
     */
    public $appId = AppEnum::MERCHANT;

    /**
     * 权限来源
     *
     * false:所有权限，true：当前角色
     *
     * @var bool
     */
    //public $sourceAuthChild = true;
    public $sourceAuthChild = false;

    /**
     * 渲染视图前缀
     *
     * @var string
     */
    public $viewPrefix = '@backend/modules/base/views/auth-role/';
}
