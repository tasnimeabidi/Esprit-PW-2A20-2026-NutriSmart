# 🔗 Database Joins & Relationships - NutriSmart Project

## All Tables in Your Database

Your project has **13 tables** with complex relationships.

---

## 📊 Database Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     UTILISATEUR (User)                          │
│  • id_utilisateur (PK)                                          │
│  • nom                                                          │
│  • email                                                        │
│  • mot_de_passe                                                 │
│  • role                                                         │
└─────────────────────────────────────────────────────────────────┘
        │
        ├─── 1:1 ──────────────────────────────────────────────────┐
        │                                                          │
        │   ┌─────────────────────────────────────┐               │
        │   │  BUDGET                             │               │
        │   │  • id_utilisateur (FK, PK)         │               │
        │   │  • montant                          │               │
        │   │  • date_creation                    │               │
        │   └─────────────────────────────────────┘               │
        │                                                          │
        ├─── 1:N ──────────────────────────────────────────────────┐
        │                                                          │
        │   ┌─────────────────────────────────────┐               │
        │   │  PROFIL_NUTRITIONNEL                │               │
        │   │  • id_utilisateur (FK)              │               │
        │   │  • age                              │               │
        │   │  • poids                            │               │
        │   │  • taille                           │               │
        │   │  • objectifs                        │               │
        │   │  • preferences_alimentaires         │               │
        │   └─────────────────────────────────────┘               │
        │                                                          │
        ├─── 1:N ──────────────────────────────────────────────────┐
        │                                                          │
        │   ┌──────────────────────────────────────────────────┐  │
        │   │  USER_ACHAT (Purchase)                           │  │
        │   │  • id (PK)                                       │  │
        │   │  • id_utilisateur (FK) ──┐                       │  │
        │   │  • id_aliment (FK) ──────┼──────────┐            │  │
        │   │  • quantite               │         │            │  │
        │   │  • prix_total             │         │            │  │
        │   │  • date_achat             │         │            │  │
        │   └──────────────────────────────────────────────────┘  │
        │                                                          │
        ├─── 1:N ──────────────────────────────────────────────────┐
        │                                                          │
        │   ┌──────────────────────────────────────────────────┐  │
        │   │  JOURNAL_NUTRITION (Food Log)                    │  │
        │   │  • id (PK)                                       │  │
        │   │  • id_utilisateur (FK)                           │  │
        │   │  • id_aliment (FK) ──────┐                       │  │
        │   │  • date_entree            │                       │  │
        │   │  • calories               │                       │  │
        │   │  • quantite               │                       │  │
        │   └──────────────────────────────────────────────────┘  │
        │                                                          │
        ├─── 1:N ──────────────────────────────────────────────────┐
        │                                                          │
        │   ┌──────────────────────────────────────────────────┐  │
        │   │  JOURNAL_SPORT (Sport Log)                       │  │
        │   │  • id (PK)                                       │  │
        │   │  • id_utilisateur (FK)                           │  │
        │   │  • date_seance                                   │  │
        │   │  • type_sport                                    │  │
        │   │  • duree_min                                     │  │
        │   │  • calories_depensees                            │  │
        │   └──────────────────────────────────────────────────┘  │
        │                                                          │
        ├─── 1:N ──────────────────────────────────────────────────┐
        │                                                          │
        │   ┌──────────────────────────────────────────────────┐  │
        │   │  PLAN_REPAS (Meal Plan)                          │  │
        │   │  • id (PK)                                       │  │
        │   │  • id_utilisateur (FK)                           │  │
        │   │  • date_debut                                    │  │
        │   │  • date_fin                                      │  │
        │   │  • objectif                                      │  │
        │   │  • statut                                        │  │
        │   │                                                  │  │
        │   │  ├─── 1:N ────────────────────────┐             │  │
        │   │  │                                 │             │  │
        │   │  │  ┌──────────────────────────┐  │             │  │
        │   │  │  │  REPAS (Meal)            │  │             │  │
        │   │  │  │  • id (PK)               │  │             │  │
        │   │  │  │  • id_plan (FK)          │  │             │  │
        │   │  │  │  • id_recette (FK) ──┐  │  │             │  │
        │   │  │  │  • type                │  │  │             │  │
        │   │  │  │  • calories            │  │  │             │  │
        │   │  │  └──────────────────────────┘  │             │  │
        │   │  │                                 │             │  │
        │   │  │  ┌──────────────────────────┐  │             │  │
        │   │  │  │  PROGRAMME_SPORTIF       │  │             │  │
        │   │  │  │  • id (PK)               │  │             │  │
        │   │  │  │  • id_plan (FK)          │  │             │  │
        │   │  │  │  • type_sport            │  │             │  │
        │   │  │  │  • niveau                │  │             │  │
        │   │  │  │  • intensite             │  │             │  │
        │   │  │  │                          │  │             │  │
        │   │  │  │  ├─── 1:N ──────┐       │  │             │  │
        │   │  │  │  │               │       │  │             │  │
        │   │  │  │  │  SEANCE_SPORT │       │  │             │  │
        │   │  │  │  │  • id (PK)    │       │  │             │  │
        │   │  │  │  │  • id_programme (FK)  │  │             │  │
        │   │  │  │  │  • date_seance        │  │             │  │
        │   │  │  │  │  • duree_min          │  │             │  │
        │   │  │  │  │  • statut             │  │             │  │
        │   │  │  │  │                       │  │             │  │
        │   │  │  └──────────────────────────┘  │             │  │
        │   │  └────────────────────────────────┘             │  │
        │   └──────────────────────────────────────────────────┘  │
        │                                                          │
        ├─── 1:N ──────────────────────────────────────────────────┐
        │                                                          │
        │   ┌──────────────────────────────────────────────────┐  │
        │   │  PUBLICATION                                    │  │
        │   │  • id (PK)                                       │  │
        │   │  • id_utilisateur (FK)                           │  │
        │   │  • titre                                         │  │
        │   │  • contenu                                       │  │
        │   │  • image                                         │  │
        │   │  • date_publication                              │  │
        │   │                                                  │  │
        │   │  ├─── 1:N ────────────────────────┐             │  │
        │   │  │                                 │             │  │
        │   │  │  ┌──────────────────────────┐  │             │  │
        │   │  │  │  COMMENTAIRE             │  │             │  │
        │   │  │  │  • id (PK)               │  │             │  │
        │   │  │  │  • id_publication (FK)   │  │             │  │
        │   │  │  │  • id_utilisateur (FK)   │  │             │  │
        │   │  │  │  • contenu               │  │             │  │
        │   │  │  │  • date_commentaire      │  │             │  │
        │   │  │  └──────────────────────────┘  │             │  │
        │   │  └────────────────────────────────┘             │  │
        │   └──────────────────────────────────────────────────┘  │
        │                                                          │
        └──────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                       ALIMENT (Food)                            │
