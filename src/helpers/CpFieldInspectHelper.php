<?php

namespace mmikkel\cpfieldinspect\helpers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\User;
use craft\commerce\elements\Product;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

use yii\base\InvalidConfigException;

class CpFieldInspectHelper
{

    /**
     * @param string|null $url
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public static function getRedirectUrl(?string $url = null): string
    {
        if (!$url) {
            $url = implode('?', array_filter([implode('/', Craft::$app->getRequest()->getSegments()), Craft::$app->getRequest()->getQueryStringWithoutPath()]));
        }
        // Special case for globals – account for their handles being edited before redirecting back
        $segments = explode('/', $url);
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
        return Craft::$app->getSecurity()->/** @scrutinizer ignore-call */hashData($url);
    }

    /**
     * @throws InvalidConfigException
     */
    public static function renderEditSourceLink(array $context): string
    {
        $element = $context['element'] ?? $context['entry'] ?? $context['asset'] ?? $context['globalSet'] ?? $context['user'] ?? $context['category'] ?? $context['product'] ?? null;
        if (empty($element)) {
            return '';
        }
        return static::getEditElementSourceButton($element);
    }

    /**
     * @param ElementInterface|null $element
     * @param array $attributes
     * @param string|null $size
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function getEditElementSourceButton(?ElementInterface $element, array $attributes = [], ?string $size = null): string
    {
        if (empty($element)) {
            return '';
        }
        $html = '';
        if ($element instanceof Entry) {
            if (!empty($element->typeId)) {
                $html .= static::_getEditSourceButtonHtml(
                    label: 'Edit entry type',
                    path: "settings/entry-types/$element->typeId",
                    attributes: [
                        'data-type-id' => $element->typeId,
                    ],
                    size: $size
                );
            }
            if (!empty($element->sectionId)) {
                $html .= static::_getEditSourceButtonHtml(label: 'Edit section', path: "settings/sections/$element->sectionId", size: $size);
            }
        } else if ($element instanceof Asset) {
            $html = static::_getEditSourceButtonHtml(label: 'Edit volume', path: "settings/assets/volumes/{$element->volumeId}", size: $size);
        } else if ($element instanceof GlobalSet) {
            $html = static::_getEditSourceButtonHtml(label: 'Edit global set', path: "settings/globals/{$element->id}", size: $size);
        } else if ($element instanceof User) {
            $html = static::_getEditSourceButtonHtml('Edit settings', 'settings/users/fields', [
                'style' => 'margin-top:20px;',
            ], $size);
        } else if ($element instanceof Category) {
            $html = static::_getEditSourceButtonHtml(label: 'Edit category group', path: "settings/categories/{$element->groupId}", size: $size);
        } else if (class_exists(Product::class) && $element instanceof Product) {
            $html = static::_getEditSourceButtonHtml(label: 'Edit product type', path: "commerce/settings/producttypes/{$element->typeId}", size: $size);
        }
        if (empty($html)) {
            return '';
        }
        return Html::tag('div', $html, [
            ...$attributes,
            'class' => [
                'cp-field-inspect-sourcebtn-wrapper flex',
                ...$attributes['class'] ?? [],
            ],
        ]);
    }

    /**
     * @param string $label
     * @param string $path
     * @param array $attributes
     * @param string|null $size
     * @return string
     */
    private static function _getEditSourceButtonHtml(string $label, string $path, array $attributes = [], ?string $size = null): string
    {
        return Html::tag('a', Craft::t('cp-field-inspect', $label), [
            'href' => UrlHelper::cpUrl($path),
            'class' => [
                'btn settings icon',
                $size === 'small' ? 'small' : null,
            ],
            'data-cp-field-inspect-sourcebtn' => true,
            ...$attributes,
        ]);
    }

}
