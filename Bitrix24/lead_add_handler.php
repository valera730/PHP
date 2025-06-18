<?
$queryURL = 'https://b24-exer2p.bitrix24.ru/rest/1/pozbunj34n6jwws0/crm.lead.add.json';

// параметры для создания лида
$queryData = [
    'fields' => [
        'NAME' => $_POST['NAME'],
        'LAST_NAME' => $_POST['LAST_NAME'],
        'PHONE' => $_POST['PHONE']
    ],
    'params' => [
        'REGISTER_SONET_EVENT' => 'Y'
    ]
];

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_POST => 1,
    CURLOPT_HEADER => 0,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $queryURL,
    CURLOPT_POSTFIELDS => $queryData
));

$result = curl_exec($curl);
curl_close($curl);
$res = json_decode($result, 1);

echo '<pre>';
print_r($res);
echo '</pre>';