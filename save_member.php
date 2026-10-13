<?php
header('Content-Type: application/json');

$file = 'members.json';
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['id'])) {
    // Edit existing
    foreach ($data as &$member) {
        if ($member['id'] == $input['id']) {
            $member = array_merge($member, $input);
            break;
        }
    }
} else {
    // Add new
    $maxId = 0;
    foreach ($data as $node) {
        if (isset($node['id']) && $node['id'] > $maxId) {
            $maxId = $node['id'];
        }
    }
    $input['id'] = $maxId + 1;
    $data[] = $input;
}

file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
echo json_encode(['success' => true, 'data' => $input]);
