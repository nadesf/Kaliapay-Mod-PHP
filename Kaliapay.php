<?php

$config_file = 'config.json';
$required_fields = ['tokenid', 'apikey', 'service'];

function requests_post($url, $data, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($http_code >= 499) {
        throw new Exception("Erreur lors de la requête POST : $response");
    }
    
    return $response;
}

function requests_get($url, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($http_code >= 499) {
        throw new Exception("Erreur lors de la requête GET : $response");
    }
    return $response;
}

class ConfigurationFileError extends Exception {}

class CollectPayment {
    public static function initialize($amount, $custom_data) {
        global $config_file, $required_fields;

        if (!file_exists($config_file)) {
            throw new ConfigurationFileError("Le fichier 'config.json' de configuration est manquant. Consulter la documentation afin d'obtenir ce fichier.");
        }

        $config = json_decode(file_get_contents($config_file), true);

        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $config)) {
                throw new ConfigurationFileError("Le champ '{$field}' est manquant dans le fichier de configuration.");
            }
        }

        $url = "https://kaliapay.com/api/generate-mobpay-qrcode/";
        $data = [
            "apikey" => $config['apikey'],
            "service" => $config['service'],
            "amount" => $amount,
            "custom_data" => $custom_data
        ];
        $headers = ["Authorization: Token {$config['tokenid']}"];

        try {
            $req = requests_post($url, $data, $headers);
            $result = json_decode($req, true);
        } catch (Exception $e) {
            $result = ["code" => 00, "response" => $e->getMessage()];
        }
        
        return $result;
    }

    public static function get_transaction_status($reference) {
        global $config_file, $required_fields;

        if (!file_exists($config_file)) {
            throw new ConfigurationFileError("Le fichier 'config.json' de configuration est manquant. Consulter la documentation afin d'obtenir ce fichier.");
        }

        $config = json_decode(file_get_contents($config_file), true);

        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $config)) {
                throw new ConfigurationFileError("Le champ '{$field}' est manquant dans le fichier de configuration.");
            }
        }

        $url = "https://kaliapay.com/api/get-express-transaction-details/{$reference}/";
        $headers = ["Authorization: Token {$config['tokenid']}"];

        try {
            $req = requests_get($url, $headers);
            $result = json_decode($req, true);
        } catch (Exception $e) {
            $result = ["code" => 00, "response" => $e->getMessage()];
        }
        
        return $result;
    }

    public static function get_config_file($username, $password) {
        $url = "https://kaliapay.com/api/signin-users/";
        $data = ["user" => $username, "password" => $password];

        try {
            $req = requests_post($url, $data);
            $json_response = json_decode($req, true);

            if ($json_response && isset($json_response['result']["tid"])) {
                $result = "Fichier de configuration créer avec succès !";
                $data_to_save = [
                    "tokenid" => $json_response['result']["tid"],
                    "apikey" => $json_response['result']["apikey"],
                    "service" => ""        
                ];

                file_put_contents("config.json", json_encode($data_to_save));
            } else {
                $result = "Echec : Vérifier vos accès !";
            }

        } catch (Exception $e) {
            $result = "Echec : Vérifier votre connexion internet";
        }
        
        return $result;
    }
}

#$result = CollectPayment::get_config_file("nadefabriece83@gmail.com", "DeviensPlusFort01.");
#print_r($result);

?>
