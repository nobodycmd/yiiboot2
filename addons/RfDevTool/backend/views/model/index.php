<?php

use yii\widgets\ActiveForm;
use common\helpers\Html;

$this->title = '模型生成（针对模块）';
$this->params['breadcrumbs'][] = ['label' => $this->title];

?>

<style>
    label {
        width: 33%;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
            </div>
            <?php $form = ActiveForm::begin([]); ?>
            <div class="box-body">
                <div class="col-sm-12">
                    <?= $form->field($model, 'addon')->dropDownList($addonList)->hint('选择插件后文件在插件的 common/model 里面'); ?>
                    <?= $form->field($model, 'tables')->checkboxList($tableList); ?>
                </div>
            </div>
            <div class="box-footer text-center">
                <button class="btn btn-primary" type="submit">立即创建</button>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
