<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "TransactionLogDetails".
 *
 * @property int $id
 * @property string $orderId
 * @property string $bookingId
 * @property string $client
 * @property string $serviceType
 * @property string $payLoad
 */
class TransactionLogDetails extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'TransactionLogDetails';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['orderId'], 'required'],
            [['payLoad'], 'safe'],
            [['orderId', 'bookingId'], 'string', 'max' => 100],
            [['client'], 'string', 'max' => 36],
            [['serviceType'], 'string', 'max' => 50],
            [['orderId'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orderId' => 'Order ID',
            'bookingId' => 'Booking ID',
            'client' => 'Client',
            'serviceType' => 'Service Type',
            'payLoad' => 'Pay Load',
        ];
    }
}
