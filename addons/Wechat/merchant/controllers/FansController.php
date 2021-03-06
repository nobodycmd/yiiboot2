<?php

namespace addons\Wechat\merchant\controllers;

use Yii;
use yii\data\Pagination;
use yii\web\Response;
use common\helpers\ArrayHelper;
use common\helpers\ResultHelper;
use addons\Wechat\common\models\Fans;
use addons\Wechat\common\models\FansTagMap;

/**
 * Class FansController
 * @package addons\Wechat\merchant\controllers
 * @author Rf <1458015476@qq.com>
 */
class FansController extends BaseController
{
    /**
     * 粉丝首页
     *
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws yii\web\UnprocessableEntityHttpException
     */
    public function actionIndex()
    {
        $follow = Yii::$app->request->get('follow', 1);
        $tag_id = Yii::$app->request->get('tag_id', null);
        $keyword = Yii::$app->request->get('keyword', null);

        $where = $keyword ? ['or', ['like', 'f.openid', $keyword], ['like', 'f.nickname', $keyword]] : [];

        // 关联角色查询
        $data = Fans::find()
            ->alias('f')
            ->where(['f.follow' => $follow])
            ->andWhere($where)
            ->joinWith("tags AS t", true, 'LEFT JOIN')
            ->filterWhere(['t.tag_id' => $tag_id])
            ->andFilterWhere(['f.merchant_id' => $this->getMerchantId()]);

        $pages = new Pagination(['totalCount' => $data->count(), 'pageSize' => $this->pageSize]);
        $models = $data->offset($pages->offset)
            ->with('tags', 'member')
            ->orderBy('followtime desc, unfollowtime desc')
            ->limit($pages->limit)
            ->all();

        // 全部标签
        $tags = Yii::$app->wechatService->fansTags->getList();

        return $this->render('index', [
            'models' => $models,
            'pages' => $pages,
            'follow' => $follow,
            'keyword' => $keyword,
            'tag_id' => $tag_id,
            'all_fans' => Yii::$app->wechatService->fans->getCountFollow(),
            'fansTags' => $tags,
            'allTag' => ArrayHelper::map($tags, 'id', 'name'),
        ]);
    }

    /**
     * 粉丝详情
     *
     * @param $id
     * @return string
     */
    public function actionView($id)
    {
        return $this->renderAjax('view', [
            'model' => Fans::findOne($id)
        ]);
    }

    /**
     * 发送消息
     *
     * @param $id
     * @return array|string
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws yii\web\UnprocessableEntityHttpException
     */
    public function actionSendMessage($openid)
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            try {
                $media_id = $data[$data['type']] ?? $data['content'];
                Yii::$app->wechatService->message->customer($openid, $data['type'], $media_id);
                return ResultHelper::json(200, '发送成功');
            } catch (\Exception $e) {
                return ResultHelper::json(422, $e->getMessage());
            }
        }

        return $this->renderAjax('send-message', [
            'model' => Yii::$app->wechatService->fans->findByOpenId($openid)
        ]);
    }

    /**
     * 贴标签
     *
     * @param $fan_id
     * @return string|Response
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionMoveTag($fan_id)
    {
        $fans = Yii::$app->wechatService->fans->findByIdWithTag($fan_id);

        // 用户当前标签
        $fansTags = array_column($fans['tags'], 'tag_id');
        if (Yii::$app->request->isPost) {
            $tags = Yii::$app->request->post('tag_id', []);
            FansTagMap::deleteAll(['fans_id' => $fan_id]);
            // 添加标签
            foreach ($tags as $tag_id) {
                !in_array($tag_id, $fansTags) && Yii::$app->wechat->app->user_tag->tagUsers([$fans['openid']], $tag_id);

                $model = new FansTagMap();
                $model->fans_id = $fan_id;
                $model->tag_id = $tag_id;
                $model->save();
            }

            // 移除标签
            foreach ($fansTags as $tag_id) {
                !in_array($tag_id, $tags) && Yii::$app->wechat->app->user_tag->untagUsers([$fans['openid']], $tag_id);
            }

            // 更新标签
            Yii::$app->wechatService->fansTags->getList(true);
            return $this->redirect(['index']);
        }

        return $this->renderAjax('move-tag', [
            'tags' => Yii::$app->wechatService->fansTags->getList(),
            'fansTags' => $fansTags,
        ]);
    }

    /**
     * 获取全部粉丝
     *
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function actionSyncAllOpenid()
    {
        $next_openid = Yii::$app->request->get('next_openid', '');
        // 设置关注全部为为关注
        empty($next_openid) && Fans::updateAll(['follow' => Fans::FOLLOW_OFF], ['merchant_id' => Yii::$app->services->merchant->getId()]);

        try {
            list($total, $count, $nextOpenid) = Yii::$app->wechatService->fans->syncAllOpenid($next_openid);

            return ResultHelper::json(200, '同步粉丝openid完成', [
                'total' => $total,
                'count' => $count,
                'next_openid' => $nextOpenid,
            ]);
        } catch (\Exception $e) {
            return ResultHelper::json(422, $e->getMessage());
        }
    }

    /**
     * 开始同步粉丝数据
     *
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws yii\db\Exception
     */
    public function actionSync()
    {
        $request = Yii::$app->request;
        $type = $request->post('type', 'all');
        $page = $request->post('page', 0);

        // 全部同步
        if ($type == 'all' && !empty($models = Yii::$app->wechatService->fans->getFollowListByPage($page))) {
            // 同步粉丝信息
            foreach ($models as $fans) {
                Yii::$app->wechatService->fans->syncByOpenid($fans['openid']);
            }

            return ResultHelper::json(200, '同步完成', [
                'page' => $page + 1
            ]);
        }

        // 选中同步
        if ($type == 'check') {
            if (empty($openids = $request->post('openids')) || !is_array($openids)) {
                return ResultHelper::json(404, '请选择粉丝');
            }

            // 系统内的粉丝
            if (!empty($sync_fans = Yii::$app->wechatService->fans->getListByOpenids($openids))) {
                // 同步粉丝信息
                foreach ($sync_fans as $fans) {
                    Yii::$app->wechatService->fans->syncByOpenid($fans['openid']);
                }
            }
        }

        return ResultHelper::json(200, '同步完成');
    }
}