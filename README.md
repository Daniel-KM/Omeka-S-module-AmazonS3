Amazon S3 (module for Omeka S)
==============================

[Amazon S3] is a module for [Omeka S] that allows to store files via an external
storage provider, Amazon S3.

This module is compatible with [Archive Repertory], that allows to keep original
filenames for the files managed by Omeka.


Installation
------------

See general end user documentation for [installing a module].

The module uses an external library [AWS SDK], so use the release zip to
install it, or use and init the source.

* From the zip

Download the last release [`AmazonS3.zip`] from the list of releases (the master
does not contain the dependency), and uncompress it in the `modules` directory.

* From the source and for development:

If the module was installed from the source, rename the name of the folder of
the module to `AmazonS3`, and go to the root of the module, and run:

```
    composer install
```

The next times:

```
    composer update
```


Quick Start
-----------

Simply config your credentials in the config page of the module.

If module is active, it will replace default Local store class with S3 Storage.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitHub.


License
-------

This module is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.


Copyright
---------

* Copyright Daniel Berthereau, 2019 (see [Daniel-KM] on GitHub)

This project was supported in part by the University of California Office of the
President MRPI funding MR-15-328710.


[Amazon S3]: https://github.com/Daniel-KM/Omeka-S-module-AmazonS3
[Omeka S]: https://omeka.org/s
[AWS SDK]: https://aws.amazon.com/sdk-for-php/
[Archive Repertory]: https://github.com/Daniel-KM/Omeka-S-module-ArchiveRepertory
[`AmazonS3.zip`]: https://github.com/Daniel-KM/Omeka-S-module-AmazonS3/releases
[installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[module issues]: https://github.com/Daniel-KM/Omeka-S-module-AmazonS3/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: https://github.com/sandywalker/webui-popover/blob/master/LICENSE.txt
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
