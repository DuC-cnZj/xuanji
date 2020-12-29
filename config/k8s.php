<?php

return [
    'cluster_ip'        => env('CLUSTER_IP', 'your-cluster-ip'),
    'docker_auth'       => env('DOCKER_AUTH'),
    'docker_server'     => env('DOCKER_SERVER'),
    'wildcard_domain'   => env('WILDCARD_DOMAIN'),
    'ns_prefix'         => env('MIX_NS_PREFIX'),
    'cluster_url'       => env('K8S_CLUSTER_URL'),
    'bearer_token'      => env('K8S_BEARER_TOKEN'),
    'helm_api_base_url' => env('HELM_API_BASE_URL'),
    'settings'          => [
        [
            'image' => [
                'registry'   => 'registry.cn-hangzhou.aliyuncs.com',
                'username'   => '18888780080',
                'password'   => 'duc123243',
                'repo'       => 'registry.cn-hangzhou.aliyuncs.com/duc-cnzj/sso',
                'tag_format' => '%s-%s', // branch-(commit-hash)
            ],
            'project' => 'duccnzj/sso-demo',
            'charts'  => 'duc/sso-chart',
        ],
    ],
];
