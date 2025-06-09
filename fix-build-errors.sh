#!/bin/bash

echo "Fixing Laravel Vite TypeScript build errors..."

# Remove node_modules and lock file
echo "Cleaning node_modules..."
rm -rf node_modules package-lock.json

# Update problematic packages
echo "Updating @types/node to match Node.js 20..."
npm install --save-dev @types/node@^20.12.0

# Update styled-components types
echo "Updating styled-components types..."
npm install --save-dev @types/styled-components@^5.1.34

# Optionally downgrade TypeScript if needed
echo "Downgrading TypeScript for compatibility..."
npm install --save-dev typescript@^5.3.0

# Reinstall all dependencies
echo "Reinstalling dependencies..."
npm install

# Clear Vite cache
echo "Clearing Vite cache..."
rm -rf node_modules/.vite

echo "Fix complete! Try running 'npm run build' now."