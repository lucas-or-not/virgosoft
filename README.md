# VirgoSoft - Cryptocurrency Exchange Platform

A mini cryptocurrency exchange platform built with Laravel (backend) and Vue.js (frontend), featuring real-time order matching, orderbook updates and secure trading operations.

## 🎥 Video Walkthrough

Watch a complete walkthrough of the application: [Video Demo](https://www.loom.com/share/d2f50b2f1ac34afa993a8bccfe775ae9)

## Overview

VirgoSoft enables users to:
- **Trade cryptocurrencies** (BTC, ETH) with real-time order matching
- **View live orderbooks** with real-time updates via WebSocket
- **Manage balances** and assets with deposit functionality
- **Track orders** and trading history

## Architecture

### Backend
- **Framework**: Laravel (latest stable)
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Real-time**: Soketi (self-hosted Pusher-compatible server)
- **Architecture Pattern**: Controller → Action → Service (optional) → Repository
- **Testing**: Pest PHP

### Frontend
- **Framework**: Vue.js 3 (Composition API)
- **Build Tool**: Vite
- **Styling**: Tailwind CSS
- **State Management**: Inertia.js
- **Real-time**: Laravel Echo + Pusher-js

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Web Server**: Nginx
- **PHP Runtime**: PHP 8.2-FPM

## Prerequisites

Before setting up the application, ensure you have the following installed:

- **Docker** (version 20.10 or higher)
- **Docker Compose** (version 2.0 or higher)
- **Node.js** (version 20.x or higher) - for building frontend assets
- **Yarn** (latest) - for managing frontend dependencies
- **Composer** (optional) - for PHP dependencies (will be installed automatically in container if not present)

## Setup Instructions

### Step 1: Clone the Repository

```bash
git clone https://github.com/lucas-or-not/virgosoft
cd virgosoft
```

### Step 2: Install PHP Dependencies (Optional)

This step is optional as the Docker entrypoint script will automatically install Composer dependencies if the `vendor` directory doesn't exist. However, you can install them manually:

```bash
composer install
```

### Step 3: Build Frontend Assets (Mandatory)

**This step is mandatory** - you must build the frontend assets before starting Docker containers:

```bash
yarn install
yarn build
```

This will:
- Install all npm/yarn dependencies
- Build frontend assets using Vite

### Step 4: Configure Environment

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Edit `.env` and ensure the following variables are set (defaults are already configured for Docker):

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=virgosoft
DB_USERNAME=virgosoft
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PORT=6379

BROADCAST_DRIVER=pusher
PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_SCHEME=http

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST=localhost
VITE_PUSHER_PORT=6003
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
```

**Port Configuration Notes**: 
- Internal container ports remain standard (3306 for MySQL, 6379 for Redis, 6001/6002 for Soketi)
- Host ports are customized to avoid conflicts: 8080 (web), 3307 (database), 6380 (Redis), 6003/6004 (Soketi)
- `VITE_PUSHER_HOST` uses `localhost` (browser connects to host), while `PUSHER_HOST` uses `soketi` (container-to-container)
- `VITE_PUSHER_PORT` is `6003` (exposed host port), while `PUSHER_PORT` is `6001` (internal container port)

### Step 5: Start Docker Containers

Build and start all services:

```bash
docker-compose up -d --build
```

This will:
- Build the PHP application container
- Start MySQL database
- Start Redis cache
- Start Soketi WebSocket server
- Start Nginx web server
- Run database migrations automatically
- Set up proper file permissions

### Step 6: Verify Installation

Check that all containers are running:

```bash
docker-compose ps
```

All services should show as "Up" and healthy.

### Step 7: Access the Application

- **Web Application**: http://localhost:8080
- **Database**: localhost:3307
- **Redis**: localhost:6380
- **Soketi WebSocket**: ws://localhost:6003

## How It Works

### Trading System

1. **Order Creation**
   - Users can place buy or sell orders with specified price and amount
   - Orders are validated for sufficient balance/assets
   - Buy orders lock USD balance; sell orders lock cryptocurrency assets

2. **Order Matching**
   - When an order is placed, the system automatically searches for matching orders
   - Matching criteria:
     - Buy orders match with sell orders where `sell_price <= buy_price`
     - Sell orders match with buy orders where `buy_price >= sell_price`
   - Orders are matched by price (best price first) and time (first-come-first-served)

3. **Trade Execution**
   - When orders match, a trade is executed immediately
   - Commission (1.5%) is deducted from the seller's proceeds
   - Buyer receives cryptocurrency; seller receives USD (minus commission)
   - Both orders are marked as filled

4. **Real-time Updates**
   - All orderbook changes are broadcast via WebSocket
   - Users see live updates when:
     - New orders are placed
     - Orders are matched/filled
     - Orders are cancelled
   - Updates are synchronized across all connected clients

### Order Management

- **Open Orders**: Orders waiting to be matched
- **Filled Orders**: Orders that have been completely matched
- **Cancelled Orders**: Orders that were cancelled by the user

### Balance Management

- **USD Balance**: Fiat currency for buying cryptocurrencies
- **Asset Balances**: Cryptocurrency holdings (BTC, ETH)
- **Locked Amounts**: Funds/assets locked by open orders
- **Available Amounts**: Unlocked funds/assets available for trading

### Commission Structure

- **Rate**: 1.5% of trade value
- **Charged to**: Seller (deducted from USD proceeds)
- **Calculation**: `commission = trade_value * 0.015`

## Development Workflow

### Running Tests

```bash
docker-compose exec app php artisan test
```

### Running Artisan Commands

```bash
docker-compose exec app php artisan <command>
```

### Viewing Logs

```bash
# Application logs
docker-compose logs -f app

# All logs
docker-compose logs -f

# Specific service
docker-compose logs -f nginx
docker-compose logs -f db
docker-compose logs -f soketi
```

### Accessing Container Shell

```bash
docker-compose exec app sh
```

### Rebuilding Frontend Assets

After making frontend changes:

```bash
yarn build
```

For development with hot-reload (run on host machine):

```bash
yarn dev
```

### Database Migrations

Migrations run automatically on container startup. To run manually:

```bash
docker-compose exec app php artisan migrate
```

### Clearing Cache

```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

## Project Structure

```
virgosoft/
├── app/
│   ├── Actions/          # Business logic actions 
│   ├── DTOs/             # Data Transfer Objects
│   ├── Events/            # Laravel events for broadcasting
│   ├── Exceptions/        # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/   # Single-action controllers
│   │   └── Requests/      # Form request validation
│   ├── Models/            # Eloquent models
│   ├── Repositories/      # Data access layer
│   └── Services/          # Business logic
├── database/
│   ├── factories/         # Model factories
│   └── migrations/        # Database migrations
├── resources/
│   ├── js/                # Vue.js frontend code
│   │   ├── components/    # Vue components
│   │   ├── composables/   # Vue composables
│   │   ├── pages/         # Inertia pages
│   │   └── lib/            # Frontend utilities
│   └── views/              # Blade templates
├── routes/
│   ├── api.php            # API routes
│   └── web.php            # Web routes
├── tests/                  # Pest tests
└── docker/                 # Docker configuration
```
