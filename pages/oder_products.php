<?php
$page_title = 'Order Product';
require_once '../config/header.php';
require_once '../config/database.php';
require_once '../config/api_config.php';

// Get product ID from URL
$productId = isset($_GET['product']) ? intval($_GET['product']) : 0;
// Function to auto-translate Vietnamese to English using Google Translate API
function translateToEnglish($text) {
    if (empty($text)) return $text;
    
    // Check if text is already in English (contains mostly ASCII)
    if (preg_match('/^[a-zA-Z0-9\s\(\)\-\.,!?]+$/u', $text)) {
        return $text;
    }
    
    // Use Google Translate API (free, no API key needed)
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=vi&tl=en&dt=t&q=" . urlencode($text);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $result = json_decode($response, true);
        
        // Extract translated text from response
        if (isset($result[0]) && is_array($result[0])) {
            $translatedText = '';
            foreach ($result[0] as $segment) {
                if (isset($segment[0])) {
                    $translatedText .= $segment[0];
                }
            }
            return !empty($translatedText) ? $translatedText : $text;
        }
    }
    
    // Fallback: return original text if translation fails
    return $text;
}


// Function to get product details from API
function getProductDetails($productId) {
    global $lang;
    
    $postData = [
        'key' => API_KEY,
        'action' => 'products'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $products = json_decode($response, true);
        if (is_array($products)) {
            foreach ($products as $product) {
                if ($product['product'] == $productId) {
                    // Translate to English if language is English
                    if ($lang === 'en') {
                        // Translate product name
                        if (isset($product['name'])) {
                            $product['name'] = translateToEnglish($product['name']);
                        }
                        
                        // Translate params descriptions
                        if (isset($product['params']) && is_array($product['params'])) {
                            foreach ($product['params'] as &$param) {
                                if (isset($param['description'])) {
                                    $param['description'] = translateToEnglish($param['description']);
                                }
                            }
                        }
                    }
                    
                    return $product;
                }
            }
        }
    }
    return null;
}
// Function to upload image and get URL
function uploadImageToServer($filePath) {
    // Upload to your server and return public URL
    $uploadDir = '../uploads/temp/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $newFileName = uniqid() . '_' . time() . '.' . $ext;
    $uploadPath = $uploadDir . $newFileName;
    
    if (copy($filePath, $uploadPath)) {
        // Return public URL - adjust this to your domain
        return 'https://kingcongstudio.com/uploads/temp/' . $newFileName;
    }
    return null;
}

