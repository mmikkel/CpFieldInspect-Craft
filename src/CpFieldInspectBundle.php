<?php
namespace mmikkel\cpfieldinspect;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CpFieldInspectBundle extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@mmikkel/cpfieldinspect/resources';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'cpfieldinspect.js',
        ];

        $this->css = [
            'cpfieldinspect.css',
        ];

        parent::init();
    }
}