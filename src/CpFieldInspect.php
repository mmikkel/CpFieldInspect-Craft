<?php
/**
 * CP Field Inspect plugin for Craft CMS 5.x
 *
 * Inspect field handles and easily edit field settings
 *
 * @link      http://mmikkel.no
 * @copyright Copyright (c) 2017 Mats Mikkel Rummelhoff
 */

namespace mmikkel\cpfieldinspect;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\User;
use craft\events\DefineElementHtmlEvent;
use craft\events\DefineHtmlEvent;
use craft\events\PluginEvent;
use craft\events\TemplateEvent;
use craft\helpers\Cp;
use craft\services\Plugins;
use craft\web\View;

use mmikkel\cpfieldinspect\helpers\CpFieldInspectHelper;
use mmikkel\cpfieldinspect\web\CpFieldInspectBundle;

use yii\base\Event;
use yii\web\View as ViewAlias;


/**
 * Class CpFieldInspect
 * @author Mats Mikkel Rummelhoff
 * @package mmikkel\cpfieldinspect
 * @since 1.0.0
 *
 * Plugin icon credit: CUSTOMIZE SEARCH by creative outlet from the Noun Project
 *
 */
class CpFieldInspect extends Plugin
{

    /**
     * @return void
     */
    public function init(): void
    {

        parent::init();

        // If admin changes are disallowed, we have no purpose.
        $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;
        if (!$allowAdminChanges) {
            return;
        }

        // After installation, set the "Show field handles" user preference for active admin users
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin !== $this) {
                    return;
                }
                $adminUsers = User::find()->admin()->status(User::STATUS_ACTIVE)->all();
                foreach ($adminUsers as $adminUser) {
                    try {
                        Craft::$app->getUsers()->saveUserPreferences($adminUser, [
                            'showFieldHandles' => true,
                        ]);
                    } catch (\Throwable $e) {
                        Craft::error($e, __METHOD__);
                    }
                }
            }
        );

        // Eject for anything that isn't a CP request
        $request = Craft::$app->getRequest();
        if (!$request->getIsCpRequest() || $request->getIsConsoleRequest() || $request->getIsLoginRequest()) {
            return;
        }

        // Defer further initialisation to after app init
        Craft::$app->onInit(function () {
            try {
                $this->doIt();
            } catch (\Throwable $e) {
                Craft::error($e, __METHOD__);
            }
        });

    }

    /**
     * @return void
     * @throws \Throwable
     */
    protected function doIt(): void
    {

        // Do nothing if the user is not an admin, or if they don't have field handles visible
        /** @var User $user */
        $user = Craft::$app->getUser()->getIdentity();
        if (!$user?->admin) {
            return;
        }

        // Make sure element cards and chips have a [data-type-id] attribute, which we'll use to bootstrap an "Edit Entry Type" link in their settings menus
        foreach ([
            Cp::EVENT_DEFINE_ELEMENT_CARD_HTML,
            Cp::EVENT_DEFINE_ELEMENT_CHIP_HTML,
        ] as $eventName) {
            Event::on(
                Cp::class,
                $eventName,
                static function (DefineElementHtmlEvent $event) {
                    $typeId = $event->element?->typeId ?? null;
                    if (empty($typeId)) {
                        return;
                    }
                    try {
                        $html = preg_replace('/<(\w+)(.*?id="[^"]+")([^>]*?)>/', "<$1$2 data-type-id=\"$typeId\"$3>", $event->html, 1);
                        if (empty($html)) {
                            return;
                        }
                        $event->html = $html;
                    } catch (\Throwable $e) {
                        Craft::error($e, __METHOD__);
                    }
                }
            );
        }

        // At this point, eject for POST and action requests
        if (!Craft::$app->getRequest()->getIsGet() || Craft::$app->getRequest()->getIsActionRequest()) {
            return;
        }

        // Inject edit source buttons for elements that support the EVENT_DEFINE_META_FIELDS_HTML event
        Event::on(
            Element::class,
            Element::EVENT_DEFINE_META_FIELDS_HTML,
            static function (DefineHtmlEvent $event) {
                if ($event->static) {
                    return;
                }
                $event->html .= CpFieldInspectHelper::getEditElementSourceButton($event->sender);
            }
        );

        // Inject edit source buttons for elements that still use the old template hooks
        Craft::$app->getView()->hook('cp.globals.edit.content', [CpFieldInspectHelper::class, 'renderEditSourceLink']);
        Craft::$app->getView()->hook('cp.commerce.product.edit.details', [CpFieldInspectHelper::class, 'renderEditSourceLink']);

        // Register asset bundle
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            static function (TemplateEvent $event) {
                if ($event->templateMode !== View::TEMPLATE_MODE_CP) {
                    return;
                }
                Craft::$app->getView()->registerAssetBundle(CpFieldInspectBundle::class, ViewAlias::POS_END);
            }
        );

    }

}
