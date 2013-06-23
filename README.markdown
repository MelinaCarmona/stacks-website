Description
-----------
This is a new version of the website for the [Stacks project](http://stacks.math.columbia.edu), enabling a comment system, improved tag lookup and a full-powered online view of its contents.


Configuration
-------------

Below you will find rough instructions to create a local copy of the Stacks project website on your system. Requirements:

1. apache with mod-rewrite and php enabled
2. unix command line tools, in particular make, python, and git
2. a directory `base`
3. `http://localhost:8080` points to `base/stacks-website`

Here are the instructions:

1. clone `stacks-website` using `git clone https://github.com/stacks/stacks-website`

2. change directories to `stacks-website/` and initialize the submodules using `git submodule init` and `git submodule update`

3. change directories to `stacks-website/` and clone the stacks project into the (not yet existing) `tex/` subdirectory using `git clone git://github.com/stacks/stacks-project tex`

4. change one occurence of `http://stacks.math.columbia.edu/tag/` in `stacks-website/tex/scripts/tag_up.py` to `http://localhost:8080/tag/

5. run `make tags` in `stacks-website/tex/`

6. clone `stacks-tools` in the `base` directory using `git clone https://github.com/stacks/stacks-website`

7. change directories to `stacks-tools` and create the database by calling `python create.py`

8. back in the `base` directory excute the following commands:
	mkdir stacks-website/database
	chmod 0777 stacks-website/database
	mv stacks-tools/stacks.sqlite stacks-website/database
	chmod 0777 stacks-website/database/stacks.sqlite
This will create a directory with the database in it with the correct permissions for the webserver.

9. change directory into stacks-website and edit the file `conf.ini` setting database = "database/stacks.sqlite", directory = "", and project = "/path/to/base/stacks-website/tex"

10. sanity check: at this point if you point your browser to `http://localhost:8080` you should not get an error concerning the database

11. get the correct styling in EpicEditor by executing
	ln -s ../../../../../css/stacks-editor.css js/EpicEditor/epiceditor/themes/editor/stacks-editor.css
	ln -s ../../../../../css/stacks-preview.css js/EpicEditor/epiceditor/themes/preview/stacks-preview.css
from the `stacks-website` directory

12. make MathJax aware of XyJax by executing
	ln -s ../../../../js/XyJax/extensions/TeX/xypic.js js/MathJax/extensions/TeX/xypic.js
	ln -s ../../../js/XyJax/extensions/fp.js js/MathJax/extensions/fp.js
from the `stacks-website` directory

Updating the website
--------------------
1. Update the `tex/` folder using `git pull` in `tex/`

2. Run steps 4 and 8 of the above
