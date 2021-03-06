<?php
namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Class HeadJsAsset
 * @package frontend\assets
 * @author Rf <1458015476@qq.com>
 */
class HeadJsAsset extends AssetBundle
{
    public $basePath = '@webroot';

    public $baseUrl = '@web';

    public $js = [
        'resources/js/jquery.min.js?v=2.1.4',
    ];

    public $jsOptions = [
        'position' => \yii\web\View::POS_HEAD
    ];
}
