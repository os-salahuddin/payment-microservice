<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * GatewaySearch represents the model behind the search form of `app\models\Gateway`.
 */
class RefundTransactionSearch extends RefundTransaction
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['uid', 'currency', 'charge', 'amount', 'bankStatus', 'transactionType', 'status', 'createdAt', 'updatedAt'], 'safe'],
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
        $query = RefundTransaction::find()->joinWith('relatedTransaction');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $conditions = [];
        if(!empty($params['gateway'])) {
            $conditions['transaction.gateway'] = $params['gateway'];
        }

        if(!empty($params['orderId'])) {
            $conditions['transaction.orderId'] = $params['orderId'];
        }

        if(!empty($params['client'])) {
            $conditions['transaction.clientId'] = $params['client'];
        }

        if(!empty($params['serviceType'])) {
            $conditions['transaction.serviceType'] = $params['serviceType'];
        }

        if ($this->currency != '') $currency = Currency::find()->where(['like', 'code', $this->currency])->one()->uid ?? $this->currency; else $currency = $this->currency;
        if (isset($params['createdAt']) && $params['createdAt'] != '') $dateFilter = date('Y-m-d', strtotime($params['createdAt'])); else $dateFilter = '';
        if (isset($params['updatedAt']) && $params['updatedAt'] != '') $dateFilterEnd = date('Y-m-d', strtotime($params['updatedAt'])); else $dateFilterEnd = '';

        $query->andFilterWhere([
            'refundTransaction.uid' => $this->uid,
            'refundTransaction.amount' => $this->amount,
            'refundTransaction.charge' => $this->charge,
            'refundTransaction.currency' => $currency,
            'refundTransaction.transactionType' => $this->transactionType,
            'refundTransaction.status' => $this->status,
        ]);

        $query->andFilterWhere($conditions);

        if ($dateFilter || $dateFilterEnd) {
            $query->andFilterWhere(['between', "from_unixtime(refundTransaction.createdAt, '%Y-%m-%d')", $dateFilter, $dateFilterEnd]);
        }

        $query->orderBy('refundTransaction.id DESC');

        return $dataProvider;
    }
}