<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\Link;
use app\models\LinkClick;
use chillerlan\QRCode\QRCode;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\base\Security;
use yii\mail\MailerInterface;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\helpers\Html;
use yii\helpers\Url;

class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly MailerInterface $mailer,
        private readonly Security $security,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
            'captcha' => [
                'class' => CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'transparent' => true,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $links = Link::find()->orderBy(['id' => SORT_DESC])->limit(5)->all();
        return $this->render('index', ['model' => new Link(), 'links' => $links]);
    }

    /**
     * Creates new link in db.
     *
     * @return string
     */
    public function actionCreateLink()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $model = new Link();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            if (Yii::$app->request->post('save', false) && Yii::$app->request->isAjax) { // return ['ok 90'];
                if ($model->save()) {
                    return [
                        'result' => 'success',
                        'url_full' => $model->url_full,
                        'url_short' => $model->url_short,
                        'html' => Html::tag('div',
                            Html::a(
                                Html::img($model->qrcode),
                                $model->qrcode,
                                ['data-fancybox' => $model->qrcode, 'rel' => 'fancybox', 'target' => '_blank']
                            )
                            . Html::a($model->url_short, Url::to(['go/'.$model->url_short]), ['target' => '_blank'])
                            . date(' - Y-m-d H:i:s', $model->created_at)
                        )
                    ];
                }
                else {
                    return [
                        'result' => 'error 101 model ' .$model->id ,
                        'errors' => $model->getErrors()
                    ];
                }
            }
            return ['result' => 'smth gone wrong...']; // not $this->redirect(Url::home())';
        }
        $errors = $model->getErrors();
        $errorMessages = implode(' ', array_map(function ($e) {
            return implode(' ', $e);
        }, $errors));

        throw new BadRequestHttpException($errorMessages);
    }

    /**
     * Redirects from shortlink
     *
     * @return string
     */
    public function actionGo($url)
    {
        if (($link = Link::findOne(['url_short' => $url]))) {
            try {
                $linkClick = new LinkClick([
                    'link_id' => $link->id,
                    'user_ip' => Yii::$app->request->userIP,
                ]);
                $linkClick->save();
            }
            catch (\Exception $ex) {
                Yii::error('actionGo error: ' . $ex->getMessage());
            }
            return $this->redirect($link->url_full, 301);
        }
        throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
    }

    /**
     * Last links
     *
     * @return string
     */
    public function actionLinks()
    {
        $links = Link::find()
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->limit(2)
            ->all();

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $links;
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm($this->security);

        if ($model->load($this->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', ['model' => $model]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact(): Response|string
    {
        $model = new ContactForm();

        $contact = $model->load($this->request->post()) && $model->contact(
            $this->mailer,
            Yii::$app->params['adminEmail'],
            Yii::$app->params['senderEmail'],
            Yii::$app->params['senderName'],
        );

        if ($contact) {
            Yii::$app->session->setFlash(
                'success',
                'Thank you for contacting us. We will respond to you as soon as possible.',
            );

            return $this->refresh();
        }

        return $this->render('contact', ['model' => $model]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout(): string
    {
        return $this->render('about');
    }
}
