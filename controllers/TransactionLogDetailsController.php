<?php

namespace app\controllers;

use app\components\TransactionLogDetailsUtil;
use app\models\TransactionLog;
use Yii;
use app\models\Transaction;
use app\models\TransactionLogDetails;
use app\models\TransactionLogDetailsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * TransactionLogDetailsController implements the CRUD actions for TransactionLogDetails model.
 */
class TransactionLogDetailsController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all TransactionLogDetails models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TransactionLogDetailsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if(isset($_GET['refresh'])) {
            TransactionLogDetailsUtil::storeTransactionLogData();
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single TransactionLogDetails model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {   
        $model = $this->findModel($id);
        $transaction = Transaction::find()->select(['status'])->where(['orderId' => $model->orderId])->one();

        
        return $this->render('view', [
            'model' => $model,
            'transaction' => $transaction
        ]);
    }

    /**
     * Finds the TransactionLogDetails model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TransactionLogDetails the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TransactionLogDetails::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
