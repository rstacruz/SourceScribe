# SourceScribe configuration
# (This file is in YAML format. Mind your indentation!)

# Document title
#   Enter your main title here. You can define your own home page by making
#   a page with this name. 
#
name: My API manual

# Document outputs
#   Define as many outputs here as you need. Available drivers by
#   default are 'html', which you can use as many times as you want.
#
#   You must always define "driver" (which defines which output plugin to
#   use) and "path" (the directory where the output documents will be put in,
#   relative to where this config file is)
#
output:
  
  - driver:   "html"
    path:     "doc"
    template: "default"
  
# Source paths (Optional)
#   Defines where the sources are. Relative paths are resolved from where
#   this config file lies in. This can be in defined as an array for multiple
#   paths.
#
#   Example:      src_path: [src, www]
#
#   In the example above, if this configuration file is in
#   ~/myproject/sourcescribe.conf, this src_path line will direct
#   SourceScribe to check on both ~/myproject/src and ~/myproject/www.
#
src_path: [ . ]

# Exclusions (Optional)
#   These are files to be excluded in regex format.
#   This list is completely optional and may be omitted.
#
exclude:
 - !.git/!
 - !.svn/!
 - !\.html$!
 - !/cache/!
