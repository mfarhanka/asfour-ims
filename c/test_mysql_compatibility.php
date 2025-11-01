<?php
/* c/test_mysql_compatibility.php - Test MySQL Compatibility */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>MySQL Compatibility Test</h2>";

try {
    require_once __DIR__ . '/../config.php';
    
    echo "✅ Config loaded successfully<br>";
    
    if (isset($conn) && $conn instanceof mysqli) {
        echo "✅ Database connection exists<br>";
        
        // Test 1: Simple query with bind_result
        echo "<h3>Test 1: Client Query with bind_result</h3>";
        
        $testSQL = "SELECT COUNT(*) as client_count FROM clients";
        $testResult = $conn->query($testSQL);
        
        if ($testResult) {
            $row = $testResult->fetch_assoc();
            echo "✅ Query successful using fetch_assoc(): " . $row['client_count'] . " clients found<br>";
        } else {
            echo "❌ Query failed: " . $conn->error . "<br>";
        }
        
        // Test 2: Prepared statement with bind_result
        echo "<h3>Test 2: Prepared Statement with bind_result</h3>";
        
        $prepSQL = "SELECT id, name FROM clients LIMIT 1";
        $stmt = $conn->prepare($prepSQL);
        
        if ($stmt) {
            echo "✅ Prepared statement created<br>";
            $stmt->execute();
            
            // Test bind_result method
            $stmt->bind_result($client_id, $client_name);
            
            if ($stmt->fetch()) {
                echo "✅ bind_result() method works: ID=$client_id, Name=$client_name<br>";
            } else {
                echo "ℹ️ No data found (table might be empty)<br>";
            }
            $stmt->close();
        } else {
            echo "❌ Failed to prepare statement: " . $conn->error . "<br>";
        }
        
        // Test 3: Check if mysqlnd is available
        echo "<h3>Test 3: MySQL Driver Information</h3>";
        
        if (function_exists('mysqli_get_client_info')) {
            echo "MySQL Client Version: " . mysqli_get_client_info() . "<br>";
        }
        
        // Check if get_result is available
        $testStmt = $conn->prepare("SELECT 1");
        if ($testStmt) {
            if (method_exists($testStmt, 'get_result')) {
                echo "✅ get_result() method is available (mysqlnd driver)<br>";
            } else {
                echo "❌ get_result() method not available (using bind_result instead)<br>";
            }
            $testStmt->close();
        }
        
        // Test 4: Investment table structure
        echo "<h3>Test 4: Investment Table Structure</h3>";
        
        $structureSQL = "DESCRIBE client_investments";
        $structureResult = $conn->query($structureSQL);
        
        if ($structureResult) {
            echo "✅ client_investments table structure:<br>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            while ($column = $structureResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ Could not describe client_investments table: " . $conn->error . "<br>";
        }
        
    } else {
        echo "❌ Database connection not available<br>";
    }
    
    echo "<h3>Test Results Summary</h3>";
    echo "<p>If all tests show ✅, the compatibility fixes should work on your server.</p>";
    echo "<p><a href='login.php'>Back to Login</a> | <a href='emergency_dashboard.php'>Emergency Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
} catch (Error $e) {
    echo "❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "<br>";
}
?>