│  • id (PK)                                                      │
│  • nom                                                          │
│  • categorie                                                    │
│  • calories_100g                                                │
│  • proteines_100g                                               │
│  • glucides_100g                                                │
│  • lipides_100g                                                 │
│  • prix                                                         │
└─────────────────────────────────────────────────────────────────┘
        │
        ├─── N:M ──────────────────────────────────────────────────┐
        │                                                          │
        │   ┌──────────────────────────────────────────────────┐  │
        │   │  RECETTE (Recipe)                                │  │
        │   │  • id (PK)                                       │  │
        │   │  • nom                                           │  │
        │   │  • instructions                                  │  │
        │   │  • calories_totales                              │  │
        │   │                                                  │  │
        │   │  ├─── N:M ────────────────────────┐             │  │
        │   │  │                                 │             │  │
        │   │  │  RECETTE_ALIMENT (Recipe Items) │             │  │
        │   │  │  • id (PK)                      │             │  │
        │   │  │  • id_recette (FK) ─────────────┤─ To RECETTE │  │
        │   │  │  • id_aliment (FK) ─────────────┤─ To ALIMENT │  │
        │   │  │  • quantite_g                   │             │  │
        │   │  │                                 │             │  │
        │   │  └────────────────────────────────┘             │  │
        │   └──────────────────────────────────────────────────┘  │
        │                                                          │
        └──────────────────────────────────────────────────────────┘
```

---

## 🔑 Foreign Key Relationships (Defined in Database)

### 1. **BUDGET → UTILISATEUR** (1:1)
```sql
ALTER TABLE budget
  ADD CONSTRAINT budget_ibfk_1 
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) 
  ON DELETE CASCADE;
```
**Usage:** Each user has one budget  
**Cascade:** Delete user = Delete budget

---

### 2. **USER_ACHAT → UTILISATEUR** (1:N)
```sql
ALTER TABLE user_achat
  ADD CONSTRAINT user_achat_ibfk_1 
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) 
  ON DELETE CASCADE;
