# CP Field Inspect Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 1.4.4 - 2022-05-07
### Changed  
- CP Field Inspect now defers any element queries to the `craft\web\Application::EVENT_INIT` event, avoiding potential issues with element queries being executed before Craft has fully initialised. Fixes #27.

## 1.4.3 - 2022-05-05
### Fixed  
- [Craft 4] Fixes an issue where field cogwheels would lead to a 404 on multi-site installs. Fixes #26  

## 1.4.2 - 2022-04-26
### Fixed
- Fixes an issue where CP Field Inspect could interfere with Plugin Store buy buttons inside the control panel  

## 1.4.1 - 2022-04-19
### Fixed
- Fixes a potential JavaScript issue where CP Field Inspect could interfere with AJAX requests in the control panel

## 1.4.0.1 - 2022-04-05

### Fixed
- Fixes a regression error that could cause a PHP exception in element edit pages on multi-site installs

## 1.4.0 - 2022-04-05

### Fixed
- Fixes an issue where CP Field Inspect would not reload field links and element source buttons after AJAX requests (for example, after switching the entry type), on Craft 4.  
- Fixes an issue where field links would not appear in Preview for newly added Matrix blocks  
- Fixes an issue where CP Field Inspect failed to redirect back to global sets' edit pages, if the global set's handle was changed via its edit page  
- Fixes some Craft 4 compatibility issues    

### Added
- After installation, CP Field Inspect will now set the "Show field handles in edit forms" admin user preference to `true` for all active admin users (but only in environments where `allowAdminChanges` is `true`)

### Improved
- CP Field Inspect's CSS and JS assets no longer outputs for pages rendered in control panel requests, if the template that rendered was in the site template folder.  

### Changed
- CP Field Inspect now requires Craft 3.7.x  

## 1.3.1 - 2022-03-17
### Fixed
- Fixes a minor styling issue for the field settings cogwheels on Craft 3.7.37+ and 4.0.0-beta.2+  

## 1.3.0 - 2022-03-14
### Added
- Added Craft 4.0 compatibility  

### Changed
- Improved plugin icon  

### Fixed
- Fixed an issue where editing the users field layout via the "Edit Users Setting" source button would not redirect back to the user edit page
- Fixed an issue where CP Field Inspect would not override the save shortcut for element sources

## 1.2.6 - 2022-02-17
### Fixed
- Fixes a JavaScript error that could occur when XHR errors happened in the control panel

## 1.2.5 - 2021-05-17  
### Fixed  
- Fixes click behaviour for the cogwheel when holding down the Cmd/Ctrl key or the middle mouse button to open field settings in a new browser tab. Resolves #17  

### Improved  
- CP Field Inspect now redirects back to the active field layout tab. Resolves #20  

## 1.2.4 - 2021-01-27

### Fixed  
- Fixes compatability issues with Craft 3.6  

## 1.2.3 - 2020-10-23

### Improved  
- Restored the old Cmd/Ctrl + S shortcut behaviour that was changed in Craft 3.5.10. If the user opens up the field settings via CP Field Inspect's cogwheel links, saving the field settings using the keyboard shortcut will now redirect the user back to the element edit form, like in Craft versions prior to 3.5.10.  

### Changed  
- CP Field Inspect now requires Craft 3.5.10 or later  

## 1.2.2 - 2020-08-20

### Improved
- Restored the ability to open up field and/or element source settings via the cogwheels in a new tab, by holding down the Cmd (Mac) or Ctrl (Windows) key
- Improved support for Neo fields

## 1.2.1 - 2020-08-13  

> {tip} Craft 3.5.3 finally added a native "show and copy field handle" feature :tada: CP Field Inspect has been updated to work with this feature, by adding the field settings buttons (y'know, those little cogwheel links) _inside_ the native "copy field handle" element.  

### Changed  
- CP Field Inspect now requires Craft 3.5.4 or higher  
- Field settings links and element source buttons no longer require `devMode`. They now show up if the new user preference "Show field handles in edit forms" (added in Craft 3.5.4) is enabled and `allowAdminChanges` is `true`  

## 1.2.0 - 2020-08-12  

### Changed
- CP Field Inspect now requires Craft 3.5.3 or higher
- Field settings links (those little cogwheels) now render inside the new "copy field handle" element added in Craft 3.5.3 (CP Field Inspect no longer adds the handle; that part is now a core feature)  
- Field settings links no longer render if `devMode` is not enabled, or if `allowAdminChanges` is set to `false`   
- Element source buttons no longer render if `devMode` is not enabled, or if `allowAdminChanges` is set to `false`    
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
