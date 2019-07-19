<?php
session_start();

$client_id = 'com.rohit.applesignin.web';
$client_secret = 'eyJraWQiOiI1MlRLTTNNN0I2IiwiYWxnIjoiRVMyNTYifQ.eyJpc3MiOiJGWDZDNTg2Nk1UIiwiaWF0IjoxNTYzNTUwNDU1LCJleHAiOjE1NzkxMDI0NTUsImF1ZCI6Imh0dHBzOi8vYXBwbGVpZC5hcHBsZS5jb20iLCJzdWIiOiJjb20ucm9oaXQuYXBwbGVzaWduaW4ud2ViIn0.OiPHtM4xigfT0L2T6S7dlmLSfbFqRuedoEAVvfMJ1MSe9-G6z0XhfJqec5w1b8WeNRbOWpRBbKNp1rIBWL-hPg';
$redirect_uri = 'https://example-app.com/redirect';

$_SESSION['state'] = bin2hex(random_bytes(5));

$authorize_url = 'https://appleid.apple.com/auth/authorize'.'?'.http_build_query([
  'response_type' => 'code',
  'client_id' => $client_id,
  'redirect_uri' => $redirect_uri,
  'state' => $_SESSION['state'],
  'scope' => 'name email',
]);

echo '<a href="'.$authorize_url.'">Sign In with Apple</a>';

if(isset($_GET['code'])) {

  if($_SESSION['state'] != $_GET['state']) {
    die('Authorization server returned an invalid state parameter');
  }

  // Token endpoint docs: 
  // https://developer.apple.com/documentation/signinwithapplerestapi/generate_and_validate_tokens

  $response = http('https://appleid.apple.com/auth/token', [
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => $redirect_uri,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
  ]);

  if(!isset($response->access_token)) {
    echo '<p>Error getting an access token:</p>';
    echo '<pre>'; print_r($response); echo '</pre>';
    echo '<p><a href="/">Start Over</a></p>';
    die();
  }

  echo '<h3>Access Token Response</h3>';
  echo '<pre>'; print_r($response); echo '</pre>';


  $claims = explode('.', $response->id_token)[1];
  $claims = json_decode(base64_decode($claims));

  echo '<h3>Parsed ID Token</h3>';
  echo '<pre>';
  print_r($claims);
  echo '</pre>';

  die();
}

function http($url, $params=false) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  if($params)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'User-Agent: curl', # Apple requires a user agent header at the token endpoint
  ]);
  $response = curl_exec($ch);
  return json_decode($response);
}