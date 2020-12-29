# 玄机

> 自动部署发布平台

## 需求
1. 需要一个创建命名空间的接口
2. 需要获取 gitlab project 接口
3. 需要一个执行 helm uninstall/upgrade --install 的接口

```yaml
config_file: .env.example # 必填
config_file_type: env # 必填 env / yaml
repository: duc/sso # 必填 仓库名称
tag_format: "$branch-$commit" # 必填 版本规则 自动注入 $branch, $commit
local_chart: my_chart.tgz # .tgz 结尾, 如果和 chart 同时配置了，那么优先使用这个配置
chart: laravel-charts/laravel-workflow # local_chart 未配置时，使用这个配置，如果配置了，那么 helm_repo_name 和 helm_repo_url 应该也是搭配一起配置的
helm_repo_name: laravel-charts # chart name 默认 空, 可选
helm_repo_url: https://github.com/DuC-cnZj/laravel-charts # chart url 默认 空, 可选
chart_version: '0.2.1' # chart_version, 可选
config_field: envFile # values 中应用配置对应的字段，默认 'env', 可选
is_simple_env: true # env 是一个 configmap，需要整体配置时, 默认 true, 可选
default_values: # 默认的 chart values 配置 默认 空, 可选
  - 'redis.enabled=true'
  - 'redis.cluster.slaveCount=0'
  - 'redis.usePassword=false'
  - 'service.type=NodePort'
branches: # 配置的分支 默认 *, 可选
  - "*"
```
