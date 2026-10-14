<?php


require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}






function ensureMedicineTableExists() {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo === false) return false;

        
        
        $create_table = "
            CREATE TABLE IF NOT EXISTS `medicine` (
                `medicine_id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `stock_quantity` INT NOT NULL DEFAULT 0,
                `description` TEXT NULL,
                `status` VARCHAR(20) GENERATED ALWAYS AS (CASE WHEN `stock_quantity` > 0 THEN 'Available' ELSE 'Out of Stock' END) STORED,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $pdo->exec($create_table);
        return true;

    } catch (Exception $e) {
        error_log("Medicine table error: " . $e->getMessage());
        return false;
    }
}


ensureMedicineTableExists();





function getAllMedicines($include_out_of_stock = true) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo === false) return [];

        $query = "
            SELECT 
                medicine_id,
                name,
                description,
                stock_quantity,
                status,
                updated_at
            FROM medicine
        ";

        if (!$include_out_of_stock) {
            $query .= " WHERE stock_quantity > 0";
        }

        $query .= " ORDER BY name ASC";

        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

    } catch (Exception $e) {
        error_log("Get medicines error: " . $e->getMessage());
        return [];
    }
}





function getMedicineById($medicine_id) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo === false) return null;

        $stmt = $pdo->prepare("
            SELECT * FROM medicine 
            WHERE medicine_id = ?
            LIMIT 1
        ");
        $stmt->execute([$medicine_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Get medicine error: " . $e->getMessage());
        return null;
    }
}







function updateMedicineStock($medicine_id, $new_quantity, $updated_by_worker_id = null) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo === false) {
            return ['success' => false, 'message' => 'Database connection error.'];
        }

        
        if ($new_quantity < 0) {
            return ['success' => false, 'message' => 'Stock quantity cannot be negative.'];
        }

        
        $medicine = getMedicineById($medicine_id);
        if (!$medicine) {
            return ['success' => false, 'message' => 'Medicine not found.'];
        }

        $old_quantity = $medicine['stock_quantity'];

        
        
        $update_stmt = $pdo->prepare("
            UPDATE medicine 
            SET stock_quantity = ?
            WHERE medicine_id = ?
        ");

        $update_stmt->execute([$new_quantity, $medicine_id]);

        
        $log_message = "MEDICINE STOCK UPDATE: {$medicine['name']} | Old: {$old_quantity} | New: {$new_quantity} | Worker: {$updated_by_worker_id}";
        error_log($log_message);

        return [
            'success' => true,
            'message' => "Stock updated: {$medicine['name']}",
            'old_quantity' => $old_quantity,
            'new_quantity' => $new_quantity
        ];

    } catch (Exception $e) {
        error_log("Update medicine stock error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error updating stock.'];
    }
}






function addMedicine($name, $description, $initial_stock = 0) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo === false) {
            return ['success' => false, 'message' => 'Database connection error.'];
        }

        
        if (empty($name) || $initial_stock < 0) {
            return ['success' => false, 'message' => 'Medicine name and valid stock required.'];
        }

        
        $insert_stmt = $pdo->prepare("
            INSERT INTO medicine 
            (name, description, stock_quantity)
            VALUES (?, ?, ?)
        ");

        $insert_stmt->execute([
            htmlspecialchars(trim($name)),
            htmlspecialchars(trim($description ?? '')),
            $initial_stock
        ]);

        $medicine_id = $pdo->lastInsertId();

        error_log("NEW MEDICINE ADDED: ID $medicine_id | Name: $name | Stock: $initial_stock");

        return [
            'success' => true,
            'message' => 'Medicine added successfully!',
            'medicine_id' => $medicine_id
        ];

    } catch (Exception $e) {
        error_log("Add medicine error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error adding medicine.'];
    }
}






function getMedicineStatus($stock_quantity) {
    
    
    return ($stock_quantity > 0) ? 'Available' : 'Out of Stock';
}






function getMedicinesNeedingReorder() {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo === false) return [];

        
        $stmt = $pdo->query("
            SELECT 
                medicine_id,
                name,
                stock_quantity,
                status
            FROM medicine
            WHERE stock_quantity = 0 OR status = 'Out of Stock'
            ORDER BY stock_quantity ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

    } catch (Exception $e) {
        error_log("Get reorder medicines error: " . $e->getMessage());
        return [];
    }
}

?>

