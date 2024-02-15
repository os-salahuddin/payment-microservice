<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * GatewaySearch represents the model behind the search form of `app\models\Gateway`.
 */
class GatewaySearch extends Gateway
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['gatewayMode'], 'integer'],
            [['uid', 'name', 'code', 'currency', 'sandboxUrl', 'liveUrl', 'merchant', 'merchantPassword', 'extraParams', 'createdAt', 'updatedAt', 'createdBy', 'updatedBy'], 'safe'],
            [['charge'], 'number'],
            [['status'], 'string'],
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
        $query = Gateway::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->currency != '') $currency = Currency::find()->where(['like', 'code', $this->currency])
                ->one()->uid ?? $this->currency; else $currency = $this->currency;

        if ($this->status == 'Active') $statusView = '1'; elseif ($this->status == 'Inactive') $statusView = '0';
        else $statusView = '';

        $query->andFilterWhere(['like', 'uid', $this->uid])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'charge', $this->charge])
            ->andFilterWhere(['like', 'currency', $currency])
            ->andFilterWhere(['like', 'gatewayMode', $this->gatewayMode])
            ->andFilterWhere(['like', 'status', $statusView]);

        return $dataProvider;
    }
}