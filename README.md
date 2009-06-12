SourceScribe
============

SourceScribe is a documentation generator written in PHP. It takes your source
tree, parses out the comments, and outputs nicely-formatted documentation.
At the moment, it supports *all* languages that support comments in either
//, #, or /**/.

To install
----------

 - Unzip the entire package somewhere,
   maybe *c:\Program Files\SourceScribe* or *~/sourcescribe/*.

 - **For Mac and Linux:** you can make a symbolic link to the
   file <code>ss</code> in a directory that's in your *PATH* environment variable.  
   Example for Mac/Linux: <code>sudo ln -s \`pwd\`/ss /usr/local/bin/ss</code>

 - **For all:** Alternatively, you can add that path in the beginning of
   your *PATH* environment variable.

   - For Windows, this is under System Properties &rarr; Advanced Tab &rarr;
     Environment variables &rarr; PATH &rarr; Edit. Add it to the beginning,
     followed by a semi-colon (;).

   - For MacOS/Linux, you can do this by adding the line to your *~/.profile*.
     Example:  
     <code>echo export PATH=/Users/rsc/sourcescribe:\$PATH | tee ~/.profile</code>

Try it!
-------

 - Once installed, type `ss` in the SourceScribe directory. This will build
   the SourceScribe documentation in `doc/` by default. This documentation has
   all information you'll need to use SourceScribe in your projects.

Using in your projects
----------------------

 - The manual contains all the information you need.