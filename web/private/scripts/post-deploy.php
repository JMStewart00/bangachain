<?php
echo "Beginning post-deploy tasks\n";

echo "Rebuilding caches to avoid problems with cached references\n";
passthru('drush cr');

echo "Importing configuration\n";
passthru('drush config-import -y');

echo "Running database updates\n";
passthru('drush updb -y');

echo "Rebuilding caches after import\n";
passthru('drush cr');

echo "Post-deploy tasks complete!\n";
