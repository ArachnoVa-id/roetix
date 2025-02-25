#!/bin/bash

set -e  # Exit if any command fails

echo "🚀 Starting dependency installation..."

# Update package lists
sudo apt update -y && sudo apt upgrade -y

# 1️⃣ Install Git (Latest)
if ! command -v git &> /dev/null; then
    echo "🛠 Installing Git..."
    sudo add-apt-repository ppa:git-core/ppa -y
    sudo apt update -y
    sudo apt install -y git
else
    echo "✅ Git already installed."
fi

# 2️⃣ Install Nginx (Latest)
if ! command -v nginx &> /dev/null; then
    echo "🛠 Installing Nginx..."
    sudo apt install -y nginx
    sudo systemctl enable nginx
    sudo systemctl start nginx
else
    echo "✅ Nginx already installed."
fi

# 3️⃣ Install MySQL Server (Latest)
if ! command -v mysql &> /dev/null; then
    echo "🛠 Installing MySQL..."
    sudo apt install -y mysql-server
    sudo systemctl enable mysql
    sudo systemctl start mysql
else
    echo "✅ MySQL already installed."
fi

# 4️⃣ Install PHP 8.2 and Required Extensions
if ! command -v php &> /dev/null || [[ $(php -v | grep -oP '(?<=PHP )\d+\.\d+') != "8.2" ]]; then
    echo "🛠 Installing PHP 8.2 and extensions..."
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt update -y
    sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip php8.2-intl php8.2-gd
    sudo systemctl enable php8.2-fpm
    sudo systemctl start php8.2-fpm
else
    echo "✅ PHP 8.2 already installed."
fi

# 5️⃣ Install Composer (Latest)
if ! command -v composer &> /dev/null; then
    echo "🛠 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
else
    echo "✅ Composer already installed."
fi

# 6️⃣ Install Node.js & NPM (Latest LTS)
if ! command -v node &> /dev/null || ! command -v npm &> /dev/null; then
    echo "🛠 Installing Node.js (Latest LTS) and NPM..."
    curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
    sudo apt install -y nodejs
else
    echo "✅ Node.js already installed."
fi

# 7️⃣ Install Supervisor (Latest)
if ! command -v supervisorctl &> /dev/null; then
    echo "🛠 Installing Supervisor..."
    sudo apt install -y supervisor
    sudo systemctl enable supervisor
    sudo systemctl start supervisor
else
    echo "✅ Supervisor already installed."
fi

# 8️⃣ Install Docker (Latest)
if ! command -v docker &> /dev/null; then
    echo "🛠 Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
else
    echo "✅ Docker already installed."
fi

# 9️⃣ Install Docker Compose (Latest)
if ! command -v docker-compose &> /dev/null; then
    echo "🛠 Installing Docker Compose..."
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
else
    echo "✅ Docker Compose already installed."
fi

echo "🎉 All dependencies installed successfully!"
