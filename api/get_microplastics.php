<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/database.php';
require_once '../config/cms.php';

function getMicroplastics($filters = []) {
    $pdo = getDatabaseConnection();

    if ($pdo === null) {
        return ['success' => false, 'error' => 'Database connection failed', 'data' => []];
    }

    try {
        $query = "SELECT id, tipo_ambiente, ecossistema, system, sampling_point, latitude, longitude,
                         concentration_sediment as concentration, concentration_value,
                         matriz, unidade, author, reference
                  FROM microplastics_sediment
                  WHERE approved = 1";
        $params = [];

        if (!empty($filters['system'])) {
            $query .= " AND system LIKE :system";
            $params[':system'] = '%' . $filters['system'] . '%';
        }

        if (!empty($filters['tipo_ambiente'])) {
            $query .= " AND tipo_ambiente = :tipo_ambiente";
            $params[':tipo_ambiente'] = $filters['tipo_ambiente'];
        }

        if (!empty($filters['ecossistema'])) {
            $query .= " AND ecossistema = :ecossistema";
            $params[':ecossistema'] = $filters['ecossistema'];
        }

        if (!empty($filters['matriz'])) {
            $query .= " AND matriz = :matriz";
            $params[':matriz'] = $filters['matriz'];
        }

        if (isset($filters['min_concentration']) && is_numeric($filters['min_concentration'])) {
            $query .= " AND concentration_value >= :min_concentration";
            $params[':min_concentration'] = $filters['min_concentration'];
        }

        if (isset($filters['max_concentration']) && is_numeric($filters['max_concentration'])) {
            $query .= " AND concentration_value <= :max_concentration";
            $params[':max_concentration'] = $filters['max_concentration'];
        }

        $query .= " ORDER BY concentration_value DESC";

        if (isset($filters['limit']) && is_numeric($filters['limit'])) {
            $query .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
        }

        $stmt = $pdo->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':limit' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        $results = $stmt->fetchAll();

        $formattedData = [];
        foreach ($results as $row) {
            if ($row['latitude'] && $row['longitude']) {
                $formattedData[] = [
                    'id' => (int)$row['id'],
                    'tipo_ambiente' => $row['tipo_ambiente'],
                    'ecossistema' => $row['ecossistema'],
                    'system' => $row['system'],
                    'sampling_point' => $row['sampling_point'],
                    'latitude' => (float)$row['latitude'],
                    'longitude' => (float)$row['longitude'],
                    'concentration' => $row['concentration'],
                    'concentration_value' => (float)$row['concentration_value'],
                    'matriz' => $row['matriz'],
                    'unidade' => $row['unidade'],
                    'author' => $row['author'],
                    'reference' => $row['reference']
                ];
            }
        }

        return ['success' => true, 'count' => count($formattedData), 'data' => $formattedData];

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Query failed', 'data' => []];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$filters = [];
if (isset($_GET['system']) && !empty($_GET['system'])) $filters['system'] = $_GET['system'];
if (isset($_GET['tipo_ambiente']) && !empty($_GET['tipo_ambiente'])) $filters['tipo_ambiente'] = $_GET['tipo_ambiente'];
if (isset($_GET['ecossistema']) && !empty($_GET['ecossistema'])) $filters['ecossistema'] = $_GET['ecossistema'];
if (isset($_GET['matriz']) && !empty($_GET['matriz'])) $filters['matriz'] = $_GET['matriz'];
if (isset($_GET['min_concentration'])) $filters['min_concentration'] = $_GET['min_concentration'];
if (isset($_GET['max_concentration'])) $filters['max_concentration'] = $_GET['max_concentration'];
if (isset($_GET['limit'])) $filters['limit'] = $_GET['limit'];

$response = getMicroplastics($filters);
$response['thresholds'] = getUnitsWithThresholds();

// Include fish data with coordinates
try {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        $fishQuery = "SELECT id, species, habit, total_individuals, individuals_with_microplastics,
                             fiber, film, fragment, foam, pellets, sphere,
                             plastic_dimension, occurrence_tissues, freshwater_system,
                             latitude, longitude, author, reference
                      FROM microplastics_fish
                      WHERE approved = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL";
        $fishResults = $pdo->query($fishQuery)->fetchAll();
        $fishData = [];
        foreach ($fishResults as $row) {
            $fishData[] = [
                'id' => (int)$row['id'],
                'species' => $row['species'],
                'habit' => $row['habit'],
                'total_individuals' => (int)$row['total_individuals'],
                'individuals_with_microplastics' => (int)$row['individuals_with_microplastics'],
                'microplastic_types' => array_filter([
                    $row['fiber'] ? 'Fibra' : null,
                    $row['film'] ? 'Filme' : null,
                    $row['fragment'] ? 'Fragmento' : null,
                    $row['foam'] ? 'Espuma' : null,
                    $row['pellets'] ? 'Pellet' : null,
                    $row['sphere'] ? 'Esfera' : null,
                ]),
                'plastic_dimension' => $row['plastic_dimension'],
                'occurrence_tissues' => $row['occurrence_tissues'],
                'freshwater_system' => $row['freshwater_system'],
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'author' => $row['author'],
                'reference' => $row['reference'],
                'type' => 'fish'
            ];
        }
        $response['fish'] = ['count' => count($fishData), 'data' => $fishData];
    }
} catch (PDOException $e) {
    error_log("Fish query error: " . $e->getMessage());
    $response['fish'] = ['count' => 0, 'data' => []];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
