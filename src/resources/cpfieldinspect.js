/** global: Craft */
/** global: Garnish */
/** global: $ */

(function (window) {

    if (!window.Craft || !window.Garnish || !window.$) {
        return;
    }

    Craft.CpFieldInspectPlugin = {

        elementEditors: {},

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
                '[value="commerce/product-types/save-product-type"]'
            ]
        },

        init: function (data) {

            var _this = this;

            this.data = data;
            this.setPathAndRedirect();

            // Initialize Live Preview support
            var now = new Date().getTime(),
                livePreviewPoller = (function getLivePreview() {
                    if (Craft.livePreview) {
                        Craft.livePreview.on('enter', this.onLivePreviewEnter.bind(this));
                        Craft.livePreview.on('exit', this.onLivePreviewExit.bind(this));
                    } else if (new Date().getTime() - now < 2000) {
                        Garnish.requestAnimationFrame(livePreviewPoller);
                    }
                }).bind(this);

            livePreviewPoller();

            // Poll for address bar change
            var url = window.location.href;
            this.addressBarChangeInterval = setInterval(function () {
                var newUrl = window.location.href;
                if (newUrl === url) {
                    return;
                }
                url = newUrl;
                try {
                    Craft.postActionRequest('cp-field-inspect/default/get-redirect-hash', { url: url }, $.proxy(function (response) {
                        this.data.redirectUrl = response.data || this.data.redirectUrl || null;
                    }, _this));
                } catch (error) {
                    console.error(error);
                }
            }, 100);

            // Add event handlers
            Garnish.$doc
                .on('click', '[data-cpfieldlinks-sourcebtn]', $.proxy(this.onSourceEditBtnClick, this))
                .on('click', '.matrix .btn.add, .matrix .btn[data-type]', $.proxy(this.onMatrixBlockAddButtonClick, this))
                .on('keydown', $.proxy(this.onKeyDown, this))
                .on('keyup', $.proxy(this.onKeyUp, this))
                .ajaxComplete($.proxy(this.onAjaxComplete, this));

            window.onblur = this.onWindowBlur;

            Garnish.requestAnimationFrame($.proxy(this.addFieldLinks, this));
        },

        initElementEditor: function () {
            var now = new Date().getTime(),
                doInitElementEditor = $.proxy(function () {
                    var timestamp = new Date().getTime(),
                        $elementEditor = $('.elementeditor:last'),
                        $hud = $elementEditor.length > 0 ? $elementEditor.closest('.hud') : false,
                        elementEditor = $hud && $hud.length > 0 ? $hud.data('elementEditor') : false;
                    if (elementEditor && elementEditor.hud) {
                        this.elementEditors[elementEditor._namespace] = elementEditor;
                        elementEditor.hud.on('hide', $.proxy(this.destroyElementEditor, this, elementEditor));
                        Garnish.requestAnimationFrame($.proxy(this.addFieldLinks, this));
                    } else if (timestamp - now < 2000) { // Poll for 2 secs
                        Garnish.requestAnimationFrame(doInitElementEditor);
                    }
                }, this);
            doInitElementEditor();
        },

        destroyElementEditor: function (elementEditor) {
            if (this.elementEditors.hasOwnProperty(elementEditor._namespace)) {
                delete this.elementEditors[elementEditor._namespace];
            }
        },

        setPathAndRedirect: function () {
            var redirectTo = Craft.getLocalStorage(this.settings.redirectKey);
            if (redirectTo) {
                var $actionInput = $('input[type="hidden"][name="action"]').filter(this.settings.actionInputKeys.join(','));
                var $redirectInput = $('input[type="hidden"][name="redirect"]');
                if ($actionInput.length > 0 && $redirectInput.length > 0) {
                    $redirectInput.attr('value', redirectTo);
                }
                // Override the new save shortcut behaviour in Craft 3.5.10
                var $primaryForm = Craft.cp.$primaryForm;
                if (
                    $primaryForm.length &&
                    $primaryForm.find('input[name="action"]').val() === 'fields/save-field' &&
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
            }
            Craft.setLocalStorage(this.settings.redirectKey, null);
        },

        addFieldLinks: function () {

            var targets = [$(this.getFieldContextSelector())];

            if (this.elementEditors && Object.keys(this.elementEditors).length) {
                for (var key in this.elementEditors) {
                    if (this.elementEditors.hasOwnProperty(key)) {
                        targets.push(this.elementEditors[key].$form);
                    }
                }
            }

            if (!targets.length) {
                return;
            }

            var _this = this;

            for (var i = 0; i < targets.length; ++i) {

                var $target = targets[i];
                if (!$target || !$target.length) {
                    continue;
                }

                var $copyFieldHandleButtons = $target.find('.field .heading [id$=-field-attribute].code:not([data-cpfieldlinks-inited])');
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

                    $btn
                        .append('<span data-icon="settings" title="' + (_this.data.editFieldBtnLabel || 'Edit field settings') + '" />')
                        .on('click', '[data-icon="settings"]', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            _this.redirectToFieldSettings(fieldId);
                        });

                }).on('mouseleave', function () {
                    $(this).blur();
                });

            }
        },

        doRedirect: function (href) {
            if (this.ctrlKeyDown) {
                window.open(href);
            } else {
                Craft.setLocalStorage(this.settings.redirectKey, this.data.redirectUrl || null);
                window.location.href = href;
            }
        },

        redirectToFieldSettings: function (fieldId) {
            var href = Craft.CpFieldInspectPlugin.data.baseEditFieldUrl + '/' + fieldId;
            this.doRedirect(href);
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

        getFieldContextSelector: function () {
            if (this.isLivePreview) {
                return '.lp-editor';
            }
            return '#main';
        },

        onLivePreviewEnter: function () {
            this.isLivePreview = true;
            Garnish.requestAnimationFrame($.proxy(this.addFieldLinks, this));
        },

        onLivePreviewExit: function () {
            this.isLivePreview = false;
            Garnish.requestAnimationFrame($.proxy(this.addFieldLinks, this));
        },

        onSourceEditBtnClick: function (e) {
            e.preventDefault();
            this.doRedirect(e.target.href);
        },

        onMatrixBlockAddButtonClick: function () {
            Garnish.requestAnimationFrame($.proxy(this.addFieldLinks, this));
        },

        onKeyDown: function(e) {
            this.ctrlKeyDown = Garnish.isCtrlKeyPressed(e);
            setTimeout(function () {
                this.ctrlKeyDown = false;
            }, 1000);
        },

        onKeyUp: function () {
            this.ctrlKeyDown = false;
        },

        onWindowBlur: function () {
            this.ctrlKeyDown = false;
        },

        onAjaxComplete: function(e, status, requestData) {
            if (requestData.url.indexOf('switch-entry-type') === -1) {
                return;
            }
            const $entryTypeSelect = $('#entryType');
            if ($entryTypeSelect.length) {
                const typeId = $entryTypeSelect.val();
                $('[data-cpfieldlinks-sourcebtn][data-typeid]:not([data-typeid="' + typeId + '"]').hide();
                $('[data-cpfieldlinks-sourcebtn][data-typeid="' + typeId + '"]').show();
            }
            this.addFieldLinks();
        }
    };

} (window));
