<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use app\components\LogBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "refundTransaction".
 *
 * @property int $id
 * @property string $uid
 * @property int $transactionId
 * @property string $response
 * @property string $bankStatus
 * @property float $amount
 * @property float $charge
 * @property int $status
 * @property string $createdAt
 * @property string $updatedAt
 */
class RefundTransaction extends ActiveRecord
{
    const CITY_REFUND_SUCCESS = "Refund Sussessful"; //Do not Correct This Typo
    const REFUND_SUCCESS = 1;
    const REFUND_FAILED = 0;
    const TRANSACTION_TYPE_REFUND = 1;
    const TRANSACTION_TYPE_VOID = 2;

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'createdAt',
                'updatedAtAttribute' => 'updatedAt',
                'value' => time(),
            ],
            [
                'class' => LogBehavior::class,
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'refundTransaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['uid', 'transactionId', 'response', 'amount', 'currency', 'transactionType', 'status'], 'required'],
            [['transactionType', 'status'], 'integer'],
            [['response'], 'string'],
            [['amount', 'charge'], 'number'],
            [['createdAt', 'updatedAt'], 'integer'],
            [['uid', 'transactionId'], 'string', 'max' => 36],
            [['bankStatus'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'uid' => 'Code',
            'transactionId' => 'Transaction ID',
            'response' => 'Response',
            'bankStatus' => 'Bank Status',
            'amount' => 'Amount',
            'charge' => 'Charge',
            'currency' => 'Currency',
            'transactionType' => 'Transaction Type',
            'status' => 'Status',
            'createdAt' => 'CreatedAt',
            'updatedAt' => 'UpdatedAt',
        ];
    }

    public static function add($data): bool
    {
        $model = new RefundTransaction();
        $model->setAttributes($data);
        if ($model->save()) {
            return true;
        }
        TransactionLog::createLog('RefundTransactionModel Insert', $data, $model->getErrors(), 0);
        return false;
    }

    public static function updateByKeyValue($key, $data): bool
    {
        $model = self::findOne($key);
        $model->setAttributes($data);
        if (!$model->save()) {
            return false;
        }
        return true;
    }

    public static function getByKey($key, $val)
    {
        $options = self::find()->where([$key => $val])->one();
        if ($options) {
            return $options;
        } else {
            return null;
        }
    }

    public function getRelatedCurrency(): ActiveQuery
    {
        return $this->hasOne(Currency::class, ['uid' => 'currency']);
    }

    public function getRelatedTransaction(): ActiveQuery
    {
        return $this->hasOne(Transaction::class, ['uid' => 'transactionId']);
    }
}