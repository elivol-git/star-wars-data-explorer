#!/bin/bash

# Simulate AWS Secrets Manager response
SAMPLE_RESPONSE='{"ARN":"arn:aws:secretsmanager:eu-north-1:078238935621:secret:rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0-QGTzsj","Name":"rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0","VersionId":"abc123","SecretString":"{\"username\":\"admin\",\"password\":\"TestPass123!\"}","CreatedDate":1234567890}'

echo "=== Testing current method (two-pipe PHP) ==="
EXTRACTED=$(echo "$SAMPLE_RESPONSE" | php -r "\$json = json_decode(file_get_contents('php://stdin'), true); echo \$json['SecretString'];" | php -r "\$secret = json_decode(file_get_contents('php://stdin'), true); echo \$secret['password'];")
echo "Extracted password: $EXTRACTED"
echo "Length: ${#EXTRACTED}"

echo ""
echo "=== Improved method (single PHP call) ==="
EXTRACTED2=$(echo "$SAMPLE_RESPONSE" | php -r "
\$response = json_decode(file_get_contents('php://stdin'), true);
if (!isset(\$response['SecretString'])) {
    fwrite(STDERR, 'ERROR: SecretString not found in response\n');
    exit(1);
}
\$secret = json_decode(\$response['SecretString'], true);
if (!isset(\$secret['password'])) {
    fwrite(STDERR, 'ERROR: password field not found in SecretString\n');
    exit(1);
}
echo \$secret['password'];
")
echo "Extracted password: $EXTRACTED2"
echo "Length: ${#EXTRACTED2}"