```
**Usage:** One user can have many purchases  
**Cascade:** Delete user = Delete all user's purchases

---

### 3. **USER_ACHAT → ALIMENT** (N:1)
```sql
ALTER TABLE user_achat
  ADD CONSTRAINT user_achat_ibfk_2 
  FOREIGN KEY (id_aliment) REFERENCES aliment (id) 
  ON DELETE CASCADE;
```
**Usage:** Many purchases can reference one food item  
**Cascade:** Delete food = Delete purchases of that food

---

## 🔗 Logical Relationships (Should Have Foreign Keys)

These relationships exist in your data but aren't enforced by the database:

### **PROFIL_NUTRITIONNEL → UTILISATEUR** (1:N)
- **Current:** Not enforced
- **Should be:** 
```sql
ALTER TABLE profil_nutritionnel
  ADD CONSTRAINT profil_nutritionnel_ibfk_1 
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) 
  ON DELETE CASCADE;
```

### **JOURNAL_NUTRITION → UTILISATEUR** (1:N)
```sql
ALTER TABLE journal_nutrition
  ADD CONSTRAINT journal_nutrition_ibfk_1 
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) 
  ON DELETE CASCADE;
```

### **JOURNAL_NUTRITION → ALIMENT** (N:1)
```sql
ALTER TABLE journal_nutrition
  ADD CONSTRAINT journal_nutrition_ibfk_2 
  FOREIGN KEY (id_aliment) REFERENCES aliment (id) 
  ON DELETE CASCADE;
```

### **JOURNAL_SPORT → UTILISATEUR** (1:N)
```sql
ALTER TABLE journal_sport
  ADD CONSTRAINT journal_sport_ibfk_1 
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) 
  ON DELETE CASCADE;
```

### **PLAN_REPAS → UTILISATEUR** (1:N)
```sql
ALTER TABLE plan_repas
  ADD CONSTRAINT plan_repas_ibfk_1 
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) 
  ON DELETE CASCADE;
```

### **REPAS → PLAN_REPAS** (N:1)
```sql
ALTER TABLE repas
  ADD CONSTRAINT repas_ibfk_1 
  FOREIGN KEY (id_plan) REFERENCES plan_repas (id) 
  ON DELETE CASCADE;
```

### **REPAS → RECETTE** (N:1)
```sql
ALTER TABLE repas
  ADD CONSTRAINT repas_ibfk_2 
  FOREIGN KEY (id_recette) REFERENCES recette (id) 
  ON DELETE SET NULL;
```

### **PROGRAMME_SPORTIF → PLAN_REPAS** (N:1)
```sql
ALTER TABLE programme_sportif
  ADD CONSTRAINT programme_sportif_ibfk_1 
  FOREIGN KEY (id_plan) REFERENCES plan_repas (id) 
  ON DELETE CASCADE;
```

### **SEANCE_SPORT → PROGRAMME_SPORTIF** (N:1)
```sql
ALTER TABLE seance_sport
  ADD CONSTRAINT seance_sport_ibfk_1 
  FOREIGN KEY (id_programme) REFERENCES programme_sportif (id) 
  ON DELETE CASCADE;
```

### **RECETTE_ALIMENT → RECETTE** (N:1)
```sql
ALTER TABLE recette_aliment
  ADD CONSTRAINT recette_aliment_ibfk_1 
  FOREIGN KEY (id_recette) REFERENCES recette (id) 
  ON DELETE CASCADE;
```

### **RECETTE_ALIMENT → ALIMENT** (N:1)
```sql
ALTER TABLE recette_aliment
  ADD CONSTRAINT recette_aliment_ibfk_2 
  FOREIGN KEY (id_aliment) REFERENCES aliment (id) 
  ON DELETE CASCADE;
```

### **PUBLICATION → UTILISATEUR** (N:1)
```sql
ALTER TABLE publication
  ADD CONSTRAINT publication_ibfk_1 
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) 
  ON DELETE CASCADE;
```

### **COMMENTAIRE → PUBLICATION** (N:1)
```sql
ALTER TABLE commentaire
  ADD CONSTRAINT commentaire_ibfk_1 
  FOREIGN KEY (id_publication) REFERENCES publication (id) 
  ON DELETE CASCADE;
```

### **COMMENTAIRE → UTILISATEUR** (N:1)
```sql
ALTER TABLE commentaire
  ADD CONSTRAINT commentaire_ibfk_2 
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) 
  ON DELETE CASCADE;
```

---

## 📝 SQL JOIN Queries for Common Tasks

### 1. Get All User Purchases with Food Details
```sql
SELECT 
  ua.id,
  ua.date_achat,
  ua.quantite,
  ua.prix_total,
  a.nom AS aliment_nom,
  a.categorie,
  a.prix
