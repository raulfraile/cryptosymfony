#!/bin/bash

php ../project/vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php
cp -p uploads/* ../project/web/uploads
rm -fr ../project/app/cache/dev/users