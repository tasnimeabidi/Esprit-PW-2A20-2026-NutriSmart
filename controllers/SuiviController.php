<?php
// Minimal SuiviController to prevent fatal errors
class SuiviController {
    public function listLogs() {
        // Return a dummy object that mimics a PDO statement but is empty
        return new class {
            public function fetch() { return false; }
        };
    }
}
?>