FROM user_achat ua
JOIN aliment a ON ua.id_aliment = a.id
WHERE ua.id_utilisateur = ?
ORDER BY ua.date_achat DESC;
```

### 2. Get User Budget and Spending
```sql
SELECT 
  u.id_utilisateur,
  u.nom,
  b.montant AS budget_total,
  COALESCE(SUM(ua.prix_total), 0) AS total_spent,
  (b.montant - COALESCE(SUM(ua.prix_total), 0)) AS remaining
FROM utilisateur u
LEFT JOIN budget b ON u.id_utilisateur = b.id_utilisateur
LEFT JOIN user_achat ua ON u.id_utilisateur = ua.id_utilisateur
WHERE u.id_utilisateur = ?
GROUP BY u.id_utilisateur, b.montant;
```

### 3. Get User's Nutrition Log with Food Details
```sql
SELECT 
  jn.date_entree,
  a.nom AS aliment_nom,
  a.categorie,
  jn.quantite,
  a.calories_100g * (jn.quantite / 100) AS calories_consumed,
  a.proteines_100g * (jn.quantite / 100) AS proteines,
  a.glucides_100g * (jn.quantite / 100) AS glucides,
  a.lipides_100g * (jn.quantite / 100) AS lipides
FROM journal_nutrition jn
JOIN aliment a ON jn.id_aliment = a.id
WHERE jn.id_utilisateur = ? 
  AND jn.date_entree BETWEEN ? AND ?
ORDER BY jn.date_entree DESC;
```

### 4. Get User's Meal Plan with Meals and Recipes
```sql
SELECT 
  pr.id AS plan_id,
  pr.objectif,
  pr.statut,
  r.id AS meal_id,
  r.type,
  rec.nom AS recipe_nom,
  rec.calories_totales,
  GROUP_CONCAT(a.nom SEPARATOR ', ') AS ingredients
FROM plan_repas pr
LEFT JOIN repas r ON pr.id = r.id_plan
LEFT JOIN recette rec ON r.id_recette = rec.id
LEFT JOIN recette_aliment ra ON rec.id = ra.id_recette
LEFT JOIN aliment a ON ra.id_aliment = a.id
WHERE pr.id_utilisateur = ?
GROUP BY r.id;
```

### 5. Get Recipe with All Ingredients
```sql
SELECT 
  rec.id,
  rec.nom,
  rec.instructions,
  rec.calories_totales,
  a.id AS aliment_id,
  a.nom AS aliment_nom,
  ra.quantite_g,
  a.calories_100g * (ra.quantite_g / 100) AS aliment_calories,
  a.proteines_100g * (ra.quantite_g / 100) AS proteines,
  a.glucides_100g * (ra.quantite_g / 100) AS glucides,
  a.lipides_100g * (ra.quantite_g / 100) AS lipides
FROM recette rec
LEFT JOIN recette_aliment ra ON rec.id = ra.id_recette
LEFT JOIN aliment a ON ra.id_aliment = a.id
WHERE rec.id = ?
ORDER BY a.nom;
```

### 6. Get User's Sports Summary
```sql
SELECT 
  js.date_seance,
  js.type_sport,
  js.duree_min,
  js.calories_depensees,
  jn.calories AS calories_consumed,
  (COALESCE(jn.calories, 0) - COALESCE(js.calories_depensees, 0)) AS net_calories
FROM journal_sport js
LEFT JOIN journal_nutrition jn ON 
  js.id_utilisateur = jn.id_utilisateur 
  AND js.date_seance = jn.date_entree
WHERE js.id_utilisateur = ?
ORDER BY js.date_seance DESC;
```

### 7. Get User's Publications with Comments
```sql
SELECT 
  p.id AS publication_id,
  p.titre,
  p.contenu,
  p.date_publication,
  COUNT(c.id) AS comment_count,
  GROUP_CONCAT(
    CONCAT(cu.nom, ': ', c.contenu) 
    SEPARATOR ' | '
  ) AS comments
FROM publication p
LEFT JOIN commentaire c ON p.id = c.id_publication
LEFT JOIN utilisateur cu ON c.id_utilisateur = cu.id_utilisateur
WHERE p.id_utilisateur = ?
GROUP BY p.id
ORDER BY p.date_publication DESC;
```

### 8. Get All Users' Budget Status
```sql
SELECT 
  u.id_utilisateur,
  u.nom,
  u.role,
  b.montant AS budget_limit,
  COALESCE(SUM(ua.prix_total), 0) AS total_spent,
  (b.montant - COALESCE(SUM(ua.prix_total), 0)) AS remaining_budget,
  ROUND(
    (COALESCE(SUM(ua.prix_total), 0) / b.montant) * 100, 
    2
  ) AS percent_used
