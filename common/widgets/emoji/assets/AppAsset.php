<?php

namespace common\widgets\emoji\assets;

use yii\web\AssetBundle;

/**
 * Class AppAsset
 * @package common\widgets\emoji\assets
 * @author Rf <1458015476@qq.com>
 */
class AppAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@common/widgets/emoji/resources/';

    public $css = [
        'css/style.css',
    ];

    public $js = [
        'js/emoji.map.js',
        'js/qq-wechat-emotion-parser.min.js',
    ];
}