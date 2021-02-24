<?php

return [
    'cluster_issuer'    => env('CLUSTER_ISSUER', 'letsencrypt-prod'),
    'cluster_ip'        => env('CLUSTER_IP', 'your-cluster-ip'),
    'docker_auth'       => env('DOCKER_AUTH'),
    'docker_server'     => env('DOCKER_SERVER'),
    'wildcard_domain'   => env('WILDCARD_DOMAIN'),
    'ns_prefix'         => env('MIX_NS_PREFIX'),
    'cluster_url'       => env('K8S_CLUSTER_URL'),
    'bearer_token'      => env('K8S_BEARER_TOKEN'),
    'helm_api_base_url' => env('HELM_API_BASE_URL'),
];