// Function to place product order with correct API format
function placeProductOrder($product, $quantity, $requireText = '', $imageFiles = null, $dimension = null, $reqs = []) {
    $boundary = '----WebKitFormBoundary' . uniqid();
    $eol = "\r\n";
    
    $data = '';
    
    // Add basic fields
    $data .= '--' . $boundary . $eol;
    $data .= 'Content-Disposition: form-data; name="key"' . $eol . $eol;
    $data .= API_KEY . $eol;
    
    $data .= '--' . $boundary . $eol;
    $data .= 'Content-Disposition: form-data; name="action"' . $eol . $eol;
    $data .= 'add_product_order' . $eol;
    
    $data .= '--' . $boundary . $eol;
    $data .= 'Content-Disposition: form-data; name="product"' . $eol . $eol;
    $data .= $product['product'] . $eol;
    
    $data .= '--' . $boundary . $eol;
    $data .= 'Content-Disposition: form-data; name="quantity"' . $eol . $eol;
    $data .= $quantity . $eol;
    
    // Add dimension for image products
    if ($dimension && in_array($product['type'], ['AI_IMAGE_OLD', 'AI_IMAGE_DEFAULT'])) {
        $data .= '--' . $boundary . $eol;
        $data .= 'Content-Disposition: form-data; name="dimension"' . $eol . $eol;
        $data .= $dimension . $eol;
    }
    
    // Determine how to handle images based on product type and structure
    $isVideoProduct = in_array($product['type'], ['AI_VIDEO_OLD', 'AI_VIDEO_DEFAULT']);
    $isImageProduct = in_array($product['type'], ['AI_IMAGE_OLD', 'AI_IMAGE_DEFAULT']);
    
    // Check params for structure
    $imageStructure = null;
    if (isset($product['params'])) {
        foreach ($product['params'] as $param) {
            if ($param['name'] === 'images' && isset($param['structure'])) {
                $imageStructure = $param['structure'];
                break;
            }
        }
    }
    
    // Handle images based on structure
    if (!empty($imageFiles) && is_array($imageFiles['tmp_name'])) {
        $uploadedUrls = [];
        
        // Upload images to get URLs
        foreach ($imageFiles['tmp_name'] as $index => $tmpName) {
            if (is_uploaded_file($tmpName)) {
                $url = uploadImageToServer($tmpName);
                if ($url) {
                    $uploadedUrls[] = $url;
                }
            }
        }
        
        if (!empty($uploadedUrls)) {
            // VIDEO PRODUCTS with structure 999: [{"type":"images","url":"..."}]
            if ($isVideoProduct && $imageStructure == 999) {
                $imagesArray = [];
                foreach ($uploadedUrls as $url) {
                    $imagesArray[] = ["type" => "images", "url" => $url];
                }
                
                $data .= '--' . $boundary . $eol;
                $data .= 'Content-Disposition: form-data; name="images"' . $eol . $eol;
                $data .= json_encode($imagesArray) . $eol;
            }
            // IMAGE PRODUCTS with structure 0 or 2: Send as object or array
            elseif ($isImageProduct) {
                // For structure 2 (reference image) or structure 0 (product image)
                // Send as simple object or first URL
                $data .= '--' . $boundary . $eol;
                $data .= 'Content-Disposition: form-data; name="images"' . $eol . $eol;
                $data .= json_encode(["url" => $uploadedUrls[0]]) . $eol;
            }
            // Default: send as multipart files
            else {
                foreach ($imageFiles['tmp_name'] as $index => $tmpName) {
                    if (is_uploaded_file($tmpName)) {
                        $fileName = $imageFiles['name'][$index];
                        $fileContent = file_get_contents($tmpName);
                        
                        // [FIX] Use getimagesize to get MIME type. Fallback to generic type if it fails.
                        $imageInfo = @getimagesize($tmpName);
                        $mimeType = ($imageInfo !== false && isset($imageInfo['mime'])) ? $imageInfo['mime'] : 'application/octet-stream';
                        
                        $data .= '--' . $boundary . $eol;
                        $data .= 'Content-Disposition: form-data; name="require[]"; filename="' . $fileName . '"' . $eol;
                        $data .= 'Content-Type: ' . $mimeType . $eol . $eol;
                        $data .= $fileContent . $eol;
                    }
                }
            }
        }
    }
    
    // [FIXED] Add require text (prompt or email) with correct parameter name
    if (!empty($requireText)) {
        // The API error indicates that for prompts, the parameter must be named 'prompt'.
        // We check if the product requirements specify a prompt.
        $fieldName = (isset($reqs['needsPrompt']) && $reqs['needsPrompt']) ? 'prompt' : 'require';
        
        $data .= '--' . $boundary . $eol;
        $data .= 'Content-Disposition: form-data; name="' . $fieldName . '"' . $eol . $eol;
        $data .= $requireText . $eol;
    }
    
    $data .= '--' . $boundary . '--' . $eol;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'Content-Length: ' . strlen($data)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $response) {
        return json_decode($response, true);
    }
    return false;
}

// Determine product requirements based on params
function getProductRequirements($product) {
    $requirements = [
        'needsEmail' => false,
        'needsPrompt' => false,
        'needsImage' => false,
        'imageOptional' => false,
        'needsDimension' => false,
        'dimensionOptions' => [],
        'needsStyle' => false,
        'styleOptions' => [],
        'promptMinLength' => 10,
        'promptMaxLength' => 5000
    ];
    
    if (!isset($product['params']) || !is_array($product['params'])) {
        return $requirements;
    }
    
    foreach ($product['params'] as $param) {
        $paramName = $param['name'] ?? '';
        $isRequired = $param['require'] ?? false;
        
        switch ($paramName) {
            case 'require_text':
                // Check description to determine if email or prompt
                $desc = strtolower($param['description'] ?? '');
                if (strpos($desc, 'email') !== false || strpos($desc, 'gmail') !== false) {
                    $requirements['needsEmail'] = true;
                } else {
                    $requirements['needsPrompt'] = true;
                    $requirements['promptMinLength'] = $param['min_length'] ?? 10;
                    $requirements['promptMaxLength'] = $param['max_length'] ?? 5000;
                }
                break;
                
            case 'prompt':
                $requirements['needsPrompt'] = true;
                $requirements['promptMinLength'] = $param['min_length'] ?? 10;
                $requirements['promptMaxLength'] = $param['max_length'] ?? 5000;
                break;
                
            case 'images':
                $requirements['needsImage'] = true;
                $requirements['imageOptional'] = !$isRequired;
                break;
                
            case 'dimension':
                $requirements['needsDimension'] = true;
                if (isset($param['options'])) {
                    $requirements['dimensionOptions'] = is_array($param['options']) 
                        ? $param['options'] 
                        : [];
                }
                break;
                
            case 'style':
                $requirements['needsStyle'] = true;
                if (isset($param['options']) && is_array($param['options'])) {
                    $requirements['styleOptions'] = $param['options'];
                }
                break;
        }
    }
    
    return $requirements;
}