FROM utilisateur u
LEFT JOIN budget b ON u.id_utilisateur = b.id_utilisateur
LEFT JOIN user_achat ua ON u.id_utilisateur = ua.id_utilisateur
GROUP BY u.id_utilisateur
ORDER BY percent_used DESC;
```

---

## 📍 Where Joins Are Used in Your Code

### In Controllers:
```php
// Controllers/TransactionController.php
// Get user data with budget and purchases
$stmt = $pdo->query("
  SELECT u.*, b.montant, 
         SUM(ua.prix_total) as total_spent
  FROM utilisateur u
  LEFT JOIN budget b ON u.id = b.id_utilisateur
  LEFT JOIN user_achat ua ON u.id = ua.id_utilisateur
");
```

### In Services:
```php
// Services/BudgetService.php
// Get budget with user info
$stmt = $pdo->query("
  SELECT b.*, u.nom, u.email
  FROM budget b
  JOIN utilisateur u ON b.id_utilisateur = u.id_utilisateur
");
```

### In Models:
```php
// Models/User.php
// Get user's purchases
$stmt = $pdo->query("
  SELECT ua.*, a.nom, a.prix
  FROM user_achat ua
  JOIN aliment a ON ua.id_aliment = a.id
  WHERE ua.id_utilisateur = ?
");
```

---

## 🔧 How to Implement Joins in Your Code

### Example 1: Get User with Budget
```php
<?php
class UserService {
    public function getUserWithBudget($userId) {
        $query = "
            SELECT 
                u.id_utilisateur,
                u.nom,
                u.email,
                b.montant as budget_limit,
                COALESCE(SUM(ua.prix_total), 0) as total_spent
            FROM utilisateur u
            LEFT JOIN budget b ON u.id_utilisateur = b.id_utilisateur
            LEFT JOIN user_achat ua ON u.id_utilisateur = ua.id_utilisateur
            WHERE u.id_utilisateur = ?
            GROUP BY u.id_utilisateur
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
```

### Example 2: Get Meals with Recipes and Ingredients
```php
<?php
class MealPlanService {
    public function getMealPlanDetails($planId) {
        $query = "
            SELECT 
                pr.id as plan_id,
                pr.objectif,
                r.id as meal_id,
                r.type,
                rec.nom as recipe_name,
                a.nom as ingredient_name,
                ra.quantite_g,
                a.calories_100g
            FROM plan_repas pr
            LEFT JOIN repas r ON pr.id = r.id_plan
            LEFT JOIN recette rec ON r.id_recette = rec.id
            LEFT JOIN recette_aliment ra ON rec.id = ra.id_recette
            LEFT JOIN aliment a ON ra.id_aliment = a.id
            WHERE pr.id = ?
            ORDER BY r.type, a.nom
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$planId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
```

---

## 📋 Summary: All Joins in Your Database

| Table 1 | Relationship | Table 2 | Enforced? |
|---------|-------------|---------|-----------|
| **utilisateur** | 1:1 | budget | ✅ YES |
| **utilisateur** | 1:N | user_achat | ✅ YES |
| **aliment** | 1:N | user_achat | ✅ YES |
| **utilisateur** | 1:N | profil_nutritionnel | ❌ NO |
| **utilisateur** | 1:N | journal_nutrition | ❌ NO |
| **aliment** | 1:N | journal_nutrition | ❌ NO |
| **utilisateur** | 1:N | journal_sport | ❌ NO |
| **utilisateur** | 1:N | plan_repas | ❌ NO |
| **plan_repas** | 1:N | repas | ❌ NO |
| **recette** | 1:N | repas | ❌ NO |
| **plan_repas** | 1:N | programme_sportif | ❌ NO |
| **programme_sportif** | 1:N | seance_sport | ❌ NO |
| **recette** | N:M | aliment | ❌ NO (via recette_aliment) |
| **utilisateur** | 1:N | publication | ❌ NO |
| **publication** | 1:N | commentaire | ❌ NO |
| **utilisateur** | 1:N | commentaire | ❌ NO |

---

## ✅ Recommendation

You should add foreign key constraints for all unenforced relationships. This ensures data integrity and prevents orphaned records.
