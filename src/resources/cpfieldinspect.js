(function (window) {

    if (!window.Craft || !window.jQuery) {
        return false;
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
                .on('click', '.' + this.settings.settingsClassSelector + ' a', this.onCpFieldLinksClick.bind(this))
                .on('click', '[data-cpfieldlinks-sourcebtn]', this.onCpFieldLinksClick.bind(this))
                .on('click', '.matrix .btn.add, .matrix .btn[data-type]', this.onMatrixBlockAddButtonClick.bind(this))
                .ajaxComplete(this.onAjaxComplete.bind(this));

            this.render();
        },
        initElementEditor: function () {
            var now = new Date().getTime(),
                doInitElementEditor = (function () {
                    var timestamp = new Date().getTime(),
                        $elementEditor = $('.elementeditor:last'),
                        $hud = $elementEditor.length > 0 ? $elementEditor.closest('.hud') : false,
                        elementEditor = $hud && $hud.length > 0 ? $hud.data('elementEditor') : false;
                    if (elementEditor && elementEditor.hud) {
                        this.elementEditors[elementEditor._namespace] = elementEditor;
                        elementEditor.hud.on('hide', $.proxy(this.destroyElementEditor, this, elementEditor));
                        Garnish.requestAnimationFrame(this.addFieldLinks.bind(this));
                    } else if (timestamp - now < 2000) { // Poll for 2 secs
                        Garnish.requestAnimationFrame(doInitElementEditor);
                    }
                }).bind(this);
            doInitElementEditor();
        },

        destroyElementEditor: function (elementEditor) {
            if (this.elementEditors.hasOwnProperty(elementEditor._namespace)) {
                delete this.elementEditors[elementEditor._namespace];
            }
        },

        setPathAndRedirect: function () {

            console.log('set path and redirect');

            var redirectTo = Craft.getLocalStorage(this.settings.redirectKey);

            if (redirectTo)
            {
                var $actionInput = $('input[type="hidden"][name="action"]').filter(this.settings.actionInputKeys.join(',')),
                    $redirectInput = $('input[type="hidden"][name="redirect"]');
                if ($actionInput.length > 0 && $redirectInput.length > 0)
                {
                    $redirectInput.attr('value', redirectTo);
                }
            }
            Craft.setLocalStorage(this.settings.redirectKey, null);
        },

        render: function () {
            $('[data-cpfieldlinks]').removeAttr('data-cpfieldlinks');
            this.addFieldLinks();
        },

        addFieldLinks: function () {

            var self = this,
                targets = [$(this.getFieldContextSelector())],
                $target;

            if (this.elementEditors && Object.keys(this.elementEditors).length) {
                for (var key in this.elementEditors) {
                    targets.push(this.elementEditors[key].$form);
                }
            }

            for (var i = 0; i < targets.length; ++i) {

                $target = targets[i];

                if (!$target || !$target.length) {
                    continue;
                }

                // Add CpFieldLinks to regular fields
                var fieldData = this.data.fields || {},
                    $fields = $target.find('.field:not([data-cpfieldlinks])').not('.matrixblock .field'),
                    $field,
                    fieldHandle;
                $fields.each(function () {
                    $field = $(this);
                    fieldHandle = self.getFieldHandleFromAttribute($field.attr('id'));
                    if (fieldHandle && fieldData.hasOwnProperty(fieldHandle)) {
                        $field.find('.heading:first label').after(self.templates.editFieldBtn(fieldData[fieldHandle]));
                    }
                    $field.attr('data-cpfieldlinks', true);
                });

                // Add CpFieldLinks to Commerce variant fields
                $target.find('.variant-matrixblock:not([data-cpfieldlinks])').each(function () {
                    $(this).attr('data-cpfieldlinks', true).find('.field').each(function () {
                        $field = $(this);
                        fieldHandle = self.getFieldHandleFromAttribute($field.attr('id'));
                        if (fieldHandle && fieldData.hasOwnProperty(fieldHandle)) {
                            $field.find('.heading:first label').after(self.templates.editFieldBtn(fieldData[fieldHandle]));
                        }
                    });
                });

                // Add CpFieldLinks to Matrix blocks
                var $matrixBlocks = $target.find('.matrixblock:not([data-cpfieldlinks])'),
                    $block,
                    blockId,
                    blockFieldData;
                $matrixBlocks.each(function () {
                    $block = $(this);
                    fieldHandle = self.getFieldHandleFromAttribute($block.closest('.field').attr('id'));
                    if (!fieldHandle || !fieldData.hasOwnProperty(fieldHandle)) return;
                    blockId = $block.data('id');
                    $block.attr('data-cpfieldlinks', true).find('.field').each(function () {
                        $field = $(this);
                        blockFieldData = {
                            id: fieldData[fieldHandle].id,
                            handle: self.getFieldHandleFromAttribute($field.attr('id'))
                        }
                        $field.find('.heading:first label').after(self.templates.editFieldBtn(blockFieldData));
                    });
                });

            }

        },

        getFieldHandleFromAttribute: function (value) {
            if (!value) return false;
            value = value.split('-');
            if (value.length < 3) return false;
            return value[value.length-2];
        },

        getFieldContextSelector: function () {
            if (this.isLivePreview) {
                return '.lp-editor';
            }
            return '#main';
        },

        templates: {
            editFieldBtn: function (attributes)
            {
                return  '<div class="cp-field-inspect cp-field-inspect-field-edit" aria-hidden="true">' +
                    '<div class="' + Craft.CpFieldInspectPlugin.settings.settingsClassSelector + '">' +
                    '<a href="' + Craft.CpFieldInspectPlugin.data.baseEditFieldUrl + '/' + attributes.id + '" class="settings icon" role="button" aria-label="Edit field" tabindex="-1"></a>' +
                    '</div>' +
                    '<div class="' + Craft.CpFieldInspectPlugin.settings.infoClassSelector + '">' + '<p><code>' + attributes.handle + '</code></p></div>' +
                    '</div>';
            }
        },

        onLivePreviewEnter: function () {
            this.isLivePreview = true;
            Garnish.requestAnimationFrame((function () {
                this.addFieldLinks();
            }).bind(this));
        },

        onLivePreviewExit: function () {
            this.isLivePreview = false;
            Garnish.requestAnimationFrame((function () {
                this.addFieldLinks();
            }).bind(this));
        },

        onCpFieldLinksClick: function (e) {
            Craft.setLocalStorage(this.settings.redirectKey, this.data.redirectUrl || null);
        },

        onMatrixBlockAddButtonClick: function (e) {
            Garnish.requestAnimationFrame((function () {
                this.addFieldLinks();
            }).bind(this));
        },

        onAjaxComplete: function(e, status, requestData) {
            if (requestData.url.indexOf('switch-entry-type') > -1) {
                const $entryTypeSelect = $('#entryType');
                if ($entryTypeSelect.length) {
                    const typeId = $entryTypeSelect.val();
                    $('[data-cpfieldlinks-sourcebtn][data-typeid]:not([data-typeid="' + typeId + '"]').hide();
                    $('[data-cpfieldlinks-sourcebtn][data-typeid="' + typeId + '"]').show();
                }
                this.render();
            }
        }
    };

} (window));
