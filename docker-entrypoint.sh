
#!/bin/sh
set -eu

SOURCE_CA="/etc/secrets/ca.pem"
TARGET_CA="/tmp/aiven-ca.pem"

if [ ! -f "$SOURCE_CA" ]; then
    echo "ERROR: Aiven CA certificate is missing."
    exit 1
fi

# Copy the Render Secret File to a readable location.
cp "$SOURCE_CA" "$TARGET_CA"

# Allow Apache/PHP workers to read the certificate.
chmod 644 "$TARGET_CA"

# Override the CA path for PHP.
export DB_SSL_CA="$TARGET_CA"

echo "Aiven CA certificate prepared successfully."

exec apache2-foreground
