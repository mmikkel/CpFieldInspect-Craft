<?php

namespace mmikkel\cpfieldinspect\services;

use Craft;
use craft\base\Component;
use craft\elements\GlobalSet;

/**
 * 
 */
class Redirect extends Component
{

    /**
     * @param string|null $url
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getRedirectUrl(?string $url = null): string
    {
        if (!$url) {
            $url = \implode('?', \array_filter([\implode('/', Craft::$app->getRequest()->getSegments()), Craft::$app->getRequest()->getQueryStringWithoutPath()]));
        }
        // Special case for globals – account for their handles being edited before redirecting back
        $segments = \explode('/', $url);
        if (($segments[0] ?? null) === 'globals') {
            if (Craft::$app->getIsMultiSite()) {
                $siteHandle = $segments[1] ?? null;
                $globalSetHandle = $segments[2] ?? null;
            } else {
                $siteHandle = Craft::$app->getSites()->getPrimarySite()->handle;
                $globalSetHandle = $segments[1] ?? null;
            }
            if ($siteHandle && $globalSetHandle && $globalSet = GlobalSet::find()->site($siteHandle)->handle($globalSetHandle)->one()) {
                $url = "edit/$globalSet->id?site=$siteHandle";
            }
        }
        return Craft::$app->getSecurity()->hashData($url, Craft::$app->getConfig()->getGeneral()->securityKey);
    }

}
