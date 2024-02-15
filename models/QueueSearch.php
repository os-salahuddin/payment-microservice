<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * QueueSearch represents the model behind the search form of `app\models\Queue`.
 */
class QueueSearch extends Queue
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'pushed_at', 'ttr', 'delay', 'priority', 'reserved_at', 'attempt', 'done_at'], 'integer'],
            [['channel', 'job'], 'safe'],
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
        $query = Queue::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'pushed_at' => $this->pushed_at,
            'ttr' => $this->ttr,
            'delay' => $this->delay,
            'priority' => $this->priority,
            'reserved_at' => $this->reserved_at,
            'attempt' => $this->attempt,
            'done_at' => $this->done_at,
        ]);

        $query->andFilterWhere(['like', 'channel', $this->channel])
            ->andFilterWhere(['like', 'job', $this->job]);

        return $dataProvider;
    }
}