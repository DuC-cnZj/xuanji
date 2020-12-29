<?php

namespace App\Services\Images;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ServerException;

class Aliyun implements DockerImageInterface
{
    protected $accessKeyId = '';
    protected $accessSecret = '';
    protected $regionId = '';
    protected $client;

    protected $exists = false;

    //array:1 [
    //  "data" => array:1 [
//    "repo" => array:16 [
//      "summary" => "3dcamega"
//      "regionId" => "cn-hangzhou"
//      "repoBuildType" => "MANUAL"
//      "gmtCreate" => 1573457387000
//      "repoNamespace" => "duc-cnzj"
//      "repoType" => "PRIVATE"
//      "repoName" => "3dcamega"
//      "repoId" => 675756
//      "repoStatus" => "NORMAL"
//      "repoOriginType" => "ALI_HUB"
//      "gmtModified" => 1603590985000
//      "logo" => "https://alidockerhub-logo.oss-cn-hangzhou.aliyuncs.com/user/default.png"
//      "stars" => 0
//      "repoDomainList" => array:3 [
//        "internal" => "registry-internal.cn-hangzhou.aliyuncs.com"
//        "public" => "registry.cn-hangzhou.aliyuncs.com"
//        "vpc" => "registry-vpc.cn-hangzhou.aliyuncs.com"
//      ]
//      "repoAuthorizeType" => "ADMIN"
//      "downloads" => 376
//    ]
    //  ]
    //]
    protected $repoInfo = [];

    private $tagInfo = [];

    public function __construct($accessKeyId, $accessSecret, $regionId)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessSecret = $accessSecret;
        $this->regionId = $regionId;
    }

    public function login(): DockerImageInterface
    {
        $this->client = AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessSecret)
            ->regionId($this->regionId)
            ->asDefaultClient();

        return $this;
    }

    public function search(string $image): DockerImageInterface
    {
        [$_, $namespace, $imageWithTag] = Str::of($image)->explode('/')->all();

        if (Str::of($imageWithTag)->contains(':')) {
            [$repoName, $tag] = explode(':', $imageWithTag);
        } else {
            $repoName = $imageWithTag;
            $tag = 'latest';
        }

        try {
            $result = AlibabaCloud::roa()
                ->host('cr.cn-hangzhou.aliyuncs.com')
                ->scheme('https') // https | http
                ->version('2016-06-07')
                ->pathPattern("/repos/${namespace}/${repoName}")
                ->method('GET')
                ->options(['query' => []])
                ->body('{}')
                ->retryByClient(3, ['Resolving timed out after'])
                ->request();
            $this->repoInfo = Arr::get($result->all(), 'data.repo', []);
        } catch (ServerException $e) {
            if ($e->getResult()->getStatusCode() == 404) {
                $this->exists = false;

                return $this;
            }

            throw $e;
        }

        try {
            $result = AlibabaCloud::roa()
                ->host('cr.cn-hangzhou.aliyuncs.com')
                ->scheme('https') // https | http
                ->version('2016-06-07')
                ->pathPattern("/repos/${namespace}/${repoName}/tags/${tag}")
                ->method('GET')
                ->options(['query' => []])
                ->retryByClient(3, ['Resolving timed out after'])
                ->body('{}')
                ->request();
            $this->tagInfo = Arr::get($result->all(), 'data', []);
        } catch (ServerException $e) {
            if ($e->getResult()->getStatusCode() == 404) {
                $this->exists = false;

                return $this;
            }

            throw $e;
        }

        $this->exists = true;

        return $this;
    }

    /**
     * @return array
     */
    public function getRepoInfo(): array
    {
        return $this->repoInfo;
    }

    /**
     * @return array
     */
    public function getTagInfo(): array
    {
        return $this->tagInfo;
    }

    public function exists(): bool
    {
        return $this->exists;
    }
}
