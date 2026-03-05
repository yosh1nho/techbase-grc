<?php
$url = "https://techbase-grc-prototipo-m35vfm9.svc.aped-4627-b74a.pinecone.io/";

echo "curl.cainfo=" . ini_get("curl.cainfo") . PHP_EOL;
echo "openssl.cafile=" . ini_get("openssl.cafile") . PHP_EOL;

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 20,
  CURLOPT_VERBOSE => true,
]);
$out = curl_exec($ch);

if ($out === false) {
  echo PHP_EOL . "cURL ERROR: " . curl_error($ch) . PHP_EOL;
  echo "errno=" . curl_errno($ch) . PHP_EOL;
} else {
  echo PHP_EOL . "OK HTTP=" . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
}
curl_close($ch);