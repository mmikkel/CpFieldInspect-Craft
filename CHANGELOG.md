# CP Field Inspect Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

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
