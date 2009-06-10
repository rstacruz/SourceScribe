SourceScribe
============

SourceScribe is a documentation generator written in PHP. It takes your source
tree, parses out the comments, and outputs nicely-formatted documentation.
At the moment, it supports *all* languages that support comments in either
//, #, or /**/.

This readme sucks and will be improved later on, I promise.

To install
----------

 - Place all these files somewhere (maybe `/usr/local/share/sourcescribe`)
 - Edit the `ss` file to point to that location
 - Copy `ss` to a bin folder like /usr/local/bin

Try it!
-------

 - Type `ss` in the SourceScribe directory. This will build the SourceScribe
   documentation in `doc/` by default.

Using in your projects
----------------------

 - To use in your projects, document your functions like the sourcescribe src,
   and put the `sourcescribe.conf` file in your project's root and edit it to
   your needs.
   
 - SourceScribe only documents explicitly-commented sections. It will not parse
   out your code at all; it will only scan through your comments.