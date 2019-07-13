<?php

namespace mmikkel\cpfieldinspect\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;

use yii\web\BadRequestHttpException;

class DefaultController extends Controller
{

    /**
     * @return string
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGetRedirectHash(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $url = Craft::$app->getRequest()->getRequiredBodyParam('url');
        $path = \parse_url($url, PHP_URL_PATH);
        if (!$path) {
            throw new BadRequestHttpException('Invalid URL');
        }
        $segments = \array_values(\array_filter(\explode('/', $path)));
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        if ($segments[0] === $cpTrigger) {
            \array_shift($segments);
        }
        $redirectTo = \implode('/', $segments);
        $queryString = \parse_url($url, PHP_URL_QUERY);
        if ($queryString) {
            $redirectTo .= "?{$queryString}";
        }
        return $this->asJson([
            'data' => Craft::$app->getSecurity()->hashData($redirectTo),
        ]);
    }

}
