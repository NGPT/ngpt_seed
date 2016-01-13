<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-5
 * Time: 上午7:40
 */

namespace frontend\controllers;

use frontend\models\Peer;
use frontend\models\SeedEvent;
use frontend\models\User;
use Yii;
use yii\db\Command;
use yii\filters\auth\QueryParamAuth;
use yii\web\Controller;
use yii\web\Response;

class UserController extends Controller
{
    public $enableCsrfValidation = false;
    public function behaviors()
    {
        $behaviors = parent::behaviors(); // TODO: Change the autogenerated stub
        $behaviors['authenticator'] = [
            'class' => QueryParamAuth::className(),
            'tokenParam' => 'passkey',
            'except' => ['add'],
        ];
        $behaviors[] = [
            'class' => 'yii\filters\ContentNegotiator',
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        return $behaviors;
    }

    public function actionInfo($detail = false)
    {
        /** @var User $user */
        $user = User::findOne(Yii::$app->user->identity->getId());
        $key = 'user_info' . ($detail ? 'd' : 's' . strval($user->user_id));
        $info = Yii::$app->cache->get($key);
        if ($info === false) {
            $info = $user->getInfo(!!$detail);
            Yii::$app->cache->set($key, $info, 120);
        }
        return $info;
    }

    public function actionAdd($password, $discuz_uid, $type = 'User')
    {
        //TODO 这个之后一定要写进配置文件而非硬编码
        if ($password !== 'ngpt_2333') {
            Yii::warning("Wrong Password!!!!!!" . $password . "uid : {$discuz_uid}");
            return [
                'result' => 'failed',
                'extra' => 'wrong password',
            ];
        }
        if (is_numeric($discuz_uid) && intval($discuz_uid) <= 0) {
            Yii::warning("uid not a number : {$discuz_uid}");
            return [
                'result' => 'failed',
                'extra' => 'discuz_uid should be numeric',
            ];
        }
        $discuz_uid = intval($discuz_uid);
        /** @var User $user */
        $user = User::findOne(
            [
                'discuz_user_id' => $discuz_uid,
            ]
        );
        if (!empty($user)) {
            return [
                'result' => 'succeed',
                'extra' => $user->passkey,
            ];
        }
        $user = new User;
        $user->discuz_user_id = $discuz_uid;
        $user->passkey = User::genPasskey();
        Yii::info($user->attributes);
        if ($user->insert()) {
            return [
                'result' => 'succeed',
                'extra' => $user->passkey,
            ];
        } else {
            Yii::warning("Insert to user table failed");
            return [
                'result' => 'failed',
                'extra' => 'Database error',
            ];
        }
    }
}
