<?php

namespace app\controllers;

use Throwable;
use Yii;
use app\models\Gateway;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\GatewaySearch;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * GatewayController implements the CRUD actions for Gateway model.
 */
class GatewayController extends Controller
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
     * Lists all Gateway models.
     * @return string
     */
    public function actionIndex(): string
    {
        $searchModel = new GatewaySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Gateway model.
     * @param $id
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id): string
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Gateway model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new Gateway();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->uid]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Gateway model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param $id
     * @return string|Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->uid]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Gateway model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param $id
     * @return Response
     * @throws NotFoundHttpException if the model cannot be found
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($id): Response
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Gateway model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param $id
     * @return Gateway the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id): Gateway
    {
        if (($model = Gateway::findOne(['uid' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
