<?php

namespace App\Services;

use Aws\Iam\IamClient;
use Aws\S3\S3Client;
use Aws\Iam\Exception\IamException;
use Aws\Exception\AwsException;

class MiniStackService
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.ministack.endpoint', 'http://localhost:4566');
    }

    protected function iamClient(string $accountId): IamClient
    {
        return new IamClient([
            'version'     => 'latest',
            'region'      => 'us-east-1',
            'endpoint'    => $this->endpoint,
            'credentials' => ['key' => $accountId, 'secret' => 'anything'],
        ]);
    }

    protected function s3Client(string $accessKey, string $secretKey): S3Client
    {
        return new S3Client([
            'version'                 => 'latest',
            'region'                  => 'us-east-1',
            'endpoint'                => $this->endpoint,
            'use_path_style_endpoint' => true,
            'credentials'             => ['key' => $accessKey, 'secret' => $secretKey],
        ]);
    }

    protected function sanitizeBucketName(string $username): string
    {
        $name = strtolower($username);
        $name = preg_replace('/[^a-z0-9-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        return trim($name, '-') . '-bucket';
    }

    public function provisionUser(string $accountId, string $username): array
    {
        $iam = $this->iamClient($accountId);
        // Create user if not exists (LocalStack returns EntityAlreadyExists if present)
        try {
            $iam->createUser(['UserName' => $username]);
        } catch (IamException|AwsException $e) {
            // If user already exists, continue; otherwise rethrow
            $code = method_exists($e, 'getAwsErrorCode') ? $e->getAwsErrorCode() : null;
            if ($code !== 'EntityAlreadyExists') {
                throw $e;
            }
        }

        // Try create access key. If it fails (e.g. existing keys limit), remove existing keys and recreate.
        try {
            $key = $iam->createAccessKey(['UserName' => $username])['AccessKey'];
        } catch (IamException|AwsException $e) {
            // If there are existing access keys, delete them and try again.
            try {
                $list = $iam->listAccessKeys(['UserName' => $username]);
                foreach ($list['AccessKeyMetadata'] ?? [] as $meta) {
                    $iam->deleteAccessKey([
                        'UserName' => $username,
                        'AccessKeyId' => $meta['AccessKeyId'],
                    ]);
                }
                // recreate
                $key = $iam->createAccessKey(['UserName' => $username])['AccessKey'];
            } catch (IamException $inner) {
                // give up with original exception if still failing
                throw $e;
            }
        }

        $bucketName = $this->sanitizeBucketName($username);
        $this->s3Client($key['AccessKeyId'], $key['SecretAccessKey'])
             ->createBucket(['Bucket' => $bucketName]);

        return [
            'account_id'        => $accountId,
            'access_key_id'     => $key['AccessKeyId'],
            'secret_access_key' => $key['SecretAccessKey'],
            'bucket_name'       => $bucketName,
        ];
    }

    public function getBucketUsageMb(string $accountId, string $accessKeyId, string $secretAccessKey, string $bucketName): float
    {
        $s3 = $this->s3Client($accessKeyId, $secretAccessKey);

        $totalBytes = 0;
        $result = $s3->listObjectsV2(['Bucket' => $bucketName]);

        foreach ($result['Contents'] ?? [] as $object) {
            $totalBytes += $object['Size'];
        }

        return round($totalBytes / 1024 / 1024, 2); // convert ke MB
    }

    public function syncStorageUsage(\App\Models\User $user): float
    {
        $credential = $user->credential;
        if (!$credential) return 0;

        $bucket = $user->buckets()->first();
        if (!$bucket) return 0;

        $secretKey = \Illuminate\Support\Facades\Crypt::decryptString($credential->secret_access_key);

        $mb = $this->getBucketUsageMb(
            $credential->ministack_account_id,
            $credential->access_key_id,
            $secretKey,
            $bucket->bucket_name
        );

        $user->update(['storage_used_mb' => $mb]);
        return $mb;
    }

}