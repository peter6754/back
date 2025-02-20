# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture Overview

This is a Laravel 11 application serving as an admin panel for a dating application platform. The system includes:

- **Backend**: Laravel PHP framework with Backpack Admin for CRUD operations
- **Frontend**: Blade templates with Vite for asset building  
- **Database**: MySQL with Laravel migrations
- **Authentication**: Multi-provider (phone, social, Telegram) via Laravel Socialite
- **Payments**: Multi-provider payment system (Robokassa, Unitpay)
- **Notifications**: Push notifications via Expo and OneSignal
- **Real-time**: WebSocket support via Pusher/Soketi

## Key Components

### Admin Interface (Backpack)
- Located in `app/Http/Controllers/Admin/`
- CRUD controllers for managing users, subscriptions, transactions, mail templates
- Admin routes defined in `routes/backpack/`

### API Endpoints
- **Recommendations**: `/recommendations/*` - User matching system
- **Payments**: `/payment/*` - Payment processing and status callbacks  
- **Authentication**: `/auth/*` - Multi-provider login system
- **Prices**: `/application/prices/*` - Subscription and gift pricing

### Core Services
- `RecommendationService`: User matching algorithm
- `PaymentsService`: Payment provider abstraction
- `NotificationService`: Push notification management
- `AuthService` & `JwtService`: Authentication handling

### Payment System
- Provider-agnostic design with `PaymentProviderInterface`
- Implementations for Robokassa and Unitpay in `app/Services/Payments/Providers/`
- Transaction tracking via `TransactionProcess` model

## Development Commands

### Laravel Commands
```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Clear caches
php artisan config:clear
php artisan cache:clear

# Queue processing
php artisan queue:work

# Generate API documentation
php artisan l5-swagger:generate
```

### Frontend Build
```bash
# Development
npm run dev

# Production build  
npm run build
```

### Testing
```bash
# Run all tests
php artisan test
# OR
vendor/bin/phpunit

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Code Quality
```bash
# Format code with Laravel Pint
vendor/bin/pint
```

## Database Considerations

- Uses MySQL with custom migration for user activity indexing
- Heavy use of Laravel relationships between models
- Payment transactions stored separately from user data for audit trails
- Soft deletes implemented for user management

## External Integrations

- **GreenSMS**: SMS verification service
- **Robokassa/Unitpay**: Payment processors  
- **Expo/OneSignal**: Push notification providers
- **Socialite**: VKontakte, Telegram, Google, Apple OAuth
- **Telescope**: Laravel debugging and monitoring

## Configuration Notes

- Payment provider configurations in `config/payments.php`
- Backpack admin customizations in `config/backpack/`
- Environment-specific settings for external service APIs