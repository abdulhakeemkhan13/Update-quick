<?php
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Simple test to see what data the query returns
try {
    // Database connection
    $pdo = new PDO('mysql:host=localhost;dbname=csuite-september', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Simple query to test
    $sql = "SELECT 
                ps.name as product_service,
                ps.id as product_service_id,
                SUM(ip.price * ip.quantity) as amount,
                SUM(CASE 
                    WHEN ip.tax IS NOT NULL AND ip.tax != '' THEN 
                        (ip.price * ip.quantity) * (
                            (SELECT COALESCE(SUM(t.rate), 0) FROM taxes t WHERE FIND_IN_SET(t.id, ip.tax)) / 100
                        )
                    ELSE 0 
                END) as tax_amount
            FROM invoices i
            JOIN invoice_products ip ON i.id = ip.invoice_id
            JOIN product_services ps ON ip.product_id = ps.id
            JOIN customers c ON i.customer_id = c.id
            WHERE i.created_by = 38
            AND i.status = 4
            AND ip.tax IS NOT NULL
            AND ip.tax != ''
            GROUP BY ps.id, ps.name
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "Found " . count($results) . " records\n";
    
    foreach ($results as $result) {
        echo "Product: " . $result->product_service . "\n";
        echo "Amount: " . $result->amount . "\n";
        echo "Tax: " . $result->tax_amount . "\n";
        echo "---\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}