<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Método no permitido.'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$message = trim($input['message'] ?? '');

if (!$name || !$email || !$phone || !$message) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Faltan campos obligatorios.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'El correo no es válido.'
    ]);
    exit;
}

/**
 * Ideal: guardar esta key en una variable de entorno o config fuera del público.
 * Para partir, puedes ponerla aquí temporalmente, pero luego te recomiendo moverla.
 */
$RESEND_API_KEY = 're_44812NDG_FZx8YfmQDLtBJFWa8FLx4Lgu';
$TO_EMAIL = 'mamba.digital.corp@gmail.com';

$subject = 'Nuevo contacto desde Mamba Digital';

$html = "
  <h2>Nuevo mensaje desde el formulario web</h2>
  <p><strong>Nombre:</strong> " . htmlspecialchars($name) . "</p>
  <p><strong>Correo:</strong> " . htmlspecialchars($email) . "</p>
  <p><strong>Teléfono:</strong> " . htmlspecialchars($phone) . "</p>
  <p><strong>Mensaje:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
";

$data = [
    'from' => 'Mamba Digital <contacto@mail.mambadigital.cl>',
    'to' => [$TO_EMAIL],
    'reply_to' => $email,
    'subject' => $subject,
    'html' => $html
];

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $RESEND_API_KEY,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error de conexión con Resend.',
        'error' => $curlError
    ]);
    exit;
}

$responseData = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        'ok' => true,
        'message' => 'Correo enviado correctamente.',
        'data' => $responseData
    ]);
    exit;
}

http_response_code($httpCode ?: 500);
echo json_encode([
    'ok' => false,
    'message' => 'Resend devolvió un error.',
    'response' => $responseData
]);