<?php

namespace common\controllers;

use common\helpers\ArrayHelper;
use Yii;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;
use common\helpers\Auth;
use common\helpers\AddonHelper;
use common\traits\BaseAction;
use common\enums\AppEnum;
use common\behaviors\ActionLogBehavior;

/**
 * 模块基类控制器
 *
 * Class AddonsController
 * @package common\controllers
 * @author Rf <1458015476@qq.com>
 */
class AddonsController extends Controller
{
    use BaseAction;

    /**
     * 视图自动加载文件路径
     *
     * @var string
     */
    public $layout = null;

    /**
     * 是否钩子
     *
     * @var bool
     */
    public $isHook = false;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        // 后台视图默认载入模块视图
        if (!$this->layout && in_array(Yii::$app->id, [AppEnum::BACKEND, AppEnum::MERCHANT])) {
            $this->layout = '@' . Yii::$app->id . '/views/layouts/addon';
        }
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'actionLog' => [
                'class' => ActionLogBehavior::class
            ]
        ];
    }

    /**
     * @param $action
     * @return bool
     * @throws UnauthorizedHttpException
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // 每页数量
        $this->pageSize = Yii::$app->request->get('per-page', 10);
        $this->pageSize > 50 && $this->pageSize = 50;

        // 后台进行权限校验
        if (in_array(Yii::$app->id, [AppEnum::MERCHANT, AppEnum::BACKEND])) {
            // 判断当前模块的是否为主模块, 模块+控制器+方法
            $permissionName = '/' . Yii::$app->controller->route;
            // 判断是否忽略校验
            if (in_array($permissionName, Yii::$app->params['noAuthRoute'])) {
                return true;
            }
            // 开始权限校验
            if (!Auth::verify($permissionName)) {
                throw new UnauthorizedHttpException('对不起，您现在还没获此操作的权限');
            }

            // 记录上一页跳转
            $this->setReferrer($action->id);
        }

        $this->pageParam = ArrayHelper::merge($_GET,$_POST);
        return true;
    }

    /**
     * 获取配置信息
     *
     * @return mixed
     */
    protected function getConfig()
    {
        return AddonHelper::getConfig(false);
    }

    /**
     * 写入配置信息
     *
     * @param $config
     * @return bool
     */
    protected function setConfig($config)
    {
        return AddonHelper::setConfig($config);
    }


    /**
     * 页面提交参数
     * get post 一起存储
     *
     * @var array
     */
    protected $pageParam = [];

    /**
     * 获取页面参数
     * @param string $name
     * @param false $default
     * @return array|false|mixed
     */
    public function getPageParam($name='',$default=false){
        if($name == false){
            return $this->pageParam;
        }
        if(isset($this->pageParam[$name])){
            return $this->pageParam[$name];
        }
        return $default;
    }

}