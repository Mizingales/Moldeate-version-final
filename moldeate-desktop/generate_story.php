<?php
// Recibe items y masa por POST y llama a OpenAI para generar la historia
header('Content-Type: application/json');
require_once 'constants.php';









// Obtener API key de forma segura
$apiKey = MoldeatConstants::getApiKey();

// Si no hay API key, devolver historia de fallback sin exponer información
if (!$apiKey) {
    $story = MoldeatConstants::getFallbackStory([]);
    echo json_encode(['story' => $story, 'fallback' => true, 'error' => 'missing_api_key']);
    exit;
}







$items = isset($_POST['items']) ? $_POST['items'] : [];
$masa = isset($_POST['masa']) ? $_POST['masa'] : '';

 

if (!is_array($items)) {
    $items = explode(',', $items);
}

$itemAttributes = MoldeatConstants::getItemAttributes($items);







//Prompt que se envía a Groq (Llama3)
$prompt = "Genera un breve relato en segunda persona sobre un personaje ficticio. Debe contar acerca de lo que ese personaje está haciendo ahora mismo y que planea hacer el resto del día. Ten en cuenta que el lector de este relato se debe sentir como si se tratara de él mismo.
La personalidad del personaje debe estar definida por los siguientes adjetivos: " . implode(', ', $itemAttributes) . ". 
El relato debe ser poético y evocador, no más de 8 oraciones. 
No hace falta mencionar explícitamente los adjetivos, transmítelos a través de las acciones, emociones y pensamientos del personaje.";







 

$url = 'https://api.groq.com/openai/v1/chat/completions';
$data = [
    'model' => 'llama-3.1-8b-instant', // Modelo actualizado de Groq
    'messages' => [
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'max_tokens' => 500,
    'temperature' => 0.8
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'timeout' => 20
    ]
];

$context  = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === FALSE) {
    // Fallback sin detalles de depuración
    $story = MoldeatConstants::getFallbackStory($itemAttributes);
    echo json_encode(['story' => $story, 'fallback' => true]);
    exit;
}

$response = json_decode($result, true);
if (isset($response['choices'][0]['message']['content'])) {
    $story = trim($response['choices'][0]['message']['content']);
    // Responder historia generada
    echo json_encode(['story' => $story, 'fallback' => false]);
} else {
    // Fallback si la respuesta no contiene el contenido esperado
    $story = MoldeatConstants::getFallbackStory($itemAttributes);
    echo json_encode(['story' => $story, 'fallback' => true]);
}
