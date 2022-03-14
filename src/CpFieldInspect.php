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
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Plugin;
use craft\commerce\elements\Product;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\User;
use craft\events\DefineHtmlEvent;
use craft\events\PluginEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;

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

/**
 * Class CpFieldInspect
 * @package mmikkel\cpfieldinspect
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

        $config = Craft::$app->getConfig()->getGeneral();
        if (!$config->allowAdminChanges) {
            // Do nothing if admin changes aren't allowed
            return;
        }

        $request = Craft::$app->getRequest();
        if (!$request->getIsCpRequest() || $request->getIsConsoleRequest()) {
            // Also do nothing if this is a console or site request
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

    /**
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function doIt()
    {

        $user = Craft::$app->getUser();

        if (!$user->getIsAdmin() || !$user->getIdentity()->getPreference('showFieldHandles')) {
            // Do nothing if the user is not an admin
            return;
        }

        // Render edit source links
        if (\version_compare(Craft::$app->getVersion(), '4.0', '<')) {

            // Legacy template hooks for entries, assets and categories on Craft 3.x
            Craft::$app->getView()->hook('cp.entries.edit.meta', fn(array $context): string => $this->renderEditSourceLink($context));
            Craft::$app->getView()->hook('cp.assets.edit.meta', fn(array $context): string => $this->renderEditSourceLink($context));
            Craft::$app->getView()->hook('cp.categories.edit.details', fn(array $context): string => $this->renderEditSourceLink($context));

        } else {

            // Use the EVENT_DEFINE_META_FIELDS_HTML event to inject source buttons for Craft 4
            Event::on(
                Element::class,
                Element::EVENT_DEFINE_META_FIELDS_HTML,
                function (DefineHtmlEvent $event) {
                    if ($event->static) {
                        return;
                    }
                    $event->html .= $this->renderEditSourceLink(['element' => $event->sender]);
                }
            );
        }

        // Hooks that work on Craft 3.x and 4.x
        Craft::$app->getView()->hook('cp.globals.edit.content', fn(array $context): string => $this->renderEditSourceLink(['element' => $context['globalSet'] ?? null]));
        Craft::$app->getView()->hook('cp.users.edit.details', fn(array $context): string => $this->renderEditSourceLink(['element' => $context['user'] ?? null]));
        Craft::$app->getView()->hook('cp.commerce.product.edit.details', fn(array $context): string => $this->renderEditSourceLink(['element' => $context['product'] ?? null]));

        $request = Craft::$app->getRequest();
        $isAjax = $request->getIsAjax() || $request->getAcceptsJson();

        if ($isAjax) {

            $segments = $request->getActionSegments();
            if (empty($segments) || !\is_array($segments) || $segments[count($segments) - 1] !== 'get-editor-html') {
                return;
            }

            Craft::$app->getView()->registerJs('Craft.CpFieldInspectPlugin.initElementEditor();');

        } else {

            $redirectUrl = \implode('?', \array_filter([\implode('/', $request->getSegments()), $request->getQueryStringWithoutPath()]));

            $data = [
                'editFieldBtnLabel' => Craft::t('cp-field-inspect', 'Edit field settings'),
                'baseEditFieldUrl' => \rtrim(UrlHelper::cpUrl('settings/fields/edit'), '/'),
                'redirectUrl' => Craft::$app->getSecurity()->hashData($redirectUrl),
            ];

            $fields = Craft::$app->getFields()->getAllFields('global');
            foreach ($fields as $field) {
                $data['fields'][$field->handle] = (int)$field->id;
            }

            Craft::$app->getView()->registerAssetBundle(CpFieldInspectBundle::class);
            Craft::$app->getView()->registerJs('Craft.CpFieldInspectPlugin.init(' . \json_encode($data, JSON_THROW_ON_ERROR) . ');');
        }
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\LoaderError
     * @throws \yii\base\Exception
     */
    protected function renderEditSourceLink(array $context): string
    {
        $element = $context['element'] ?? $context['entry'] ?? $context['asset'] ?? $context['globalSet'] ?? $context['user'] ?? $context['category'] ?? $context['product'] ?? null;
        if ($element instanceof Entry) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-entry-type-link', ['entry' => $element]);
        }
        if ($element instanceof Asset) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-volume-link', ['asset' => $element]);
        }
        if ($element instanceof GlobalSet) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-globalset-link', ['globalSet' => $element]);
        }
        if ($element instanceof User) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-users-link', ['user' => $element]);
        }
        if ($element instanceof Category) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-category-group-link', ['category' => $element]);
        }
        if ($element instanceof Product) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-commerce-product-type-link', ['product' => $element]);
        }
        return '';
    }

}
