# AdPro Billboard Marketplace - Architecture Recommendations

## Current State Analysis (September 2025)
- Laravel 12 + Filament v3 admin panel ✅
- Billboard/Client/Contract management ✅  
- PDF contract generation ✅
- Search functionality ✅
- Permission system (Shield) ✅

## Recommended Hybrid Architecture

### 1. Keep Filament for Admin Panel (/admin)
**For**: Billboard operators, account managers, admins
**Features**: 
- Billboard inventory management
- Contract creation/management  
- Client relationship management
- Financial reporting & analytics
- User role management

### 2. Add Public Frontend (/app or /)
**Options Analysis**:

#### Option A: Inertia.js + Vue 3 (Recommended)
**Pros**:
- Modern SPA experience for customers
- Excellent for interactive billboard browsing/filtering
- Real-time availability updates
- Mobile-responsive booking flow
- SEO-friendly with SSR
- Shares same Laravel backend/auth

**Cons**: 
- Additional learning curve if team unfamiliar with Vue
- More complex build process

#### Option B: Livewire + Blade (Alternative)
**Pros**:
- Consistent with current Filament stack
- Simpler deployment
- Less JavaScript complexity
- Team already knows Livewire

**Cons**:
- Less interactive than SPA
- Mobile experience limitations
- Slower perceived performance

## Required Functionality Additions

### Customer-Facing Features (Missing)
1. **Public Billboard Browsing**
   - Map-based billboard discovery
   - Filter by location, size, price, availability
   - Photo galleries for each billboard
   - Availability calendar view

2. **Self-Service Booking Flow** 
   - Real-time availability checking
   - Pricing calculator (duration, discounts)
   - Online booking requests
   - Payment integration (mobile money for Malawi)

3. **Customer Portal**
   - Booking history
   - Contract status tracking
   - Invoice/payment history
   - Campaign performance metrics

4. **Marketing Website**
   - Landing pages
   - Pricing information
   - Case studies/testimonials
   - Contact forms

### Backend API Enhancements Needed
1. **Real-time Availability API**
2. **Pricing Engine** (dynamic pricing, bulk discounts)
3. **Payment Processing** (Airtel Money, TNM Mpamba)
4. **Notification System** (SMS, email, in-app)
5. **Analytics API** (campaign performance, ROI)

### Mobile-First Considerations
- **Progressive Web App (PWA)** for mobile users
- **Offline capability** for browsing billboards
- **GPS integration** for location-based discovery
- **Camera integration** for campaign proof uploads

## Package Recommendations

### Keep (Essential)
- `filament/filament` - Admin panel excellence
- `barryvdh/laravel-dompdf` - Contract PDFs
- `spatie/laravel-medialibrary` - Billboard photos
- `laravel/sanctum` - API auth for mobile
- `hirethunk/verbs` - Event sourcing for bookings

### Add (Customer Frontend)
- `inertiajs/inertia-laravel` + Vue 3 ecosystem
- `spatie/laravel-permission` - Role-based access
- `laravel/cashier` or payment gateway SDK
- `pusher/pusher-php-server` - Real-time updates
- `geocoder-php/geocoder` - Location services

### Remove (Unnecessary Complexity)
- `filament/spatie-laravel-google-fonts-plugin` - Use system fonts
- `leandrocfe/filament-apex-charts` - Move to dedicated analytics service
- `creagia/laravel-sign-pad` - Digital signatures not needed initially

## Implementation Priority

### Phase 1: Public Frontend Foundation
1. Set up Inertia.js + Vue 3
2. Create public routes and layout
3. Billboard browsing/search interface
4. Basic booking request form

### Phase 2: Booking Engine  
1. Real-time availability system
2. Pricing calculator
3. Booking workflow
4. Email notifications

### Phase 3: Payments & Customer Portal
1. Payment gateway integration
2. Customer dashboard
3. Booking management
4. Contract tracking

### Phase 4: Mobile & Advanced Features
1. PWA implementation
2. Mobile app (if needed)
3. Advanced analytics
4. Campaign management tools

Would you like me to start implementing Phase 1 with the Inertia.js setup?
