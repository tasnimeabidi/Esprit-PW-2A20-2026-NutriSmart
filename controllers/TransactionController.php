<?php
require_once __DIR__ . '/../Services/BudgetService.php';
require_once __DIR__ . '/../Services/AchatService.php';

class TransactionController {
    private $budgetService;
    private $achatService;

    public function __construct() {
        $this->budgetService = new BudgetService();
        $this->achatService = new AchatService();
    }

    // Example action: Show user dashboard
public function showDashboard($userId) {
    $budget = $this->budgetService->getBudgetInfo($userId);
    $history = $this->achatService->getUserHistory($userId);
    
    // FIX: If no budget exists in DB, create a default array to prevent errors
    if (!$budget) {
        $budget = [
            'montant' => 0, 
            'total_depense' => 0,
            'date_creation' => 'Not set'
        ];
    }

    // Now this won't crash because $budget is guaranteed to be an array
    $solde = $budget['montant'] - ($budget['total_depense'] ?? 0);

    return [
        'budget' => $budget,
        'history' => $history,
        'solde' => $solde
    ];
}

    // Example action: Record a new purchase
    public function handleNewPurchase($data) {
        // You would usually validate $data here
        return $this->achatService->addPurchase(
            $data['user_id'], 
            $data['aliment_id'], 
            $data['qty'], 
            $data['price']
        );
    }
}