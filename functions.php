<?php
// Подключение к базе данных
require_once 'config.php';

/**
 * Получить все категории комплектующих
 * 
 * @return array Массив категорий
 */
function getCategories() {
    global $conn;
    $categories = [];
    
    $sql = "SELECT * FROM component_categories ORDER BY sort_order ASC";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

/**
 * Получить комплектующие по категории
 * 
 * @param int $categoryId ID категории
 * @return array Массив комплектующих
 */
function getComponentsByCategory($categoryId) {
    global $conn;
    $components = [];
    
    $sql = "SELECT * FROM components WHERE category_id = ? ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $categoryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $components[] = $row;
        }
    }
    
    return $components;
}

/**
 * Получить информацию о комплектующем по ID
 * 
 * @param int $componentId ID комплектующего
 * @return array|null Данные о комплектующем или null
 */
function getComponentById($componentId) {
    global $conn;
    
    $sql = "SELECT * FROM components WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $componentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Проверка совместимости комплектующих
 * 
 * @param int $componentId ID комплектующего
 * @param array $configItems Массив ID уже выбранных комплектующих
 * @return bool Совместимы ли комплектующие
 */
function checkCompatibility($componentId, $configItems) {
    global $conn;
    
    // Если нет выбранных комплектующих, то совместимость гарантирована
    if (empty($configItems)) {
        return true;
    }
    
    foreach ($configItems as $item) {
        $sql = "SELECT COUNT(*) as count FROM compatibility 
                WHERE (component_id = ? AND compatible_with = ?) 
                OR (component_id = ? AND compatible_with = ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiii", $componentId, $item, $item, $componentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        // Если нет записи в таблице совместимости, значит компоненты несовместимы
        if ($row['count'] == 0) {
            return false;
        }
    }
    
    return true;
}

/**
 * Сохранить конфигурацию
 * 
 * @param string $name Название конфигурации
 * @param array $components Массив ID выбранных комплектующих
 * @param int $userId ID пользователя (необязательно)
 * @param bool $isPublic Публичная ли конфигурация
 * @return int|bool ID новой конфигурации или false в случае ошибки
 */
function saveConfiguration($name, $components, $userId = null, $isPublic = false) {
    global $conn;
    
    // Вычисление общей стоимости
    $totalPrice = 0;
    foreach ($components as $componentId) {
        $component = getComponentById($componentId);
        if ($component) {
            $totalPrice += $component['price'];
        }
    }
    
    // Начало транзакции
    mysqli_begin_transaction($conn);
    
    try {
        // Добавление конфигурации
        $sql = "INSERT INTO configurations (name, user_id, total_price, is_public) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sidi", $name, $userId, $totalPrice, $isPublic);
        mysqli_stmt_execute($stmt);
        
        $configId = mysqli_insert_id($conn);
        
        // Добавление комплектующих в конфигурацию
        foreach ($components as $componentId) {
            $sql = "INSERT INTO configuration_items (configuration_id, component_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $configId, $componentId);
            mysqli_stmt_execute($stmt);
        }
        
        // Подтверждение транзакции
        mysqli_commit($conn);
        
        return $configId;
    } catch (Exception $e) {
        // Откат транзакции в случае ошибки
        mysqli_rollback($conn);
        return false;
    }
}

/**
 * Получить сохраненную конфигурацию по ID
 * 
 * @param int $configId ID конфигурации
 * @return array|null Данные о конфигурации или null
 */
function getConfigurationById($configId) {
    global $conn;
    
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM configuration_items WHERE configuration_id = c.id) as items_count 
            FROM configurations c WHERE c.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $configId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $config = mysqli_fetch_assoc($result);
        
        // Получаем комплектующие конфигурации
        $sql = "SELECT ci.*, c.name, c.price, c.image, cc.name as category_name 
                FROM configuration_items ci 
                JOIN components c ON ci.component_id = c.id 
                JOIN component_categories cc ON c.category_id = cc.id 
                WHERE ci.configuration_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $configId);
        mysqli_stmt_execute($stmt);
        $itemsResult = mysqli_stmt_get_result($stmt);
        
        $config['items'] = [];
        if ($itemsResult && mysqli_num_rows($itemsResult) > 0) {
            while ($item = mysqli_fetch_assoc($itemsResult)) {
                $config['items'][] = $item;
            }
        }
        
        return $config;
    }
    
    return null;
}

/**
 * Получить счетчик товаров в категории
 * 
 * @param int $categoryId ID категории
 * @return int Количество товаров
 */
function getCategoryItemsCount($categoryId) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM components WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $categoryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['count'];
    }
    
    return 0;
}