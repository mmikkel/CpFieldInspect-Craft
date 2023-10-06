<?php
namespace mmikkel\cpfieldinspect\web;

use Craft;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\App;
use craft\helpers\ConfigHelper;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

use mmikkel\cpfieldinspect\helpers\CpFieldInspectHelper;

class CpFieldInspectBundle extends AssetBundle
{

    public $sourcePath = '@mmikkel/cpfieldinspect/web/assets';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'cpfieldinspect.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'cpfieldinspect.js',
    ];

    /**
     * @param $view
     * @return void
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $this->_registerTranslations($view);
        }

        try {
            $data = Json::encode($this->_getData());
        } catch (\Throwable $e) {
            Craft::error($e, __METHOD__);
            return;
        }

        $js = <<<JS
            if (window.Craft.CpFieldInspectPlugin) {
                window.Craft.CpFieldInspectPlugin.init($data);
            }
JS;
        $view->registerJs($js, View::POS_END);

    }

    /**
     * @param View $view
     * @return void
     */
    private function _registerTranslations(View $view): void
    {
        $translations = @include App::parseEnv('@mmikkel/cpfieldinspect') . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . 'cp-field-inspect.php';
        if (empty($translations)) {
            Craft::error('Unable to register translations', __METHOD__);
            return;
        }
        $view->registerTranslations('cp-field-inspect', array_keys($translations));
    }

    /**
     * @return array
     */
    private function _getData(): array
    {
        try {
            $fieldLayouts = Craft::$app->getFields()->getAllLayouts();
            $customFieldElements = [];
            foreach ($fieldLayouts as $fieldLayout) {
                $customFieldElements += array_reduce(
                    $fieldLayout->getCustomFieldElements(),
                    static function(array $carry, CustomField $fieldElement) {
                        $fieldId = (int)$fieldElement->getField()?->id;
                        if (!$fieldId) {
                            return $carry;
                        }
                        $carry[$fieldElement->uid] = $fieldId;
                        return $carry;
                    },
                    []
                );
            }
            return [
                'redirectUrl' => CpFieldInspectHelper::getRedirectUrl(),
                'customFieldElements' => $customFieldElements,
            ];
        } catch (\Throwable $e) {
            Craft::error($e, __METHOD__);
        }
        return [];
    }

}
