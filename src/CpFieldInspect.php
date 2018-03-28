<?php
/**
 * CP Field Inspect plugin for Craft CMS 3.x
 *
 * Inspect field handles and easily edit field settings
 *
 * @link      http://mmikkel.no
 * @copyright Copyright (c) 2017 Mats Mikkel Rummelhoff
 */

namespace mmikkel\cpfieldinspect;


use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;

use craft\helpers\UrlHelper;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Mats Mikkel Rummelhoff
 * @package   CpFieldInspect
 * @since     1.0.0
 *
 *
 * Plugin icon credit: CUSTOMIZE SEARCH by creative outlet from the Noun Project
 *
 */
class CpFieldInspect extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * CpFieldInspect::$plugin
     *
     * @var CpFieldInspect
     */
    public static $plugin;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * CpFieldInspect::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $user = Craft::$app->getUser();
        $request = Craft::$app->getRequest();

        if (!$user->getIsAdmin() || !$request->getIsCpRequest() || $request->getIsConsoleRequest()) {
            return;
        }

        // Handler: EVENT_AFTER_LOAD_PLUGINS
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function () {
                $this->doIt();
            }
        );

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'cp-field-inspect',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================
    protected function doIt()
    {

        $request = Craft::$app->getRequest();

        if ($request->getIsAjax()) {

            if (!$request->getIsPost()) {
                return false;
            }

            $segments = $request->segments;
            $actionSegment = $segments[count($segments) - 1];

            if ($actionSegment !== 'get-editor-html') {
                return false;
            }

            Craft::$app->getView()->registerJs('Craft.CpFieldInspectPlugin.initElementEditor();');

        } else {

            $data = array(
                'redirectUrl' => Craft::$app->getSecurity()->hashData(implode('/', $request->segments)),
                'fields' => array(),
                'entryTypeIds' => array(),
                'baseEditFieldUrl' => rtrim(UrlHelper::cpUrl('settings/fields/edit'), '/'),
                'baseEditEntryTypeUrl' => rtrim(UrlHelper::cpUrl('settings/sections/sectionId/entrytypes'), '/'),
                'baseEditGlobalSetUrl' => rtrim(UrlHelper::cpUrl('settings/globals'), '/'),
                'baseEditCategoryGroupUrl' => rtrim(UrlHelper::cpUrl('settings/categories'), '/'),
                'baseEditCommerceProductTypeUrl' => rtrim(UrlHelper::cpUrl('commerce/settings/producttypes'), '/'),
            );

            $sectionIds = Craft::$app->getSections()->getAllSectionIds();
            foreach ($sectionIds as $sectionId)
            {
                $entryTypes = Craft::$app->getSections()->getEntryTypesBySectionId($sectionId);
                $data['entryTypeIds']['' . $sectionId] = array();
                foreach ($entryTypes as $entryType)
                {
                    $data['entryTypeIds']['' . $sectionId][] = $entryType->id;
                }
            }


            $fields = Craft::$app->getFields()->getAllFields();

            foreach ($fields as $field)
            {

                $data['fields'][$field->handle] = array(
                    'id' => $field->id,
                    'handle' => $field->handle,
                );
            }

            Craft::$app->getView()->registerAssetBundle(CpFieldInspectBundle::class);
            Craft::$app->getView()->registerJs('Craft.CpFieldInspectPlugin.init('.json_encode($data).');');
        }
    }

}
