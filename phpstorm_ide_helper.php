<?php

/**
 * IDE 组件提示,无任何实际功能
 *
 * Class Yii
 */
class Yii
{
    /**
     * @var MyApplication
     */
    public static $app;
}

/**
 * Class MyApplication
 *
 * @property \yii\mutex\MysqlMutex $mutex
 * @property \yii\redis\Connection $redis
 * @property \yii\redis\Connection $websocketRedis
 * @property \yii\queue\cli\Queue $queue
 * @property \services\Application $services
 * @property \common\components\Debris $debris
 * @property \common\components\Pay $pay
 * @property \common\components\Logistics $logistics
 * @property \common\components\UploadDrive $uploadDrive
 * @property \common\components\BaseAddonModule $addons
 * @property \addons\TinyShop\services\Application $tinyShopService
 * @property \addons\TinyDistribution\services\Application $tinyDistributionService
 * @property \addons\Wechat\services\Application $wechatService
 * @property \addons\RfOnlineDoc\services\Application $rfOnlineDocService
 * @property \addons\TinyService\services\Application $tinyServiceService
 * @property \addons\BigWheel\services\Application $bigWheelService
 * @property \addons\LivingAliyun\services\Application $livingAliyunService
 * @property \addons\TinyForum\services\Application $tinyForumService
 * @property \Detection\MobileDetect $mobileDetect
 * @property \jianyan\easywechat\Wechat $wechat
 * @property \Da\QrCode\Component\QrCodeComponent $qr
 *
 * @author Rf <1458015476@qq.com>
 */
class MyApplication
{

}
