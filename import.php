<?php
// Получаем данные с API
$json = file_get_contents('https://api.gdeslon.ru/gdeslon-categories.json');
$categories = json_decode($json, true);

// Подключение к базе данных
$host = '----';
$username = '----';
$password = '----';
$dbname = '----';

// Соединяемся с базой данных
$conn = mysqli_connect($host, $username, $password, $dbname);
mysqli_query($conn, "SET NAMES utf8");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Перебираем данные и добавляем их в базу данных
foreach ($categories as $category) {
    $term_id = $category['_id'];
    $parent = $category['parent_id'];
    $name = mysqli_real_escape_string($conn, $category['name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    $path = trim($category['path'], '/');
    $api_id = $category['_id'];

    // Проверяем, существует ли категория с данным ID
    $sql = "SELECT * FROM wp_terms WHERE term_id = $term_id";
    $result = mysqli_query($conn, $sql);

    // Если категория уже существует, пропускаем её
    if (mysqli_num_rows($result) > 0) {
        echo "Category with ID $term_id already exists. Skipping.<br>";
        continue;
    }

    // Вставляем новую категорию
    $sql = "INSERT INTO wp_terms (term_id, name, slug) VALUES ($term_id, '$name', '$slug')";

    if (mysqli_query($conn, $sql) === TRUE) {
        $term_id = mysqli_insert_id($conn);
        if ($parent != 0) {
            $sql = "INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES ($parent, $term_id)";
            mysqli_query($conn, $sql);
        }
        $sql = "INSERT INTO wp_term_taxonomy (term_id, taxonomy, parent) VALUES ($term_id, 'product_cat', $parent)";
        mysqli_query($conn, $sql);
        $term_taxonomy_id = mysqli_insert_id($conn);
        $sql = "INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES ($term_id, 'thumbnail_id', '')";
        mysqli_query($conn, $sql);
        echo "Category with ID $term_id added successfully.<br>";
    } else {
        echo "Error adding category: " . mysqli_error($conn) . "<br>";
    }
}

// Закрываем соединение с базой данных
mysqli_close($conn);
?>
