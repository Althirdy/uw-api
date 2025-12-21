#!/bin/bash

# Stop script on first error
set -e

PROJECT_NAME="urbanwatch"
DOCROOT="public"
PROJECT_TYPE="laravel"

echo "🚀 Starting UrbanWatch setup..."


# FIX: Prevent the "Permission to beam up" prompt
ddev config global --instrumentation-opt-in=false
ddev config global --simple-formatting=true


# Step 1: Configure DDEV if not exists
if [ ! -d ".ddev" ]; then
  echo "⚙️ Configuring ddev..."
  ddev config --project-name $PROJECT_NAME --docroot $DOCROOT --project-type $PROJECT_TYPE
fi

# Step 2: Ensure .env exists BEFORE starting
# It's safer to do this on the host side before containers start
if [ ! -f ".env" ]; then
  echo "📄 Copying .env.example to .env..."
  cp .env.example .env
else
  echo "📄 .env file already exists, skipping..."
fi

# Step 3: Start DDEV
echo "▶️ Starting ddev..."
ddev start

# Step 4: Install Dependencies
echo "📦 Installing PHP dependencies..."
ddev composer install

echo "📦 Installing Node dependencies..."
ddev npm install

# Step 5: Generate Key (Only if not set)
if ! grep -q "^APP_KEY=base64" .env; then
    echo "🔑 Generating application key..."
    ddev artisan key:generate
fi

# Step 6: Database Setup
echo "🗂 Running database migrations..."
ddev artisan migrate --force

echo "🌱 Seeding database..."
ddev artisan db:seed --force || echo "⚠️ Seeding failed or no seeders found."

# Step 7: Build Frontend (Don't run 'dev' here!)
echo "🏗 Building frontend assets..."
ddev npm run build

# Done
echo "✅ UrbanWatch setup complete!"
echo "👉 You can now run: 'ddev describe' to see your URL."
echo "👉 To start coding, run: 'ddev npm run dev'"