// Handle form submission
$orderResult = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $productId = intval($_POST['product']);
    $quantity = intval($_POST['quantity']);
    $requireText = isset($_POST['require_text']) ? trim($_POST['require_text']) : '';
    $dimension = isset($_POST['dimension']) ? trim($_POST['dimension']) : null;
    $style = isset($_POST['style']) ? trim($_POST['style']) : null;
    
    // Get product details
    $product = getProductDetails($productId);
    
    if (!$product) {
        $error = $lang === 'vi' ? 'Không tìm thấy sản phẩm' : 'Product not found';
    } elseif ($quantity < $product['min'] || $quantity > $product['max']) {
        $error = $lang === 'vi' 
            ? "Số lượng phải từ {$product['min']} đến {$product['max']}" 
            : "Quantity must be between {$product['min']} and {$product['max']}";
    } else {
        $reqs = getProductRequirements($product);
        
        // Validate requirements
        if ($reqs['needsEmail'] && empty($requireText)) {
            $error = $lang === 'vi' ? 'Vui lòng nhập email!' : 'Please enter email!';
        } elseif ($reqs['needsPrompt'] && empty($requireText)) {
            $error = $lang === 'vi' ? 'Vui lòng nhập prompt/mô tả!' : 'Please enter prompt/description!';
        } elseif ($reqs['needsPrompt'] && strlen($requireText) < $reqs['promptMinLength']) {
            $error = $lang === 'vi' 
                ? "Prompt phải có ít nhất {$reqs['promptMinLength']} ký tự" 
                : "Prompt must be at least {$reqs['promptMinLength']} characters";
        } elseif ($reqs['needsImage'] && !$reqs['imageOptional'] && empty($_FILES['images']['tmp_name'][0])) {
            $error = $lang === 'vi' ? 'Vui lòng upload ít nhất 1 ảnh!' : 'Please upload at least 1 image!';
        } elseif ($reqs['needsDimension'] && empty($dimension)) {
            $error = $lang === 'vi' ? 'Vui lòng chọn kích thước!' : 'Please select dimension!';
        } elseif ($reqs['needsStyle'] && empty($style)) {
            $error = $lang === 'vi' ? 'Vui lòng chọn phong cách!' : 'Please select style!';
        } else {
            // Validate image files if uploaded
            if (!empty($_FILES['images']['tmp_name'][0])) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxFileSize = 5 * 1024 * 1024; // 5MB
                
                foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                    if (is_uploaded_file($tmpName)) {
                        $fileSize = $_FILES['images']['size'][$index];
                        
                        // [FIX] Use getimagesize to safely get MIME type, avoiding fileinfo dependency.
                        $imageInfo = @getimagesize($tmpName);
                        if ($imageInfo === false) {
                            $error = $lang === 'vi' ? "File không hợp lệ hoặc không phải là ảnh: " . htmlspecialchars($_FILES['images']['name'][$index]) : "Invalid file or not an image: " . htmlspecialchars($_FILES['images']['name'][$index]);
                            break;
                        }
                        $fileType = $imageInfo['mime'];
                        
                        if (!in_array($fileType, $allowedTypes)) {
                            $error = $lang === 'vi' 
                                ? 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)' 
                                : 'Only image files accepted (JPG, PNG, GIF, WEBP)';
                            break;
                        }
                        
                        if ($fileSize > $maxFileSize) {
                            $error = $lang === 'vi' 
                                ? 'Kích thước file không được vượt quá 5MB' 
                                : 'File size must not exceed 5MB';
                            break;
                        }
                    }
                }
            }
            
            if (!$error) {
                // Calculate price with 20% profit margin
                $rateUSD_original = floatval($product['rate']);
                $rateUSD = $rateUSD_original * 1.2;
                $priceUSD = $rateUSD * $quantity;

                // Check balance
                if ($user['sodu'] < $priceUSD) {
                    $error = $lang === 'vi' 
                        ? "Số dư không đủ! Cần: $" . number_format($priceUSD, 2) . ". Số dư hiện tại: $" . number_format($user['sodu'], 2) 
                        : "Insufficient balance! Required: $" . number_format($priceUSD, 2) . ". Current: $" . number_format($user['sodu'], 2);
                } else {
                    // Place order with correct format
                    $hasImages = !empty($_FILES['images']['tmp_name'][0]);
                    
                    // Build require text (include style if needed)
                    $finalRequireText = $requireText;
                    if ($reqs['needsStyle'] && !empty($style)) {
                        $finalRequireText .= "\nStyle: " . $style;
                    }
                    
                    $result = placeProductOrder(
                        $product,
                        $quantity, 
                        $finalRequireText,
                        $hasImages ? $_FILES['images'] : null,
                        $dimension,
                        $reqs // Pass requirements to function
                    );
                    
                    if ($result && isset($result['order'])) {
                        $orderResult = $result;
                        
                        // Save to database
                        $mysqli->begin_transaction();
                        
                        try {
                            $imagePaths = [];
                            
                            // Save images locally if uploaded
                            if ($hasImages) {
                                $uploadDir = '../uploads/product_orders/';
                                if (!file_exists($uploadDir)) {
                                    mkdir($uploadDir, 0755, true);
                                }
                                
                                foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                                    if (is_uploaded_file($tmpName)) {
                                        $ext = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);
                                        $newFileName = $result['order'] . '_' . $index . '_' . time() . '.' . $ext;
                                        $uploadPath = $uploadDir . $newFileName;
                                        
                                        if (copy($tmpName, $uploadPath)) {
                                            $imagePaths[] = $newFileName;
                                        }
                                    }
                                }
                            }
                            
                            // Determine require type
                            $requireType = 'text';
                            if ($reqs['needsEmail']) {
                                $requireType = 'email';
                            } elseif ($reqs['needsPrompt'] && !empty($imagePaths)) {
                                $requireType = 'mixed';
                            } elseif ($reqs['needsPrompt']) {
                                $requireType = 'prompt';
                            } elseif ($reqs['needsImage']) {
                                $requireType = 'image';
                            }
                            
                            $requireImagesJson = !empty($imagePaths) ? json_encode($imagePaths) : NULL;
                            
                            // Insert into product_orders table
                            $stmt = $mysqli->prepare("
                                INSERT INTO product_orders 
                                (order_id, user_id, product_id, product_name, category, quantity, price, 
                                 require_type, require_text, require_images, dimension, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
                            ");
                            
                            $category = $product['category'] ?? 'N/A';
                            $requireTextVal = !empty($finalRequireText) ? $finalRequireText : NULL;
                            
                            $stmt->bind_param(
                                "siissidssss",
                                $result['order'],
                                $user['id'],
                                $productId,
                                $product['name'],
                                $category,
                                $quantity,
                                $priceUSD,
                                $requireType,
                                $requireTextVal,
                                $requireImagesJson,
                                $dimension
                            );
                            $stmt->execute();
                            
                            // Update user balance
                            $updateBalance = $mysqli->prepare("UPDATE Users SET sodu = sodu - ? WHERE id = ?");
                            $updateBalance->bind_param("di", $priceUSD, $user['id']);
                            $updateBalance->execute();
                            
                            // Insert transaction
                            $transContent = ($lang === 'vi' ? 'Đặt sản phẩm: ' : 'Order product: ') 
                                . $product['name'] 
                                . ' - Order ID: ' . $result['order'];
                            
                            $insertTrans = $mysqli->prepare("
                                INSERT INTO Transactions (user_id, amount, type, content) 
                                VALUES (?, ?, 'product_order', ?)
                            ");
                            $insertTrans->bind_param("ids", $user['id'], $priceUSD, $transContent);
                            $insertTrans->execute();
                            
                            $mysqli->commit();
                            $user['sodu'] -= $priceUSD;
                            
                        } catch (Exception $e) {
                            $mysqli->rollback();
                            $error = $lang === 'vi' 
                                ? 'Lỗi lưu đơn hàng: ' . $e->getMessage() 
                                : 'Error saving order: ' . $e->getMessage();
                            $orderResult = null;
                        }
                        
                    } elseif ($result && isset($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $error = $lang === 'vi' ? 'Có lỗi xảy ra khi đặt hàng' : 'Error occurred while placing order';
                    }
                }
            }
        }
    }
}



