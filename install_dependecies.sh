#!/bin/bash

set -e  # Exit if any command fails

echo "🚀 Starting dependency installation..."

# Update package lists
sudo apt update -y

# 1️⃣ Install Git
if ! command -v git &> /dev/null; then
    echo "🛠 Installing Git..."
    sudo apt install -y git
else
    echo "✅ Git already installed."
fi

# 2️⃣ Install Nginx
if ! command -v nginx &> /dev/null; then
    echo "🛠 Installing Nginx..."
    sudo apt install -y nginx
    sudo systemctl enable nginx
    sudo systemctl start nginx
else
    echo "✅ Nginx already installed."
fi

# 3️⃣ Install MySQL Server
if ! command -v mysql &> /dev/null; then
    echo "🛠 Installing MySQL..."
    sudo apt install -y mysql-server
    sudo systemctl enable mysql
    sudo systemctl start mysql
else
    echo "✅ MySQL already installed."
fi

# 4️⃣ Install PHP and Required Extensions
if ! command -v php &> /dev/null; then
    echo "🛠 Installing PHP and extensions..."
    sudo apt install -y php8.1 php8.1-fpm php8.1-cli php8.1-mbstring php8.1-xml php8.1-bcmath php8.1-curl php8.1-zip
    sudo systemctl enable php8.1-fpm
    sudo systemctl start php8.1-fpm
else
    echo "✅ PHP already installed."
fi

# 5️⃣ Install Composer
if ! command -v composer &> /dev/null; then
    echo "🛠 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
else
    echo "✅ Composer already installed."
fi

# 6️⃣ Install Node.js & NPM (for frontend builds)
if ! command -v node &> /dev/null; then
    echo "🛠 Installing Node.js and NPM..."
    sudo apt install -y nodejs npm
else
    echo "✅ Node.js already installed."
fi

# 7️⃣ Install Supervisor (For Process Management)
if ! command -v supervisorctl &> /dev/null; then
    echo "🛠 Installing Supervisor..."
    sudo apt install -y supervisor
    sudo systemctl enable supervisor
    sudo systemctl start supervisor
else
    echo "✅ Supervisor already installed."
fi

# 8️⃣ Install Docker
if ! command -v docker &> /dev/null; then
    echo "Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
else
    echo "✅ Docker already installed."
fi

# 9️⃣ Install Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo "Installing Docker Compose..."
    sudo apt-get install -y docker-compose
else
    echo "✅ Docker Compose already installed."
fi

echo "🎉 All dependencies installed successfully!"
