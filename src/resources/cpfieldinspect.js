/** global: Craft */
/** global: Garnish */
/** global: $ */

(function (window) {

    if (!window.Craft || !window.Garnish || !window.$) {
        return;
    }

    Craft.CpFieldInspectPlugin = {

        settings: {
            settingsClassSelector: 'cp-field-inspect-settings',
            infoClassSelector: 'cp-field-inspect-info',
            redirectKey: '_CpFieldInspectRedirectTo',
            actionInputKeys: [
                '[value="fields/save-field"]',
                '[value="sections/save-entry-type"]',
                '[value="globals/save-set"]',
                '[value="categories/save-group"]',
                '[value="volumes/save-volume"]',
                '[value="users/save-field-layout"]',
                '[value="commerce/product-types/save-product-type"]',
            ]
        },

        init: function (data) {

            var _this = this;

            this.data = data;
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
                            _this.data.redirectUrl = res.data;
                        }
                    })
                    .catch(function (error) {
                        console.error(error);
                    });
            }, 100);

            // Add event handlers
            Garnish.$doc
                .on('click', '[data-cpfieldlinks-sourcebtn]', $.proxy(this.onSourceEditBtnClick, this))
                .on('click', '.matrix .btn.add, .matrix .btn[data-type]', $.proxy(this.onMatrixBlockAddButtonClick, this));

            if (Craft.DraftEditor) {
                Garnish.on(Craft.DraftEditor, 'update', function () {
                    setTimeout($.proxy(_this.update, _this), 0);
                });
            } else if (Craft.ElementEditor) {
                var afterSaveDraftFn = Craft.ElementEditor.prototype._afterSaveDraft;
                Craft.ElementEditor.prototype._afterSaveDraft = function () {
                    afterSaveDraftFn.apply(this, arguments);
                    setTimeout($.proxy(_this.update, _this), 0);
                }
            }

            // Add field links
            Garnish.requestAnimationFrame($.proxy(this.addFieldLinks, this));
        },

        update: function () {
            this.addFieldLinks();
            this.updateEntryTypeButton();
        },

        setPathAndRedirect: function () {
            var redirectTo = Craft.getLocalStorage(this.settings.redirectKey);
            Craft.setLocalStorage(this.settings.redirectKey, null);
            if (!redirectTo) {
                return;
            }
            var $actionInput = $('input[type="hidden"][name="action"]').filter(this.settings.actionInputKeys.join(','));
            var $redirectInput = $('input[type="hidden"][name="redirect"]');
            if ($actionInput.length > 0) {
                if ($redirectInput.length > 0) {
                    $redirectInput.attr('value', redirectTo);
                } else {
                    $actionInput.after('<input type="hidden" name="redirect" value="' + redirectTo +'" />');
                }
            }
            // Override the new save shortcut behaviour in Craft 3.5.10+
            var $primaryForm = Craft.cp.$primaryForm;
            if (
                $primaryForm.length &&
                Garnish.hasAttr($primaryForm, 'data-saveshortcut') &&
                !!Craft.cp.submitPrimaryForm
            ) {
                Garnish.shortcutManager.unregisterShortcut({ keyCode: Garnish.S_KEY, ctrl: true });
                Garnish.shortcutManager.registerShortcut({ keyCode: Garnish.S_KEY, ctrl: true }, () => {
                    Craft.cp.submitPrimaryForm({ redirect: redirectTo, retainScroll: false });
                });
                var submitBtn = ($primaryForm.find('.btn.submit.menubtn').data() || {}).menubtn || null;
                if (submitBtn && submitBtn.menu) {
                    $(submitBtn.menu.$options[0]).find('span.shortcut').remove();
                }
            }
        },

        addFieldLinks: function () {

            var targets = [$('#main,.lp-editor')];

            if (!targets.length) {
                return;
            }

            var _this = this;

            for (var i = 0; i < targets.length; ++i) {

                var $target = targets[i];
                if (!$target || !$target.length) {
                    continue;
                }

                var $copyFieldHandleButtons = $target.find('.field .heading [id$=-attribute].copytextbtn:not([data-cpfieldlinks-inited])');

                $copyFieldHandleButtons.each(function () {

                    var $btn = $(this);
                    $btn.attr('data-cpfieldlinks-inited', true);

                    var rootField = $btn.parents('.field').get().slice(-1).pop();
                    var parentField = $btn.closest('.field');
                    if (!rootField || !parentField) {
                        return;
                    }

                    var field;
                    var rootFieldType = $(rootField).data('type');

                    switch (rootFieldType) {
                        case 'benf\\neo\\Field':
                            field = parentField;
                            break;
                        default:
                            field = rootField;
                    }

                    var fieldId = _this.getFieldId(field);
                    if (!fieldId) {
                        return;
                    }

                    var url = Craft.getCpUrl('settings/fields/edit/' + fieldId);
                    $btn
                        .append('<span data-icon="settings" title="' + (_this.data.editFieldBtnLabel || 'Edit field settings') + '" />')
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

            }
        },

        doRedirect: function (href) {
            Craft.setLocalStorage(this.settings.redirectKey, this.data.redirectUrl || null);
            window.location.href = href;
        },

        getFieldId: function (field) {
            var id = $(field).attr('id');
            if (!id) {
                return null;
            }
            var segments = id.split('-');
            if (segments.length < 3) {
                return null;
            }
            var handle = segments[segments.length - 2];
            return (this.data.fields || {})[handle] || null;
        },

        onSourceEditBtnClick: function (e) {
            e.preventDefault();
            this.doRedirect(e.target.href);
        },

        onMatrixBlockAddButtonClick: function () {
            Garnish.requestAnimationFrame($.proxy(this.addFieldLinks, this));
        },

        updateEntryTypeButton: function () {
            const $entryTypeSelect = $('#entryType');
            if (!$entryTypeSelect.length) {
                return;
            }
            const typeId = $entryTypeSelect.val();
            $('[data-cpfieldlinks-sourcebtn][data-typeid]:not([data-typeid="' + typeId + '"]').hide();
            $('[data-cpfieldlinks-sourcebtn][data-typeid="' + typeId + '"]').show();
        }
    };

} (window));
