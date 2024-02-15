<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * TransactionLogDetailsSearch represents the model behind the search form of `app\models\TransactionLogDetails`.
 */
class TransactionLogDetailsSearch extends TransactionLogDetails
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['orderId', 'bookingId', 'client', 'serviceType', 'payLoad'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = TransactionLogDetails::find();
        $query->select(['orderId', 'bookingId', 'client', 'serviceType', 'id']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query->andFilterWhere(['like', 'orderId', $this->orderId])
            ->andFilterWhere(['like', 'bookingId', $this->bookingId])
            ->andFilterWhere(['like', 'client', $this->client])
            ->andFilterWhere(['like', 'serviceType', $this->serviceType])
            ->orderBy('id DESC');

        return $dataProvider;
    }
}
