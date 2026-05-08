# Resolve conflicts by moving files as suggested by Git
git rm nutrismart/DATABASE_JOINS.md
git add DATABASE_JOINS.md

git rm nutrismart/EMAIL_SYSTEM_README.md
git add EMAIL_SYSTEM_README.md

git rm nutrismart/Services/AlimentService-refactored.php
git add Services/AlimentService-refactored.php

git rm nutrismart/Services/EmailService.php
git add Services/EmailService.php

git rm nutrismart/Utils/Router.php
git add Utils/Router.php

git rm nutrismart/Utils/ViewRenderer.php
git add Utils/ViewRenderer.php

git rm nutrismart/Views/backoffice/aliment/create-example.php
git add Views/backoffice/aliment/create-example.php

git rm nutrismart/Views/backoffice/aliment/index-example.php
git add Views/backoffice/aliment/index-example.php

git rm nutrismart/Views/backoffice/budget-list.php
git add Views/backoffice/budget-list.php

git rm nutrismart/Views/backoffice/delete_purchase.php
git add Views/backoffice/delete_purchase.php

git rm nutrismart/Views/backoffice/update_purchase.php
git add Views/backoffice/update_purchase.php

git rm nutrismart/composer-setup.php
git add composer-setup.php

git rm nutrismart/config/email_config.php
git add config/email_config.php

git rm nutrismart/controllers/AlimentController-refactored.php
git add controllers/AlimentController-refactored.php

git rm nutrismart/controllers/BaseController.php
git add controllers/BaseController.php

git rm nutrismart/controllers/BudgetController.php
git add controllers/BudgetController.php

git rm nutrismart/index-new.php
git add index-new.php

git rm nutrismart/test_email.php
git add test_email.php

# Delete files that were deleted in the feature branch but modified in main
git rm nutrismart/Services/AchatService.php
git rm nutrismart/Services/BudgetService.php
git rm nutrismart/config/db_connect.php
git rm nutrismart/testDB.php

# Final cleanup of nutrismart folder if empty or redundant
# (Optional, git rm handles most of it)