// Get product details
$product = null;
$reqs = null;
if ($productId > 0) {
    $product = getProductDetails($productId);
    if ($product) {
        $product['rate_usd_original'] = floatval($product['rate']);
        $product['rate_usd'] = floatval($product['rate']) * 1.2;
        $reqs = getProductRequirements($product);
    }
}

require_once '../config/sidebar.php';
?>

<!-- UI and Javascript code remains the same as the previous version -->
<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 25px;
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .page-header h1 {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: white !important;
    }

    .page-header p {
        font-size: 14px;
        opacity: 0.9;
        margin: 0;
    }

    .order-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .product-info-card {
        background-color: var(--content-bg);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .product-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--border-color);
    }

    .product-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        color: white;
        font-size: 24px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .info-item {
        background-color: var(--input-bg);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .info-label {
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 5px;
        display: block;
    }

    .info-value {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-color);
    }

    .balance-warning {
        background: linear-gradient(135deg, #e8f4fd, #f0f9ff);
        border-left: 4px solid #3b82f6;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    html.dark .balance-warning {
        background: linear-gradient(135deg, #1e3a5f, #2d4a6f);
    }

    .balance-amount {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-color);
    }

    .order-form-card {
        background-color: var(--content-bg);
        border-radius: 12px;
        padding: 25px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-color);
        margin-bottom: 8px;
        display: block;
    }

    .form-label .required {
        color: #e74c3c;
        margin-left: 3px;
    }

    .form-label .optional {
        color: #6c757d;
        margin-left: 3px;
        font-size: 12px;
        font-weight: 400;
    }

    .form-input, .form-textarea, .form-select {
        width: 100%;
        padding: 12px 15px;
        background-color: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-color);
        font-size: 14px;
        transition: all 0.2s;
        font-family: inherit;
    }

    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .form-input:focus, .form-textarea:focus, .form-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-help {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 5px;
    }
    
    /* [NEW] Styles for the new image uploader */
    #image-upload-area {
        background-color: var(--input-bg);
        border-radius: 12px;
        padding: 15px;
        border: 1px solid var(--border-color);
    }

    .image-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 15px;
    }

    .image-preview-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        aspect-ratio: 1;
        background-color: var(--content-bg);
    }

    .image-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .remove-image-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(220, 53, 69, 0.85);
        backdrop-filter: blur(4px);
        color: white;
        border: none;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease;
        padding: 0;
    }

    .remove-image-btn:hover {
        background: #dc3545;
        transform: scale(1.1);
    }
    
    .remove-image-btn i {
        line-height: 1;
    }

    .image-upload-box {
        border: 2px dashed var(--border-color);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        aspect-ratio: 1;
        padding: 10px;
        flex-direction: column;
    }
    .image-upload-box:hover, .image-upload-box.drag-over {
        border-color: #667eea;
        background-color: rgba(102, 126, 234, 0.05);
    }

    .upload-icon-large {
        font-size: 28px;
        color: #667eea;
        margin-bottom: 8px;
    }

    .upload-box-text {
        font-size: 13px;
        font-weight: 500;
        color: var(--text-secondary);
        line-height: 1.3;
    }
    .upload-box-hint {
        font-size: 11px;
        color: var(--text-secondary);
        opacity: 0.8;
        margin-top: 4px;
    }

    .cost-calculator {
        background: linear-gradient(135deg, #e8f4fd, #f0f9ff);
        border-left: 4px solid #667eea;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }

    html.dark .cost-calculator {
        background: linear-gradient(135deg, #1e3a5f, #2d4a6f);
    }

    .cost-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .cost-row:last-child {
        margin-bottom: 0;
        padding-top: 8px;
        border-top: 1px solid rgba(102, 126, 234, 0.2);
        font-weight: 600;
        font-size: 16px;
    }

    .submit-btn {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 20px;
    }

    .submit-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .submit-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .alert-error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    html.dark .alert-success {
        background-color: #1e4620;
        border-color: #2d5f2f;
        color: #9fdf9f;
    }

    html.dark .alert-error {
        background-color: #4d1f1f;
        border-color: #6b2929;
        color: #f8b4b4;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #667eea;
        text-decoration: none;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .style-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
        margin-top: 10px;
    }

    .style-option {
        position: relative;
    }

    .style-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .style-option label {
        display: block;
        padding: 12px 15px;
        background: var(--input-bg);
        border: 2px solid var(--border-color);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        font-size: 13px;
    }

    .style-option input[type="radio"]:checked + label {
        background: rgba(102, 126, 234, 0.1);
        border-color: #667eea;
        color: #667eea;
        font-weight: 600;
    }

    .style-option label:hover {
        border-color: #667eea;
    }
</style>

<div class="order-container">
    <a href="/products" class="back-link">
        <i class="bi bi-arrow-left"></i>
        <?php echo $lang === 'vi' ? 'Quay lại danh sách sản phẩm' : 'Back to products'; ?>
    </a>

    <div class="page-header">
        <h1>
            <i class="bi bi-cart-plus"></i>
            <?php echo $lang === 'vi' ? 'Đặt Sản Phẩm' : 'Order Product'; ?>
        </h1>
        <p><?php echo $lang === 'vi' ? 'Điền thông tin để đặt sản phẩm' : 'Fill in information to order product'; ?></p>
    </div>

    <div class="balance-warning">
        <i class="bi bi-wallet2"></i>
        <div class="balance-info">
            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 4px;">
                <?php echo $lang === 'vi' ? 'Số dư hiện tại' : 'Current Balance'; ?>
            </div>
            <div class="balance-amount">
                $<?php echo number_format($user['sodu'], 2); ?>
            </div>
        </div>
    </div>

    <?php if ($orderResult): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill" style="font-size: 20px; flex-shrink: 0;"></i>
        <div>
            <strong><?php echo $lang === 'vi' ? 'Đặt hàng thành công!' : 'Order placed successfully!'; ?></strong><br>
            <?php echo $lang === 'vi' ? 'Mã đơn hàng: ' : 'Order ID: '; ?><strong><?php echo $orderResult['order']; ?></strong><br>
            <a href="/orderstatus"><?php echo $lang === 'vi' ? 'Xem đơn hàng' : 'View orders'; ?></a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="bi bi-exclamation-triangle-fill" style="font-size: 20px; flex-shrink: 0;"></i>
        <div>
            <strong><?php echo $lang === 'vi' ? 'Lỗi!' : 'Error!'; ?></strong><br>
            <?php echo htmlspecialchars($error); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($product && $reqs): ?>
    <div class="product-info-card">
        <div class="product-header">
            <div class="product-icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="product-details">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <div style="font-size: 13px; color: var(--text-secondary);">
                    <i class="bi bi-folder"></i>
                    <?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-tag"></i>
                    <?php echo $lang === 'vi' ? 'Giá bán' : 'Sale Price'; ?>
                </span>
                <div class="info-value" style="color: #667eea;">
                    $<?php echo number_format($product['rate_usd'], 4); ?>
                </div>
            </div>

            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-arrow-down-circle"></i>
                    <?php echo $lang === 'vi' ? 'Tối thiểu' : 'Minimum'; ?>
                </span>
                <div class="info-value"><?php echo number_format($product['min']); ?></div>
            </div>

            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-arrow-up-circle"></i>
                    <?php echo $lang === 'vi' ? 'Tối đa' : 'Maximum'; ?>
                </span>
                <div class="info-value"><?php echo number_format($product['max']); ?></div>
            </div>

            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-check-circle"></i>
                    <?php echo $lang === 'vi' ? 'Trạng thái' : 'Status'; ?>
                </span>
                <div class="info-value" style="color: <?php echo $product['status'] === 'In stock' ? '#28a745' : '#dc3545'; ?>;">
                    <?php echo $product['status'] === 'In stock' ? ($lang === 'vi' ? 'Còn hàng' : 'In stock') : ($lang === 'vi' ? 'Hết hàng' : 'Out of stock'); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="order-form-card">
        <form method="POST" id="orderForm" enctype="multipart/form-data">
            <input type="hidden" name="product" value="<?php echo $product['product']; ?>">

            <!-- Quantity -->
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-123"></i>
                    <?php echo $lang === 'vi' ? 'Số lượng' : 'Quantity'; ?>
                    <span class="required">*</span>
                </label>
                <input 
                    type="number" 
                    name="quantity" 
                    id="quantityInput"
                    class="form-input" 
                    min="<?php echo $product['min']; ?>"
                    max="<?php echo $product['max']; ?>"
                    value="<?php echo $product['min']; ?>"
                    required
                    <?php echo $product['status'] !== 'In stock' ? 'disabled' : ''; ?>
                >
                <div class="form-help">
                    Min: <?php echo number_format($product['min']); ?> - Max: <?php echo number_format($product['max']); ?>
                </div>
            </div>

            <?php if ($reqs['needsEmail']): ?>
            <!-- Email Input -->
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-envelope"></i>
                    <?php echo $lang === 'vi' ? 'Email / Gmail' : 'Email / Gmail'; ?>
                    <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    name="require_text" 
                    class="form-input" 
                    placeholder="<?php echo $lang === 'vi' ? 'Nhập email của bạn (không cần mật khẩu)' : 'Enter your email (no password required)'; ?>"
                    required
                    <?php echo $product['status'] !== 'In stock' ? 'disabled' : ''; ?>
                >
                <div class="form-help">
                    <i class="bi bi-info-circle"></i>
                    <?php echo $lang === 'vi' ? 'Chỉ cần email, KHÔNG cần mật khẩu' : 'Email only, NO password required'; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($reqs['needsPrompt']): ?>
            <!-- Prompt/Description -->
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-chat-left-text"></i>
                    <?php echo $lang === 'vi' ? 'Prompt / Mô tả' : 'Prompt / Description'; ?>
                    <span class="required">*</span>
                </label>
                <textarea 
                    name="require_text" 
                    class="form-textarea" 
                    placeholder="<?php echo $lang === 'vi' ? 'Nhập prompt hoặc mô tả chi tiết...' : 'Enter prompt or detailed description...'; ?>"
                    minlength="<?php echo $reqs['promptMinLength']; ?>"
                    maxlength="<?php echo $reqs['promptMaxLength']; ?>"
                    required
                    <?php echo $product['status'] !== 'In stock' ? 'disabled' : ''; ?>
                ></textarea>
                <div class="form-help">
                    <i class="bi bi-lightbulb"></i>
                    <?php echo $lang === 'vi' ? 'Từ ' : 'From '; ?><?php echo $reqs['promptMinLength']; ?> 
                    <?php echo $lang === 'vi' ? ' đến ' : ' to '; ?><?php echo $reqs['promptMaxLength']; ?> 
                    <?php echo $lang === 'vi' ? ' ký tự' : ' characters'; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($reqs['needsDimension']): ?>
            <!-- Dimension Selection -->
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-aspect-ratio"></i>
                    <?php echo $lang === 'vi' ? 'Kích thước' : 'Dimension'; ?>
                    <span class="required">*</span>
                </label>
                <select 
                    name="dimension" 
                    class="form-select" 
                    required
                    <?php echo $product['status'] !== 'In stock' ? 'disabled' : ''; ?>
                >
                    <option value=""><?php echo $lang === 'vi' ? '-- Chọn kích thước --' : '-- Select dimension --'; ?></option>
                    <?php foreach ($reqs['dimensionOptions'] as $dim): ?>
                        <?php 
                        $dimValue = is_array($dim) ? $dim['value'] : $dim;
                        $dimName = is_array($dim) ? $dim['name'] : $dim;
                        ?>
                        <option value="<?php echo htmlspecialchars($dimValue); ?>">
                            <?php echo htmlspecialchars($dimName); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($reqs['needsStyle']): ?>
            <!-- Style Selection -->
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-palette"></i>
                    <?php echo $lang === 'vi' ? 'Phong cách' : 'Style'; ?>
                    <span class="required">*</span>
                </label>
                <div class="style-grid">
                    <?php foreach ($reqs['styleOptions'] as $style): ?>
                        <?php 
                        $styleValue = $style['value'] ?? '';
                        $styleName = $style['name'] ?? $styleValue;
                        
                        // Check for Vietnamese translation
                        if ($lang === 'vi' && isset($style['languages']['vi']['name'])) {
                            $styleName = $style['languages']['vi']['name'];
                        }
                        ?>
                        <div class="style-option">
                            <input 
                                type="radio" 
                                name="style" 
                                id="style_<?php echo htmlspecialchars($styleValue); ?>" 
                                value="<?php echo htmlspecialchars($styleValue); ?>"
                                required
                                <?php echo $product['status'] !== 'In stock' ? 'disabled' : ''; ?>
                            >
                            <label for="style_<?php echo htmlspecialchars($styleValue); ?>">
                                <?php echo htmlspecialchars($styleName); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($reqs['needsImage']): ?>
            <!-- [NEW] Image Upload Section -->
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-image"></i>
                    <?php echo $lang === 'vi' ? 'Upload Ảnh' : 'Upload Images'; ?>
                    <?php if ($reqs['imageOptional']): ?>
                        <span class="optional">(<?php echo $lang === 'vi' ? 'Tuỳ chọn' : 'Optional'; ?>)</span>
                    <?php else: ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>
                
                <div id="image-upload-area">
                    <!-- Hidden file input remains the core -->
                    <input 
                        type="file" 
                        name="images[]" 
                        id="imageInput"
                        accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                        multiple
                        hidden
                        <?php echo $product['status'] !== 'In stock' ? 'disabled' : ''; ?>
                    >
                    
                    <!-- The visual grid for previews and the upload button -->
                    <div class="image-preview-grid" id="imagePreviewGrid">
                        <!-- JS will populate this with previews -->
                        
                        <!-- The uploader button/dropzone -->
                        <div class="image-upload-box" id="uploadBox" title="<?php echo $lang === 'vi' ? 'Chọn ảnh' : 'Select images'; ?>">
                            <i class="bi bi-cloud-arrow-up-fill upload-icon-large"></i>
                            <div class="upload-box-text">
                                <?php echo $lang === 'vi' ? 'Nhấp hoặc kéo ảnh vào đây' : 'Click or drag images here'; ?>
                            </div>
                            <div class="upload-box-hint">
                                 <?php echo $lang === 'vi' ? 'Tối đa 5MB' : 'Max 5MB'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!$reqs['imageOptional']): ?>
                <div class="form-help" style="color: #e74c3c; margin-top: 10px;">
                    <i class="bi bi-exclamation-circle"></i>
                    <?php echo $lang === 'vi' ? 'Bắt buộc phải upload ít nhất 1 ảnh!' : 'At least 1 image is required!'; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Cost Calculator -->
            <div class="cost-calculator">
                <h4 style="margin: 0 0 15px 0; font-size: 16px;">
                    <i class="bi bi-calculator"></i>
                    <?php echo $lang === 'vi' ? 'Tính tiền' : 'Cost Calculator'; ?>
                </h4>
                <div class="cost-row">
                    <span><?php echo $lang === 'vi' ? 'Giá bán/sản phẩm :' : 'Sale price/unit :'; ?></span>
                    <span id="displayRate">
                        $<?php echo number_format($product['rate_usd'], 4); ?>
                    </span>
                </div>
                <div class="cost-row">
                    <span><?php echo $lang === 'vi' ? 'Số lượng:' : 'Quantity:'; ?></span>
                    <span id="displayQuantity"><?php echo number_format($product['min']); ?></span>
                </div>
                <div class="cost-row">
                    <span><strong><?php echo $lang === 'vi' ? 'Tổng thanh toán:' : 'Total Payment:'; ?></strong></span>
                    <span id="totalCost" style="color: #667eea;">
                        <strong>
                            $<?php 
                            $initialTotal = $product['rate_usd'] * $product['min'];
                            echo number_format($initialTotal, 4);
                            ?>
                        </strong>
                    </span>
                </div>
            </div>

            <button 
                type="submit" 
                name="submit_order" 
                class="submit-btn"
                id="submitBtn"
                <?php echo $product['status'] !== 'In stock' ? 'disabled' : ''; ?>
            >
                <i class="bi bi-check-circle"></i>
                <?php echo $lang === 'vi' ? 'Đặt Hàng Ngay' : 'Place Order Now'; ?>
            </button>
        </form>
    </div>

    <?php else: ?>
    <div class="alert alert-error">
        <i class="bi bi-exclamation-triangle-fill" style="font-size: 20px;"></i>
        <div>
            <strong><?php echo $lang === 'vi' ? 'Không tìm thấy sản phẩm!' : 'Product not found!'; ?></strong><br>
            <?php echo $lang === 'vi' ? 'Vui lòng quay lại và chọn sản phẩm.' : 'Please go back and select a product.'; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isVietnamese = <?php echo $lang === 'vi' ? 'true' : 'false'; ?>;
    let rateUSD = <?php echo $product ? $product['rate_usd'] : 0; ?>;
    const needsImage = <?php echo $reqs && $reqs['needsImage'] ? 'true' : 'false'; ?>;
    const isOptionalImage = <?php echo $reqs && $reqs['imageOptional'] ? 'true' : 'false'; ?>;

    const quantityInput = document.getElementById('quantityInput');
    const displayQuantity = document.getElementById('displayQuantity');
    const totalCost = document.getElementById('totalCost');
    const submitBtn = document.getElementById('submitBtn');
    
    // Calculate cost
    function calculateCost() {
        if (!quantityInput || !displayQuantity || !totalCost) return;

        const quantity = parseInt(quantityInput.value) || 0;
        displayQuantity.textContent = quantity.toLocaleString('en-US');

        const cost = rateUSD * quantity;
        totalCost.innerHTML = '<strong>$' + cost.toLocaleString('en-US', {
            minimumFractionDigits: 4,
            maximumFractionDigits: 4
        }) + '</strong>';
    }

    if (quantityInput) {
        quantityInput.addEventListener('input', calculateCost);
    }
    
    // [NEW] Image upload handling
    if (needsImage) {
        const imageInput = document.getElementById('imageInput');
        const uploadBox = document.getElementById('uploadBox');
        const imagePreviewGrid = document.getElementById('imagePreviewGrid');
        const orderForm = document.getElementById('orderForm');
        
        let selectedFiles = []; // This will hold our File objects

        const openFileDialog = () => {
            if (imageInput) imageInput.click();
        };

        if (uploadBox) {
            uploadBox.addEventListener('click', openFileDialog);

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadBox.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadBox.addEventListener(eventName, () => uploadBox.classList.add('drag-over'));
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadBox.addEventListener(eventName, () => uploadBox.classList.remove('drag-over'));
            });

            uploadBox.addEventListener('drop', (e) => {
                if (e.dataTransfer.files.length) {
                    handleFiles(Array.from(e.dataTransfer.files));
                }
            });
        }

        if (imageInput) {
            imageInput.addEventListener('change', (e) => {
                if (e.target.files.length) {
                    handleFiles(Array.from(e.target.files));
                }
            });
        }
        
        function handleFiles(files) {
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;

            files.forEach(file => {
                if (!validTypes.includes(file.type) || file.size > maxSize) {
                    console.warn(`Skipping invalid file: ${file.name}`);
                    return; 
                }
                if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                }
            });

            updateImagePreview();
            updateFileInput();
        }
        
        function renderPreview(file, index) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'image-preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <button type="button" class="remove-image-btn" data-index="${index}" title="Remove image">
                        <i class="bi bi-x"></i>
                    </button>
                `;
                imagePreviewGrid.insertBefore(previewItem, uploadBox);
            };
            reader.readAsDataURL(file);
        }

        function updateImagePreview() {
            if (!imagePreviewGrid) return;
            document.querySelectorAll('.image-preview-item').forEach(el => el.remove());
            selectedFiles.forEach((file, index) => {
                renderPreview(file, index);
            });
        }

        function updateFileInput() {
            if (!imageInput) return;
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            imageInput.files = dataTransfer.files;

            if (!isOptionalImage) {
               imageInput.required = selectedFiles.length === 0;
            }
        }

        if (imagePreviewGrid) {
            imagePreviewGrid.addEventListener('click', (e) => {
                const removeBtn = e.target.closest('.remove-image-btn');
                if (removeBtn) {
                    const indexToRemove = parseInt(removeBtn.dataset.index, 10);
                    if (!isNaN(indexToRemove)) {
                        selectedFiles.splice(indexToRemove, 1);
                        updateImagePreview();
                        updateFileInput();
                    }
                }
            });
        }

        if (orderForm && !isOptionalImage) {
            orderForm.addEventListener('submit', function(e) {
                if (selectedFiles.length === 0) {
                    e.preventDefault();
                    uploadBox.style.borderColor = '#dc3545';
                    setTimeout(() => uploadBox.style.borderColor = '', 2000);
                }
            });
        }
    }
});
</script>

<?php require_once '../config/footer.php'; ?>
