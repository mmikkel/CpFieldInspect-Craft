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
use craft\base\ElementInterface;
use craft\base\Plugin;
use craft\elements\Asset;
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

    /**
     * @param array $context
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function renderEditSourceLink(array $context)
    {
        $entry = $context['entry'] ?? null;
        if ($entry) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-entry-type-link', $context);
        }
        $asset = ($context['element'] ?? null) && $context['element'] instanceof Asset ? $context['element'] : null;
        if ($asset) {
            $context['asset'] = $context['element'];
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-volume-link', $context);
        }
        $globalSet = $context['globalSet'] ?? null;
        if ($globalSet) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-globalset-link', $context);
        }
        $user = $context['user'] ?? null;
        if ($user) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-users-link', $context);
        }
        $category = $context['category'] ?? null;
        if ($category) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-category-group-link', $context);
        }
        $product = $context['product'] ?? null;
        if ($product) {
            return Craft::$app->getView()->renderTemplate('cp-field-inspect/edit-commerce-product-type-link', $context);
        }
        return '';
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
        $view = Craft::$app->getView();
        $view->hook('cp.assets.edit.meta', [$this, 'renderEditSourceLink']);
        $view->hook('cp.entries.edit.meta', [$this, 'renderEditSourceLink']);
        $view->hook('cp.globals.edit.content', [$this, 'renderEditSourceLink']);
        $view->hook('cp.users.edit.details', [$this, 'renderEditSourceLink']);
        $view->hook('cp.categories.edit.details', [$this, 'renderEditSourceLink']);
        $view->hook('cp.commerce.product.edit.details', [$this, 'renderEditSourceLink']);
        $view->hook('cp.commerce.order.edit.main-pane', [$this, 'renderEditSourceLink']);

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

            $view->registerAssetBundle(CpFieldInspectBundle::class);
            $view->registerJs('Craft.CpFieldInspectPlugin.init(' . \json_encode($data) . ');');
        }
    }

}
