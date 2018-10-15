<?php

function couch_get_decode_json($couchViewAndKey)
{
    // Due to function scope we have to require the Guzzle Client class again inside the function
    require "db.php";

    $response = $client->request("GET", $couchViewAndKey);
    $json = $response->getBody()->getContents();
    $decoded_json = json_decode($json, true);
    return $decoded_json["rows"];
}

function couch_put_or_post($put_or_post, $id, $doc)
{
    // Due to function scope we have to require the Guzzle Client class again inside the function
    require "db.php";
    $response = $client->request("$put_or_post", "/phpusers/$id", [
        "json" => $doc
    ]);
}
