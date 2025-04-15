# Test API Endpoints
$baseUrl = "http://localhost:8000"
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

# Variables to store IDs
$firstProductId = $null
$orderId = $null

# Generate a unique email for testing
$testEmail = "test_$(Get-Random)@example.com"

Write-Host "`nTesting Authentication Endpoints:`n"

# Test Registration
Write-Host "Testing /api/register..."
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

# Test Login
Write-Host "`nTesting /api/login..."
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

Write-Host "`nTesting Product Endpoints:`n"

# Test Get All Products
Write-Host "Testing /api/products..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/products" -Method Get -Headers $headers
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

# Test Get Single Product
Write-Host "`nTesting /api/customer/products/{product}..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/customer/products/$firstProductId" -Method Get -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $product = $response.Content | ConvertFrom-Json
    Write-Host "Product: $($product.name) - $($product.price)"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

Write-Host "`nTesting Order Endpoints:`n"

# Test Get Orders
Write-Host "Testing /api/customer/orders..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/customer/orders" -Method Get -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $orders = $response.Content | ConvertFrom-Json
    Write-Host "Orders found: $($orders.Count)"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

# Test Create Order
Write-Host "`nTesting /api/customer/orders..."
$orderBody = @{
    items = @(
        @{
            product_id = $firstProductId
            quantity = 1
        }
    )
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/customer/orders" -Method Post -Body $orderBody -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $order = $response.Content | ConvertFrom-Json
    $orderId = $order.id
    Write-Host "Order created with ID: $orderId"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

# Test Get Single Order
Write-Host "`nTesting /api/customer/orders/{order}..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/customer/orders/$orderId" -Method Get -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $order = $response.Content | ConvertFrom-Json
    Write-Host "Order Status: $($order.status)"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

Write-Host "`nTesting Admin Endpoints:`n"

# Test Get All Orders (Admin)
Write-Host "Testing /api/admin/orders..."
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/orders" -Method Get -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $orders = $response.Content | ConvertFrom-Json
    Write-Host "Orders found: $($orders.Count)"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

# Test Update Order Status (Admin) - Follow valid status transitions
Write-Host "`nTesting /api/admin/orders/{order}/status..."

# First transition: pending -> processing
$statusBody = @{
    status = "processing"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/orders/$orderId/status" -Method Put -Body $statusBody -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $order = $response.Content | ConvertFrom-Json
    Write-Host "Order Status: $($order.status)"
} catch {
    Write-Host "Status: Error ($($_.Exception.Response.StatusCode.value__))"
    Write-Host "Message: $($_.Exception.Message)"
}

# Second transition: processing -> completed
$statusBody = @{
    status = "completed"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/admin/orders/$orderId/status" -Method Put -Body $statusBody -Headers $headers
    Write-Host "Status: Success ($($response.StatusCode))"
    $order = $response.Content | ConvertFrom-Json
    Write-Host "Order Status: $($order.status)"
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