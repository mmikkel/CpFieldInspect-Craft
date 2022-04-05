<?php

namespace mmikkel\cpfieldinspect\controllers;

use Craft;
use craft\web\Controller;

use mmikkel\cpfieldinspect\CpFieldInspect;

use yii\web\BadRequestHttpException;
use yii\web\Response;

class DefaultController extends Controller
{

    /**
     * @return string
     * @throws BadRequestHttpException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGetRedirectHash(): string
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $url = $this->request->getRequiredBodyParam('url');
        if (!$url || !\is_string($url) || !$path = \parse_url($url, PHP_URL_PATH)) {
            throw new BadRequestHttpException('Bad URL parameter');
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
        $hashbang = \parse_url($url, PHP_URL_FRAGMENT);
        if ($hashbang) {
            $redirectTo .= "#{$hashbang}";
        }
        return CpFieldInspect::getInstance()->redirect->getRedirectUrl($redirectTo);
    }

}
