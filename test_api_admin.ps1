# Test API Endpoints as Admin
$baseUrl = "http://localhost:8000"
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

# Variables to store IDs
$firstProductId = $null
$orderId = $null

# Generate a unique email for testing
$testEmail = "admin_$(Get-Random)@example.com"

Write-Host "`nTesting Authentication Endpoints:`n"

# Test Registration as Admin
Write-Host "Testing /api/register as Admin..."
$registerBody = @{
    name = "Test Admin"
    email = $testEmail
    password = "password123"
    password_confirmation = "password123"
    role = "admin"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/register" -Method Post -Body $registerBody -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $content = $response.Content | ConvertFrom-Json
    $token = $content.access_token
    $headers["Authorization"] = "Bearer $token"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
    if ($_.Exception.Response.StatusCode.value__ -eq 422) {
        $errorContent = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host "Validation Errors:"
        $errorContent.errors.PSObject.Properties | ForEach-Object {
            Write-Host "$($_.Name): $($_.Value)"
        }
    }
}

# Test Login as Admin
Write-Host "`nTesting /api/login as Admin..."
$loginBody = @{
    email = $testEmail
    password = "password123"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/login" -Method Post -Body $loginBody -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $content = $response.Content | ConvertFrom-Json
    $token = $content.access_token
    $headers["Authorization"] = "Bearer $token"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

Write-Host "`nTesting Admin Product Endpoints:`n"

# Test Get All Products
Write-Host "Testing /api/admin/products..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/products" -Method Get -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $products = $response.Content | ConvertFrom-Json
    Write-Host "Products found: $($products.Count)"
    if ($products.Count -gt 0) {
        $firstProductId = $products[0].id
        Write-Host "Using product ID: $firstProductId"
    }
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

# Test Create Product
Write-Host "`nTesting /api/admin/products..."
$productBody = @{
    name = "New Test Product"
    description = "A test product created by admin"
    price = 99.99
    stock_quantity = 10
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/products" -Method Post -Body $productBody -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $product = $response.Content | ConvertFrom-Json
    Write-Host "Product created with ID: $($product.id)"
    $firstProductId = $product.id
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

# Test Get Product
Write-Host "`nTesting /api/admin/products/$firstProductId..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/products/$firstProductId" -Method Get -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $product = $response.Content | ConvertFrom-Json
    Write-Host "Product details: $($product | ConvertTo-Json)"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

# Test Update Product
Write-Host "`nTesting /api/admin/products/$firstProductId..."
$updateBody = @{
    name = "Updated Test Product"
    description = "An updated test product"
    price = 149.99
    stock_quantity = 50
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/products/$firstProductId" -Method Put -Body $updateBody -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $product = $response.Content | ConvertFrom-Json
    Write-Host "Updated product details: $($product | ConvertTo-Json)"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

# Test Delete Product
Write-Host "`nTesting /api/admin/products/$firstProductId..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/products/$firstProductId" -Method Delete -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

Write-Host "`nTesting Admin Order Endpoints:`n"

# Test Get All Orders
Write-Host "Testing /api/admin/orders..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/orders" -Method Get -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $orders = $response.Content | ConvertFrom-Json
    Write-Host "Orders found: $($orders.Count)"
    
    # Find a pending or processing order
    $pendingOrder = $orders | Where-Object { $_.status -eq "pending" -or $_.status -eq "processing" } | Select-Object -First 1
    
    if ($pendingOrder) {
        $orderId = $pendingOrder.id
        Write-Host "Found order ID: $orderId with status: $($pendingOrder.status)"
        
        # Determine the next valid status based on current status
        $nextStatus = if ($pendingOrder.status -eq "pending") { "processing" } else { "completed" }
        
        Write-Host "Attempting to update status to: $nextStatus"
        
        # Test Update Order Status
        Write-Host "`nTesting /api/admin/orders/{order}/status..."
        $statusBody = @{
            status = $nextStatus
        } | ConvertTo-Json

        try {
            $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/orders/$orderId/status" -Method Put -Body $statusBody -Headers $headers
            Write-Host "Status: Success ($($response.StatusCode))"
            $updatedOrder = $response.Content | ConvertFrom-Json
            Write-Host "Order Status: $($updatedOrder.status)"
        } catch {
            Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
            Write-Host "Message: $($_.Exception.Message)"
            if ($_.Exception.Response.StatusCode.value__ -eq 422) {
                $errorContent = $_.ErrorDetails.Message | ConvertFrom-Json
                Write-Host "Validation Errors:"
                $errorContent.PSObject.Properties | ForEach-Object {
                    Write-Host "$($_.Name): $($_.Value)"
                }
            }
        }
    } else {
        Write-Host "No orders found with pending or processing status. Cannot test status update."
    }
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

# Test Logout
Write-Host "`nTesting /api/logout..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/logout" -Method Post -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    Write-Host "Message: $($response.Content | ConvertFrom-Json).message"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
} 