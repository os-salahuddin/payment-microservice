<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use app\components\Utils;
use app\components\LogBehavior;

/**
 * This is the model class for table "transaction".
 *
 * @property string $uid
 * @property int gatewayMode
 * @property int $status
 * @property int $createdBy
 * @property int $updatedBy
 * @property string $createdAt
 * @property string $updatedAt
 */
class Gateway extends ActiveRecord
{
    const GATEWAY_MODE_TEST_INT = 0;
    const GATEWAY_MODE_LIVE_INT = 1;
    const STATUS_ACTIVE = 1;

    const GATEWAY_CBL = 'CBL';
    const GATEWAY_UCB = 'UCB';
    const GATEWAY_EBL = 'EBL';
    const GATEWAY_SBL = 'SBL';
    const GATEWAY_EBL_USD = 'EBL_USD';
    const GATEWAY_BKASH = 'Bkash';
    const GATEWAY_BKASH_ST = 'BKASH_ST';
    const GATEWAY_NAGAD = 'Nagad';
    const GATEWAY_NEXUS = 'Nexus';
    const GATEWAY_BRAC = 'BBL';
    const GATEWAY_BRAC_CYBERSOURCE = 'BRAC_CYBERSOURCE';
    const GATEWAY_CHECKOUT = 'CHECKOUT';
    const GATEWAY_EBL_CYBERSOURCE = 'EBL_CYBERSOURCE';
    const GATEWAY_EBL_CYBERSOURCE_USD = 'EBL_CYBERSOURCE_USD';
    const GATEWAY_EBL_TOKENIZATION = 'EBLTokenization';
    const GATEWAY_EBL_TOKENIZATION_USD = 'EBLTokenizationUSD';
    const GATEWAY_TAZAPAY = 'Tazapay';
    const GATEWAY_POCKET = 'Pocket';
    const GATEWAY_UPAY = 'UPay';
    const GATEWAY_TAP = 'TAP';
    const GATEWAY_DESHI_PAY = 'DeshiPay';
    const GATEWAY_DESHI_PAY_B2C = 'DeshiPay_B2C';
    const GATEWAY_SSLCOMMERZ = 'SSLCommerz';
    const GATEWAY_OKWALLET = 'OkWallet';
    const GATEWAY_EKPAY = 'EkPay';

    public static function tableName(): string
    {
        return 'gateway';
    }

    public function rules(): array
    {
        return [
            [['gatewayMode', 'name', 'code', 'charge', 'sandboxUrl', 'liveUrl', 'merchant', 'logo', 'status'], 'required'],
            [['charge'], 'number'],
            [['createdBy', 'updatedBy'], 'integer'],
            [['extraParams', 'logo', 'currency'], 'string'],
            [['gatewayMode', 'status'], 'integer'],
            [['createdAt', 'updatedAt'], 'safe'],
            [['name', 'code', 'merchant'], 'string', 'max' => 50],
            [['sandboxUrl', 'liveUrl'], 'string', 'max' => 200],
            [['merchantPassword'], 'string', 'max' => 256],
            [['name'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'uid' => 'Code',
            'name' => 'Name',
            'code' => 'Gateway Code',
            'charge' => 'Charge',
            'sandboxUrl' => 'SandBox Url',
            'liveUrl' => 'Live Url',
            'merchant' => 'Merchant Merchant ID',
            'merchantPassword' => 'Merchant Password',
            'currency' => 'Currency',
            'gatewayMode' => 'Gateway Mode',
            'logo' => 'Logo',
            'extraParams' => 'Extra Params',
            'status' => 'Status',
            'createdBy' => 'Created By',
            'updatedBy' => 'Updated By',
            'createdAt' => 'Created At',
            'updatedAt' => 'Updated At',
        ];
    }

    public function behaviors(): array
    {
        return [
            'class' => LogBehavior::class,
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($this->isNewRecord) {

            $this->uid = Utils::uniqueCode(36);
            $this->createdBy = Yii::$app->user->id;
            $this->updatedBy = Yii::$app->user->id;
            $this->createdAt = date('Y-m-d h:i:s');
        } else {
            $this->updatedBy = Yii::$app->user->id;
        }
        $this->updatedAt = date('Y-m-d h:i:s');

        return parent::beforeSave($insert);
    }

    public function getCreator(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'createdBy']);
    }

    public function getUpdater(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'updatedBy']);
    }

    public function getRelatedCurrency(): ActiveQuery
    {
        return $this->hasOne(Currency::class, ['uid' => 'currency']);
    }

    public function getRelatedLogo(): ActiveQuery
    {
        return $this->hasOne(Logo::class, ['uid' => 'logo']);
    }

    public function getGatewayRelatedBank(): ActiveQuery
    {
        return $this->hasMany(Bank::class, ['gateway' => 'uid']);
    }

    public function getRelatedCardType(): ActiveQuery
    {
        return $this->hasOne(CardType::class, ['gateway' => 'uid']);
    }

    public function getConversion(): ActiveQuery
    {
        return $this->hasOne(CurrencyConversion::class, ['fromCurrency' => 'currency']);
    }

    public static function dropDownList(): array
    {
        $values = static::findAll(['status' => 1]);
        $list = [];
        foreach ($values as $value) {
            if (!empty($value)) {
                $list[$value->uid] = $value->name;
            }
        }
        return $list;
    }

    public static function getByKey($key, $val)
    {
        $options = self::find()->where([$key => $val, 'status' => Gateway::STATUS_ACTIVE])->one();
        if ($options) {
            return $options;
        } else {
            return null;
        }
    }
}