<?php
// Simple database connection test
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'csuite-september';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First, let's check if there are any paid invoices
    $sql = "SELECT COUNT(*) as count FROM invoices WHERE status = 4 AND created_by = 38";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    echo "Paid invoices: " . $result->count . "\n";
    
    // Check for Sent invoices
    $sql = "SELECT COUNT(*) as count FROM invoices WHERE status = 1 AND created_by = 38";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    echo "Sent invoices: " . $result->count . "\n";
    
    // Check created_by values
    $sql = "SELECT created_by, COUNT(*) as count FROM invoices WHERE status = 4 GROUP BY created_by";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    echo "Paid invoices by created_by: \n";
    foreach ($results as $result) {
        echo "created_by " . $result->created_by . ": " . $result->count . " invoices\n";
    }
    echo "\n";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    echo "Paid invoices: " . $result->count . "\n";
    
    // Check if there are any invoice products with tax
    $sql = "SELECT COUNT(*) as count FROM invoice_products WHERE tax IS NOT NULL AND tax != ''";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    echo "Invoice products with tax: " . $result->count . "\n";
    
    // Check the status values in the invoices table
    $sql = "SELECT status, COUNT(*) as count FROM invoices GROUP BY status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    echo "Invoice statuses and counts: \n";
    foreach ($results as $result) {
        echo "Status " . $result->status . ": " . $result->count . " invoices\n";
    }
    echo "\n";
    
    // Test query to see if we can fetch data
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
            LIMIT 10";
    
    // Also test a simple query to see what tax values look like
    $sql2 = "SELECT ip.tax, ps.name FROM invoice_products ip JOIN product_services ps ON ip.product_id = ps.id WHERE ip.tax IS NOT NULL AND ip.tax != '' LIMIT 5";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    $results2 = $stmt2->fetchAll(PDO::FETCH_OBJ);
    echo "Sample tax values: \n";
    foreach ($results2 as $result) {
        echo "Product: " . $result->name . ", Tax: " . $result->tax . "\n";
    }
    echo "\n";
    
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