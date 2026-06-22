import boto3

ENDPOINT = "http://localhost:4566"

def get_client(service, account_id):
    return boto3.client(
        service,
        endpoint_url=ENDPOINT,
        aws_access_key_id=account_id,
        aws_secret_access_key="anything",
        region_name="us-east-1",
    )

import re
def make_bucket_name(username):
    name = username.lower()
    name = re.sub(r'[^a-z0-9-]', '-', name)
    name = re.sub(r'-+', '-', name).strip('-')
    return f"{name}-bucket"

# === User A ===
account_a = "111111111111"
username_a = "user-ani"

iam_a = get_client("iam", account_a)
iam_a.create_user(UserName=username_a)
key_a = iam_a.create_access_key(UserName=username_a)["AccessKey"]
bucket_a = make_bucket_name(username_a)

s3_a = boto3.client("s3", endpoint_url=ENDPOINT,
                    aws_access_key_id=key_a["AccessKeyId"],
                    aws_secret_access_key=key_a["SecretAccessKey"],
                    region_name="us-east-1")
s3_a.create_bucket(Bucket=bucket_a)

print("=" * 50)
print(f"[User A] Account ID : {account_a}")
print(f"[User A] Access Key : {key_a['AccessKeyId']}")
print(f"[User A] Bucket     : {bucket_a}")

# === User B ===
account_b = "222222222222"
username_b = "user-budi"

iam_b = get_client("iam", account_b)
iam_b.create_user(UserName=username_b)
key_b = iam_b.create_access_key(UserName=username_b)["AccessKey"]
bucket_b = make_bucket_name(username_b)

s3_b = boto3.client("s3", endpoint_url=ENDPOINT,
                    aws_access_key_id=key_b["AccessKeyId"],
                    aws_secret_access_key=key_b["SecretAccessKey"],
                    region_name="us-east-1")
s3_b.create_bucket(Bucket=bucket_b)

print(f"[User B] Account ID : {account_b}")
print(f"[User B] Access Key : {key_b['AccessKeyId']}")
print(f"[User B] Bucket     : {bucket_b}")
print("=" * 50)

# === Uji Isolasi ===
print("\n[UJI ISOLASI] Bucket milik User A dilihat dari akun User B:")
buckets_from_b = s3_b.list_buckets()["Buckets"]
if buckets_from_b:
    for b in buckets_from_b:
        print(f"  - {b['Name']}")
else:
    print("  -> KOSONG (isolasi berhasil, bucket User A tidak terlihat)")

print("\n[UJI ISOLASI] Bucket milik User B dilihat dari akun User A:")
buckets_from_a = s3_a.list_buckets()["Buckets"]
if buckets_from_a:
    for b in buckets_from_a:
        print(f"  - {b['Name']}")
else:
    print("  -> KOSONG (isolasi berhasil, bucket User B tidak terlihat)")