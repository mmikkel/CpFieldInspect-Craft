# CP Field Inspect Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 1.2.0 - 2020-08-12  

> {tip} Craft 3.5.3 finally adds a native "show and copy field handle" feature :tada: CP Field Inspect has been updated to work with this feature, by adding the field settings links (y'know, those little cogwheels) _inside_ the native "copy field handle" element. _One small thing to note_ is that due to these elements only being visible if `devMode` is enabled, CP Field Inspect's field settings links will also not display anymore if `devMode` is disabled (previously the links would render as long as the current user was an admin).  

### Changed
- CP Field Inspect now requires Craft 3.5.3 or higher
- Field settings links (those little cogwheels) now render inside the new "copy field handle" element added in Craft 3.5.3 (CP Field Inspect no longer adds the handle; that part is now a core feature)  
- Field settings links no longer render without `devMode` enabled  
- Field settings links no longer render in environments where `allowAdminChanges` is set to `false` 
- Edit source buttons no longer render in environments where `allowAdminChanges` is set to `false`  
- The "Edit Users Settings" source button no longer renders when creating a new user  

## 1.1.3 - 2020-02-29

### Fixed
- Fixes an issue where multiple cogwheels could appear for a single field

## 1.1.2 - 2020-02-14

### Fixed
- Fixes some issues with static translations for edit source buttons

## 1.1.0.1 - 2020-02-09

### Fixed
- Fixes a regression bug introduced in 1.1.0, where edit field links would not work

## 1.1.0 - 2020-02-09

### Added
- Adds "Edit Volume" button to the new Asset edit view
- Adds "Settings" button to the User edit view  

### Changed
- CP Field Inspect now requires Craft 3.4.2  

### Improved
- Fixes some Craft 3.4 compatibility issues
- Replaces JavaScript injected element source links with native buttons rendered using core template hooks
- Fixes support for Commerce Products (fixes an issue where the "Edit Product Type" source button was gone)

## 1.0.7 - 2019-10-21

### Improved
- Fixes an issue where CP Field Inspect could conflict with other plugins.

## 1.0.6 - 2019-07-21

### Improved
- Fixes compatibility with Craft 3.2's new auto-saving drafts system

## 1.0.5 - 2018-09-26

### Fixed
- Fixes an issue where cogwheels would disappear when changing entry types
- Fixes an issue where cogwheels would not display for categories

## 1.0.4 - 2018-03-28

### Fixed
- Fixes an issue where CP Field Inspect could potentially conflict with other plugins (e.g. SEOmatic)

## 1.0.2 - 2017-12-07

### Improved
- Updates Craft CMS semver dependency to ^3.0.0-RC1

## 1.0.1 - 2017-12-06

### Added
- Adds plugin icon

## 1.0.0 - 2017-10-22

### Added
- Initial release
