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
use craft\events\TemplateEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\Application;
use craft\web\Controller;
use craft\web\View;

use mmikkel\cpfieldinspect\services\Redirect;

use yii\base\ActionEvent;
use yii\base\Event;

/**
 * Class CpFieldInspect
 * @author Mats Mikkel Rummelhoff
 * @package mmikkel\cpfieldinspect
 * @since 1.0.0
 *
 * Plugin icon credit: CUSTOMIZE SEARCH by creative outlet from the Noun Project
 *
 * @property Redirect $redirect
 */
class CpFieldInspect extends Plugin
{

    /**
     * @return void
     */
    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'redirect' => Redirect::class,
        ]);

        $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        // After installation, set the "Show field handles" user preference for active admin users
        if ($allowAdminChanges) {
            Event::on(
                Plugins::class,
                Plugins::EVENT_AFTER_INSTALL_PLUGIN,
                function (PluginEvent $event) {
                    if ($event->plugin !== $this) {
                        return;
                    }
                    $adminUsers = User::find()->admin(true)->status('active')->all();
                    foreach ($adminUsers as $adminUser) {
                        Craft::$app->getUsers()->saveUserPreferences($adminUser, [
                            'showFieldHandles' => true,
                        ]);
                    }
                }
            );
        }

        // Defer further initialisation to after plugins have loaded
        // Or, do nothing if admin changes are not allowed, or if this isn't a CP web request
        $request = Craft::$app->getRequest();
        if ($allowAdminChanges && $request->getIsCpRequest() && !$request->getIsConsoleRequest() && !$request->getIsLoginRequest()) {
            Event::on(
                Application::class,
                Application::EVENT_INIT,
                function () {
                    $this->doIt();
                }
            );
        }

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
     * @return void
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function doIt(): void
    {

        // Do nothing if the user is not an admin
        if (!Craft::$app->getUser()->getIsAdmin()) {
            return;
        }

        // Also do nothing if the user doesn't have field handles visible
        /** @var User $user */
        $user = Craft::$app->getUser()->getIdentity();
        if (!$user->getPreference('showFieldHandles')) {
            return;
        }

        $isCraft4 = \version_compare(Craft::$app->getVersion(), '4.0', '>=');

        // Register asset bundle
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event) {
                if ($event->templateMode !== View::TEMPLATE_MODE_CP) {
                    return;
                }
                $this->registerAssetBundle();
            }
        );

        // Edit source links
        if ($isCraft4) {

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

        } else {

            // Legacy template hooks for entries, assets and categories on Craft 3.x
            Craft::$app->getView()->hook('cp.entries.edit.meta', [$this, 'renderEditSourceLink']);

            Craft::$app->getView()->hook('cp.assets.edit.meta', [$this, 'renderEditSourceLink']);

            Craft::$app->getView()->hook('cp.categories.edit.details', [$this, 'renderEditSourceLink']);

        }

        // Hooks that still work on both Craft 3.x and 4.x
        Craft::$app->getView()->hook('cp.globals.edit.content', [$this, 'renderEditSourceLink']);

        Craft::$app->getView()->hook('cp.users.edit.details', [$this, 'renderEditSourceLink']);

        Craft::$app->getView()->hook('cp.commerce.product.edit.details', [$this, 'renderEditSourceLink']);

    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\LoaderError
     * @throws \yii\base\Exception
     */
    public function renderEditSourceLink(array $context): string
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

    /**
     * @return void
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function registerAssetBundle(): void
    {

        $data = [
            'editFieldBtnLabel' => Craft::t('cp-field-inspect', 'Edit field settings'),
            'redirectUrl' => $this->redirect->getRedirectUrl(),
        ];

        $fields = Craft::$app->getFields()->getAllFields('global');

        foreach ($fields as $field) {
            $data['fields'][$field->handle] = (int)$field->id;
        }

        Craft::$app->getView()->registerAssetBundle(CpFieldInspectBundle::class, \yii\web\View::POS_END);
        Craft::$app->getView()->registerJs('Craft.CpFieldInspectPlugin.init(' . \json_encode($data) . ');', \yii\web\View::POS_END);

    }

}
