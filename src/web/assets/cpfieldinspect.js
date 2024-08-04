/** global: Craft */
/** global: Garnish */
/** global: $ */

(function (window) {

    const {Craft, Garnish, $} = window;

    if (!Craft || !Garnish || !$) {
        return;
    }

    Craft.CpFieldInspectPlugin = {

        settings: {
            settingsClassSelector: 'cp-field-inspect-settings',
            infoClassSelector: 'cp-field-inspect-info',
            redirectKey: '_CpFieldInspectRedirectTo',
            actionInputKeys: [
                '[value="fields/save-field"]',
                '[value="entry-types/save"]',
                '[value="globals/save-set"]',
                '[value="categories/save-group"]',
                '[value="volumes/save-volume"]',
                '[value="users/save-field-layout"]',
                '[value="commerce/product-types/save-product-type"]'
            ]
        },

        redirectUrl: null,

        customFieldElements: {},

        init: function (data) {

            this.redirectUrl = data.redirectUrl || null;
            this.customFieldElements = data.customFieldElements || {};

            const _this = this;

            this.setPathAndRedirect();

            // Poll for address bar change
            var url = window.location.href;
            this.addressBarChangeInterval = setInterval(function () {
                var newUrl = window.location.href;
                if (newUrl === url) {
                    return;
                }
                url = newUrl;
                Craft.sendActionRequest('POST', 'cp-field-inspect/default/get-redirect-hash', {
                    data: {
                        url: url
                    }
                })
                    .then(function (res) {
                        if (res.status === 200 && res.data) {
                            _this.redirectUrl = res.data;
                        }
                    })
                    .catch(function (error) {
                        console.error(error);
                    });
            }, 100);

            // Add event handlers
            Garnish.$doc.on('click', '[data-cp-field-inspect-sourcebtn]', $.proxy(this.onSourceEditBtnClick, this));

            // Init disclosure menus
            if (Garnish.DisclosureMenu) {
                const disclosureMenuShowFn = Garnish.DisclosureMenu.prototype.show;
                Garnish.DisclosureMenu.prototype.show = function () {
                    _this.initDisclosureMenu(this);
                    disclosureMenuShowFn.apply(this, arguments);
                };
            }

            if (Craft.ElementEditor) {
                var afterSaveDraftFn = Craft.ElementEditor.prototype._afterSaveDraft;
                Craft.ElementEditor.prototype._afterSaveDraft = function () {
                    afterSaveDraftFn.apply(this, arguments);
                    setTimeout($.proxy(_this.update, _this, this), 0);
                };
            }

            // Add field links
            this.addFieldLinks();

        },

        update: function (editor) {
            this.addFieldLinks(editor);
            this.updateEntryTypeButton(editor);
        },

        setPathAndRedirect: function () {
            const redirectTo = Craft.getLocalStorage(this.settings.redirectKey);
            Craft.setLocalStorage(this.settings.redirectKey, null);
            if (!redirectTo) {
                return;
            }
            const $actionInput = $('input[type="hidden"][name="action"]').filter(this.settings.actionInputKeys.join(','));
            const $redirectInput = $('input[type="hidden"][name="redirect"]');
            if ($actionInput.length > 0) {
                if ($redirectInput.length > 0) {
                    $redirectInput.attr('value', redirectTo);
                } else {
                    $actionInput.after('<input type="hidden" name="redirect" value="' + redirectTo + '" />');
                }
            }
            // Override the new save shortcut behaviour in Craft 3.5.10+
            const $primaryForm = Craft.cp.$primaryForm;
            if (
                $primaryForm.length &&
                Garnish.hasAttr($primaryForm, 'data-saveshortcut') &&
                !!Craft.cp.submitPrimaryForm
            ) {
                Garnish.shortcutManager.unregisterShortcut({keyCode: Garnish.S_KEY, ctrl: true});
                Garnish.shortcutManager.registerShortcut({keyCode: Garnish.S_KEY, ctrl: true}, () => {
                    Craft.cp.submitPrimaryForm({redirect: redirectTo, retainScroll: false});
                });
                var submitBtn = ($primaryForm.find('.btn.submit.menubtn').data() || {}).menubtn || null;
                if (submitBtn && submitBtn.menu) {
                    $(submitBtn.menu.$options[0]).find('span.shortcut').remove();
                }
            }
        },

        addFieldLinks: function (editor) {

            const _this = this;

            let $container;
            if (editor) {
                $container = editor.$container;
            } else {
                $container = $('body');
            }

            const $copyFieldHandleButtons = $container.find('.field .heading [id$=-attribute].copytextbtn:not([data-cpfieldinspect-inited])');

            $copyFieldHandleButtons.each(function () {

                const $btn = $(this);
                const field = $btn.closest('[data-layout-element]');
                const fieldId = _this.getFieldIdForLayoutElement(field);

                if (!fieldId) {
                    return;
                }

                $btn.attr('data-cpfieldinspect-inited', true);

                const url = Craft.getCpUrl('settings/fields/edit/' + fieldId);
                $btn.append(`<span class="cp-field-inspect"><span data-icon="settings" role="button" tabindex="0" title="${Craft.t('cp-field-inspect', 'Edit field settings')}" aria-label="${Craft.t('cp-field-inspect', 'Edit field settings')}" /></span>`);
                $btn
                    .on('mouseup', '[data-icon="settings"]', function (e) {
                        if (e.which === Garnish.PRIMARY_CLICK || e.which === Garnish.SECONDARY_CLICK) {
                            return;
                        }
                        e.preventDefault();
                        e.stopPropagation();
                        window.open(url);
                    })
                    .on('click', '[data-icon="settings"]', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (Garnish.isCtrlKeyPressed(e)) {
                            window.open(url);
                            return;
                        }
                        _this.doRedirect(url);
                    });

            }).on('mouseleave', function () {
                $(this).blur();
            });

        },

        initDisclosureMenu(disclosureMenu) {
            if (disclosureMenu._hasCpFieldInspectInited) {
                return;
            }
            disclosureMenu._hasCpFieldInspectInited = true;
            const {$trigger, $container} = disclosureMenu;
            if (!$trigger || !$container || !$trigger.hasClass('action-btn') || ['craft\\fields\\Link'].indexOf($trigger.closest('[data-type]').attr('data-type')) > -1) {
                return;
            }
            const $element = $trigger.closest('.matrixblock,.element.card,.element.chip');
            if (!$element.length) {
                return;
            }
            const {typeId} = $element.data();
            if (!typeId) {
                return;
            }
            const $editEntryTypeLink = `<li><a href="${Craft.getCpUrl('settings/entry-types/' + typeId)}" data-icon="settings" data-cp-field-inspect-sourcebtn>${Craft.t('cp-field-inspect', 'Edit entry type')}</a></li>`;
            $container.find('ul').eq(0).append($editEntryTypeLink);
        },

        doRedirect: function (href) {
            Craft.setLocalStorage(this.settings.redirectKey, this.redirectUrl || null);
            window.location.href = href;
        },

        getFieldIdForLayoutElement: function (field) {
            const layoutElementUid = $(field).data('layout-element');
            if (!layoutElementUid) {
                return null;
            }
            return this.customFieldElements[layoutElementUid] || null;
        },

        onSourceEditBtnClick: function (e) {
            if (Garnish.isCtrlKeyPressed(e)) {
                return true;
            }
            e.preventDefault();
            this.doRedirect(e.target.href);
            return false;
        },

        updateEntryTypeButton: function (editor) {
            let $container;
            if (editor) {
                $container = editor.$container;
            } else {
                $container = $('body');
            }
            const $entryTypeInput = $container.find('input[type="hidden"][id$="entryType-input"]').eq(0);
            if (!$entryTypeInput.length) {
                return;
            }
            const currentTypeId = $entryTypeInput.val();
            if (!currentTypeId) {
                return;
            }
            const _this = this;
            $container.find('[data-cp-field-inspect-sourcebtn][data-type-id]').each(function () {
                const { typeId } = this.dataset;
                if (typeId === currentTypeId) {
                    return;
                }
                this.setAttribute('href', this.getAttribute('href').replace(`settings/entry-types/${typeId}`, `settings/entry-types/${currentTypeId}`));
                _this.overrideSerializedForm($container);
            });
        },

        overrideSerializedForm($form) {
            setTimeout(() => {
                const { elementEditor } = $form.data();
                if (!elementEditor) {
                    return;
                }
                const data = elementEditor.serializeForm(true);
                if (data === $form.data('initialSerializedValue') && data === elementEditor.lastSerializedValue) {
                    return;
                }
                $form.data('initialSerializedValue', data);
                elementEditor.lastSerializedValue = data;
            }, 0);
        }

    };

}(window));
