<?
addDeal();

// создадим сделку в Битрикс24
function addDeal() {
    $dealData = sendDataToBitrix('crm.deal.add', [
        'fields' => [
            'TITLE' => 'Заявка с сайта2',
            'STAGE_ID' => 'NEW',
        ],
        'params' => [
            'REGISTER_SONET_EVENT' => 'Y'   // Зарегистрировать событие добавления сделки в живой ленте
        ]
    ]);

    return $dealData;
}

function sendDataToBitrix($method, $data) {
    $queryUrl = 'https://b24-exer2p.bitrix24.ru/rest/1/cfexm0r3cd0r7tkx/' . $method;

    $queryData = http_build_query($data);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData
    ));

    $result = curl_exec($curl);
    curl_close($curl);

    return json_decode($result, 1);
}