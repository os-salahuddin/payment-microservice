<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * TransactionSearch represents the model behind the search form of `app\models\Transaction`.
 */
class TransactionSearch extends Transaction
{

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'bookingId', 'status', 'notify'], 'integer'],
            [['uid', 'description', 'requestId', 'bankCode', 'orderId', 'sessionId', 'bankRequestUrl', 'bankResponse', 'type', 'rrn', 'pan', 'bankTransactionDate', 'bankResponseCode', 'bankResponseDescription', 'cardHolderName', 'cardBrand', 'bankOrderStatus', 'bankApprovalCode', 'bankApprovalCodeScr', 'acqFee', 'bankMerchantTranId', 'bankOrderStatusScr', 'bankThreeDsvVerification', 'bankThreeDssStatus', 'sessionVersion', 'resultIndicator', 'callbackUrl', 'currency', 'gateway', 'clientId', 'cardPrefix', 'serviceType', 'createdAt', 'updatedAt', 'customerId', 'customerName', 'bankApprovalCode', 'cardSeries'], 'safe'],
            [['amount'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     * @param $params
     * @return ActiveDataProvider
     */
    public function search($params): ActiveDataProvider
    {
        $query = Transaction::find()->select([
            'uid', 'orderId', 'bookingId', 'rrn',
            'bankApprovalCode', 'pan', 'customerId', 'customerName',
            'clientId', 'gateway', 'bankCode', 'serviceType', 'status', 'amount', 'createdAt',
            'currency', 'cardSeries', 'bankMerchantTranId'
        ]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['createdAt' => SORT_DESC]],
        ]);
        $this->load($params);

        if (!$this->validate())
            return $dataProvider;

        $query->andFilterWhere([
            'clientId' => $this->clientId,
            'amount' => $this->amount,
            'bookingId' => $this->bookingId,
            'status' => $this->status,
            'notify' => $this->notify,
            'orderId' => $this->orderId,
            'bankCode' => $this->bankCode,
            'rrn' => $this->rrn,
            'bankApprovalCode' => $this->bankApprovalCode,
            'gateway' => $this->gateway,
            'serviceType' => $this->serviceType,
        ]);

        if (isset($params['createdAt']) && !empty($params['createdAt'])) {
            $dateArray = explode(' - ', $params['createdAt']);
            $from = $dateArray[0] . " 00:00:00";
            $to = $dateArray[1] . " 23:59:59";
            $query->andFilterWhere(['>=', 'createdAt', strtotime($from) - 21600]);
            $query->andFilterWhere(['<=', 'createdAt', strtotime($to) - 21600]);
        }

        $query->andFilterWhere(['like', 'customerId', $this->customerId])
            ->andFilterWhere(['like', 'customerName', $this->customerName])
            ->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'cardPrefix', $this->cardPrefix]);
        $query->with(['relatedClient', 'relatedBank', 'relatedGateway', 'relatedCardSeries', 'relatedCurrency']);
        return $dataProvider;
    }
}