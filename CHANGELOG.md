# CP Field Inspect Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased
### Fixed
- Fixed a regression bug introduced in 2.0.3, where the "Edit entry type" link would no longer be added to Matrix entries   

## 2.0.3 - 2025-01-22
### Fixed
- Fixed a minor Craft 5.6 compatibility bug where the "Edit entry type" link could be added to field action menus 

## 2.0.2 - 2024-08-05
### Fixed
- Fixed a bug where field and source setting links wouldn't appear in element edit forms with validation errors.  
- Fixed a compatibility issue with Craft 5.3, where Link field values' action menus could get add "Edit Entry Type" link added.  

## 2.0.1 - 2024-04-01
### Fixed
- Fixed some layout issues in edit pages for element source buttons  

## 2.0.0 - 2024-03-27  
### Added
- Added Craft 5 compatibility
- Added fully functioning field settings cogwheel buttons to sub fields in inline-editable Matrix fields  
- Added an "Edit entry type" link to nested entries' action menus in inline-editable Matrix fields
- Added an "Edit entry type" link to element cards and chips' action menus
- Added an "Edit section" link to section entries

### Changed
- CP Field Inspect no longer requires the "Show field handles in edit forms" admin preference to be enabled to initialise. If that admin preference is disabled, field cogwheels will not be rendered, but element source settings links (i.e. "Edit Entry Type" etc.) will still be available.  

### Improved
- Improved performance
- Improved accessibility
- Source edit pages can now be opened in a new tab by holding down the Cmd/Ctrl key or the middle mouse button
