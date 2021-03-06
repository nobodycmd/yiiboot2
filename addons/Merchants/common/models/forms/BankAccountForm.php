<?php

namespace addons\Merchants\common\models\forms;

use common\enums\AccountTypeEnum;
use common\helpers\ArrayHelper;
use common\models\merchant\BankAccount;

/**
 * Class BankAccountForm
 * @package common\models\forms
 * @author Rf <1458015476@qq.com>
 */
class BankAccountForm extends BankAccount
{
    /**
     * @return array
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            ['account_type', 'verifyAccountType']
        ]);
    }

    /**
     * @param $attribute
     */
    public function verifyAccountType($attribute)
    {
        if ($this->account_type == AccountTypeEnum::UNION) {
            !$this->account_number && $this->addError('account_number', '请填写银行账号');
            !$this->branch_bank_name && $this->addError('branch_bank_name', '请填写支行信息');
        }

        if ($this->account_type == AccountTypeEnum::ALI) {
            !$this->ali_number && $this->addError('ali_number', '请填写支付宝账号');
        }
    }
}