#!/bin/bash
# Test script to validate Secrets Manager password extraction

set -e

echo "=== Testing Secrets Manager Password Extraction ==="
echo ""

# Simulate AWS response format
TEST_RESPONSE='{"ARN":"arn:aws:secretsmanager:eu-north-1:078238935621:secret:rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0-QGTzsj","Name":"rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0","VersionId":"test123","SecretString":"{\"username\":\"admin\",\"password\":\"TestPassword123!\",\"engine\":\"mysql\",\"host\":\"planets.cn2eau4c0ak7.eu-north-1.rds.amazonaws.com\",\"port\":3306,\"dbname\":\"planets\"}","CreatedDate":1700000000,"LastAccessedDate":1700000000,"LastRotatedDate":1700000000,"LastChangedDate":1700000000,"Tags":{}}'

echo "Test 1: Extract password from mock AWS response"
PASSWORD=$(echo "$TEST_RESPONSE" | php << 'PHPEOF'
<?php
$json = json_decode(file_get_contents('php://stdin'), true);
if (!isset($json['SecretString'])) {
    fwrite(STDERR, "ERROR: SecretString not found in AWS response\n");
    exit(1);
}
$secret = json_decode($json['SecretString'], true);
if (!isset($secret['password'])) {
    fwrite(STDERR, "ERROR: password field not found in SecretString\n");
    exit(1);
}
echo $secret['password'];
?>
PHPEOF
)

if [ -z "$PASSWORD" ]; then
    echo "❌ FAIL: Password extraction returned empty"
    exit 1
fi

if [ "$PASSWORD" != "TestPassword123!" ]; then
    echo "❌ FAIL: Expected 'TestPassword123!' but got '$PASSWORD'"
    exit 1
fi

echo "✅ PASS: Password extracted correctly: $PASSWORD"
echo ""

echo "Test 2: Test error handling - missing SecretString"
INVALID_RESPONSE='{"ARN":"test","Name":"test"}'
if echo "$INVALID_RESPONSE" | php << 'PHPEOF'
<?php
$json = json_decode(file_get_contents('php://stdin'), true);
if (!isset($json['SecretString'])) {
    fwrite(STDERR, "ERROR: SecretString not found in AWS response\n");
    exit(1);
}
?>
PHPEOF
2>/dev/null; then
    echo "❌ FAIL: Should have failed on missing SecretString"
    exit 1
fi
echo "✅ PASS: Error handling works for missing SecretString"
echo ""

echo "Test 3: Test error handling - missing password field"
INVALID_RESPONSE='{"SecretString":"{\"username\":\"admin\",\"host\":\"localhost\"}"}'
if echo "$INVALID_RESPONSE" | php << 'PHPEOF'
<?php
$json = json_decode(file_get_contents('php://stdin'), true);
$secret = json_decode($json['SecretString'], true);
if (!isset($secret['password'])) {
    fwrite(STDERR, "ERROR: password field not found in SecretString\n");
    exit(1);
}
?>
PHPEOF
2>/dev/null; then
    echo "❌ FAIL: Should have failed on missing password"
    exit 1
fi
echo "✅ PASS: Error handling works for missing password field"
echo ""

echo "=== All tests passed ==="
