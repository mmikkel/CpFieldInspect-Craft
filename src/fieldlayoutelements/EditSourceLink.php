<?php

namespace mmikkel\cpfieldinspect\fieldlayoutelements;

use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\helpers\Html;
use mmikkel\cpfieldinspect\helpers\CpFieldInspectHelper;

class EditSourceLink extends FieldLayoutElement
{

    /** @inheritdoc */
    protected function conditional(): bool
    {
        return false;
    }

    /** @inheritdoc */
    public function selectorHtml(): string
    {
        return '';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return CpFieldInspectHelper::getEditElementSourceButton($element, 'small');
    }
}
