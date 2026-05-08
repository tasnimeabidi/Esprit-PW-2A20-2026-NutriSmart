<?php
require_once __DIR__ . '/../Models/config.php';
require_once __DIR__ . '/../Models/Budget.php';
require_once __DIR__ . '/../Services/UserService.php';

class BudgetController {
    private $db;
    private $budget;
    private $userService;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->budget = new Budget($this->db);
        $this->userService = new UserService();
    }


    public function index() {
        $budgets = $this->budget->getAllWithUserDetails();
        $users = $this->userService->getAllUsers();
        
        include __DIR__ . '/../Views/backoffice/budget-list.php';
    }


    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $userId = $_POST['user_id_select'] ?? $_POST['user_id'] ?? null;
        $montant = $_POST['montant'] ?? null;

        if (!$userId || !$montant) {
            $_SESSION['error'] = "User ID and budget amount are required.";
            header("Location: budget-admin.php");
            exit;
        }

        $this->budget->id_utilisateur = $userId;
        $result = $this->budget->setBudget($montant);

        if ($result) {
            $_SESSION['success'] = "Budget saved successfully.";
        } else {
            $_SESSION['error'] = "Failed to save budget.";
        }

        // Check if AJAX request
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            echo $result ? 'success' : 'error';
            exit;
        }

        header("Location: budget-admin.php");
        exit;
    }

 
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $userId = $_POST['user_id'] ?? null;

        if (!$userId) {
            $_SESSION['error'] = "User ID is required.";
            header("Location: budget-admin.php");
            exit;
        }

        $this->budget->id_utilisateur = $userId;
        $result = $this->budget->deleteByUserId();

        if ($result) {
            $_SESSION['success'] = "Budget deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete budget.";
        }

        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            echo $result ? 'success' : 'error';
            exit;
        }

        header("Location: budget-admin.php");
        exit;
    }

  
    public function getBudgetByUser() {
        header('Content-Type: application/json');
        
        $userId = $_GET['user_id'] ?? null;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit;
        }

        $budget = $this->budget->getByUserId($userId);
        $status = $this->budget->getBudgetStatus();
        
        if ($budget) {
            echo json_encode([
                'success' => true,
                'budget' => $budget,
                'status' => $status
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Budget not found'
            ]);
        }
        exit;
    }


    public function handleAction() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = $_POST['action'] ?? null;
        
        switch ($action) {
            case 'create':
            case 'update':
                $this->save();
                break;
            case 'delete':
                $this->delete();
                break;
            default:
                $_SESSION['error'] = "Invalid action.";
                header("Location: budget-admin.php");
                exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename($_SERVER['PHP_SELF']) === 'BudgetController.php') {
    $controller = new BudgetController();
    $action = $_POST['action'] ?? null;
    
    if (in_array($action, ['create', 'update', 'delete'])) {
        $controller->handleAction();
    }
}
?>