<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 15-12-31
 * Time: 上午12:07
 */

namespace frontend\controllers;

use frontend\models\Peer;
use frontend\models\Seed;
use Yii;
use yii\filters\auth\QueryParamAuth;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use frontend\models\QueryPeersForm;
use frontend\models\User;

class AnnounceController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors(); // TODO: Change the autogenerated stub
        $behaviors['authenticator'] = [
            'class' => QueryParamAuth::className(),
            'tokenParam' => 'passkey',
        ];
        return $behaviors;
    }
    public function actionQuery()
    {
        Yii::$app->response->headers->add("Content-Type", "text/plain");
        Yii::trace(print_r(Yii::$app->request->get(), true));
        $form = new QueryPeersForm();
        $form->attributes = Yii::$app->request->get();
        Yii::info($form->ip);

        $peer_list = null;

        if ($form->validate()) {
            $peer = $form->peer;
            $peer->real_up = $form->uploaded;
            $peer->real_down = $form->downloaded;
            $peer->save();
            //处理event
            switch ($form->event) {
                case 'regular':
                    break;
                case 'started':
                    //do nothing?
                    break;
                case 'stopped':
                    $peer->delete();
                    break;
                case 'completed':
                    $form->seed->completed_count++;
                    $form->seed->save();
                    break;
                default:
                    Yii::warning("Unknown event", static::className());
            }

            if ($form->numwant == 0) {
                $form->numwant = 100;
            }
            //Seeder会排到前面
            $peer_list = Peer::find()->
            where([
                'seed_id' => $form->seed->seed_id,
            ])->orderBy([
                'status' => SORT_ASC,
                'update_time' => SORT_DESC,
            ])->limit(min(300, intval($form->numwant)))->all();
        } else {
            $errors = $form->errors;
            Yii::warning($errors);
        }
        return $this->renderPartial("queryPeerList", [
            'peer_list' => $peer_list,
            'form' => $form,
        ]);
    }
    public function actionScrape()
    {
        $infohash = Yii::$app->request->get("info_hash");
        if (!is_array($infohash)) {
            $infohash = [$infohash];
        }
        $res = [];
        foreach ($infohash as $ih) {
            $tmp = Seed::findOne([
                'info_hash' => strtoupper(bin2hex($ih)),
            ]);
            if (!empty($tmp)) {
                $res[] = $tmp;
            }
        }
        return $this->renderPartial("queryScrape", [
            'seeds' => $res,
        ]);
    }
    public function actionError()
    {
    }
}
