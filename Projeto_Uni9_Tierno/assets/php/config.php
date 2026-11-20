<?php
date_default_timezone_set('America/Sao_Paulo');
/*
 * |--------------------------------------------------------------------------
 * | DADOS DO SUPABASE
 * |--------------------------------------------------------------------------
 */
define('SUPABASE_URL', rtrim((string)(getenv('SUPABASE_URL') ?: 'https://syyojqrkqwadelibimmc.supabase.co'), '/'));
define('SUPABASE_KEY', (string)(getenv('SUPABASE_KEY') ?: 'sb_publishable_eIkBJneM0zfSdwXuwcZnRw_vNrrldWy'));
define('SUPABASE_SERVICE_ROLE_KEY', 'sb_secret_tyOFmCVKtxhEvO7u97SS4A_ItVAHM1u');
/*
 * |--------------------------------------------------------------------------
 * | ESCAPA HTML
 * |--------------------------------------------------------------------------
 * |
 * | Evita problemas quando exibimos dados vindos do banco.
 * |
 */
if (!function_exists('h')) {
    function h($valor): string
    {
        return htmlspecialchars(
            (string) $valor,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}
/*
 * |--------------------------------------------------------------------------
 * | REQUISIÇÃO AO SUPABASE
 * |--------------------------------------------------------------------------
 * |
 * | Esta é a ÚNICA função supabaseRequest() do projeto.
 * |
 * | Não declare outra função com o mesmo nome em:
 * |
 * | - admin.php
 * | - dashboard.php
 * | - barbeiro.php
 * | - index.php
 * | - qualquer outro arquivo
 * |
 */
if (!function_exists('supabaseRequest')) {
    function supabaseRequest(
        string $method,
        string $path,
        ?array $data = null,
        ?string $token = null,
        bool $serviceRole = false
    ): array {
        /*
         * |--------------------------------------------------------------------------
         * | ESCOLHE A CHAVE
         * |--------------------------------------------------------------------------
         */
        if ($serviceRole) {
            $key = SUPABASE_SERVICE_ROLE_KEY;
        } else {
            $key = SUPABASE_KEY;
        }
        /*
         * |--------------------------------------------------------------------------
         * | VERIFICA CONFIGURAÇÃO
         * |--------------------------------------------------------------------------
         */
        if (
            $key === '' ||
            str_contains($key, 'COLOQUE_SUA') ||
            str_contains($key, 'SEU-PROJETO')
        ) {
            return [
                'status' => 0,
                'data' => [],
                'raw' => '',
                'error' =>
                $serviceRole
                ? 'SUPABASE_SERVICE_ROLE_KEY não configurada.'
                : 'SUPABASE_KEY não configurada.'
            ];
        }
        /*
         * |--------------------------------------------------------------------------
         * | MONTA URL
         * |--------------------------------------------------------------------------
         */
        $url =
        rtrim(
            SUPABASE_URL,
            '/'
        )
        . '/'
        . ltrim(
            $path,
            '/'
        );
        /*
         * |--------------------------------------------------------------------------
         * | HEADERS
         * |--------------------------------------------------------------------------
         */
        $headers = [
            'apikey: ' . $key,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        /*
         * |--------------------------------------------------------------------------
         * | TOKEN DO USUÁRIO
         * |--------------------------------------------------------------------------
         */
        if (
            $token !== null &&
            $token !== ''
        ) {
            $headers[] =
            'Authorization: Bearer '
            . $token;
        } elseif ($serviceRole) {
            /*
             * |--------------------------------------------------------------------------
             * | SERVICE ROLE
             * |--------------------------------------------------------------------------
             */
            $headers[] =
            'Authorization: Bearer '
            . SUPABASE_SERVICE_ROLE_KEY;
        }
        /*
         * |--------------------------------------------------------------------------
         * | EXECUTA REQUISIÇÃO (cURL ou stream, conforme extensões disponíveis)
         * |--------------------------------------------------------------------------
         */
        $json = null;
        if ($data !== null) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                return ['status' => 0, 'data' => [], 'raw' => '', 'error' => 'Erro ao converter os dados para JSON.'];
            }
        }
        $raw = false;
        $status = 0;
        $error = '';
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30
            ];
            if ($json !== null) $options[CURLOPT_POSTFIELDS] = $json;
            curl_setopt_array($curl, $options);
            $raw = curl_exec($curl);
            $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
        } else {
            $headerText = implode("\r\n", $headers);
            $context = stream_context_create(['http' => [
                'method' => strtoupper($method),
                'header' => $headerText,
                'content' => $json ?? '',
                'timeout' => 30,
                'ignore_errors' => true
            ]]);
            $raw = @file_get_contents($url, false, $context);
            foreach (($http_response_header ?? []) as $responseHeader) {
                if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $responseHeader, $match)) $status = (int)$match[1];
            }
            if ($raw === false) $error = 'Falha na conexão HTTP com o Supabase. Verifique a rede e allow_url_fopen.';
        }
        if ($raw === false) {
            return ['status' => 0, 'data' => [], 'raw' => '', 'error' => $error ?: 'Falha na conexão com o Supabase.'];
        }
        /*
         * |--------------------------------------------------------------------------
         * | CONVERTE JSON
         * |--------------------------------------------------------------------------
         */
        $decoded =
        json_decode(
            $raw,
            true
        );
        /*
         * |--------------------------------------------------------------------------
         * | RETORNO
         * |--------------------------------------------------------------------------
         */
        return [
            'status' =>
            $status,
            'data' =>
            is_array($decoded)
            ? $decoded
            : [],
            'raw' =>
            $raw,
            'error' => ''
        ];
    }
}
/*
 * |--------------------------------------------------------------------------
 * | REDIRECT
 * |--------------------------------------------------------------------------
 * |
 * | Função utilizada pelos demais arquivos.
 * |
 */
if (!function_exists('redirect')) {
    function redirect(
        string $url
    ): never {
        header(
            'Location: ' . $url
        );
        exit;
    }
}
