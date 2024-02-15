<?php

namespace app\controllers;

use Yii;
use app\models\Queue;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\QueueSearch;
use yii\web\NotFoundHttpException;

/**
 * QueueController implements the CRUD actions for Queue model.
 */
class QueueController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        Yii::$app->asm->has();
        return parent::beforeAction($action);
    }

    /**
     * Lists all Queue models.
     * @return string
     */
    public function actionIndex(): string
    {
        $searchModel = new QueueSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Finds the Queue model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param $id
     * @return Queue the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id): Queue
    {
        if (($model = Queue::findOne($id)) !== null) return $model;
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}